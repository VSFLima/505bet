<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
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
#======================================#
#expulsa user
checa_login_adm();
#======================================#
//inicio do script expulsa usuario bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

# TYPES de atividade válidos (rota/tela do front). Vazio = usar padrão do sistema.
$ACTIVITY_TYPES = [
    ''                     => 'Padrão do sistema',
    'Agency'               => 'Agency (indicação / baús)',
    'AssistanceCash'       => 'AssistanceCash (roleta)',
    'RedPacket'            => 'RedPacket (mina)',
    'Rebate'               => 'Rebate',
    'SignIn'               => 'SignIn (check-in)',
    'RechargePromotion'    => 'RechargePromotion (recarga promo)',
    'FirstWithdrawRebate'  => 'FirstWithdrawRebate (1o saque)',
    'FirstRechargeRebate'  => 'FirstRechargeRebate (1a recarga)',
    'Custom'               => 'Custom (link/detalhe externo)',
];

# Função para buscar as promoções
function get_promocoes() {
    global $mysqli;
    $qry = "SELECT * FROM promocoes";
    $result = mysqli_query($mysqli, $qry);
    $promocoes = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $promocoes[] = $row;
        }
    }
    return $promocoes;
}

# Função para atualizar a promoção
function update_promocao($id, $titulo, $status, $activity_type = null, $img = null) {
    global $mysqli;
    $activity_type = ($activity_type === '') ? null : $activity_type;

    try {
        if ($img) {
            $qry = $mysqli->prepare("UPDATE promocoes SET titulo = ?, status = ?, img = ?, activity_type = ? WHERE id = ?");
            $qry->bind_param("sissi", $titulo, $status, $img, $activity_type, $id);
        } else {
            $qry = $mysqli->prepare("UPDATE promocoes SET titulo = ?, status = ?, activity_type = ? WHERE id = ?");
            $qry->bind_param("sisi", $titulo, $status, $activity_type, $id);
        }

        return $qry->execute();
    } catch (Exception $e) {
        error_log("Erro ao atualizar promoção: " . $e->getMessage());
        return false;
    }
}

# Função para criar uma nova promoção
function create_promocao($titulo, $status, $img, $activity_type = null) {
    global $mysqli;
    $activity_type = ($activity_type === '') ? null : $activity_type;
    $qry = $mysqli->prepare("INSERT INTO promocoes (titulo, status, img, activity_type, criado_em) VALUES (?, ?, ?, ?, NOW())");
    if (!$qry) { return false; }
    $qry->bind_param("siss", $titulo, $status, $img, $activity_type);
    return $qry->execute();
}

# Função para remover uma promoção
function delete_promocao($id) {
    global $mysqli;
    $img = null;
    $sel = $mysqli->prepare("SELECT img FROM promocoes WHERE id = ?");
    if ($sel) {
        $sel->bind_param("i", $id);
        $sel->execute();
        $r = $sel->get_result();
        if ($row = $r->fetch_assoc()) { $img = $row['img']; }
        $sel->close();
    }
    $qry = $mysqli->prepare("DELETE FROM promocoes WHERE id = ?");
    if (!$qry) { return false; }
    $qry->bind_param("i", $id);
    $ok = $qry->execute();
    if ($ok && !empty($img)) {
        $chk = $mysqli->prepare("SELECT COUNT(*) AS c FROM promocoes WHERE img = ?");
        if ($chk) {
            $chk->bind_param("s", $img);
            $chk->execute();
            $cr = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ((int)$cr['c'] === 0) {
                $name = (strpos($img, '/uploads/') === 0) ? substr($img, strlen('/uploads/')) : $img;
                $file = __DIR__ . '/../uploads/' . $name;
                if (is_file($file)) { @unlink($file); }
            }
        }
    }
    return $ok;
}

