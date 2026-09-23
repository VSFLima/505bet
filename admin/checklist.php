<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();

checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// Funções para manipular signin_config
function get_signin_config()
{
    global $mysqli;
    $qry = "SELECT * FROM signin_config ORDER BY day ASC";
    $result = mysqli_query($mysqli, $qry);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['amount_max'] = $row['amount_max'] / 100;
        $row['amount_min'] = $row['amount_min'] / 100;
        $row['recharge_amount'] = $row['recharge_amount'] / 100;
        $row['valid_bet'] = $row['valid_bet'] / 100;
        $row['extra_reward'] = $row['extra_reward'] / 100;
        $data[] = $row;
    }
    return $data;
}

function update_signin_config($id, $data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE signin_config SET 
        amount_type = ?, 
        amount_max = ?, 
        amount_min = ?, 
        recharge_amount = ?, 
        valid_bet = ?, 
        extra_reward = ?
        WHERE id = ?");

    $qry->bind_param(
        "idddddi",
        $data['amount_type'],
        $data['amount_max'],
        $data['amount_min'],
        $data['recharge_amount'],
        $data['valid_bet'],
        $data['extra_reward'],
        $id
    );
    return $qry->execute();
}

// Funções para manipular signin_records
function get_signin_records($limit = 50, $offset = 0)
{
    global $mysqli;
    $qry = "SELECT * FROM signin_records ORDER BY date_record DESC LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function delete_signin_record($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM signin_records WHERE id = ?");
    $qry->bind_param("i", $id);
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_config') {
            $success = true;
            foreach ($_POST['config'] as $id => $item) {
                $item['amount_max'] = $item['amount_max'] * 100;
                $item['amount_min'] = $item['amount_min'] * 100;
                $item['recharge_amount'] = $item['recharge_amount'] * 100;
                $item['valid_bet'] = $item['valid_bet'] * 100;
                $item['extra_reward'] = $item['extra_reward'] * 100;

                if (!update_signin_config($id, $item)) {
                    $success = false;
                }
            }
            if ($success) {
                $toastType = 'success';
                $toastMessage = admin_t('toast_config_updated');
            } else {
                $toastType = 'error';
                $toastMessage = admin_t('toast_config_error');
            }
        } elseif ($_POST['action'] == 'delete_record') {
            $id = intval($_POST['record_id']);
            if (delete_signin_record($id)) {
                $toastType = 'success';
                $toastMessage = admin_t('toast_record_deleted');
            } else {
                $toastType = 'error';
                $toastMessage = admin_t('toast_record_delete_error');
            }
        }
    }
}

$configList = get_signin_config();
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 50;
$offset = ($page - 1) * $limit;
$recordsList = get_signin_records($limit, $offset);

// Contar total de registros para paginação
$totalRecordsResult = $mysqli->query("SELECT COUNT(*) as total FROM signin_records");
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

?>

<head>
    <?php $title = admin_t('page_checklist_title');
    include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <?php if ($toastType): ?>
                <div class="alert alert-<?= $toastType == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                    <?= $toastMessage ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card rounded-4 shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h4 class="card-title fw-bold mb-0"><?= admin_t('checklist_card_title') ?></h4>
                                <p class="text-muted fs-13 mb-0"><?= admin_t('checklist_card_subtitle') ?></p>
                            </div>

                            <div class="card-body p-4">
                                <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="config-tab" data-bs-toggle="tab" data-bs-target="#config" type="button" role="tab" aria-controls="config" aria-selected="true"><?= admin_t('checklist_tab_config') ?></button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="records-tab" data-bs-toggle="tab" data-bs-target="#records" type="button" role="tab" aria-controls="records" aria-selected="false"><?= admin_t('checklist_tab_records') ?></button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="myTabContent">
                                    
                                    <!-- Aba Configuração -->
                                    <div class="tab-pane fade show active" id="config" role="tabpanel" aria-labelledby="config-tab">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="update_config">
                                            <div class="table-responsive">
                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th><?= admin_t('checklist_col_day') ?></th>
                                                            <th><?= admin_t('checklist_col_type') ?></th>
                                                            <th><?= admin_t('checklist_col_min') ?></th>
                                                            <th><?= admin_t('checklist_col_max') ?></th>
                                                            <th><?= admin_t('checklist_col_recharge') ?></th>
                                                            <th><?= admin_t('checklist_col_bet') ?></th>
                                                            <th><?= admin_t('checklist_col_extra') ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($configList as $item): ?>
                                                        <tr>
                                                            <td class="fw-bold text-center"><?= $item['day'] ?></td>
                                                            <td>
                                                                <select class="form-select form-select-sm" name="config[<?= $item['id'] ?>][amount_type]">
                                                                    <option value="0" <?= $item['amount_type'] == 0 ? 'selected' : '' ?>>Fixo</option>
                                                                    <option value="1" <?= $item['amount_type'] == 1 ? 'selected' : '' ?>>Aleatório</option>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm" name="config[<?= $item['id'] ?>][amount_min]" value="<?= $item['amount_min'] ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm" name="config[<?= $item['id'] ?>][amount_max]" value="<?= $item['amount_max'] ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm" name="config[<?= $item['id'] ?>][recharge_amount]" value="<?= $item['recharge_amount'] ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm" name="config[<?= $item['id'] ?>][valid_bet]" value="<?= $item['valid_bet'] ?>">
                                                            </td>
                                                            <td>
                                                                <input type="number" step="0.01" class="form-control form-control-sm" name="config[<?= $item['id'] ?>][extra_reward]" value="<?= $item['extra_reward'] ?>">
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="mt-3">
                                                <button type="submit" class="btn btn-primary"><i class="iconoir-floppy-disk"></i> <?= admin_t('button_save_changes') ?></button>
                                            </div>
                                        </form>
                                    </div>

                                    <!-- Aba Registros -->
                                    <div class="tab-pane fade" id="records" role="tabpanel" aria-labelledby="records-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th><?= admin_t('checklist_col_user_id') ?></th>
                                                            <th><?= admin_t('checklist_col_redeem_date') ?></th>
                                                            <th><?= admin_t('table_action') ?></th>
                                                        </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($recordsList) > 0): ?>
                                                        <?php foreach ($recordsList as $record): ?>
                                                        <tr>
                                                            <td><?= $record['id'] ?></td>
                                                            <td><?= $record['user_id'] ?></td>
                                                            <td><?= date('d/m/Y H:i:s', strtotime($record['date_record'])) ?></td>
                                                            <td>
                                                                <form method="POST" action="" onsubmit="return confirm('<?= admin_t('checklist_confirm_delete') ?>');">
                                                                    <input type="hidden" name="action" value="delete_record">
                                                                    <input type="hidden" name="record_id" value="<?= $record['id'] ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="iconoir-trash"></i> <?= admin_t('button_delete') ?></button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted"><?= admin_t('checklist_no_records') ?></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Paginação Simples -->
                                        <?php if ($totalPages > 1): ?>
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination justify-content-center">
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>"><?= admin_t('previous') ?></a>
                                                </li>
                                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                                </li>
                                                <?php endfor; ?>
                                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>"><?= admin_t('next') ?></a>
                                                </li>
                                            </ul>
                                        </nav>
                                        <?php endif; ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'partials/footer.php' ?>
            
        </div>
    </div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
