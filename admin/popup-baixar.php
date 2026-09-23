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

# Função para buscar os dados atuais da tabela afiliados_config
function get_afiliados_config()
{
    global $mysqli;
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function get_download_popup()
{
    global $mysqli;
    $qry = "SELECT baixar_ativado, topIconColor, topBgColor, link_app_android, link_app_ios FROM config WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return [
            'baixar_ativado' => intval($row['baixar_ativado']),
            'topBgColor' => $row['topBgColor'] ?? '',
            'topIconColor' => $row['topIconColor'] ?? '',
            'link_app_android' => $row['link_app_android'] ?? '',
            'link_app_ios' => $row['link_app_ios'] ?? ''
        ];
    }
    return [
        'baixar_ativado' => 0,
        'topBgColor' => '',
        'topIconColor' => '',
        'link_app_android' => '',
        'link_app_ios' => ''
    ];
}

# Função para atualizar os dados da tabela afiliados_config
function update_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE config SET 
        baixar_ativado = ?,
        topBgColor = ?,
        topIconColor = ?,
        link_app_android = ?,
        link_app_ios = ?
        WHERE id = 1");

    $qry->bind_param(
           "sssss",
    $data['baixar_ativado'],
    $data['topBgColor'],
    $data['topIconColor'],
    $data['link_app_android'],
    $data['link_app_ios']
    );
    return $qry->execute();
}

# Se o formulário for enviado, atualizar os dados
$toastType = null; // Variável para definir o tipo de Toast
$toastMessage = ''; // Variável para definir a mensagem do Toast

# Se o formulário for enviado, atualizar os dados
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      $data = [
        'baixar_ativado' => $_POST['baixar_ativado'],
        'topBgColor' => $_POST['topBgColor'],
        'topIconColor' => $_POST['topIconColor'],
        'link_app_android' => $_POST['link_app_android'] ?? '',
        'link_app_ios' => $_POST['link_app_ios'] ?? ''
    ];

    if (update_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Configurações de apps atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as configurações. Tente novamente.';
    }
}


# Buscar os dados atuais
$config = get_afiliados_config();
$download_popup = get_download_popup();
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
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de popup de download</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <!-- Switch de ativar/desativar jackpot -->
                                        <div class="row mb-4 align-items-center">
                                            <div class="col-auto">
                                                <label for="download_popup" class="col-form-label fw-semibold">Status Do PopUp</label>
                                            </div>
                                            <div class="col-auto">
                                               <select class="form-select" id="baixar_ativado" name="baixar_ativado">
    <option value="1" <?= isset($download_popup['baixar_ativado']) && $download_popup['baixar_ativado'] == 1 ? 'selected' : '' ?>>Ativado</option>
    <option value="0" <?= isset($download_popup['baixar_ativado']) && $download_popup['baixar_ativado'] == 0 ? 'selected' : '' ?>>Desativado</option>
</select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Novo input para Cor Do Topo -->
                                     <div class="row mb-4 align-items-center">
                                         <div class="col-auto">
                                             <label for="topIconColor" class="col-form-label fw-semibold">Cor Do Topo (topIconColor)</label>
                                         </div>
                                         <div class="col-auto">
                                             <input type="text" class="form-control" id="topIconColor" name="topIconColor" value="<?= htmlspecialchars($download_popup['topIconColor'] ?? '') ?>" placeholder="#FFFFFF">
                                         </div>
                                     </div>

                                     <!-- Novo input para Cor Do Topo -->
                                     <div class="row mb-4 align-items-center">
                                         <div class="col-auto">
                                             <label for="topBgColor" class="col-form-label fw-semibold">Cor Do Fundo Do Topo (topBgColor)</label>
                                         </div>
                                         <div class="col-auto">
                                             <input type="text" class="form-control" id="topBgColor" name="topBgColor" value="<?= htmlspecialchars($download_popup['topBgColor'] ?? '') ?>" placeholder="#FFFFFF">
                                         </div>
                                     </div>

                                     <!-- Link APK Android -->
                                     <div class="row mb-4 align-items-center">
                                         <div class="col-auto">
                                             <label for="link_app_android" class="col-form-label fw-semibold">Link APK Android</label>
                                         </div>
                                         <div class="col-auto" style="min-width:360px">
                                             <input type="text" class="form-control" id="link_app_android" name="link_app_android" value="<?= htmlspecialchars($download_popup['link_app_android'] ?? '') ?>" placeholder="https://...">
                                         </div>
                                     </div>

                                     <!-- Link APK iOS -->
                                     <div class="row mb-4 align-items-center">
                                         <div class="col-auto">
                                             <label for="link_app_ios" class="col-form-label fw-semibold">Link APK iOS</label>
                                         </div>
                                         <div class="col-auto" style="min-width:360px">
                                             <input type="text" class="form-control" id="link_app_ios" name="link_app_ios" value="<?= htmlspecialchars($download_popup['link_app_ios'] ?? '') ?>" placeholder="https://...">
                                         </div>
                                     </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">Salvar Configurações</button>
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

            setTimeout(function () {
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