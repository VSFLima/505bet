<?php include 'partials/html.php' ?>
<?php
ini_set('display_errors', 0);
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

function get_banners() {
    global $mysqli;
    $qry = "SELECT * FROM popups";
    $result = mysqli_query($mysqli, $qry);
    $banners = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $banners[] = $row;
        }
    }
    return $banners;
}

function update_banner($id, $titulo, $status, $img = null, $redirect_url = null) {
    global $mysqli;

    if ($img) {
        $qry = $mysqli->prepare("UPDATE popups SET titulo = ?, status = ?, img = ?, redirect_url = ? WHERE id = ?");
        $qry->bind_param("sissi", $titulo, $status, $img, $redirect_url, $id);
    } else {
        $qry = $mysqli->prepare("UPDATE popups SET titulo = ?, status = ?, redirect_url = ? WHERE id = ?");
        $qry->bind_param("sisi", $titulo, $status, $redirect_url, $id);
    }

    return $qry->execute();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $titulo = $_POST['titulo'];
    $status = intval($_POST['status']);
    $redirect_url = isset($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null;

    $query = "SELECT img FROM popups WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $banner = $result->fetch_assoc();
    $img = $banner['img'];

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

    if (update_banner($id, $titulo, $status, $img, $redirect_url)) {
        $toastType = 'success';
        $toastMessage = 'Popup atualizado com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar o popup. Tente novamente.';
    }
}

$banners = get_banners();
?>

<head>
    <?php $title = "Gerenciamento de Popups"; include 'partials/title-meta.php' ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Popups</h4>
                            </div>

                            <div class="card-body">
                                <table class="table table-centered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Imagem</th>
                                            <th>Status</th>
                                            <th>Data de Criação</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($banners as $banner): ?>
                                            <tr>
                                                <td><?= $banner['id']; ?></td>
                                                <td><?= $banner['titulo']; ?></td>
                                                <td><img src="/uploads/<?= $banner['img']; ?>" alt="Banner" width="100"></td>
                                                <td><?= $banner['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                <td><?= $banner['criado_em']; ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#editBannerModal<?= $banner['id']; ?>">Editar</button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editBannerModal<?= $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editBannerLabel">Editar Popup</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <input type="hidden" name="id" value="<?= $banner['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="titulo" class="form-label">Título</label>
                                                                    <input type="text" class="form-control" name="titulo" value="<?= $banner['titulo']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="img" class="form-label">Imagem</label>
                                                                    <input type="file" class="form-control" name="img">
                                                                    <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="redirect_url" class="form-label">URL de Redirecionamento</label>
                                                                    <input type="text" class="form-control" name="redirect_url" value="<?= $banner['redirect_url']; ?>" placeholder="https://example.com">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select class="form-select" name="status">
                                                                        <option value="1" <?= $banner['status'] == 1 ? 'selected' : ''; ?>>Ativo</option>
                                                                        <option value="0" <?= $banner['status'] == 0 ? 'selected' : ''; ?>>Inativo</option>
                                                                    </select>
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
            </div>
    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div>
    </div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function showToast(type, message){window.showToast(type,message);}

        <?php if (isset($toastType) && isset($toastMessage)): ?>
            showToast('<?= $toastType; ?>', '<?= $toastMessage; ?>');
        <?php endif; ?>
    </script>
</body>
</html>
