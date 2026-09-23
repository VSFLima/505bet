<?php include 'partials/html.php' ?>
<?php
ini_set('display_errors', 1);
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

function get_checkin_config()
{
    global $mysqli;
    $qry = "SELECT * FROM checkin_config LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    // Default if not exists
    return [
        'dep_on' => 0, 'dep' => 50, 'aposta_on' => 0, 'aposta' => 0,
        'day1_prize' => 1.00, 'day2_prize' => 1.50, 'day3_prize' => 2.00,
        'day4_prize' => 3.00, 'day5_prize' => 4.00, 'day6_prize' => 5.00,
        'day7_prize' => 6.00
    ];
}

function update_checkin_config($data)
{
    global $mysqli;
    // Check if row exists
    $check = mysqli_query($mysqli, "SELECT id FROM checkin_config LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $qry = $mysqli->prepare("INSERT INTO checkin_config (dep_on, dep, aposta_on, aposta, day1_prize, day2_prize, day3_prize, day4_prize, day5_prize, day6_prize, day7_prize) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $qry->bind_param("ididddddddd", 
            $data['dep_on'], $data['dep'], $data['aposta_on'], $data['aposta'],
            $data['day1_prize'], $data['day2_prize'], $data['day3_prize'], $data['day4_prize'], $data['day5_prize'], $data['day6_prize'], $data['day7_prize']
        );
    } else {
        $qry = $mysqli->prepare("UPDATE checkin_config SET dep_on = ?, dep = ?, aposta_on = ?, aposta = ?, day1_prize = ?, day2_prize = ?, day3_prize = ?, day4_prize = ?, day5_prize = ?, day6_prize = ?, day7_prize = ?");
        $qry->bind_param("ididddddddd", 
            $data['dep_on'], $data['dep'], $data['aposta_on'], $data['aposta'],
            $data['day1_prize'], $data['day2_prize'], $data['day3_prize'], $data['day4_prize'], $data['day5_prize'], $data['day6_prize'], $data['day7_prize']
        );
    }
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'dep_on' => isset($_POST['dep_on']) ? 1 : 0,
        'dep' => floatval($_POST['dep']),
        'aposta_on' => isset($_POST['aposta_on']) ? 1 : 0,
        'aposta' => floatval($_POST['aposta']),
        'day1_prize' => floatval($_POST['day1_prize']),
        'day2_prize' => floatval($_POST['day2_prize']),
        'day3_prize' => floatval($_POST['day3_prize']),
        'day4_prize' => floatval($_POST['day4_prize']),
        'day5_prize' => floatval($_POST['day5_prize']),
        'day6_prize' => floatval($_POST['day6_prize']),
        'day7_prize' => floatval($_POST['day7_prize'])
    ];

    if (update_checkin_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Configurações de Check-in atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar. Tente novamente.';
    }
}

$config = get_checkin_config();
?>

<head>
    <?php $title = "Configurações de Check-in";
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
                        <div class="card rounded-4 shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h4 class="card-title fw-bold mb-0">Configurações de Check-in Diário</h4>
                                <p class="text-muted fs-13 mb-0">Defina as regras e valores para o recebimento do check-in diário.</p>
                            </div>

                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="row g-4 mb-4">
                                        <!-- Depósito Obrigatório -->
                                        <div class="col-md-6">
                                            <div class="card bg-light border-0">
                                                <div class="card-body">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="dep_on" name="dep_on" <?= $config['dep_on'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-bold" for="dep_on">Exigir Depósito Mínimo</label>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label">Valor do Depósito (R$)</label>
                                                        <input type="number" step="0.01" name="dep" class="form-control" value="<?= $config['dep'] ?>">
                                                        <div class="form-text text-muted fs-12">Total depositado necessário para coletar.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Aposta Obrigatória -->
                                        <div class="col-md-6">
                                            <div class="card bg-light border-0">
                                                <div class="card-body">
                                                    <div class="form-check form-switch mb-3">
                                                        <input class="form-check-input" type="checkbox" id="aposta_on" name="aposta_on" <?= $config['aposta_on'] ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-bold" for="aposta_on">Exigir Aposta Mínima</label>
                                                    </div>
                                                    <div class="mb-0">
                                                        <label class="form-label">Valor da Aposta (R$)</label>
                                                        <input type="number" step="0.01" name="aposta" class="form-control" value="<?= $config['aposta'] ?>">
                                                        <div class="form-text text-muted fs-12">Total apostado necessário para coletar.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold mb-3">Valores das Recompensas (R$)</h5>
                                    <div class="row g-3">
                                        <?php for($i=1; $i<=7; $i++): ?>
                                        <div class="col-md-3">
                                            <label class="form-label">Dia <?= $i ?></label>
                                            <input type="number" step="0.01" name="day<?= $i ?>_prize" class="form-control" 
                                                   value="<?= isset($config["day{$i}_prize"]) ? $config["day{$i}_prize"] : ($i==1?1:($i==2?1.5:($i==3?2:($i==4?3:($i==5?4:($i==6?5:6)))))) ?>">
                                        </div>
                                        <?php endfor; ?>
                                    </div>

                                    <div class="row mt-5">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary rounded-3 px-5 py-2 fw-semibold">
                                                <i class="iconoir-check me-2"></i> Salvar Configurações
                                            </button>
                                        </div>
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
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <h5 class="me-auto my-0">Notificação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);
            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();
        }
    </script>
    <?php if ($toastType && $toastMessage): ?>
        <script>showToast('<?= $toastType ?>', '<?= $toastMessage ?>');</script>
    <?php endif; ?>
</body>
</html>