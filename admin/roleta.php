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
    if (isset($_POST['action']) && $_POST['action'] == 'limpar_dados') {
        $mysqli->query("TRUNCATE TABLE promotion_1867_data");
        $mysqli->query("TRUNCATE TABLE promotion_1867_logs");
        $toastType = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
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
            $toastMessage = admin_t('toast_config_updated');
        } else {
            $toastType = 'error';
            $toastMessage = admin_t('toast_config_error');
        }
    }
}

$config = get_promotion_config();
?>

<head>
    <?php $title = admin_t('page_config_title');
    include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-fluid">
                <?php include 'partials/page-title.php' ?>

                <?php if ($toastType): ?>
                    <div class="alert alert-<?= $toastType == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                        <?= $toastMessage ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card bg-info-subtle border-info">
                            <div class="card-body">
                                <h5 class="card-title text-info"><i class="fas fa-info-circle me-2"></i>Como funciona a Configuração da Roleta</h5>
                                <p class="card-text text-info-emphasis">
                                    Nesta página você pode ajustar os parâmetros de manipulação e resetar o progresso dos usuários.
                                </p>
                                <ul class="text-info-emphasis">
                                    <li><strong>Valor Alvo (Target Amount):</strong> O valor total que o usuário precisa acumular para completar a missão e sacar.</li>
                                    <li><strong>Níveis de Manipulação:</strong> Define faixas de valores acumulados onde o ganho por giro muda.
                                        <ul>
                                            <li><em>Limite:</em> Até qual valor acumulado a regra se aplica.</li>
                                            <li><em>Ganho:</em> Quanto o usuário ganha por giro enquanto estiver nessa faixa (ex: 0.02 = 2 centavos).</li>
                                        </ul>
                                    </li>
                                    <li><strong>Zona de Perigo:</strong> O botão "Limpar Dados da Roleta" apaga TODO o histórico de todos os usuários (valores acumulados e logs de giros), fazendo com que todos comecem do zero. Use com cautela!</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= admin_t('page_config_title') ?></h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="target_amount" class="form-label">Valor Alvo (Target Amount)</label>
                                            <input type="number" step="0.01" class="form-control" id="target_amount" name="target_amount" value="<?= $config['target_amount'] ?>" required>
                                        </div>
                                    </div>
                                    
                                    <h5 class="mt-4">Níveis de Manipulação</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="manipulation1" class="form-label">Limite 1</label>
                                            <input type="number" step="0.01" class="form-control" id="manipulation1" name="manipulation1" value="<?= $config['manipulation1'] ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="gain1" class="form-label">Ganho 1</label>
                                            <input type="number" step="0.0001" class="form-control" id="gain1" name="gain1" value="<?= $config['gain1'] ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="manipulation2" class="form-label">Limite 2</label>
                                            <input type="number" step="0.01" class="form-control" id="manipulation2" name="manipulation2" value="<?= $config['manipulation2'] ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="gain2" class="form-label">Ganho 2</label>
                                            <input type="number" step="0.0001" class="form-control" id="gain2" name="gain2" value="<?= $config['gain2'] ?>">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="manipulation3" class="form-label">Limite 3</label>
                                            <input type="number" step="0.01" class="form-control" id="manipulation3" name="manipulation3" value="<?= $config['manipulation3'] ?>">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-danger">
                            <div class="card-header bg-danger text-white">
                                <h4 class="card-title text-white">Zona de Perigo</h4>
                            </div>
                            <div class="card-body">
                                <p>Esta ação irá apagar TODOS os dados e logs da roleta. Isso resetará o progresso de todos os usuários.</p>
                                <form method="POST" action="" onsubmit="return confirm('Tem certeza que deseja limpar TODOS os dados e logs da roleta? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="action" value="limpar_dados">
                                    <button type="submit" class="btn btn-danger">Limpar Dados da Roleta</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include 'partials/footer.php' ?>
    </div>
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
</body>
</html>
