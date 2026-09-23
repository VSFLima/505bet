<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once "services/checa_login_adm.php";
checa_login_adm();

// ── Garante tabelas ──────────────────────────────────────────────────────────
function rw_ensure_tables($mysqli) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS welcome_roulette_settings (
        id INT PRIMARY KEY,
        active TINYINT(1) NOT NULL DEFAULT 1,
        min_cents INT NOT NULL DEFAULT 30,
        max_cents INT NOT NULL DEFAULT 300,
        audit_multiple DECIMAL(10,2) NOT NULL DEFAULT 1.00,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $mysqli->query("ALTER TABLE welcome_roulette_settings ADD COLUMN IF NOT EXISTS active TINYINT(1) NOT NULL DEFAULT 1");
    $mysqli->query("ALTER TABLE welcome_roulette_settings ADD COLUMN IF NOT EXISTS audit_multiple DECIMAL(10,2) NOT NULL DEFAULT 1.00");

    $res = $mysqli->query("SELECT COUNT(*) AS c FROM welcome_roulette_settings WHERE id=1");
    $row = $res ? $res->fetch_assoc() : ['c' => 0];
    if ((int)$row['c'] === 0) {
        $mysqli->query("INSERT INTO welcome_roulette_settings (id, active, min_cents, max_cents, audit_multiple) VALUES (1, 1, 30, 300, 1.00)");
    }

    $mysqli->query("CREATE TABLE IF NOT EXISTS roulette_prizes (
        id INT PRIMARY KEY AUTO_INCREMENT,
        label VARCHAR(100) NOT NULL DEFAULT '',
        type ENUM('rangeAmount','fixedAmount','bonus','nothing') NOT NULL DEFAULT 'rangeAmount',
        amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        weight INT NOT NULL DEFAULT 10,
        sort_order INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        uuid VARCHAR(64) NOT NULL DEFAULT ''
    )");
}

function rw_get($mysqli) {
    rw_ensure_tables($mysqli);
    $res = $mysqli->query("SELECT * FROM welcome_roulette_settings WHERE id=1");
    return $res ? $res->fetch_assoc() : ['active'=>1,'min_cents'=>30,'max_cents'=>300,'audit_multiple'=>1.00];
}

