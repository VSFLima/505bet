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

function get_promotion_config()
{
    global $mysqli;
    $qry = "SELECT * FROM promotion_1867_config LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    // Default values if table is empty
    return [
        'target_amount' => 100.00,
        'manipulation1' => 0.00,
        'gain1' => 0.00,
        'manipulation2' => 0.00,
        'gain2' => 0.00,
        'manipulation3' => 0.00
    ];
}

function update_promotion_config($data)
{
    global $mysqli;
    // Check if row exists
    $check = mysqli_query($mysqli, "SELECT id FROM promotion_1867_config LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        $qry = $mysqli->prepare("INSERT INTO promotion_1867_config (target_amount, manipulation1, gain1, manipulation2, gain2, manipulation3) VALUES (?, ?, ?, ?, ?, ?)");
        $qry->bind_param("dddddd", 
            $data['target_amount'], 
            $data['manipulation1'], $data['gain1'],
            $data['manipulation2'], $data['gain2'],
            $data['manipulation3']
        );
    } else {
        $qry = $mysqli->prepare("UPDATE promotion_1867_config SET target_amount = ?, manipulation1 = ?, gain1 = ?, manipulation2 = ?, gain2 = ?, manipulation3 = ?");
        $qry->bind_param("dddddd", 
            $data['target_amount'], 
            $data['manipulation1'], $data['gain1'],
            $data['manipulation2'], $data['gain2'],
            $data['manipulation3']
        );
    }
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'target_amount' => floatval($_POST['target_amount']),
        'manipulation1' => floatval($_POST['manipulation1']),
        'gain1' => floatval($_POST['gain1']),
        'manipulation2' => floatval($_POST['manipulation2']),
        'gain2' => floatval($_POST['gain2']),
        'manipulation3' => floatval($_POST['manipulation3'])
    ];

    if (update_promotion_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Configurações da Promoção 1867 atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar. Tente novamente.';
    }
}

$config = get_promotion_config();
?>

<head>
    <?php $title = "Configuração Promoção 1867";
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
                                <h4 class="card-title fw-bold mb-0">Configuração Promoção 1867</h4>
                                <p class="text-muted fs-13 mb-0">Configure os valores e manipulações da promoção.</p>
                            </div>

                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="row g-4">
                                        
                                        <!-- Target Amount -->
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Target Amount</label>
                                                <input type="text" name="target_amount" class="form-control"
                                                    value="<?= $config['target_amount'] ?>" required>
                                            </div>
                                        </div>

                                        <!-- Manipulation 1 -->
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Manipulação 1 (Valor Gatilho)</label>
                                                <input type="text" name="manipulation1" class="form-control"
                                                    value="<?= $config['manipulation1'] ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Ganho 1 (Valor Fixo)</label>
                                                <input type="text" name="gain1" class="form-control"
                                                    value="<?= $config['gain1'] ?>">
                                            </div>
                                        </div>

                                        <!-- Manipulation 2 -->
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Manipulação 2 (Valor Gatilho)</label>
                                                <input type="text" name="manipulation2" class="form-control"
                                                    value="<?= $config['manipulation2'] ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold">Ganho 2 (Valor Fixo)</label>
                                                <input type="text" name="gain2" class="form-control"
                                                    value="<?= $config['gain2'] ?>">
                                            </div>
                                        </div>

                                        <!-- Manipulation 3 -->
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold text-danger">Manipulação 3 (Zerar Prêmio)</label>
                                                <input type="text" name="manipulation3" class="form-control"
                                                    value="<?= $config['manipulation3'] ?>">
                                                <div class="form-text text-muted fs-12">Se o valor acumulado for maior ou igual a este, o prêmio será 0.</div>
                                            </div>
                                        </div>

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
        function showToast(type, message){window.showToast(type,message);}
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
