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
$csrf = new CSRF_Protect();
#======================================#
checa_login_adm();
#======================================#

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}


function get_gateways_config()
{
    global $mysqli;
    $bspayQuery  = "SELECT * FROM bspay WHERE id = 1";
    $bspayResult = mysqli_query($mysqli, $bspayQuery);
    $bspayConfig = mysqli_fetch_assoc($bspayResult);
    return ['ecompag' => $bspayConfig];
}

function update_gateway_status($selectedGateway)
{
    global $mysqli;
    $mysqli->query("UPDATE bspay SET ativo = 0 WHERE id = 1");
    if ($selectedGateway === 'Ecompag') {
        $mysqli->query("UPDATE bspay SET ativo = 1 WHERE id = 1");
    }
}

function update_config($data)
{
    global $mysqli;
    // Ecompag usa a tabela bspay — apenas client_id e client_secret (URL é fixa)
    if ($data['gateway'] === 'Ecompag') {
        $qry = $mysqli->prepare("UPDATE bspay SET client_id = ?, client_secret = ? WHERE id = 1");
        $qry->bind_param("ss", $data['client_id'], $data['client_secret']);
    } else {
        return false;
    }
    return $qry->execute();
}

function toggle_gateway_status($gateway, $status)
{
    global $mysqli;
    $status = (int)$status;
    // Ecompag usa a tabela bspay
    $stmt = $mysqli->prepare("UPDATE bspay SET ativo = ? WHERE id = 1");
    if ($stmt) {
        $stmt->bind_param("i", $status);
        return $stmt->execute();
    }
    return false;
}

function get_active_gateway($mysqli)
{
    $result = $mysqli->query("SELECT ativo FROM bspay WHERE id = 1");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['ativo'] == 1) return 'Ecompag';
    }
    return 'Nenhum';
}

$toastType = null;
$toastMessage = '';

// Validação de 2FA para desbloquear credenciais

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gateway'])) {
    $data = [
        'gateway'       => $_POST['gateway'],
        'client_id'     => $_POST['client_id'],
        'client_secret' => $_POST['client_secret'],
        'url'           => $_POST['url'] ?? ''
    ];

    $update_success = update_config($data);

    if ($update_success) {
        $toastType    = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
        $toastType    = 'error';
        $toastMessage = admin_t('toast_config_error');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_gateway'])) {
    $gateway = $_POST['gateway_name'];
    $new_status = $_POST['new_status'];
    if (toggle_gateway_status($gateway, $new_status)) {
        $toastType = 'success';
        $toastMessage = 'Status do gateway ' . htmlspecialchars($gateway) . ' atualizado!';
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('gateway_toast_status_error');
    }
}

$config = get_gateways_config();
$activeGateway = get_active_gateway($mysqli);
?>

<head>
    <?php $title = admin_t('page_gateway_title');
    include 'partials/title-meta.php' ?>
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><?= admin_t('gateway_card_title') ?></h4>
                            </div>

                                <div class="card-body payment-layout">
                                <div class="payment-header">
                                    <h4><i class="ti ti-credit-card"></i><?= admin_t('gateway_card_title') ?></h4>
                                    <p class="mb-0"><?= admin_t('gateway_card_subtitle') ?></p>
                                </div>

                                <div class="active-gateway-section">
                                    <div class="active-gateway-status"><?= admin_t('status_active') ?></div>
                                    <div>
                                        <label class="form-label mb-1"><?= admin_t('gateway_active_label') ?></label>
                                        <div class="active-gateway-value">
                                            <strong><?php echo htmlspecialchars($activeGateway); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            <div class="gateways-grid-top">
                                    

                                    <div class="gateway-card">
                                        <div class="gateway-header">
                                            <div class="gateway-title">
                                                <i class="ti ti-qrcode text-success"></i>
                                                <div>
                                                    <h5 class="gateway-name">Ecompag</h5>
                                                    <p class="gateway-description">Gateway de pagamento Ecompag v2</p>
                                                </div>
                                            </div>
                                            <div class="gateway-status <?= ($activeGateway === 'Ecompag') ? 'active' : 'inactive' ?>"><?= ($activeGateway === 'Ecompag') ? admin_t('status_active') : admin_t('status_inactive') ?></div>
                                        </div>

                                        <div class="px-3 pt-3">
                                            <form method="POST" class="d-flex align-items-center">
                                                <input type="hidden" name="toggle_gateway" value="1">
                                                <input type="hidden" name="gateway_name" value="Ecompag">
                                                <input type="hidden" name="new_status" value="<?= ($config['ecompag']['ativo'] == 1) ? '0' : '1' ?>">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="switchEcompag"
                                                        <?= ($config['ecompag']['ativo'] == 1) ? 'checked' : '' ?>
                                                        onchange="this.form.submit()">
                                                    <label class="form-check-label" for="switchEcompag">
                                                        <?= ($config['ecompag']['ativo'] == 1) ? admin_t('status_active') : admin_t('status_inactive') ?>
                                                    </label>
                                                </div>
                                            </form>
                                        </div>

                                        <div class="gateway-form">
                                            <form method="POST" action="" id="formEcompag">
                                                <input type="hidden" name="gateway" value="Ecompag">
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-key"></i>Client ID</label>
                                                    <div class="input-group">
                                                        <input type="password" id="ecompag_client_id" name="client_id" class="form-control" value="<?= $config['ecompag']['client_id'] ?>" required>
                                                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('ecompag_client_id', this)"><i class="ti ti-eye"></i></span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-shield-lock"></i>Client Secret</label>
                                                    <div class="input-group">
                                                        <input type="password" id="ecompag_client_secret" name="client_secret" class="form-control" value="<?= $config['ecompag']['client_secret'] ?>" required>
                                                        <span class="input-group-text" style="cursor: pointer;" onclick="togglePassword('ecompag_client_secret', this)"><i class="ti ti-eye"></i></span>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><i class="ti ti-link"></i>Endpoint</label>
                                                    <input type="text" class="form-control" value="https://api.ecompag.com/v2/pix/qrcode.php" readonly disabled>
                                                    <small class="text-muted">Endpoint fixo da API Ecompag v2</small>
                                                </div>
                                                <button type="button" class="save-btn" onclick="document.getElementById('formEcompag').submit()"><i class="ti ti-device-floppy me-1"></i><?= admin_t('gateway_save_button_prefix') ?> Ecompag</button>
                                                <a href="https://ecompag.com" target="_blank" class="btn btn-outline-success w-100 mt-2"><i class="ti ti-external-link me-1"></i>Abra sua conta agora</a>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                </div>
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
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <h5 class="me-auto my-0"><?= admin_t('gateway_toast_title') ?></h5>
                    <small><?= admin_t('gateway_toast_now') ?></small>
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

        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');

            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('fa-eye','ti-eye');
                iconElement.classList.add('ti-eye-off');
            } else {
                input.type = "password";
                iconElement.classList.remove('fa-eye-slash','ti-eye-off');
                iconElement.classList.add('ti-eye');
            }
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>