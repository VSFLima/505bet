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
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();

checa_login_adm();

function get_playfiver_config()
{
    global $mysqli;
    $result = mysqli_query($mysqli, "SELECT * FROM playfiver WHERE id = 1");
    return mysqli_fetch_assoc($result);
}

function update_playfiver_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE playfiver SET url = ?, agent_code = ?, agent_token = ?, agent_secret = ?, ativo = ? WHERE id = 1");
    $qry->bind_param("ssssi", $data['url'], $data['agent_code'], $data['agent_token'], $data['agent_secret'], $data['ativo']);
    return $qry->execute();
}

$toastType    = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_playfiver'])) {
    $data = [
        'url'          => trim($_POST['url_playfiver']),
        'agent_code'   => trim($_POST['agent_code_playfiver']),
        'agent_token'  => trim($_POST['agent_token_playfiver']),
        'agent_secret' => trim($_POST['agent_secret_playfiver']),
        'ativo'        => intval($_POST['ativo_playfiver']),
    ];

    if (update_playfiver_config($data)) {
        $toastType    = 'success';
        $toastMessage = 'Credenciais da PlayFiver atualizadas com sucesso!';
    } else {
        $toastType    = 'error';
        $toastMessage = 'Erro ao atualizar as credenciais da PlayFiver. Tente novamente.';
    }
}

$playfiver_config = get_playfiver_config();
?>

<head>
    <?php $title = "Configurações PlayFiver"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">

                <div class="row justify-content-center mb-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><i class="ti ti-plug me-2"></i>Credenciais PlayFiver</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="update_playfiver">
                                    <div class="row">

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">URL</h5>
                                                    <input type="text" name="url_playfiver" class="form-control" value="<?= htmlspecialchars($playfiver_config['url'] ?? '') ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Code</h5>
                                                    <div class="input-group">
                                                        <input type="password" id="agent_code_playfiver" name="agent_code_playfiver" class="form-control" value="<?= htmlspecialchars($playfiver_config['agent_code'] ?? '') ?>" required>
                                                        <span class="input-group-text" style="cursor:pointer" onclick="togglePassword('agent_code_playfiver', this)">
                                                            <i class="ti ti-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Token</h5>
                                                    <div class="input-group">
                                                        <input type="password" id="agent_token_playfiver" name="agent_token_playfiver" class="form-control" value="<?= htmlspecialchars($playfiver_config['agent_token'] ?? '') ?>" required>
                                                        <span class="input-group-text" style="cursor:pointer" onclick="togglePassword('agent_token_playfiver', this)">
                                                            <i class="ti ti-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Secret</h5>
                                                    <div class="input-group">
                                                        <input type="password" id="agent_secret_playfiver" name="agent_secret_playfiver" class="form-control" value="<?= htmlspecialchars($playfiver_config['agent_secret'] ?? '') ?>" required>
                                                        <span class="input-group-text" style="cursor:pointer" onclick="togglePassword('agent_secret_playfiver', this)">
                                                            <i class="ti ti-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Ativo</h5>
                                                    <select name="ativo_playfiver" class="form-select" required>
                                                        <option value="1" <?= ($playfiver_config['ativo'] ?? 0) == 1 ? 'selected' : '' ?>>Sim</option>
                                                        <option value="0" <?= ($playfiver_config['ativo'] ?? 0) == 0 ? 'selected' : '' ?>>Não</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">
                                            <i class="ti ti-device-floppy me-1"></i>Salvar Configurações PlayFiver
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>

        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = 'toast align-items-center bg-light border-0 fade show';
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = '<div class="toast-header"><h5 class="me-auto my-0">Atualização</h5><small>Agora</small><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body">' + message + '</div>';
            toastPlacement.appendChild(toast);
            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();
            setTimeout(function () { bootstrapToast.hide(); setTimeout(() => toast.remove(), 500); }, 3000);
        }

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                iconElement.classList.replace('ti-eye', 'ti-eye-off');
            } else {
                input.type = 'password';
                iconElement.classList.replace('ti-eye-off', 'ti-eye');
            }
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>showToast('<?= $toastType ?>', '<?= $toastMessage ?>');</script>
    <?php endif; ?>

</body>
</html>