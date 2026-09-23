<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

include_once "services/database.php";
include_once "services/checa_login_adm.php";

checa_login_adm();

if(!isset($mysqli)){
    die("Erro: conexão com banco não encontrada");
}

function ev_ensure_table($mysqli){

    // Cria tabela se não existir (com coluna active)
    $sql = "CREATE TABLE IF NOT EXISTS red_envelope_settings (
        id INT PRIMARY KEY,
        active TINYINT(1) NOT NULL DEFAULT 1,
        app_remark TEXT,
        double_min INT NOT NULL DEFAULT 1000,
        double_max INT NOT NULL DEFAULT 1800,
        amount_min DECIMAL(10,2) NOT NULL DEFAULT 0.20,
        amount_max DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if(!$mysqli->query($sql)){
        die("Erro ao criar tabela: ".$mysqli->error);
    }

    // Adiciona coluna active se já existia a tabela sem ela
    $mysqli->query("ALTER TABLE red_envelope_settings ADD COLUMN IF NOT EXISTS active TINYINT(1) NOT NULL DEFAULT 1");

    $res = $mysqli->query("SELECT COUNT(*) AS c FROM red_envelope_settings WHERE id=1");

    if(!$res){
        die("Erro SQL: ".$mysqli->error);
    }

    $row = $res->fetch_assoc();

    if ((int)$row['c'] === 0){

        $stmt = $mysqli->prepare("INSERT INTO red_envelope_settings 
        (id, active, app_remark, double_min, double_max, amount_min, amount_max)
        VALUES (1, 1, ?, 1000, 1800, 0.20, 1.00)");

        if(!$stmt){
            die("Erro prepare: ".$mysqli->error);
        }

        $def = "Bônus liberado! Mais diversão te espera!";

        $stmt->bind_param("s", $def);
        $stmt->execute();
        $stmt->close();
    }
}

function ev_get($mysqli){

    ev_ensure_table($mysqli);

    $res = $mysqli->query("SELECT * FROM red_envelope_settings WHERE id=1");

    if(!$res){
        return null;
    }

    return $res->fetch_assoc();
}

function ev_save($mysqli, $data){

    ev_ensure_table($mysqli);

    $app    = $data['app_remark'];

    $dmin = (int)round((float)str_replace(',', '.', $data['double_min']) * 100);
    $dmax = (int)round((float)str_replace(',', '.', $data['double_max']) * 100);

    $amin = (float)$data['amount_min'];
    $amax = (float)$data['amount_max'];

    if ($dmin > $dmax){ $t=$dmin; $dmin=$dmax; $dmax=$t; }
    if ($amin > $amax){ $t=$amin; $amin=$amax; $amax=$t; }

    $stmt = $mysqli->prepare("UPDATE red_envelope_settings 
        SET app_remark=?, double_min=?, double_max=?, amount_min=?, amount_max=? 
        WHERE id=1");

    if(!$stmt){
        return false;
    }

    $stmt->bind_param("siidd", $app, $dmin, $dmax, $amin, $amax);

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

$msg = null;

// Toggle rápido via botão dedicado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_active'])){
    $newActive = (int)$_POST['toggle_active'];
    $mysqli->query("UPDATE red_envelope_settings SET active=$newActive WHERE id=1");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'){

    $ok = ev_save($mysqli, [
        'app_remark' => $_POST['app_remark'] ?? '',
        'double_min' => $_POST['double_min'] ?? 1000,
        'double_max' => $_POST['double_max'] ?? 1800,
        'amount_min' => $_POST['amount_min'] ?? 0.20,
        'amount_max' => $_POST['amount_max'] ?? 1.00,
    ]);

    $msg = $ok
        ? ['type'=>'success','text'=>'Configurações salvas com sucesso!']
        : ['type'=>'danger','text'=>'Falha ao salvar'];
}

$cfg = ev_get($mysqli);
$isActive = (int)($cfg['active'] ?? 1);
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title="Envelope Vermelho"; include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>
<body>
<?php include 'partials/topbar.php' ?>
<?php include 'partials/startbar.php' ?>
<div class="page-wrapper">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="card-title mb-0">Configurações • Envelope Vermelho</h4>
                            <!-- Badge de status -->
                            <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?> fs-6">
                                <?= $isActive ? 'ATIVO' : 'INATIVO' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php if($msg): ?>
                                <div class="alert alert-<?= $msg['type'] ?>"><?= htmlspecialchars($msg['text']) ?></div>
                            <?php endif; ?>
                            <form method="post">

                                <!-- Toggle Ativar/Desativar -->
                                <div class="mb-4">
                                    <div class="card border <?= $isActive ? 'border-success' : 'border-secondary' ?> bg-light">
                                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                                            <div>
                                                <h5 class="mb-1 fw-bold">Chuva de Envelopes</h5>
                                                <p class="mb-0 text-muted small">Status atual: <strong><?= $isActive ? 'ATIVO — aparece no frontend' : 'INATIVO — oculto no frontend' ?></strong></p>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="toggle_active" value="1">
                                                    <button type="submit" class="btn btn-success <?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'disabled' : '' ?>>
                                                        Ativar
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="toggle_active" value="0">
                                                    <button type="submit" class="btn btn-danger <?= !$isActive ? 'active' : '' ?>" <?= !$isActive ? 'disabled' : '' ?>>
                                                        Desativar
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mensagem (appRemark)</label>
                                    <textarea class="form-control" name="app_remark" rows="3"><?= htmlspecialchars($cfg['app_remark'] ?? '') ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Double Multiplier Mín.</label>
                                        <input type="number" step="1" class="form-control" name="double_min"
                                            value="<?= (int)round(((int)($cfg['double_min'] ?? 1000))/100) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Double Multiplier Máx.</label>
                                        <input type="number" step="1" class="form-control" name="double_max"
                                            value="<?= (int)round(((int)($cfg['double_max'] ?? 1800))/100) ?>">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Amount Mín. (R$)</label>
                                        <input type="number" step="0.01" class="form-control" name="amount_min"
                                            value="<?= number_format((float)($cfg['amount_min'] ?? 0.20),2,'.','') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Amount Máx. (R$)</label>
                                        <input type="number" step="0.01" class="form-control" name="amount_max"
                                            value="<?= number_format((float)($cfg['amount_max'] ?? 1.00),2,'.','') ?>">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary px-4">Salvar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>
        </div>
    </div>
</div>
<?php include 'partials/vendorjs.php' ?>
<script src="assets/js/app.js"></script>
<script>
// Atualiza label do toggle em tempo real
document.getElementById('activeToggle').addEventListener('change', function(){
    document.getElementById('toggleLabel').textContent = this.checked ? 'Ligado' : 'Desligado';
});
</script>
</body>
</html>