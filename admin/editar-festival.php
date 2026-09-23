<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 1);
error_reporting(E_ALL);
#======================================#
session_start();
include_once "services/database.php";
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

# Função para buscar os banners
function get_banners() {
    global $mysqli;
    $qry = "SELECT * FROM festival";
    $result = mysqli_query($mysqli, $qry);
    $banners = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $banners[] = $row;
        }
    }
    return $banners;
}

# Função para atualizar o banner
function update_banner($id, $titulo, $status, $img = null) {
    global $mysqli;

    if ($img) {
        $qry = $mysqli->prepare("UPDATE festival SET titulo = ?, status = ?, img = ? WHERE id = ?");
        $qry->bind_param("sisi", $titulo, $status, $img, $id);
    } else {
        $qry = $mysqli->prepare("UPDATE festival SET titulo = ?, status = ? WHERE id = ?");
        $qry->bind_param("sii", $titulo, $status, $id);
    }

    return $qry->execute();
}

# Se o formulário for enviado, atualizar os dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $titulo = $_POST['titulo'];
    $status = intval($_POST['status']);

    // Buscar a imagem atual no banco de dados
    $query = "SELECT img FROM festival WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $promocao = $result->fetch_assoc();
    $img = $promocao['img'];

    if (!empty($_FILES['img']['name'])) {
        $upload_dir = "../holiday/14/";
        $original_name = basename($_FILES['img']['name']);
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
        if (in_array($file_extension, $allowed_extensions, true)) {
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? finfo_file($finfo, $_FILES['img']['tmp_name']) : ($_FILES['img']['type'] ?? '');
            if ($finfo) { finfo_close($finfo); }
            $is_image = stripos((string)$mime, 'image/') === 0;
            if ($is_image) {
                $img_path = $upload_dir . basename($img);
                if (file_exists($img_path)) {
                    unlink($img_path);
                }
                if (move_uploaded_file($_FILES["img"]["tmp_name"], $img_path)) {
                    $img = basename($img);
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
    } else {
        $img = $img;
    }

    // Atualizar a promoção no banco de dados
    if (update_banner($id, $titulo, $status, '/holiday/14/' .$img)) {
        $toastType = 'success';
        $toastMessage = 'Promoção atualizada com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar a promoção. Tente novamente.';
        error_log("Erro ao atualizar a promoção com ID $id");
    }
}

# Buscar os banners atuais
$banners = get_banners();
?>

<head>
    <?php $title = "Gerenciamento de festival";
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
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de festival</h4>
                            </div>

                            <div class="card-body">
                                <table class="table table-centered mb-0">
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
                                        <?php foreach ($banners as $banner): ?>
                                            <tr>
                                                <td><?= $banner['id']; ?></td>
                                                <td><?= $banner['titulo']; ?></td>
                                                <td><img src="<?= $banner['img']; ?>?v=<?= time(); ?>" alt="Banner" width="100"></td>
                                                <td><?= $banner['status'] == 1 ? 'Ativo' : 'Inativo'; ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-bs-toggle="modal"
                                                            data-bs-target="#editBannerModal<?= $banner['id']; ?>">Editar</button>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
                                            <div class="modal fade" id="editBannerModal<?= $banner['id']; ?>" tabindex="-1" aria-labelledby="editBannerLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editBannerLabel">Editar Banner</h5>
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
                </div><!-- end row -->
            </div><!-- container -->
            
                            <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->

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
