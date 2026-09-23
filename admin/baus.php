<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
#======================================#
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

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

function get_afiliados_config()
{
    global $mysqli;
    $qry = "SELECT * FROM afiliados_config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function get_config()
{
    global $mysqli;
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

# Função para buscar configurações de manipulação de indicações
function get_manipulacao_indicacoes()
{
    global $mysqli;
    $qry = "SELECT * FROM manipulacao_indicacoes WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    $config = mysqli_fetch_assoc($result);
    
    // Se não existir, criar com valores padrão
    if (!$config) {
        $create_qry = "INSERT INTO manipulacao_indicacoes (id, dar_indicacoes, roubar_indicacoes, ativo) VALUES (1, 3, 1, 0)";
        mysqli_query($mysqli, $create_qry);
        $result = mysqli_query($mysqli, $qry);
        $config = mysqli_fetch_assoc($result);
    }
    
    return $config;
}

# Função para atualizar os dados da tabela afiliados_config
function update_afiliados_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE afiliados_config SET 
        cpaLvl1 = ?, 
        cpaLvl2 = ?, 
        cpaLvl3 = ?,
        chanceCpa = ?,
        minDepForCpa = ?,
        minResgate = ?,
        pagar_baus = ?,
        dep_on = ?,
        bet_on = ?
        WHERE id = 1");

    $qry->bind_param(
        "ddddddiii",
        $data['cpaLvl1'],
        $data['cpaLvl2'],
        $data['cpaLvl3'],
        $data['chanceCpa'],
        $data['minDepForCpa'],
        $data['minResgate'],
        $data['pagar_baus'],
        $data['dep_on'],
        $data['bet_on']
    );
    return $qry->execute();
}

# Função para atualizar os dados da tabela config (baús)
function update_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE config SET 
        qntsbaus = ?, 
        niveisbau = ?, 
        pessoasbau = ?
        WHERE id = 1");

    $qry->bind_param(
        "ssd",
        $data['qntsbaus'],
        $data['niveisbau'],
        $data['pessoasbau']
    );
    return $qry->execute();
}

# Função para atualizar configurações de manipulação
function update_manipulacao_indicacoes($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE manipulacao_indicacoes SET 
        dar_indicacoes = ?, 
        roubar_indicacoes = ?,
        ativo = ?
        WHERE id = 1");

    $qry->bind_param(
        "iii",
        $data['dar_indicacoes'],
        $data['roubar_indicacoes'],
        $data['ativo']
    );
    return $qry->execute();
}

# Se o formulário for enviado, atualizar os dados
$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dataAfiliados = [
        'cpaLvl1' => floatval($_POST['cpaLvl1']),
        'cpaLvl2' => floatval($_POST['cpaLvl2']),
        'cpaLvl3' => floatval($_POST['cpaLvl3']),
        'chanceCpa' => floatval($_POST['chanceCpa']),
        'minDepForCpa' => floatval($_POST['minDepForCpa']),
        'minResgate' => floatval($_POST['minResgate']),
        'pagar_baus' => isset($_POST['pagar_baus']) ? 1 : 0,
        'dep_on' => isset($_POST['dep_on']) ? 1 : 0,
        'bet_on' => isset($_POST['bet_on']) ? 1 : 0,
    ];

    $dataConfig = [
        'qntsbaus' => floatval($_POST['qntsbaus']),
        'niveisbau' => $_POST['niveisbau'],
        'pessoasbau' => floatval($_POST['pessoasbau']),
    ];

    $dataManipulacao = [
        'dar_indicacoes' => intval($_POST['dar_indicacoes']),
        'roubar_indicacoes' => intval($_POST['roubar_indicacoes']),
        'ativo' => isset($_POST['manipulacao_ativa']) ? 1 : 0,
    ];

    $sucessoAfiliados = update_afiliados_config($dataAfiliados);
    $sucessoConfig = update_config($dataConfig);
    $sucessoManipulacao = update_manipulacao_indicacoes($dataManipulacao);

    if ($sucessoAfiliados && $sucessoConfig && $sucessoManipulacao) {
        $toastType = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('toast_config_error');
    }
}

# Buscar os dados atuais
$afiliadosConfig = get_afiliados_config();
$config = get_config();
$manipulacaoConfig = get_manipulacao_indicacoes();
?>

