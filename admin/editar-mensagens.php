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

# Função para buscar os banners
function get_banners()
{
    global $mysqli;
    $qry = "SELECT * FROM mensagens";
    $result = mysqli_query($mysqli, $qry);
    $banners = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $banners[] = $row;
        }
    }
    return $banners;
}

# Função para inserir um novo banner (ou mensagem)
function insert_banner($titulo, $content, $banner, $status, $texto)
{
    global $mysqli;

    $qry = $mysqli->prepare("INSERT INTO mensagens (titulo, content, banner, status, texto) VALUES (?, ?, ?, ?, ?)");
    $qry->bind_param("sssii", $titulo, $content, $banner, $status, $texto);

    return $qry->execute();
}

# Função para atualizar o banner
function update_banner($id, $titulo, $content, $status = null, $banner = null, $texto = 0)
{
    global $mysqli;

    # Verificar se a imagem foi enviada
    if ($banner !== null) {
        $qry = $mysqli->prepare("UPDATE mensagens SET titulo = ?, content = ?, banner = ?, status = ?, texto = ? WHERE id = ?");
        $qry->bind_param("sssiii", $titulo, $content, $banner, $status, $texto, $id);
    } else {
        # Se não houver imagem, apenas atualiza o título, conteúdo e status
        $qry = $mysqli->prepare("UPDATE mensagens SET titulo = ?, content = ?, status = ?, texto = ? WHERE id = ?");
        $qry->bind_param("ssiii", $titulo, $content, $status, $texto, $id);
    }

    return $qry->execute();
}

# Se o formulário for enviado para atualização ou inserção
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : null;
    $titulo = $_POST['titulo'];
    $content = $_POST['content'];
    $status = intval($_POST['status']);
    $texto = isset($_POST['texto']) ? intval($_POST['texto']) : 0;

    if ($id) {
        $query = "SELECT banner FROM mensagens WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $banner = $result->fetch_assoc();
        $img = $banner['banner'] ?? null;
    } else {
        $img = null;
    }

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

    $valid = true;
    if ($texto === 1) {
        if (trim($content) === '') {
            $valid = false;
            $toastType = 'error';
            $toastMessage = 'Conteúdo de texto é obrigatório.';
        }
    } else {
        if (!$img) {
            $valid = false;
            $toastType = 'error';
            $toastMessage = 'Imagem é obrigatória para tipo imagem.';
        }
    }

    # Se houver ID, atualiza a mensagem
    if ($id) {
        if ($valid && update_banner($id, $titulo, $content, $status, $img, $texto)) {
            $toastType = 'success';
            $toastMessage = 'Anúncio atualizado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar o anúncio. Tente novamente.';
        }
    } else {
        # Caso contrário, insere uma nova mensagem
        if ($valid && insert_banner($titulo, $content, $img, $status, $texto)) {
            $toastType = 'success';
            $toastMessage = 'Nova mensagem inserida com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao inserir a nova mensagem. Tente novamente.';
        }
    }
}

# Buscar os banners atuais
$banners = get_banners();
?>

<head>
    <?php $title = "Gerenciamento de Mensagens";
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
                                <h4 class="card-title">Gerenciamento de Mensagens</h4>
                                <!-- Botão para criar nova mensagem -->
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createBannerModal">
                                    Criar Nova Mensagem
                                </button>
                            </div>

                            <div class="card-body">
                                <table class="table table-centered mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Tipo</th>
                                            <th>Conteúdo/Imagem</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($banners as $banner): ?>
                                            <tr>
                                                <td><?= $banner['id']; ?></td>
                                                <td><?= $banner['titulo']; ?></td>
                                                <td><?= ($banner['texto'] == 1 ? 'Texto' : 'Imagem'); ?></td>
                                                <td>
                                                    <?php if ($banner['texto'] == 1): ?>
                                                        <?= html_entity_decode($banner['content']); ?>
                                                    <?php else: ?>
                                                        <img src="/uploads/<?= $banner['banner']; ?>?v=<?= time(); ?>" alt="Banner" width="100">
                                                    <?php endif; ?>
                                                </td>
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
                                                            <h5 class="modal-title" id="editBannerLabel">Editar Mensagem</h5>
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
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="texto" class="form-label">Tipo</label>
                                                                    <select class="form-select" name="texto">
                                                                        <option value="1" <?= $banner['texto'] == 1 ? 'selected' : ''; ?>>Texto</option>
                                                                        <option value="0" <?= $banner['texto'] == 0 ? 'selected' : ''; ?>>Imagem</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="content" class="form-label">Conteúdo</label>
                                                                    <textarea class="form-control" name="content" rows="8"><?= htmlspecialchars($banner['content'], ENT_QUOTES | ENT_HTML5); ?></textarea>
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

                                            <!-- Modal de Criação -->
                                            <div class="modal fade" id="createBannerModal" tabindex="-1" aria-labelledby="createBannerLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="createBannerLabel">Criar Nova Mensagem</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" enctype="multipart/form-data">
                                                                <div class="mb-3">
                                                                    <label for="titulo" class="form-label">Título</label>
                                                                    <input type="text" class="form-control" name="titulo" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="content" class="form-label">Conteúdo</label>
                                                                    <textarea class="form-control" name="content" rows="8"></textarea>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="texto" class="form-label">Tipo</label>
                                                                    <select class="form-select" name="texto" required>
                                                                        <option value="1">Texto</option>
                                                                        <option value="0">Imagem</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="img" class="form-label">Imagem</label>
                                                                    <input type="file" class="form-control" name="img">
                                                                    <small class="text-muted">Deixe em branco se não quiser alterar a imagem.</small>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select class="form-select" name="status" required>
                                                                        <option value="1">Ativo</option>
                                                                        <option value="0">Inativo</option>
                                                                    </select>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" class="btn btn-primary">Criar</button>
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

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <!-- Função de Toast -->
    <script>
        function showToast(type, message){window.showToast(type,message);}

        // Mostrar Toast com base no resultado do PHP
        <?php if (isset($toastType) && isset($toastMessage)): ?>
            showToast("<?= $toastType; ?>", "<?= $toastMessage; ?>");
        <?php endif; ?>
    </script>
</body>

</html>
