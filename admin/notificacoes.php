<?php include 'partials/html.php' ?>

<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "services/database.php";
include_once "logs/registrar_logs.php";
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "services/checa_login_adm.php";
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();

checa_login_adm();

$toastType = '';
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_notification') {
    $titulo = mysqli_real_escape_string($mysqli, $_POST['titulo']);
    $conteudo = mysqli_real_escape_string($mysqli, $_POST['conteudo']);
    $imagem = mysqli_real_escape_string($mysqli, $_POST['imagem'] ?? '');
    $tipo = mysqli_real_escape_string($mysqli, $_POST['tipo']);
    $destinatario_type = $_POST['destinatario_type'];
    $destinatario_id = isset($_POST['destinatario_id']) ? mysqli_real_escape_string($mysqli, $_POST['destinatario_id']) : '';
    $destinatario = ($destinatario_type === 'todos') ? 'todos' : $destinatario_id;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    if (!empty($titulo) && !empty($conteudo)) {
        $sql = "INSERT INTO notificacoes (titulo, conteudo, imagem, tipo, destinatario, status, criado_em) VALUES ('$titulo', '$conteudo', '$imagem', '$tipo', '$destinatario', $status, NOW())";
        if (mysqli_query($mysqli, $sql)) {
            $toastType = 'success';
            $toastMessage = admin_t('toast_config_updated');
        } else {
            $toastType = 'error';
            $toastMessage = admin_t('toast_config_error');
        }
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('notifications_title_content_required');
    }
}

$notificacoes = [];
$query = "SELECT id, titulo, conteudo, imagem, tipo, destinatario, status, criado_em FROM notificacoes ORDER BY criado_em DESC";
$result = mysqli_query($mysqli, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notificacoes[] = $row;
    }
}
?>

<head>
    <?php $title = admin_t('page_notifications_title'); include 'partials/title-meta.php' ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0"><?= admin_t('notifications_create_title') ?></h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="create_notification">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label"><?= admin_t('notifications_field_title') ?></label>
                                            <input type="text" name="titulo" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label"><?= admin_t('notifications_field_image_url') ?></label>
                                            <input type="text" name="imagem" class="form-control" placeholder="https://exemplo.com/imagem.jpg">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"><?= admin_t('notifications_field_type') ?></label>
                                            <select name="tipo" class="form-select">
                                                <option value="notificacao"><?= admin_t('notifications_type_notification') ?></option>
                                                <option value="sistema"><?= admin_t('notifications_type_system') ?></option>
                                                <option value="alerta"><?= admin_t('notifications_type_alert') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"><?= admin_t('notifications_field_recipient') ?></label>
                                            <select name="destinatario_type" class="form-select" onchange="toggleDestinatario(this.value)">
                                                <option value="todos"><?= admin_t('notifications_recipient_all') ?></option>
                                                <option value="especifico"><?= admin_t('notifications_recipient_specific') ?></option>
                                            </select>
                                            <input type="text" name="destinatario_id" id="destinatario_id" class="form-control mt-2" placeholder="ID do Usuário" style="display:none;">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label"><?= admin_t('notifications_field_content') ?></label>
                                            <textarea name="conteudo" class="form-control" rows="4" required></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label"><?= admin_t('notifications_field_status') ?></label>
                                            <select name="status" class="form-select">
                                                <option value="1"><?= admin_t('notifications_status_active') ?></option>
                                                <option value="0"><?= admin_t('notifications_status_inactive') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100"><?= admin_t('notifications_button_save') ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0"><?= admin_t('notifications_list_title') ?></h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th><?= admin_t('notifications_field_title') ?></th>
                                                <th><?= admin_t('notifications_table_image') ?></th>
                                                <th><?= admin_t('notifications_field_type') ?></th>
                                                <th><?= admin_t('notifications_field_recipient') ?></th>
                                                <th><?= admin_t('notifications_field_status') ?></th>
                                                <th><?= admin_t('notifications_table_created_at') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($notificacoes) === 0): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted"><?= admin_t('notifications_none') ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($notificacoes as $n): ?>
                                                    <tr>
                                                        <td><?= $n['id'] ?></td>
                                                        <td><?= htmlspecialchars($n['titulo']) ?></td>
                                                        <td>
                                                            <?php if (!empty($n['imagem'])): ?>
                                                                <img src="<?= htmlspecialchars($n['imagem']) ?>" alt="Img" style="height: 30px;">
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($n['tipo']) ?></td>
                                                        <td><?= htmlspecialchars($n['destinatario']) ?></td>
                                                        <td>
                                                            <?php if ($n['status'] == 1): ?>
                                                                <span class="badge bg-success"><?= admin_t('notifications_status_active') ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><?= admin_t('notifications_status_inactive') ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'partials/footer.php' ?>
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function toggleDestinatario(value) {
            const input = document.getElementById('destinatario_id');
            if (value === 'especifico') {
                input.style.display = 'block';
                input.required = true;
            } else {
                input.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }
    </script>
</body>
