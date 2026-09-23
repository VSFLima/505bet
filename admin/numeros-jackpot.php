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

# Função para buscar o numero_jackpot atual
function get_numero_jackpot_atual()
{
    global $mysqli;
    $qry = "SELECT numero_jackpot FROM config WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['numero_jackpot'];
}

# Função para atualizar o numero_jackpot no banco de dados
function update_numero_jackpot($numero_jackpot)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE config SET numero_jackpot = ? WHERE id = 1");
    $qry->bind_param("i", $numero_jackpot);
    return $qry->execute();
}

# Verificar se um novo numero_jackpot foi selecionado e atualizar
$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $numero_jackpot_selecionado = intval($_POST['numero_jackpot']);

    if (update_numero_jackpot($numero_jackpot_selecionado)) {
        $toastType = 'success';
        $toastMessage = 'Estilo de numeração atualizado com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar o numeração. Tente novamente.';
    }
}

# Buscar o numero_jackpot atual
$numero_jackpot_atual = get_numero_jackpot_atual();
?>

<head>
    <?php $title = "Configurações de Numeros"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<style>
    .img-container {
        width: 100%;
        height: 150px; /* Ajuste a altura conforme necessário */
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
                                <h4 class="card-title">Selecione um estilo de número para personalizar</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row">
                                        <?php
                                        // Exibir 24 imagens de numero_jackpots para seleção
                                        for ($i = 1; $i <= 5; $i++) {
                                            $selected = ($numero_jackpot_atual == $i) ? 'border border-success' : '';
                                            echo "
                                            <div class='col-6 col-md-4 col-lg-3'>
                                                <div class='card mb-4'>
                                                <div class='img-container'>
                                                    <img src='/uploads/numero$i.png' class='card-img-top img-fluid $selected' alt='numero_jackpot $i'>
                                                </div>
                                                    <div class='card-body text-center'>
                                                        <input type='radio' name='numero_jackpot' value='$i' id='numero_jackpot$i' ".($numero_jackpot_atual == $i ? 'checked' : '').">
                                                        <label for='numero_jackpot$i'>Numeração $i</label>
                                                    </div>
                                                </div>
                                            </div>";
                                        }
                                        ?>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">Salvar Personalização</button>
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