<head>
    <?php $title = admin_t('page_chests_title');
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
                                <h4 class="card-title"><?= admin_t('chests_card_title') ?></h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    
                                    <h5 class="mb-3 text-primary"><i class="iconoir-percentage"></i> <?= admin_t('chests_section_affiliates_title') ?></h5>
                                    
                                    

                                    <div class="row">
                                        <!-- CPA Level 1 -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-medal"></i> <?= admin_t('chests_cpa_level1_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_cpa_level1_subtitle') ?>
                                                    </p>
                                                    <div class="input-group">
                                                        <span class="input-group-text">%</span>
                                                        <input type="number" step="0.01" name="cpaLvl1" class="form-control"
                                                            value="<?= $afiliadosConfig['cpaLvl1'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CPA Level 2 -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-medal"></i> <?= admin_t('chests_cpa_level2_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_cpa_level2_subtitle') ?>
                                                    </p>
                                                    <div class="input-group">
                                                        <span class="input-group-text">%</span>
                                                        <input type="number" step="0.01" name="cpaLvl2" class="form-control"
                                                            value="<?= $afiliadosConfig['cpaLvl2'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- CPA Level 3 -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-medal"></i> <?= admin_t('chests_cpa_level3_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_cpa_level3_subtitle') ?>
                                                    </p>
                                                    <div class="input-group">
                                                        <span class="input-group-text">%</span>
                                                        <input type="number" step="0.01" name="cpaLvl3" class="form-control"
                                                            value="<?= $afiliadosConfig['cpaLvl3'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Chance CPA -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-dice"></i> <?= admin_t('chests_cpa_chance_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_cpa_chance_subtitle') ?>
                                                    </p>
                                                    <div class="input-group">
                                                        <input type="number" step="0.01" name="chanceCpa" class="form-control"
                                                            value="<?= $afiliadosConfig['chanceCpa'] ?>" required>
                                                        <span class="input-group-text">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Depósito Mínimo para CPA -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-wallet"></i> <?= admin_t('chests_min_deposit_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_min_deposit_subtitle') ?>
                                                    </p>
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" id="dep_on" name="dep_on" <?= isset($afiliadosConfig['dep_on']) && $afiliadosConfig['dep_on'] == 1 ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="dep_on"><?= admin_t('chests_min_deposit_toggle') ?></label>
                                                    </div>
                                                    <div class="input-group">
                                                        <span class="input-group-text">R$</span>
                                                        <input type="number" step="0.01" name="minDepForCpa" class="form-control"
                                                            value="<?= $afiliadosConfig['minDepForCpa'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Valor Mínimo Apostado -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-credit-card"></i> <?= admin_t('chests_min_bet_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_min_bet_subtitle') ?>
                                                    </p>
                                                    <div class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" id="bet_on" name="bet_on" <?= isset($afiliadosConfig['bet_on']) && $afiliadosConfig['bet_on'] == 1 ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="bet_on"><?= admin_t('chests_min_bet_toggle') ?></label>
                                                    </div>
                                                    <div class="input-group">
                                                        <span class="input-group-text">R$</span>
                                                        <input type="number" step="0.01" name="minResgate" class="form-control"
                                                            value="<?= $afiliadosConfig['minResgate'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">

                                    <h5 class="mb-3 text-warning"><i class="iconoir-shuffle"></i> <?= admin_t('chests_manip_section_title') ?></h5>
                                    <div class="alert alert-warning" role="alert">
                                        <i class="iconoir-warning-triangle me-2"></i>
                                        <strong><?= admin_t('alert_attention') ?></strong> <?= admin_t('chests_manip_section_text') ?>
                                    </div>

                                    <div class="row">
                                        <!-- Ativar/Desativar Manipulação -->
                                        <div class="col-md-12 mb-3">
                                            <div class="card border-warning">
                                                <div class="card-body">
                                                    <div class="form-check form-switch form-switch-lg">
                                                        <input class="form-check-input" type="checkbox" id="manipulacao_ativa" 
                                                               name="manipulacao_ativa" <?= $manipulacaoConfig['ativo'] == 1 ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="manipulacao_ativa">
                                                            <h5 class="mb-0">
                                                                <?= admin_t('chests_manip_toggle_title') ?>
                                                            </h5>
                                                            <small class="text-muted"><?= admin_t('chests_manip_toggle_helper') ?></small>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Dar Indicações -->
                                        <div class="col-md-6">
                                            <div class="card mb-4 border-success">
                                                <div class="card-body">
                                                    <h5 class="card-title text-success">
                                                        <i class="iconoir-plus-circle"></i> <?= admin_t('chests_manip_give_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-3">
                                                        <?= admin_t('chests_manip_give_subtitle') ?>
                                                    </p>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-success text-white">
                                                            <i class="iconoir-user-plus"></i>
                                                        </span>
                                                        <input type="number" min="1" max="100" name="dar_indicacoes" 
                                                               class="form-control form-control-lg" 
                                                               value="<?= $manipulacaoConfig['dar_indicacoes'] ?>" required>
                                                        <span class="input-group-text"><?= admin_t('chests_manip_indicacoes_suffix') ?></span>
                                                    </div>
                                                    <small class="text-muted mt-2 d-block">
                                                        <i class="iconoir-info-circle"></i> <?= admin_t('chests_manip_give_helper') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Roubar Indicações -->
                                        <div class="col-md-6">
                                            <div class="card mb-4 border-danger">
                                                <div class="card-body">
                                                    <h5 class="card-title text-danger">
                                                        <i class="iconoir-minus-circle"></i> <?= admin_t('chests_manip_steal_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-3">
                                                        <?= admin_t('chests_manip_steal_subtitle') ?>
                                                    </p>
                                                    <div class="input-group input-group-lg">
                                                        <span class="input-group-text bg-danger text-white">
                                                            <i class="iconoir-user-minus"></i>
                                                        </span>
                                                        <input type="number" min="1" max="100" name="roubar_indicacoes" 
                                                               class="form-control form-control-lg" 
                                                               value="<?= $manipulacaoConfig['roubar_indicacoes'] ?>" required>
                                                        <span class="input-group-text"><?= admin_t('chests_manip_indicacoes_suffix') ?></span>
                                                    </div>
                                                    <small class="text-muted mt-2 d-block">
                                                        <i class="iconoir-info-circle"></i> <?= admin_t('chests_manip_steal_helper') ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                    </div>

                                    <hr class="my-4">

                                    <h5 class="mb-3 text-primary"><i class="iconoir-box"></i> <?= admin_t('chests_section_chests_title') ?></h5>
                                    
                                    <div class="row">
                                        <!-- Quantidade de Baús -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-box"></i> <?= admin_t('chests_qty_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('chests_qty_subtitle') ?>
                                                    </p>
                                                    <input type="number" name="qntsbaus" class="form-control"
                                                        value="<?= $config['qntsbaus'] ?>" required>
                                                    <small class="text-muted">Define quantos baús serão exibidos.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Valores dos Baús -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-group"></i> <?= admin_t('chests_values_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Separado por vírgula (ex: 10,20,50)
                                                    </p>
                                                    <input type="text" name="niveisbau" class="form-control"
                                                        value="<?= $config['niveisbau'] ?>" required placeholder="Ex: 10,20,50,100">
                                                    <small class="text-muted">Valor em R$ que o usuário ganha.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Quantidade de Pessoas -->
                                        <div class="col-md-4">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-community"></i> <?= admin_t('chests_people_qty_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Separado por vírgula (ex: 5,10,20)
                                                    </p>
                                                    <input type="text" name="pessoasbau" class="form-control"
                                                        value="<?= $config['pessoasbau'] ?>" required placeholder="Ex: 5,10,20,50">
                                                    <small class="text-muted">Indicações necessárias para abrir.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                        // Processar os dados para exibição dinâmica
                                        $niveis = explode(',', $config['niveisbau']);
                                        $pessoas = explode(',', $config['pessoasbau']);
                                        $qtd_baus = (int)$config['qntsbaus'];
                                        
                                        // Pegar o valor base de pessoas (o primeiro valor) para incremento
                                        $base_pessoas = isset($pessoas[0]) ? (int)$pessoas[0] : 1;
                                    ?>

                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h5 class="card-title mb-4">Visualização dos Baús (Preview)</h5>
                                                    <div class="d-flex flex-wrap justify-content-center gap-4">
                                                        <?php for($i = 0; $i < $qtd_baus; $i++): 
                                                            $nivel_index = $i; // Use direct index if available, or last one
                                                            if ($nivel_index >= count($niveis)) {
                                                                $nivel_index = count($niveis) - 1;
                                                            }
                                                            $valor = isset($niveis[$nivel_index]) ? $niveis[$nivel_index] : end($niveis);
                                                            
                                                            // Calculate cumulative people count: (i + 1) * base_pessoas
                                                            // This matches API logic: "userCount" => $i * $pessoas (where $i starts at 1)
                                                            $qtd_pessoas_acumulado = ($i + 1) * $base_pessoas;
                                                        ?>
                                                        <div class="text-center p-3 border rounded bg-white shadow-sm" style="min-width: 150px;">
                                                            <div class="position-relative mb-2" style="height: 80px;">
                                                                <img src="/images/activity/treasureBoxClose.png" alt="Baú" style="height: 100%; object-fit: contain;">
                                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                                                    #<?= $i + 1 ?>
                                                                </span>
                                                            </div>
                                                            <h4 class="text-success mb-1">R$ <?= number_format((float)$valor, 2, ',', '.') ?></h4>
                                                            <small class="text-muted d-block">
                                                                <i class="iconoir-user-plus"></i> <?= $qtd_pessoas_acumulado ?> indicações
                                                            </small>
                                                        </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="iconoir-check-circle"></i> <?= admin_t('button_save_all_settings') ?>
                                        </button>
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
                    <img src="/uploads/logo.png.webp" alt="" height="20" class="me-1">
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
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
