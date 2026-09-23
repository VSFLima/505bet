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
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();
checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// ── Garantir tabela ──────────────────────────────────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS mina_config (
    id INT PRIMARY KEY DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    daily_max_count INT NOT NULL DEFAULT 6,
    reward_min INT NOT NULL DEFAULT 10000,
    reward_max INT NOT NULL DEFAULT 20000,
    reward_recharge_min INT NOT NULL DEFAULT 0,
    reward_recharge_max INT NOT NULL DEFAULT 200,
    hours_json TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$mysqli->query("INSERT IGNORE INTO mina_config (id,active,daily_max_count,reward_min,reward_max,reward_recharge_min,reward_recharge_max,hours_json) VALUES (1,1,6,10000,20000,0,200,NULL)");

// ── Horários padrão ──────────────────────────────────────────────────────────
$defaultHours = [2,3,6,7,8,9,10,11,12,13,14,17,19,20,21,22,23,0];

$msg = null;

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $val = (int)$_POST['active'];
        $mysqli->query("UPDATE mina_config SET active=$val WHERE id=1");
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    if ($action === 'save') {
        $daily   = max(1, (int)($_POST['daily_max_count'] ?? 6));
        $rMin    = (int)round((float)str_replace(',','.',$_POST['reward_min']   ?? 10000));
        $rMax    = (int)round((float)str_replace(',','.',$_POST['reward_max']   ?? 20000));
        $rcMin   = (int)($_POST['reward_recharge_min'] ?? 0);
        $rcMax   = (int)($_POST['reward_recharge_max'] ?? 200);
        if ($rMin > $rMax) { $t=$rMin; $rMin=$rMax; $rMax=$t; }

        // Montar hours_json a partir dos checkboxes
        $selectedHours = $_POST['hours'] ?? [];
        $durationIn    = max(1, (int)($_POST['duration_in'] ?? 60));
        $uuids = [
            2=>"2617ff3ae88640e2a519a69b76ea8238", 3=>"c2cffcc3a0704a28ad8190f362e44313",
            6=>"3f435034077b428cb3ea3509850f2d45", 7=>"98df1968ea874bcdba7f041d1c087b95",
            8=>"e6a65d40cd1549478bda72a2e06f0e9b", 9=>"b069e48c87a94a05b5f477a3928ff35a",
            10=>"66790ca35d7949aa919204f43f08b78b",11=>"3e77b6768b2c4408b0904d5dab4492c7",
            12=>"320000893e1b4da8aa1505f3fe9304b8",13=>"9c8677e62bb7432e9a548ef042c27b11",
            14=>"5c88163c9bb143f8a7207b7c7f5f1edc",17=>"aa3fb50f84a449d995b499256219659e",
            19=>"8934bde757e54f058e1fe995ec81913b",20=>"82c8b00981c940f1bafe9013def81cef",
            21=>"2ae93c3b93424ce1b15ec274676d90bd",22=>"caaeb3b1b44245279069177d1764ca7b",
            23=>"80b61caf0cf74526b549e589ebe106e6", 0=>"fa6aad0db9834df7beb48ed572e92840"
        ];
        $hoursArr = [];
        foreach ($selectedHours as $h) {
            $h = (int)$h;
            $hoursArr[] = ["uuid" => $uuids[$h] ?? md5($h), "hour" => $h, "durationIn" => $durationIn];
        }
        $hoursJson = empty($hoursArr) ? null : json_encode($hoursArr);

        $stmt = $mysqli->prepare("UPDATE mina_config SET daily_max_count=?, reward_min=?, reward_max=?, reward_recharge_min=?, reward_recharge_max=?, hours_json=? WHERE id=1");
        $stmt->bind_param("iiiiss", $daily, $rMin, $rMax, $rcMin, $rcMax, $hoursJson);
        $ok = $stmt->execute();
        $stmt->close();
        $msg = $ok ? ['type'=>'success','text'=>'Configurações salvas!'] : ['type'=>'danger','text'=>'Erro ao salvar.'];
    }
}

// ── Buscar config atual ──────────────────────────────────────────────────────
$cfg = $mysqli->query("SELECT * FROM mina_config WHERE id=1")->fetch_assoc();
$isActive    = (int)($cfg['active'] ?? 1);
$activeHours = [];
if (!empty($cfg['hours_json'])) {
    $decoded = json_decode($cfg['hours_json'], true);
    if ($decoded) $activeHours = array_column($decoded, 'hour');
}
if (empty($activeHours)) $activeHours = $defaultHours;
$currentDuration = 60;
if (!empty($cfg['hours_json'])) {
    $decoded = json_decode($cfg['hours_json'], true);
    if ($decoded) $currentDuration = (int)($decoded[0]['durationIn'] ?? 60);
}
?>

<head>
    <?php $title = "Mina Misteriosa"; include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>
<body>
<?php include 'partials/topbar.php' ?>
<?php include 'partials/startbar.php' ?>

