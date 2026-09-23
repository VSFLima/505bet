<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/code-promocional.log');
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[$errno] $errstr in $errfile:$errline");
    return false;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("FATAL: [{$e['type']}] {$e['message']} in {$e['file']}:{$e['line']}");
    }
});
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "services/checa_login_adm.php";
checa_login_adm();

// ── Garante tabelas ──────────────────────────────────────────────────────────
$mysqli->query("CREATE TABLE IF NOT EXISTS cupom (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(64) NOT NULL,
    range_valor VARCHAR(32) NOT NULL DEFAULT '10',
    qtd_insert INT NOT NULL DEFAULT 1,
    qtd_original INT NOT NULL DEFAULT 1,
    status TINYINT(1) NOT NULL DEFAULT 1,
    data_expiracao DATETIME NULL,
    descricao VARCHAR(255) DEFAULT '',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
try { $mysqli->query("ALTER TABLE cupom ADD COLUMN qtd_original INT NOT NULL DEFAULT 1"); } catch(Exception $e) {}
try { $mysqli->query("ALTER TABLE cupom ADD COLUMN data_expiracao DATETIME NULL"); } catch(Exception $e) {}
try { $mysqli->query("ALTER TABLE cupom ADD COLUMN descricao VARCHAR(255) DEFAULT ''"); } catch(Exception $e) {}
$mysqli->query("CREATE TABLE IF NOT EXISTS cupom_usados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT NOT NULL,
    codigo VARCHAR(64) NOT NULL,
    valor DECIMAL(10,2) NOT NULL DEFAULT 0,
    bonus DECIMAL(10,2) NOT NULL DEFAULT 0,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$msg = null;

// ── POST handlers ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nome      = strtoupper(trim($_POST['nome'] ?? ''));
        $valor_min = (float)str_replace(',', '.', $_POST['valor_min'] ?? '0');
        $valor_max = (float)str_replace(',', '.', $_POST['valor_max'] ?? '0');
        $qtd       = max(1, (int)($_POST['qtd'] ?? 1));
        $descricao = trim($_POST['descricao'] ?? '');
        $expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;
        $range = ($valor_max > $valor_min && $valor_max > 0)
            ? number_format($valor_min,2,'.',''). ','.number_format($valor_max,2,'.','')
            : number_format($valor_min,2,'.','');
        $stmt = $mysqli->prepare("INSERT INTO cupom (nome, range_valor, qtd_insert, qtd_original, status, data_expiracao, descricao) VALUES (?,?,?,?,1,?,?)");
        $stmt->bind_param("ssiiss", $nome, $range, $qtd, $qtd, $expiracao, $descricao);
        try {
            $ok = $stmt->execute();
            $msg = $ok ? ['type'=>'success','text'=>'Código criado com sucesso!'] : ['type'=>'danger','text'=>'Erro ao criar.'];
        } catch (Exception $e) {
            $msg = ['type'=>'danger','text'=>'Erro: código já existe ou dados inválidos.'];
        }
        $stmt->close();
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id']; $val = (int)$_POST['status'];
        $mysqli->query("UPDATE cupom SET status=$val WHERE id=$id");
        header("Location: ".$_SERVER['PHP_SELF']); exit;
    }

    if ($action === 'delete') {
        $mysqli->query("DELETE FROM cupom WHERE id=".(int)$_POST['id']);
        $msg = ['type'=>'success','text'=>'Código removido.'];
    }

    if ($action === 'edit') {
        $id        = (int)$_POST['id'];
        $valor_min = (float)str_replace(',','.', $_POST['valor_min'] ?? '0');
        $valor_max = (float)str_replace(',','.', $_POST['valor_max'] ?? '0');
        $qtd       = max(0, (int)($_POST['qtd'] ?? 0));
        $descricao = trim($_POST['descricao'] ?? '');
        $expiracao = !empty($_POST['data_expiracao']) ? $_POST['data_expiracao'] : null;
        $range = ($valor_max > $valor_min && $valor_max > 0)
            ? number_format($valor_min,2,'.',''). ','.number_format($valor_max,2,'.','')
            : number_format($valor_min,2,'.','');
        $stmt = $mysqli->prepare("UPDATE cupom SET range_valor=?, qtd_insert=?, data_expiracao=?, descricao=? WHERE id=?");
        $stmt->bind_param("sissi", $range, $qtd, $expiracao, $descricao, $id);
        $stmt->execute(); $stmt->close();
        $msg = ['type'=>'success','text'=>'Código atualizado!'];
    }
}

