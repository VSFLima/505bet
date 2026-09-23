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


// Função para fazer o upload e renomear o arquivo com a extensão original
function upload_and_rename_as_original($file)
{
    $upload_dir = "../uploads/"; // Diretório de uploads
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); // Obter a extensão do arquivo

    // Verifica se o arquivo enviado é uma imagem
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false; // Não é uma imagem válida
    }

    // Gerar um nome aleatório para o arquivo
    $random_name = uniqid('img_', true); // Gera um ID único e um nome aleatório

    // Definir o caminho final do arquivo com o nome aleatório e a extensão original
    $target_file = $upload_dir . $random_name . '.' . $file_extension;

    // Mover o arquivo para o diretório de uploads com o nome e a extensão originais
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $random_name . '.' . $file_extension; // Retorna o nome aleatório com a extensão original
    }

    return false; // Retorna falso se houver falha
}



# Função para atualizar logo e/ou favicon na tabela config
// function update_config_images($logo = null)
// {
//     global $mysqli;

//     $qry_string = "UPDATE config SET ";
//     $params = [];
//     $types = '';

//     // Adicionar logo à consulta se estiver presente
//     if ($logo !== null) {
//         $qry_string .= "logo_pg = ?, ";
//         $params[] = $logo;
//         $types .= 's';
//     }

//     // Remover a última vírgula e espaço
//     $qry_string = rtrim($qry_string, ', ') . " WHERE id = 1";

//     $qry = $mysqli->prepare($qry_string);
//     $qry->bind_param($types, ...$params);

//     return $qry->execute();
// }

function upload_custom_brl_avif($file)
{
    $upload_path1 = "/game_pictures/p/7920/EA/hot/200/3/custom_BRL.avif";
    $upload_path2 = "/game_pictures/p/7920/EA/200/3/custom_BRL.avif";
    $full_path1 = $_SERVER['DOCUMENT_ROOT'] . $upload_path1;
    $full_path2 = $_SERVER['DOCUMENT_ROOT'] . $upload_path2;

    // Verifica se o arquivo enviado é uma imagem AVIF
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // if ($file_extension !== 'avif') {
    //     return false; // Só aceita AVIF
    // }

    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false; // Não é uma imagem válida
    }

    // Move o arquivo para o primeiro destino fixo
    if (move_uploaded_file($file['tmp_name'], $full_path1)) {
        // Copia para o segundo destino
        copy($full_path1, $full_path2);
        return $upload_path1;
    }

    return false;
}

# Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $toastType = null;
    $toastMessage = '';

    $logo = null;

    // Verificar se o logo foi enviado
    if (!empty($_FILES['logo']['name'])) {
        $logo = upload_custom_brl_avif($_FILES['logo']); // Agora preserva a extensão original
        if (!$logo) {
            $toastType = 'error';
            $toastMessage = 'Erro ao enviar o logo. Verifique a extensão do arquivo.';
        }
    }



    // Atualizar as imagens no banco de dados
    // if ($logo) {
    //     if (update_config_images($logo)) {
    //         $toastType = 'success';
    //         $toastMessage = 'Imagens atualizadas com sucesso!';
    //     } else {
    //         $toastType = 'error';
    //         $toastMessage = 'Erro ao atualizar as imagens.';
    //     }
    // }
}

# Buscar o caminho atual das imagens logo e favicon
$query = "SELECT logo FROM config WHERE id = 1";
$result = mysqli_query($mysqli, $query);
$config = mysqli_fetch_assoc($result);

?>

<head>
    <?php $title = "Gerenciamento de Identidade Visual"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<style>
    .img-container {
        width: 100%;
        height: 150px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: hidden;
    }

    .img-container img {
        width: auto;
        object-fit: cover;
    }
</style>


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
                                <h4 class="card-title">Gerenciamento De Identidade Visual</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row mt-12">
                                        <div class="col-md-4"> <!-- Ajustado para 4 colunas para cada imagem -->
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <label for="logo" class="form-label">AvatarPG</label>
                                                    <?php if (!empty($config['logo'])): ?>
                                                        <div class="mb-3">
                                                            <img src="/game_pictures/p/7920/EA/hot/200/3/custom_BRL.avif" class="img-fluid" alt="Logo" style="max-height: 150px;">
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-muted">Nenhuma imagem de logo enviada ainda.</p>
                                                    <?php endif; ?>
                                                    <input type="file" name="logo" id="logo" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success mb-4">Salvar Alterações</button>
                                    </div>
                                </form>
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

            setTimeout(function() {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);

        }
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>