# Se o formulário for enviado, atualizar os dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : 'update';
    if ($action === 'delete') {
        $delId = intval($_POST['id']);
        if (delete_promocao($delId)) {
            $toastType = 'success';
            $toastMessage = 'Promoção removida com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao remover a promoção.';
        }
    } elseif ($action === 'create') {
        $titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
        $status = intval($_POST['status']);
        $activity_type = isset($_POST['activity_type']) ? trim($_POST['activity_type']) : '';
        if (!array_key_exists($activity_type, $ACTIVITY_TYPES)) { $activity_type = ''; }
        if (empty($titulo) || empty($_FILES['img']['name'])) {
            $toastType = 'error';
            $toastMessage = 'Informe título e imagem para criar a promoção.';
        } else {
            $upload_dir = "../uploads/";
            $original_name = basename($_FILES['img']['name']);
            $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
            if (in_array($file_extension, $allowed_extensions, true)) {
                $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                $mime = $finfo ? finfo_file($finfo, $_FILES['img']['tmp_name']) : ($_FILES['img']['type'] ?? '');
                if ($finfo) { finfo_close($finfo); }
                if (stripos((string)$mime, 'image/') === 0) {
                    $new_img_name = time() . '_' . $original_name;
                    if (move_uploaded_file($_FILES["img"]["tmp_name"], $upload_dir . $new_img_name)) {
                        if (create_promocao($titulo, $status, $new_img_name, $activity_type)) {
                            $toastType = 'success';
                            $toastMessage = 'Promoção criada com sucesso!';
                        } else {
                            $toastType = 'error';
                            $toastMessage = 'Erro ao salvar a promoção.';
                        }
                    } else {
                        $toastType = 'error';
                        $toastMessage = 'Erro ao enviar a imagem. Tente novamente.';
                    }
                } else {
                    $toastType = 'error';
                    $toastMessage = 'O arquivo enviado não é uma imagem válida.';
                }
            } else {
                $toastType = 'error';
                $toastMessage = 'Extensão de arquivo não permitida.';
            }
        }
    } else {
    $id = intval($_POST['id']);
    $titulo = $_POST['titulo'];
    $status = intval($_POST['status']);
    $activity_type = isset($_POST['activity_type']) ? trim($_POST['activity_type']) : '';
    if (!array_key_exists($activity_type, $ACTIVITY_TYPES)) { $activity_type = ''; }

    # Buscar a imagem atual no banco de dados
    $query = "SELECT img FROM promocoes WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promocao = $result->fetch_assoc();
    $img = $promocao['img'];

    if (!empty($_FILES['img']['name'])) {
        $upload_dir = "../uploads/";
        $original_name = basename($_FILES['img']['name']);
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
        if (in_array($file_extension, $allowed_extensions, true)) {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? finfo_file($finfo, $_FILES['img']['tmp_name']) : ($_FILES['img']['type'] ?? '');
            if ($finfo) { finfo_close($finfo); }
            $is_image = stripos((string)$mime, 'image/') === 0;
            if ($is_image) {
                $new_img_name = time() . '_' . $original_name;
                $img_path = $upload_dir . $new_img_name;
                if (move_uploaded_file($_FILES["img"]["tmp_name"], $img_path)) {
                    $img = $new_img_name;
                } else {
                    $toastType = 'error';
                    $toastMessage = 'Erro ao enviar a imagem. Tente novamente.';
                    error_log("Erro ao mover o arquivo da imagem para $img_path");
                }
            } else {
                $toastType = 'error';
                $toastMessage = 'O arquivo enviado não é uma imagem válida.';
            }
        } else {
            $toastType = 'error';
            $toastMessage = 'Extensão de arquivo não permitida.';
        }
    }

    # Atualizar a promoção no banco de dados
    if (update_promocao($id, $titulo, $status, $activity_type, $img)) {
        $toastType = 'success';
        $toastMessage = 'Promoção atualizada com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar a promoção. Tente novamente.';
        error_log("Erro ao atualizar a promoção com ID $id");
    }
    }
}

# Buscar as promoções atuais
$promocoes = get_promocoes();
?>

<head>
    <?php $title = "Gerenciamento de Promoções";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Gerenciamento de Promoções</h4>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPromocaoModal">
                                    <i class="ti ti-plus me-1"></i>Nova Promoção
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                <table class="table table-sm table-compact align-middle text-nowrap mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Imagem</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($promocoes as $promocao): ?>
                                            <tr>
                                                <td><?= $promocao['id']; ?></td>
                                                <td><?= $promocao['titulo']; ?></td>
                                                <td><img src="<?= (strpos($promocao['img'], '/uploads/') === 0 ? '' : '/uploads/') . $promocao['img']; ?>?v=<?= time(); ?>" alt="Promoção" width="90"></td>
                                                <td><?= $promocao['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                <td>
                                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                                            data-bs-target="#editPromocaoModal<?= $promocao['id']; ?>">Editar</button>
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Remover esta promocao? Esta acao nao pode ser desfeita.');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $promocao['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm ms-1">Remover</button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
                                            <div class="modal fade" id="editPromocaoModal<?= $promocao['id']; ?>" tabindex="-1" aria-labelledby="editPromocaoLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editPromocaoLabel">Editar Promoção</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="id" value="<?= $promocao['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="titulo" class="form-label">Título</label>
                                                                    <input type="text" class="form-control" name="titulo" value="<?= $promocao['titulo']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="img" class="form-label">Imagem</label>
                                                                    <input type="file" class="form-control" name="img">
                                                                    <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select class="form-select" name="status">
                                                                        <option value="1" <?= $promocao['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                        <option value="0" <?= $promocao['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tipo de atividade (rota/tela)</label>
                                                                    <select class="form-select" name="activity_type">
                                                                        <?php foreach ($ACTIVITY_TYPES as $atVal => $atLabel): ?>
                                                                            <option value="<?= $atVal; ?>" <?= (($promocao['activity_type'] ?? '') === $atVal) ? 'selected' : ''; ?>><?= $atLabel; ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                    <small class="text-muted">Deixe em "Padrão do sistema" para não alterar a rota atual.</small>
                                                                </div>
                                                                <div class="text-center">
                                                                    <button type="submit" class="btn btn-success">Salvar Alterações</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- end row -->
            </div><!-- container -->
                                        <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <!-- Modal de Criação -->
    <div class="modal fade" id="createPromocaoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Promoção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Imagem</label>
                            <input type="file" class="form-control" name="img" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo de atividade (rota/tela)</label>
                            <select class="form-select" name="activity_type">
                                <?php foreach ($ACTIVITY_TYPES as $atVal => $atLabel): ?>
                                    <option value="<?= $atVal; ?>"><?= $atLabel; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Promoção nova sem tipo abre como link/detalhe (Custom).</small>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-success">Criar Promoção</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function showToast(type, message){window.showToast(type,message);}

        // Mostrar Toast com base no resultado do PHP
        <?php if (isset($toastType) && isset($toastMessage)): ?>
            showToast("<?= $toastType; ?>", "<?= $toastMessage; ?>");
        <?php endif; ?>
    </script>
</body>
</html>