// ── Buscar dados ──────────────────────────────────────────────────────────────
$codigos = [];
$res = $mysqli->query("SELECT cupom.*, (SELECT COUNT(*) FROM cupom_usados WHERE cupom_usados.codigo = cupom.nome) as usos FROM cupom ORDER BY cupom.id DESC");
if ($res) while ($r = $res->fetch_assoc()) $codigos[] = $r;

$historico = [];
$res2 = $mysqli->query("SELECT cu.*, u.mobile FROM cupom_usados cu INNER JOIN cupom cp ON cp.nome = cu.codigo LEFT JOIN usuarios u ON u.id = cu.id_user ORDER BY cu.id DESC LIMIT 100");
if ($res2) while ($r = $res2->fetch_assoc()) $historico[] = $r;

$total_distribuido = 0;
foreach ($historico as $h) $total_distribuido += (float)$h['bonus'];
$codigos_ativos = 0;
foreach ($codigos as $c) { if ($c['status'] == 1) $codigos_ativos++; }
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title="Códigos Promocionais"; include 'partials/title-meta.php' ?>
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
            <?= $msg['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mt-2">
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body p-3">
                <p class="stat-title mb-0">Total Códigos</p>
                <h4 class="mb-0 fw-bold"><?= count($codigos) ?></h4>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body p-3">
                <p class="stat-title mb-0">Resgates</p>
                <h4 class="mb-0 fw-bold"><?= count($historico) ?></h4>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body p-3">
                <p class="stat-title mb-0">Total Distribuído</p>
                <h4 class="mb-0 fw-bold">R$ <?= number_format($total_distribuido,2,',','.') ?></h4>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card"><div class="card-body p-3">
                <p class="stat-title mb-0">Códigos Ativos</p>
                <h4 class="mb-0 fw-bold"><?= $codigos_ativos ?></h4>
            </div></div>
        </div>
    </div>

    <!-- Códigos -->
    <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">Códigos Cadastrados</h4>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">+ Novo Código</button>
        </div>
        <div class="card-body p-0">
            <?php if (empty($codigos)): ?>
                <div class="text-center text-muted py-4">Nenhum código cadastrado ainda.</div>
            <?php else: ?>
            <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th><th>Valor (R$)</th><th>Usos Rest.</th>
                        <th>Progresso</th><th>Expiração</th><th>Descrição</th>
                        <th>Status</th><th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($codigos as $c):
                    $range = $c['range_valor'];
                    if (strpos($range, ',') !== false) {
                        $pts = explode(',', $range);
                        $label_valor = 'R$ '.number_format((float)$pts[0],2,',','.').' ~ R$ '.number_format((float)$pts[1],2,',','.');
                    } else {
                        $label_valor = 'R$ '.number_format((float)$range,2,',','.');
                    }
                    $original = max(1, (int)($c['qtd_original'] ?: ((int)$c['qtd_insert'] + (int)$c['usos'])));
                    $pct = $original > 0 ? round((int)$c['usos'] / $original * 100) : 0;
                    $exp_txt = $c['data_expiracao'] ? date('d/m/Y H:i', strtotime($c['data_expiracao'])) : '—';
                    $expirado = $c['data_expiracao'] && strtotime($c['data_expiracao']) < time();
                ?>
                <tr class="<?= $expirado ? 'text-muted' : '' ?>">
                    <td>
                        <strong style="letter-spacing:1.5px"><?= htmlspecialchars($c['nome']) ?></strong>
                        <?php if ($expirado): ?><span class="badge bg-danger ms-1">Expirado</span><?php endif; ?>
                    </td>
                    <td><?= $label_valor ?></td>
                    <td><strong><?= (int)$c['qtd_insert'] ?></strong> <span class="text-muted small">/ <?= $original ?></span></td>
                    <td style="min-width:100px">
                        <div class="progress" style="height:6px"><div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div></div>
                        <small class="text-muted"><?= (int)$c['usos'] ?> (<?= $pct ?>%)</small>
                    </td>
                    <td><?= $exp_txt ?></td>
                    <td><small><?= htmlspecialchars($c['descricao'] ?: '—') ?></small></td>
                    <td>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <input type="hidden" name="status" value="<?= $c['status'] ? 0 : 1 ?>">
                            <button type="submit" class="btn btn-xs btn-<?= $c['status'] ? 'success' : 'secondary' ?>" style="font-size:.75rem;padding:2px 8px">
                                <?= $c['status'] ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <button class="btn btn-xs btn-outline-primary me-1" style="font-size:.75rem;padding:2px 8px"
                            onclick='openEdit(<?= json_encode($c) ?>)'>Editar</button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Remover este código?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
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

    <!-- Histórico -->
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title mb-0">Histórico de Resgates <small class="text-muted fw-normal">(últimos 100)</small></h4>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Usuário</th><th>Código</th><th>Valor Creditado</th><th>Data/Hora</th></tr>
                </thead>
                <tbody>
                <?php if (empty($historico)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum resgate ainda.</td></tr>
                <?php else: foreach ($historico as $h): ?>
                    <tr>
                        <td><?= $h['id'] ?></td>
                        <td><?= htmlspecialchars($h['mobile'] ?? $h['id_user']) ?></td>
                        <td><span class="badge bg-primary" style="letter-spacing:1px"><?= htmlspecialchars($h['codigo']) ?></span></td>
                        <td class="fw-bold text-success">R$ <?= number_format((float)$h['bonus'],2,',','.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($h['data_registro'])) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
</div>
</div>
</div>

<!-- Modal Criar -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h5 class="modal-title">Criar Novo Código</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nome" placeholder="ex: PROMO2025" required maxlength="32"
                        style="letter-spacing:2px;font-weight:700;text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                    <small class="text-muted">Use letras maiúsculas e números</small>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor Mínimo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0.01" name="valor_min" class="form-control" placeholder="10.00" required>
                        </div>
                        <small class="text-muted">Fixo ou mínimo da faixa</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor Máximo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0" name="valor_max" class="form-control" placeholder="0 = fixo">
                        </div>
                        <small class="text-muted">0 = valor fixo</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Quantidade de Usos</label>
                        <input type="number" min="1" name="qtd" class="form-control" value="1" required>
                        <small class="text-muted">Quantas pessoas podem usar</small>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Expiração</label>
                        <input type="datetime-local" name="data_expiracao" class="form-control">
                        <small class="text-muted">Vazio = sem expiração</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição interna</label>
                    <input type="text" name="descricao" class="form-control" placeholder="ex: Campanha março 2025" maxlength="255">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Criar Código</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header">
                <h5 class="modal-title">Editar — <span id="edit_nome_lbl" class="text-primary fw-bold"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor Mínimo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0.01" name="valor_min" id="edit_vmin" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Valor Máximo (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" step="0.01" min="0" name="valor_max" id="edit_vmax" class="form-control">
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Usos Restantes</label>
                        <input type="number" min="0" name="qtd" id="edit_qtd" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Expiração</label>
                        <input type="datetime-local" name="data_expiracao" id="edit_exp" class="form-control">
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="descricao" id="edit_desc" class="form-control" maxlength="255">
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

<?php include 'partials/vendorjs.php' ?>
<script src="assets/js/app.js"></script>
<script>
function openEdit(c) {
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_nome_lbl').textContent = c.nome;
    var range = c.range_valor || '0';
    if (range.indexOf(',') !== -1) {
        var pts = range.split(',');
        document.getElementById('edit_vmin').value = parseFloat(pts[0]);
        document.getElementById('edit_vmax').value = parseFloat(pts[1]);
    } else {
        document.getElementById('edit_vmin').value = parseFloat(range);
        document.getElementById('edit_vmax').value = 0;
    }
    document.getElementById('edit_qtd').value = c.qtd_insert;
    document.getElementById('edit_desc').value = c.descricao || '';
    document.getElementById('edit_exp').value = c.data_expiracao ? c.data_expiracao.replace(' ', 'T').substring(0, 16) : '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
</body>
</html>