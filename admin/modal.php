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

# Funções CRUD para Modais
function get_modais() {
    global $mysqli;
    $qry = "SELECT * FROM modais ORDER BY id DESC";
    $result = $mysqli->query($qry);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function add_modal($data) {
    global $mysqli;
    $stmt = $mysqli->prepare("INSERT INTO modais (announcementType, content, imgType, imgUrl, popupMethod, title, type, value, valueType, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $active = 1;
    $stmt->bind_param("sssssssssi", 
        $data['announcementType'], 
        $data['content'], 
        $data['imgType'], 
        $data['imgUrl'], 
        $data['popupMethod'], 
        $data['title'], 
        $data['type'], 
        $data['value'], 
        $data['valueType'], 
        $active
    );
    return $stmt->execute();
}

function update_modal($data) {
    global $mysqli;
    $stmt = $mysqli->prepare("UPDATE modais SET announcementType=?, content=?, imgType=?, imgUrl=?, popupMethod=?, title=?, type=?, value=?, valueType=?, active=? WHERE id=?");
    $stmt->bind_param("sssssssssii", 
        $data['announcementType'], 
        $data['content'], 
        $data['imgType'], 
        $data['imgUrl'], 
        $data['popupMethod'], 
        $data['title'], 
        $data['type'], 
        $data['value'], 
        $data['valueType'], 
        $data['active'],
        $data['id']
    );
    return $stmt->execute();
}

function delete_modal($id) {
    global $mysqli;
    $stmt = $mysqli->prepare("DELETE FROM modais WHERE id=?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

# Se o formulário for enviado, atualizar os dados
$toastType = null; // Variável para definir o tipo de Toast
$toastMessage = ''; // Variável para definir a mensagem do Toast

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_modal') {
            // Handle File Upload
            $imgUrl = '';
            if (isset($_FILES['imgUrl']) && $_FILES['imgUrl']['error'] == 0) {
                $target_dir = "../uploads/";
                $original_name = basename($_FILES["imgUrl"]["name"]);
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
                if (in_array($file_extension, $allowed_extensions, true)) {
                    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                    $mime = $finfo ? finfo_file($finfo, $_FILES['imgUrl']['tmp_name']) : ($_FILES['imgUrl']['type'] ?? '');
                    if ($finfo) { finfo_close($finfo); }
                    $is_image = stripos((string)$mime, 'image/') === 0;
                    if ($is_image) {
                        $file_name = time() . '_' . $original_name;
                        $target_file = $target_dir . $file_name;
                        if (move_uploaded_file($_FILES["imgUrl"]["tmp_name"], $target_file)) {
                            $imgUrl = "/uploads/" . $file_name;
                        }
                    }
                }
            } else {
                $imgUrl = $_POST['imgUrlText'] ?? '';
            }

            $data = [
                'announcementType' => $_POST['announcementType'],
                'content' => $_POST['content'],
                'imgType' => $_POST['imgType'],
                'imgUrl' => $imgUrl,
                'popupMethod' => $_POST['popupMethod'],
                'title' => $_POST['title'],
                'type' => $_POST['type'],
                'value' => $_POST['value'],
                'valueType' => $_POST['valueType']
            ];
            if (add_modal($data)) {
                $toastType = 'success';
                $toastMessage = 'Modal adicionado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao adicionar modal.';
            }
        } elseif ($_POST['action'] == 'edit_modal') {
             // Handle File Upload
             $imgUrl = $_POST['existingImgUrl'];
             if (isset($_FILES['imgUrl']) && $_FILES['imgUrl']['error'] == 0) {
                 $target_dir = "../uploads/";
                 $original_name = basename($_FILES["imgUrl"]["name"]);
                 $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                 $allowed_extensions = ['png','jpg','jpeg','webp','gif','ico','avif','svg'];
                 if (in_array($file_extension, $allowed_extensions, true)) {
                     $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                     $mime = $finfo ? finfo_file($finfo, $_FILES['imgUrl']['tmp_name']) : ($_FILES['imgUrl']['type'] ?? '');
                     if ($finfo) { finfo_close($finfo); }
                     $is_image = stripos((string)$mime, 'image/') === 0;
                     if ($is_image) {
                         $file_name = time() . '_' . $original_name;
                         $target_file = $target_dir . $file_name;
                         if (move_uploaded_file($_FILES["imgUrl"]["tmp_name"], $target_file)) {
                             $imgUrl = "/uploads/" . $file_name;
                         }
                     }
                 }
             } elseif (!empty($_POST['imgUrlText'])) {
                 $imgUrl = $_POST['imgUrlText'];
             }

            $data = [
                'id' => $_POST['id'],
                'announcementType' => $_POST['announcementType'],
                'content' => $_POST['content'],
                'imgType' => $_POST['imgType'],
                'imgUrl' => $imgUrl,
                'popupMethod' => $_POST['popupMethod'],
                'title' => $_POST['title'],
                'type' => $_POST['type'],
                'value' => $_POST['value'],
                'valueType' => $_POST['valueType'],
                'active' => isset($_POST['active']) ? 1 : 0
            ];
            if (update_modal($data)) {
                $toastType = 'success';
                $toastMessage = 'Modal atualizado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao atualizar modal.';
            }
        } elseif ($_POST['action'] == 'delete_modal') {
            if (delete_modal($_POST['id'])) {
                $toastType = 'success';
                $toastMessage = 'Modal excluído com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao excluir modal.';
            }
        }
    }
}


# Buscar os dados atuais
$modais = get_modais();
?>

<head>
    <?php $title = "Configurações de App";
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
                        <!-- MODAIS SECTION -->
                        <div class="card mt-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title">Gerenciamento de Modais de Anúncio</h4>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                                    <i class="ti ti-plus me-1"></i>Novo Modal
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle text-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Tipo</th>
                                                <th>Método Popup</th>
                                                <th>Imagem</th>
                                                <th>Ativo</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($modais as $modal): ?>
                                                <tr>
                                                    <td><?= $modal['id'] ?></td>
                                                    <td><?= htmlspecialchars($modal['title']) ?></td>
                                                    <td><?= htmlspecialchars($modal['announcementType']) ?></td>
                                                    <td><?= htmlspecialchars($modal['popupMethod']) ?></td>
                                                    <td>
                                                        <?php if ($modal['imgUrl']): ?>
                                                            <img src="<?= htmlspecialchars($modal['imgUrl']) ?>" alt="Img" style="height: 50px;">
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($modal['active']): ?>
                                                            <span class="badge bg-success">Ativo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Inativo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-outline-primary btn-sm btn-edit" 
                                                            data-id="<?= $modal['id'] ?>"
                                                            data-title="<?= htmlspecialchars($modal['title']) ?>"
                                                            data-announcementtype="<?= htmlspecialchars($modal['announcementType']) ?>"
                                                            data-content="<?= htmlspecialchars($modal['content']) ?>"
                                                            data-imgtype="<?= htmlspecialchars($modal['imgType']) ?>"
                                                            data-imgurl="<?= htmlspecialchars($modal['imgUrl']) ?>"
                                                            data-popupmethod="<?= htmlspecialchars($modal['popupMethod']) ?>"
                                                            data-type="<?= htmlspecialchars($modal['type']) ?>"
                                                            data-value="<?= htmlspecialchars($modal['value']) ?>"
                                                            data-valuetype="<?= htmlspecialchars($modal['valueType']) ?>"
                                                            data-active="<?= $modal['active'] ?>"
                                                            data-bs-toggle="modal" data-bs-target="#editModal">
                                                            <i class="ti ti-pencil me-1"></i>Editar
                                                        </button>
                                                        <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                                            <input type="hidden" name="action" value="delete_modal">
                                                            <input type="hidden" name="id" value="<?= $modal['id'] ?>">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm ms-1"><i class="ti ti-trash me-1"></i>Remover</button>
                                                        </form>
                                                    </td>
                                                </tr>
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Adicionar Novo Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_modal">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Título</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Anúncio</label>
                                <select class="form-select" name="announcementType">
                                    <option value="img">Imagem</option>
                                    <option value="text">Texto</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Método Popup</label>
                                <select class="form-select" name="popupMethod">
                                    <option value="login">Login (Logado)</option>
                                    <option value="logout">Logout (Visitante)</option>
                                    <option value="both">Ambos (Login e Visitante)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Imagem</label>
                                <input type="text" class="form-control" name="imgType" value="default">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">URL da Imagem (Upload ou Link)</label>
                                <input type="file" class="form-control mb-2" name="imgUrl">
                                <input type="text" class="form-control" name="imgUrlText" placeholder="Ou cole a URL da imagem aqui">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Conteúdo (Opcional)</label>
                                <textarea class="form-control" name="content"></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Ação</label>
                                <input type="text" class="form-control" name="type" value="InternalLink">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Valor da Ação</label>
                                <input type="text" class="form-control" name="value" value="/Redeem">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo do Valor</label>
                                <input type="text" class="form-control" name="valueType" value="CODE">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Modal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_modal">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="existingImgUrl" id="edit_existingImgUrl">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Título</label>
                                <input type="text" class="form-control" name="title" id="edit_title" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Anúncio</label>
                                <select class="form-select" name="announcementType" id="edit_announcementType">
                                    <option value="img">Imagem</option>
                                    <option value="text">Texto</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Método Popup</label>
                                <select class="form-select" name="popupMethod" id="edit_popupMethod">
                                    <option value="login">Login (Logado)</option>
                                    <option value="logout">Logout (Visitante)</option>
                                    <option value="both">Ambos (Login e Visitante)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Imagem</label>
                                <input type="text" class="form-control" name="imgType" id="edit_imgType">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">URL da Imagem (Upload ou Link)</label>
                                <div class="mb-2">
                                    <img src="" id="edit_imgPreview" style="max-height: 100px; display: none;">
                                </div>
                                <input type="file" class="form-control mb-2" name="imgUrl">
                                <input type="text" class="form-control" name="imgUrlText" id="edit_imgUrlText" placeholder="Ou cole a URL da imagem aqui">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Conteúdo (Opcional)</label>
                                <textarea class="form-control" name="content" id="edit_content"></textarea>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo de Ação</label>
                                <input type="text" class="form-control" name="type" id="edit_type">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Valor da Ação</label>
                                <input type="text" class="form-control" name="value" id="edit_value">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Tipo do Valor</label>
                                <input type="text" class="form-control" name="valueType" id="edit_valueType">
                            </div>
                            <div class="col-md-12 mb-3 form-check ms-2">
                                <input type="checkbox" class="form-check-input" name="active" id="edit_active">
                                <label class="form-check-label" for="edit_active">Ativo</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <!-- Função de Toast -->
    <script>
        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    
                    <h5 class="me-auto my-0">Atualização</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Script para preencher o modal de edição
        document.addEventListener('DOMContentLoaded', function() {
            var editButtons = document.querySelectorAll('.btn-edit');
            editButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var id = this.getAttribute('data-id');
                    var title = this.getAttribute('data-title');
                    var announcementType = this.getAttribute('data-announcementtype');
                    var content = this.getAttribute('data-content');
                    var imgType = this.getAttribute('data-imgtype');
                    var imgUrl = this.getAttribute('data-imgurl');
                    var popupMethod = this.getAttribute('data-popupmethod');
                    var type = this.getAttribute('data-type');
                    var value = this.getAttribute('data-value');
                    var valueType = this.getAttribute('data-valuetype');
                    var active = this.getAttribute('data-active');

                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_title').value = title;
                    document.getElementById('edit_announcementType').value = announcementType;
                    document.getElementById('edit_content').value = content;
                    document.getElementById('edit_imgType').value = imgType;
                    document.getElementById('edit_existingImgUrl').value = imgUrl;
                    document.getElementById('edit_imgUrlText').value = imgUrl;
                    document.getElementById('edit_popupMethod').value = popupMethod;
                    document.getElementById('edit_type').value = type;
                    document.getElementById('edit_value').value = value;
                    document.getElementById('edit_valueType').value = valueType;
                    document.getElementById('edit_active').checked = active == 1;

                    if (imgUrl) {
                        document.getElementById('edit_imgPreview').src = imgUrl;
                        document.getElementById('edit_imgPreview').style.display = 'block';
                    } else {
                        document.getElementById('edit_imgPreview').style.display = 'none';
                    }
                });
            });
        });
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>