<div class="page-wrapper">
<div class="page-content">
<div class="container-xxl">

    <?php if ($msg): ?>
        <div class="alert alert-<?= $msg['type'] ?> alert-dismissible mt-3 fade show">
            <?= htmlspecialchars($msg['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Status ──────────────────────────────────────────────────────────── -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                Mina Misteriosa
            </h4>
            <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?> fs-6">
                <?= $isActive ? 'ATIVA' : 'INATIVA' ?>
            </span>
        </div>
        <div class="card-body">
            <div class="p-3 rounded border <?= $isActive ? 'border-success bg-light' : 'border-secondary bg-light' ?> d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-bold">Status da Mina Misteriosa</h5>
                    <p class="mb-0 text-muted small">
                        <?= $isActive ? 'Aparece no frontend para os usuários logados.' : 'Oculta no frontend para todos os usuários.' ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="active" value="1">
                        <button class="btn btn-success" <?= $isActive ? 'disabled' : '' ?>>Ativar</button>
                    </form>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="active" value="0">
                        <button class="btn btn-danger" <?= !$isActive ? 'disabled' : '' ?>>Desativar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Configurações ───────────────────────────────────────────────────── -->
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title mb-0">Configurações de Prêmios e Horários</h4>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="save">

                <div class="row">
                    <!-- Prêmios -->
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3">Valores dos Prêmios</h5>

                        <!-- Campos ocultos que vão pro PHP em centavos -->
                        <input type="hidden" name="reward_min"          id="hd_reward_min">
                        <input type="hidden" name="reward_max"          id="hd_reward_max">
                        <input type="hidden" name="reward_recharge_min" id="hd_rech_min">
                        <input type="hidden" name="reward_recharge_max" id="hd_rech_max">

                        <div class="mb-3">
                            <label class="form-label">Prêmio Mínimo</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="ui_reward_min"
                                    value="<?= number_format((int)($cfg['reward_min'] ?? 10000) / 100, 2, '.', '') ?>"
                                    placeholder="ex: 100.00">
                            </div>
                            <small class="text-muted">Valor mínimo que o usuário pode ganhar ao coletar a Mina</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prêmio Máximo</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="ui_reward_max"
                                    value="<?= number_format((int)($cfg['reward_max'] ?? 20000) / 100, 2, '.', '') ?>"
                                    placeholder="ex: 200.00">
                            </div>
                            <small class="text-muted">Valor máximo que o usuário pode ganhar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Depósito mínimo exigido para participar</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="ui_rech_min"
                                    value="<?= number_format((int)($cfg['reward_recharge_min'] ?? 0) / 100, 2, '.', '') ?>"
                                    placeholder="0.00">
                            </div>
                            <small class="text-muted">Deixe em R$ 0,00 para não exigir depósito mínimo</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Depósito máximo para participar</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="ui_rech_max"
                                    value="<?= number_format((int)($cfg['reward_recharge_max'] ?? 200) / 100, 2, '.', '') ?>"
                                    placeholder="ex: 2.00">
                            </div>
                            <small class="text-muted">Limite de depósito acumulado no dia para ser elegível</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Máximo de coletas por dia</label>
                            <input type="number" min="1" class="form-control" name="daily_max_count"
                                value="<?= (int)($cfg['daily_max_count'] ?? 6) ?>"
                                placeholder="ex: 6">
                            <small class="text-muted">Quantas vezes por dia cada usuário pode coletar a Mina</small>
                        </div>
                    </div>

                    <!-- Horários -->
                    <div class="col-md-6">
                        <h5 class="fw-bold mb-3">Horários de Aparição</h5>
                        <div class="alert alert-info py-2 small">
                            Selecione em quais horas do dia a Mina Misteriosa vai aparecer no frontend. Ela fica visível durante o tempo de duração configurado abaixo.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duração visível (minutos)</label>
                            <input type="number" min="1" max="60" class="form-control" name="duration_in" value="<?= $currentDuration ?>">
                            <small class="text-muted">Quantos minutos após o horário marcado a Mina fica disponível para coletar. Ex: se marcar 09:00 e duração = 60, ela aparece das 09:00 às 09:59</small>
                        </div>
                        <div class="mb-2"><label class="form-label">Horas ativas (selecione quais horas do dia a Mina aparece)</label></div>
                        <div class="row g-2">
                            <?php for ($h = 0; $h < 24; $h++): ?>
                            <div class="col-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="hours[]" value="<?= $h ?>"
                                        id="hour_<?= $h ?>" <?= in_array($h, $activeHours) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="hour_<?= $h ?>">
                                        <?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00
                                    </label>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="toggleAll(true)">Marcar Todos</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAll(false)">Desmarcar Todos</button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Info -->
    <div class="card border-info mt-3">
        <div class="card-body py-2 small">
            <strong class="text-info">Como funciona:</strong>
            A Mina Misteriosa aparece no frontend nos horários selecionados. O usuário clica para coletar um prêmio aleatório entre o <strong>Prêmio Mínimo</strong> e o <strong>Prêmio Máximo</strong>.
            O campo <strong>Duração</strong> define quantos minutos após o horário a mina fica disponível para coleta.
            As alterações têm efeito imediato — sem necessidade de reiniciar o servidor.
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
function toggleAll(state) {
    document.querySelectorAll('input[name="hours[]"]').forEach(cb => cb.checked = state);
}
// Converter R$ → centavos antes de enviar
document.querySelector('form[method="post"] button[type="submit"]').closest('form').addEventListener('submit', function() {
    const toC = id => Math.round(parseFloat(document.getElementById(id).value || 0) * 100);
    document.getElementById('hd_reward_min').value  = toC('ui_reward_min');
    document.getElementById('hd_reward_max').value  = toC('ui_reward_max');
    document.getElementById('hd_rech_min').value    = toC('ui_rech_min');
    document.getElementById('hd_rech_max').value    = toC('ui_rech_max');
});
</script>
</body>
</html>