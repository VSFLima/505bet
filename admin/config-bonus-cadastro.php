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
$mysqli->query("CREATE TABLE IF NOT EXISTS cadastro_bonus_config (
    id INT PRIMARY KEY DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    valor_min INT NOT NULL DEFAULT 100,
    valor_max INT NOT NULL DEFAULT 9880,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$mysqli->query("INSERT IGNORE INTO cadastro_bonus_config (id, active, valor_min, valor_max) VALUES (1, 1, 100, 9880)");

$msg = null;

// ── POST ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
        $val = (int)$_POST['active'];
        $mysqli->query("UPDATE cadastro_bonus_config SET active=$val WHERE id=1");
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    if ($action === 'save') {
        // Os campos hidden já chegam em centavos (conversão feita pelo JS)
        $vMin = (int)($_POST['valor_min'] ?? 100);
        $vMax = (int)($_POST['valor_max'] ?? 9880);
        if ($vMin < 0) $vMin = 0;
        if ($vMax < $vMin) { $t = $vMin; $vMin = $vMax; $vMax = $t; }

        $stmt = $mysqli->prepare("UPDATE cadastro_bonus_config SET valor_min=?, valor_max=? WHERE id=1");
        $stmt->bind_param("ii", $vMin, $vMax);
        $ok = $stmt->execute();
        $stmt->close();
        $msg = $ok
            ? ['type' => 'success', 'text' => 'Configurações salvas com sucesso!']
            : ['type' => 'danger',  'text' => 'Erro ao salvar.'];
    }
}

// ── Buscar config atual ──────────────────────────────────────────────────────
$cfg      = $mysqli->query("SELECT * FROM cadastro_bonus_config WHERE id=1")->fetch_assoc();
$isActive = (int)($cfg['active'] ?? 1);
$vMinBRL  = number_format((int)($cfg['valor_min'] ?? 100)  / 100, 2, '.', '');
$vMaxBRL  = number_format((int)($cfg['valor_max'] ?? 9880) / 100, 2, '.', '');

// Preview do texto exibido no frontend (ex: +1-98.8)
$previewMin = rtrim(rtrim(number_format((int)($cfg['valor_min'] ?? 100)  / 100, 2, '.', ''), '0'), '.');
$previewMax = rtrim(rtrim(number_format((int)($cfg['valor_max'] ?? 9880) / 100, 2, '.', ''), '0'), '.');
$preview    = "+{$previewMin}-{$previewMax}";
?>

<head>
    <?php $title = "Bônus de Cadastro"; include 'partials/title-meta.php' ?>
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
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Bônus de Cadastro
            </h4>
            <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?> fs-6">
                <?= $isActive ? 'ATIVO' : 'INATIVO' ?>
            </span>
        </div>
        <div class="card-body">
            <div class="p-3 rounded border <?= $isActive ? 'border-success bg-light' : 'border-secondary bg-light' ?> d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-bold">Status do Bônus de Cadastro</h5>
                    <p class="mb-0 text-muted small">
                        <?= $isActive
                            ? 'Bônus ativo — novos usuários recebem crédito ao se cadastrar.'
                            : 'Bônus inativo — nenhum crédito é concedido no cadastro.' ?>
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
            <h4 class="card-title mb-0">Configuração dos Valores</h4>
        </div>
        <div class="card-body">
            <form method="post" id="frmBonus">
                <input type="hidden" name="action" value="save">
                <!-- Campos ocultos em centavos -->
                <input type="hidden" name="valor_min" id="hd_valor_min">
                <input type="hidden" name="valor_max" id="hd_valor_max">

                <div class="row g-4">
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Valor Mínimo do Bônus</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="ui_valor_min"
                                value="<?= $vMinBRL ?>" placeholder="ex: 1.00" oninput="updatePreview()">
                        </div>
                        <small class="text-muted">Menor valor que o novo usuário pode receber no cadastro.</small>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Valor Máximo do Bônus</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="ui_valor_max"
                                value="<?= $vMaxBRL ?>" placeholder="ex: 98.80" oninput="updatePreview()">
                        </div>
                        <small class="text-muted">Maior valor que o novo usuário pode receber no cadastro.</small>
                    </div>

                    <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                        <small class="text-muted mb-1">Preview no app</small>
                        <div id="previewBadge" style="
                            background: linear-gradient(90deg,#f59e0b,#ef4444);
                            color:#fff;
                            font-size:0.7rem;
                            font-weight:700;
                            padding:3px 8px;
                            border-radius:6px;
                            letter-spacing:.5px;
                            white-space:nowrap;
                        "><?= htmlspecialchars($preview) ?></div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary px-4">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── Info ─────────────────────────────────────────────────────────────── -->
    <div class="card border-info mt-3">
        <div class="card-body py-2 small">
            <strong class="text-info">Como funciona:</strong>
            Quando um novo usuario se cadastra na plataforma, o sistema credita automaticamente um valor aleatório entre o
            <strong>Valor Mínimo</strong> e o <strong>Valor Máximo</strong> definidos aqui.
            No frontend o banner exibe o intervalo no formato <code>+min-max</code> (ex: <code><?= htmlspecialchars($preview) ?></code>).
            Se o bônus estiver <strong>inativo</strong>, nenhum crédito é concedido e o banner some automaticamente.
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
function fmt(v) {
    if (!v || isNaN(v)) return '0';
    // Remove trailing zeros: 98.80 -> 98.8, 1.00 -> 1
    return parseFloat(parseFloat(v).toFixed(2)).toString();
}
function updatePreview() {
    var mn = document.getElementById('ui_valor_min').value;
    var mx = document.getElementById('ui_valor_max').value;
    document.getElementById('previewBadge').textContent = '+' + fmt(mn) + '-' + fmt(mx);
}
document.getElementById('frmBonus').addEventListener('submit', function() {
    var toC = function(id) { return Math.round(parseFloat(document.getElementById(id).value || 0) * 100); };
    document.getElementById('hd_valor_min').value = toC('ui_valor_min');
    document.getElementById('hd_valor_max').value = toC('ui_valor_max');
});
</script>
</body>
</html>