function rw_save($mysqli, $data) {
    $min   = (int)round((float)str_replace(',', '.', $data['min_cents']));
    $max   = (int)round((float)str_replace(',', '.', $data['max_cents']));
    $audit = (float)str_replace(',', '.', $data['audit_multiple']);
    if ($min > $max) { $t=$min; $min=$max; $max=$t; }
    $stmt = $mysqli->prepare("UPDATE welcome_roulette_settings SET min_cents=?, max_cents=?, audit_multiple=? WHERE id=1");
    $stmt->bind_param("iid", $min, $max, $audit);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

rw_ensure_tables($mysqli);
$msg = null;

// ── POST handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Toggle ativo/inativo da roleta
    if ($action === 'toggle_roulette') {
        $val = (int)$_POST['active'];
        $mysqli->query("UPDATE welcome_roulette_settings SET active=$val WHERE id=1");
        header("Location: ".$_SERVER['PHP_SELF']); exit;
    }

    // Salvar configurações gerais
    if ($action === 'save_settings') {
        $ok = rw_save($mysqli, [
            'min_cents'      => $_POST['min_cents']      ?? 30,
            'max_cents'      => $_POST['max_cents']      ?? 300,
            'audit_multiple' => $_POST['audit_multiple'] ?? 1.00,
        ]);
        $msg = $ok ? ['type'=>'success','text'=>'Configurações salvas!'] : ['type'=>'danger','text'=>'Falha ao salvar'];
    }

    // Adicionar prêmio
    if ($action === 'add_prize') {
        $stmt = $mysqli->prepare("INSERT INTO roulette_prizes (label,type,amount,weight,sort_order,active,uuid) VALUES (?,?,?,?,?,1,?)");
        $label  = trim($_POST['label'] ?? '');
        $type   = $_POST['type']   ?? 'rangeAmount';
        $amount = (float)str_replace(',', '.', $_POST['amount'] ?? '0');
        $weight = max(1, (int)($_POST['weight'] ?? 10));
        $sort   = (int)($_POST['sort_order'] ?? 0);
        $uuid   = trim($_POST['uuid'] ?? '');
        $stmt->bind_param("ssdiss", $label, $type, $amount, $weight, $sort, $uuid);
        $ok = $stmt->execute(); $stmt->close();
        $msg = $ok ? ['type'=>'success','text'=>'Prêmio adicionado!'] : ['type'=>'danger','text'=>'Erro: '.$mysqli->error];
    }

    // Editar prêmio
    if ($action === 'edit_prize') {
        $stmt = $mysqli->prepare("UPDATE roulette_prizes SET label=?,type=?,amount=?,weight=?,sort_order=?,uuid=? WHERE id=?");
        $label  = trim($_POST['label'] ?? '');
        $type   = $_POST['type']   ?? 'rangeAmount';
        $amount = (float)str_replace(',', '.', $_POST['amount'] ?? '0');
        $weight = max(1, (int)($_POST['weight'] ?? 10));
        $sort   = (int)($_POST['sort_order'] ?? 0);
        $uuid   = trim($_POST['uuid'] ?? '');
        $id     = (int)$_POST['id'];
        $stmt->bind_param("ssdissi", $label, $type, $amount, $weight, $sort, $uuid, $id);
        $ok = $stmt->execute(); $stmt->close();
        $msg = $ok ? ['type'=>'success','text'=>'Prêmio atualizado!'] : ['type'=>'danger','text'=>'Erro ao atualizar'];
    }

    // Toggle prêmio ativo
    if ($action === 'toggle_prize') {
        $id  = (int)$_POST['id'];
        $val = (int)$_POST['active'];
        $mysqli->query("UPDATE roulette_prizes SET active=$val WHERE id=$id");
        header("Location: ".$_SERVER['PHP_SELF']); exit;
    }

    // Remover prêmio
    if ($action === 'delete_prize') {
        $mysqli->query("DELETE FROM roulette_prizes WHERE id=".(int)$_POST['id']);
        $msg = ['type'=>'success','text'=>'Prêmio removido!'];
    }

    // Inserir prêmios padrão
    if ($action === 'reset_default') {
        $mysqli->query("DELETE FROM roulette_prizes");
        $defaults = [
            ['Valor Aleatório', 'rangeAmount', 0,    30, 0, ''],
            ['R$ 0,10',         'fixedAmount', 0.10, 25, 1, ''],
            ['R$ 0,20',         'fixedAmount', 0.20, 20, 2, ''],
            ['R$ 0,50',         'fixedAmount', 0.50, 12, 3, ''],
            ['Valor Aleatório', 'rangeAmount', 0,    30, 4, ''],
            ['Bônus',           'bonus',       0,     5, 5, ''],
            ['R$ 1,00',         'fixedAmount', 1.00,  3, 6, ''],
            ['Sem Prêmio',      'nothing',     0,     5, 7, ''],
        ];
        $stmt = $mysqli->prepare("INSERT INTO roulette_prizes (label,type,amount,weight,sort_order,active,uuid) VALUES (?,?,?,?,?,1,?)");
        foreach ($defaults as $d) {
            $stmt->bind_param("ssdiss", $d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
            $stmt->execute();
        }
        $stmt->close();
        $msg = ['type'=>'success','text'=>'Prêmios padrão inseridos!'];
    }
}

$cfg     = rw_get($mysqli);
$isActive = (int)($cfg['active'] ?? 1);

$prizes = [];
$res = $mysqli->query("SELECT * FROM roulette_prizes ORDER BY sort_order ASC, id ASC");
if ($res) while ($row = $res->fetch_assoc()) $prizes[] = $row;

$activeWeightSum = array_sum(array_column(array_filter($prizes, fn($x) => $x['active']), 'weight'));
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title="Roleta de Boas-Vindas"; include 'partials/title-meta.php' ?>
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

    <!-- ── CARD: Status + Config ──────────────────────────────────────────── -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Roleta de Boas-Vindas</h4>
            <span class="badge <?= $isActive ? 'bg-success' : 'bg-secondary' ?> fs-6">
                <?= $isActive ? 'ATIVA' : 'INATIVA' ?>
            </span>
        </div>
        <div class="card-body">

            <!-- Toggle Ativar/Desativar -->
            <div class="p-3 rounded border <?= $isActive ? 'border-success bg-light' : 'border-secondary bg-light' ?> mb-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-1 fw-bold">Status da Roleta</h5>
                    <p class="mb-0 text-muted small">
                        <?= $isActive ? 'Aparece no frontend para novos usuários.' : 'Oculta no frontend para todos os usuários.' ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle_roulette">
                        <input type="hidden" name="active" value="1">
                        <button class="btn btn-success" <?= $isActive ? 'disabled' : '' ?>>Ativar</button>
                    </form>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="toggle_roulette">
                        <input type="hidden" name="active" value="0">
                        <button class="btn btn-danger" <?= !$isActive ? 'disabled' : '' ?>>Desativar</button>
                    </form>
                </div>
            </div>

            <!-- Config geral -->
            <form method="post">
                <input type="hidden" name="action" value="save_settings">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Recompensa Mínima (centavos)</label>
                        <input type="number" step="1" class="form-control" name="min_cents"
                            value="<?= (int)($cfg['min_cents'] ?? 30) ?>">
                        <small class="text-muted">30 = R$ 0,30</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Recompensa Máxima (centavos)</label>
                        <input type="number" step="1" class="form-control" name="max_cents"
                            value="<?= (int)($cfg['max_cents'] ?? 300) ?>">
                        <small class="text-muted">300 = R$ 3,00</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Audit Multiple (x)</label>
                        <input type="number" step="0.01" class="form-control" name="audit_multiple"
                            value="<?= number_format((float)($cfg['audit_multiple'] ?? 1.00),2,'.','') ?>">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ── CARD: Prêmios ──────────────────────────────────────────────────── -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <div>
                <h4 class="card-title mb-0">Prêmios da Roda</h4>
                <?php if ($activeWeightSum > 0): ?>
                    <small class="text-muted">Total de chances: <strong><?= $activeWeightSum ?></strong></small>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if (empty($prizes)): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="reset_default">
                    <button class="btn btn-sm btn-outline-secondary">Inserir Padrões</button>
                </form>
                <?php endif; ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    + Adicionar Prêmio
                </button>
            </div>
        </div>

        <div class="card-body p-0">
            <?php if (empty($prizes)): ?>
                <div class="text-center text-muted py-4">
                    Nenhum prêmio cadastrado — o frontend usa os prêmios originais da plataforma.<br>
                    <small>Adicione prêmios para substituí-los ou clique em "Inserir Padrões".</small>
                </div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ordem</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Chance</th>
                        <th>Prob.</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $typeBadge = [
                    'rangeAmount' => 'bg-primary',
                    'fixedAmount' => 'bg-success',
                    'bonus'       => 'bg-warning text-dark',
                    'nothing'     => 'bg-secondary',
                ];
                foreach ($prizes as $p):
                    $prob = ($activeWeightSum > 0 && $p['active']) ? round($p['weight'] / $activeWeightSum * 100, 1) : 0;
                ?>
                <tr class="<?= !$p['active'] ? 'text-muted' : '' ?>">
                    <td><?= $p['sort_order'] ?></td>
                    <td><?= htmlspecialchars($p['label']) ?></td>
                    <td><span class="badge <?= $typeBadge[$p['type']] ?? 'bg-dark' ?>"><?= $p['type'] ?></span></td>
                    <td><?= $p['type'] === 'rangeAmount' ? '<em>aleatório</em>' : 'R$ '.number_format($p['amount'],2,',','.') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= $p['weight'] ?></span></td>
                    <td>
                        <?php if ($p['active'] && $activeWeightSum > 0): ?>
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:7px;min-width:50px">
                                    <div class="progress-bar bg-success" style="width:<?= $prob ?>%"></div>
                                </div>
                                <small><?= $prob ?>%</small>
                            </div>
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="toggle_prize">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="active" value="<?= $p['active'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-xs btn-<?= $p['active'] ? 'success' : 'secondary' ?>" style="font-size:.75rem;padding:2px 8px">
                                <?= $p['active'] ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <button class="btn btn-xs btn-outline-primary me-1" style="font-size:.75rem;padding:2px 8px"
                            onclick='openEdit(<?= json_encode($p) ?>)'>Editar</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Remover este prêmio?')">
                            <input type="hidden" name="action" value="delete_prize">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size:.75rem;padding:2px 8px">X</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info tipos -->
    <div class="card border-info mt-3">
        <div class="card-body py-2 small">
            <strong class="text-info">Tipos de prêmio:</strong>
            <span class="badge bg-primary">rangeAmount</span> valor aleatório entre mín/máx acima &nbsp;
            <span class="badge bg-success">fixedAmount</span> valor fixo &nbsp;
            <span class="badge bg-warning text-dark">bonus</span> bônus especial &nbsp;
            <span class="badge bg-secondary">nothing</span> sem prêmio &nbsp;
            | <strong>Peso:</strong> maior número maior = aparece mais vezes. Ex: peso 30 em total 100 = 30% de chance.
        </div>
    </div>

    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
</div>
</div>
</div>

<!-- ── Modal Adicionar ────────────────────────────────────────────────────── -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
            <input type="hidden" name="action" value="add_prize">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Prêmio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome do Prêmio</label>
                    <input type="text" class="form-control" name="label" placeholder="Ex: R$ 0,50">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="type">
                        <option value="rangeAmount">rangeAmount — valor aleatório (usa mín/máx acima)</option>
                        <option value="fixedAmount">fixedAmount — valor fixo</option>
                        <option value="bonus">bonus — bônus especial</option>
                        <option value="nothing">nothing — sem prêmio</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" value="0.00">
                        <small class="text-muted">0 para rangeAmount / nothing</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Chance de sair (%)</label>
                        <input type="number" step="1" min="1" class="form-control" name="weight" value="10">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Ordem na Roda</label>
                        <input type="number" step="1" class="form-control" name="sort_order" value="<?= count($prizes) ?>">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">UUID <small class="text-muted">(opcional)</small></label>
                        <input type="text" class="form-control" name="uuid" placeholder="deixe vazio">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal Editar ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
            <input type="hidden" name="action" value="edit_prize">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Editar Prêmio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome do Prêmio</label>
                    <input type="text" class="form-control" name="label" id="edit_label">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" name="type" id="edit_type">
                        <option value="rangeAmount">rangeAmount — valor aleatório</option>
                        <option value="fixedAmount">fixedAmount — valor fixo</option>
                        <option value="bonus">bonus — bônus especial</option>
                        <option value="nothing">nothing — sem prêmio</option>
                    </select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor (R$)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Chance de sair (%)</label>
                        <input type="number" step="1" min="1" class="form-control" name="weight" id="edit_weight">
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Ordem na Roda</label>
                        <input type="number" step="1" class="form-control" name="sort_order" id="edit_sort_order">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">UUID</label>
                        <input type="text" class="form-control" name="uuid" id="edit_uuid">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Atualizar</button>
            </div>
            </form>
        </div>
    </div>
</div>

<?php include 'partials/vendorjs.php' ?>
<script src="assets/js/app.js"></script>
<script>
function openEdit(p) {
    document.getElementById('edit_id').value         = p.id;
    document.getElementById('edit_label').value      = p.label;
    document.getElementById('edit_type').value       = p.type;
    document.getElementById('edit_amount').value     = p.amount;
    document.getElementById('edit_weight').value     = p.weight;
    document.getElementById('edit_sort_order').value = p.sort_order;
    document.getElementById('edit_uuid').value       = p.uuid;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
</body>
</html>