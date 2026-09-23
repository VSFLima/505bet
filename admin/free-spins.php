<?php
// Handler AJAX — antes de qualquer HTML
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

// ============================================================
// LOG
// ============================================================
function fsLog($msg) {
    $f    = __DIR__ . '/free-spins.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($f, "[$date] $msg" . PHP_EOL, FILE_APPEND);
}

// ============================================================
// AJAX: ENVIAR FREE SPINS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_freespins') {
    header('Content-Type: application/json');

    $user_ids  = json_decode($_POST['user_ids']  ?? '[]', true);
    $game_code = trim($_POST['game_code']  ?? '');
    $rounds    = intval($_POST['rounds']   ?? 0);

    if (empty($user_ids) || !$game_code || $rounds < 1 || $rounds > 23) {
        echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos. Rounds: 1-23.']);
        exit;
    }

    // Buscar credenciais PlayFiver
    $res  = $mysqli->query("SELECT agent_token, agent_secret FROM playfiver WHERE id = 1 LIMIT 1");
    $cred = $res ? $res->fetch_assoc() : null;
    if (!$cred || empty($cred['agent_token'])) {
        echo json_encode(['success' => false, 'message' => 'Credenciais PlayFiver não configuradas.']);
        exit;
    }

    $sent   = 0;
    $failed = 0;
    $errors = [];

    foreach ($user_ids as $uid) {
        $uid = intval($uid);
        $stmt = $mysqli->prepare("SELECT mobile FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            fsLog("ERRO: Usuário ID=$uid não encontrado");
            $failed++;
            continue;
        }

        $user_code = $user['mobile'];

        $payload = [
            'agent_token' => $cred['agent_token'],
            'secret_key'  => $cred['agent_secret'],
            'user_code'   => $user_code,
            'game_code'   => $game_code,
            'rounds'      => $rounds,
        ];

        fsLog("Enviando free spins: user=$user_code | game=$game_code | rounds=$rounds");

        $ch = curl_init('https://api.playfivers.com/api/v2/free_bonus');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        fsLog("HTTP $httpCode | Response: $response");

        if ($curlError) {
            fsLog("ERRO cURL: $curlError");
            $failed++;
            $errors[] = "User $user_code: Erro de conexão";
            continue;
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && !empty($data['status'])) {
            fsLog("SUCESSO: $user_code");
            $sent++;
        } else {
            $msg = $data['msg'] ?? "HTTP $httpCode";
            fsLog("FALHOU: $user_code | $msg");
            $failed++;
            $errors[] = "User $user_code: $msg";
        }
    }

    $message = "$sent enviado(s) com sucesso" . ($failed ? ", $failed falhou(ram)" : "") . ".";
    if (!empty($errors)) $message .= " Erros: " . implode('; ', array_slice($errors, 0, 3));

    echo json_encode([
        'success' => $sent > 0,
        'message' => $message,
        'sent'    => $sent,
        'failed'  => $failed,
    ]);
    exit;
}

// ============================================================
// FILTROS E PAGINAÇÃO
// ============================================================
$search      = trim($_GET['search']      ?? '');
$show_all    = isset($_GET['show_all']) && $_GET['show_all'] == '1';
$limit       = $show_all ? 99999 : 20;
$page        = max(1, intval($_GET['page'] ?? 1));
$offset      = $show_all ? 0 : ($page - 1) * $limit;

$where = ["1=1"];
if ($search !== '') {
    $s = $mysqli->real_escape_string($search);
    if (is_numeric($search)) {
        $where[] = "(id = $s OR mobile LIKE '%$s%' OR celular LIKE '%$s%')";
    } else {
        $where[] = "(mobile LIKE '%$s%' OR celular LIKE '%$s%')";
    }
}
$whereStr = implode(" AND ", $where);

$total_users = $mysqli->query("SELECT COUNT(*) as t FROM usuarios WHERE $whereStr")->fetch_assoc()['t'];
$total_pages = $show_all ? 1 : max(1, ceil($total_users / $limit));
$res         = $mysqli->query("SELECT id, mobile, celular, saldo, data_registro FROM usuarios WHERE $whereStr ORDER BY id DESC LIMIT $limit OFFSET $offset");
$users       = [];
while ($row = $res->fetch_assoc()) $users[] = $row;

// Provedores com jogos PlayFiver ativos
$provs_res = $mysqli->query("SELECT DISTINCT provider FROM games WHERE api = 'playfiver' AND status = 1 ORDER BY provider ASC");
$game_providers = [];
while ($row = $provs_res->fetch_assoc()) $game_providers[] = $row['provider'];

// Jogos da PlayFiver com filtro de provedor
$filter_provider = trim($_GET['filter_provider'] ?? '');
$games_sql = "SELECT id, game_code, game_name, provider, banner FROM games WHERE api = 'playfiver' AND status = 1";
if ($filter_provider) {
    $fp = $mysqli->real_escape_string($filter_provider);
    $games_sql .= " AND provider = '$fp'";
}
$games_sql .= " ORDER BY game_name ASC";
$games_res = $mysqli->query($games_sql);
$games     = [];
while ($row = $games_res->fetch_assoc()) $games[] = $row;
?>
<?php include 'partials/html.php' ?>
<?php ini_set('display_errors', 0); ?>

<head>
    <?php $title = "Free Spins - PlayFiver"; include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; background: #f0f0f0; }
        .game-thumb  { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
        .saldo-badge { font-size: .75rem; }
    </style>
</head>
<body>

<?php include 'partials/topbar.php' ?>
<?php include 'partials/startbar.php' ?>

<div class="page-wrapper">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row g-4">

                <!-- COLUNA ESQUERDA: USUÁRIOS -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h4 class="card-title mb-0">
                                <i class="ti ti-users me-2"></i>Usuários
                                <span class="badge bg-secondary ms-2"><?= $total_users ?></span>
                            </h4>
                            <div class="d-flex gap-2">
                                <button id="btnSelectAll" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-checkbox me-1"></i>Todos desta página
                                </button>
                                <a href="?<?= http_build_query(array_merge($_GET, ['show_all' => $show_all ? '0' : '1', 'page' => 1])) ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-<?= $show_all ? 'list' : 'stack' ?> me-1"></i><?= $show_all ? 'Paginado' : 'Ver todos' ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Busca -->
                            <form method="GET" class="mb-3">
                                <input type="hidden" name="show_all" value="<?= $show_all ? '1' : '0' ?>">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Celular, ID ou número do usuário..." value="<?= htmlspecialchars($search) ?>">
                                    <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i></button>
                                    <?php if ($search): ?>
                                        <a href="free-spins.php<?= $filter_provider ? '?filter_provider='.urlencode($filter_provider) : '' ?>" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <!-- Tabela de usuários -->
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="checkAllUsers"></th>
                                            <th>Celular</th>
                                            <th>Saldo</th>
                                            <th>Cadastro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhum usuário encontrado</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td><input type="checkbox" class="chk-user" value="<?= $u['id'] ?>" data-mobile="<?= htmlspecialchars($u['mobile']) ?>"></td>
                                                <td>
                                                    <span class="fw-semibold"><?= htmlspecialchars($u['mobile']) ?></span>
                                                    <br><small class="text-muted">ID: <?= $u['id'] ?></small>
                                                </td>
                                                <td><span class="badge bg-success-subtle text-success saldo-badge">R$ <?= number_format($u['saldo'], 2, ',', '.') ?></span></td>
                                                <td><small class="text-muted"><?= date('d/m/Y', strtotime($u['data_registro'])) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Paginação -->
                            <?php if ($total_pages > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination justify-content-center flex-wrap">
                                    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page-1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>">&laquo;</a>
                                    </li>
                                    <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
                                        <li class="page-item <?= $i==$page?'active':'' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $page==$total_pages?'disabled':'' ?>">
                                        <a class="page-link" href="?page=<?= $page+1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>''])) ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: CONFIGURAR FREE SPINS -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title mb-0"><i class="ti ti-gift me-2 text-success"></i>Enviar Free Spins</h4>
                        </div>
                        <div class="card-body">

                            <!-- Selecionados -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Usuários selecionados</label>
                                <div id="selectedUsers" class="border rounded p-2 min-vh" style="min-height:48px;max-height:120px;overflow-y:auto;background:#f8f9fa;">
                                    <small class="text-muted" id="noSelMsg">Nenhum usuário selecionado</small>
                                </div>
                                <small class="text-muted"><span id="selectedCount">0</span> usuário(s) selecionado(s)</small>
                            </div>

                            <!-- Jogo -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-dark me-1">1</span> Jogo
                                </label>
                                <form method="GET" class="mb-2 d-flex gap-2">
                                    <?php foreach (['search','show_all','page'] as $k) if (isset($_GET[$k])) echo '<input type="hidden" name="'.$k.'" value="'.htmlspecialchars($_GET[$k]).'">'; ?>
                                    <select name="filter_provider" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="">Todos os provedores</option>
                                        <?php foreach ($game_providers as $gp): ?>
                                            <option value="<?= htmlspecialchars($gp) ?>" <?= $filter_provider === $gp ? 'selected' : '' ?>><?= htmlspecialchars($gp) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($filter_provider): ?>
                                        <a href="?<?= http_build_query(array_diff_key($_GET, ['filter_provider'=>''])) ?>" class="btn btn-outline-secondary btn-sm"><i class="ti ti-x"></i></a>
                                    <?php endif; ?>
                                </form>
                                <select id="gameSelect" class="form-select">
                                    <option value="">-- Selecione um jogo (<?= count($games) ?>) --</option>
                                    <?php foreach ($games as $g): ?>
                                        <option value="<?= htmlspecialchars($g['game_code']) ?>"
                                                data-banner="<?= htmlspecialchars($g['banner']) ?>"
                                                data-provider="<?= htmlspecialchars($g['provider']) ?>">
                                            <?= htmlspecialchars($g['game_name']) ?> (<?= htmlspecialchars($g['game_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <!-- Preview jogo -->
                                <div id="gamePreview" class="mt-2 d-none d-flex align-items-center gap-2">
                                    <img id="gameThumb" src="" class="game-thumb" onerror="this.style.opacity='.3'">
                                    <div>
                                        <div id="gameName" class="fw-semibold small"></div>
                                        <div id="gameProvider" class="text-muted" style="font-size:.75rem"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rounds -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <span class="badge bg-dark me-1">2</span> Rounds (1-23)
                                </label>
                                <div class="d-flex align-items-center gap-3">
                                    <input type="range" id="roundsRange" min="1" max="23" value="10" class="form-range flex-grow-1">
                                    <input type="number" id="roundsInput" min="1" max="23" value="10" class="form-control" style="width:70px">
                                </div>
                                <div class="d-flex gap-1 mt-2 flex-wrap">
                                    <?php foreach ([1,5,10,15,20,23] as $r): ?>
                                        <button class="btn btn-outline-secondary btn-sm rounds-preset" data-val="<?= $r ?>"><?= $r ?>x</button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Botão enviar -->
                            <button id="btnSend" class="btn btn-success w-100 py-3" disabled>
                                <i class="ti ti-send me-2"></i>Enviar Free Spins
                            </button>

                            <!-- Resultado -->
                            <div id="sendResult" class="mt-3" style="display:none"></div>

                            <hr>
                            <small class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                Máximo 23 rounds por envio. Log em <code>free-spins.log</code>.
                                Apenas jogos com suporte a free spins funcionarão.
                            </small>
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
    var c = document.getElementById('toastPlacement');
    var t = document.createElement('div');
    t.className = 'toast align-items-center bg-light border-0 fade show';
    t.setAttribute('role', 'alert');
    t.innerHTML = '<div class="toast-header"><h5 class="me-auto my-0">Free Spins</h5><small>Agora</small><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body">' + message + '</div>';
    c.appendChild(t);
    new bootstrap.Toast(t).show();
    setTimeout(function() { t.remove(); }, 4000);
}

// ── Seleção de usuários ──────────────────────────────────────
var selectedIds = new Set();

document.getElementById('checkAllUsers').addEventListener('change', function() {
    document.querySelectorAll('.chk-user').forEach(c => {
        c.checked = this.checked;
        if (this.checked) selectedIds.add(c.value);
        else selectedIds.delete(c.value);
    });
    renderSelected();
});

document.getElementById('btnSelectAll').addEventListener('click', function() {
    document.querySelectorAll('.chk-user').forEach(c => {
        c.checked = true;
        selectedIds.add(c.value);
    });
    renderSelected();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('chk-user')) {
        if (e.target.checked) selectedIds.add(e.target.value);
        else selectedIds.delete(e.target.value);
        renderSelected();
    }
});

function renderSelected() {
    var box   = document.getElementById('selectedUsers');
    var count = document.getElementById('selectedCount');
    var noMsg = document.getElementById('noSelMsg');
    count.textContent = selectedIds.size;
    document.getElementById('btnSend').disabled = selectedIds.size === 0 || !document.getElementById('gameSelect').value;
    if (selectedIds.size === 0) {
        box.innerHTML = '<small class="text-muted" id="noSelMsg">Nenhum usuário selecionado</small>';
        return;
    }
    var html = '';
    document.querySelectorAll('.chk-user:checked').forEach(function(c) {
        html += '<span class="badge bg-primary me-1 mb-1">' + c.getAttribute('data-mobile') + '</span>';
    });
    box.innerHTML = html;
}

// ── Jogo ────────────────────────────────────────────────────
document.getElementById('gameSelect').addEventListener('change', function() {
    var opt     = this.options[this.selectedIndex];
    var preview = document.getElementById('gamePreview');
    if (this.value) {
        document.getElementById('gameThumb').src     = opt.getAttribute('data-banner') || '';
        document.getElementById('gameName').textContent     = opt.text;
        document.getElementById('gameProvider').textContent = opt.getAttribute('data-provider') || '';
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
    document.getElementById('btnSend').disabled = selectedIds.size === 0 || !this.value;
});

// ── Rounds ───────────────────────────────────────────────────
document.getElementById('roundsRange').addEventListener('input', function() {
    document.getElementById('roundsInput').value = this.value;
});
document.getElementById('roundsInput').addEventListener('input', function() {
    var v = Math.min(23, Math.max(1, parseInt(this.value) || 1));
    this.value = v;
    document.getElementById('roundsRange').value = v;
});
document.querySelectorAll('.rounds-preset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var v = this.getAttribute('data-val');
        document.getElementById('roundsInput').value = v;
        document.getElementById('roundsRange').value  = v;
    });
});

// ── Enviar ───────────────────────────────────────────────────
document.getElementById('btnSend').addEventListener('click', function() {
    var ids    = Array.from(selectedIds);
    var game   = document.getElementById('gameSelect').value;
    var rounds = parseInt(document.getElementById('roundsInput').value);
    if (!ids.length || !game || rounds < 1 || rounds > 23) return;
    if (!confirm('Enviar ' + rounds + ' free spin(s) para ' + ids.length + ' usuário(s)?')) return;

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="ti ti-loader me-2"></i>Enviando...';

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=send_freespins&user_ids=' + encodeURIComponent(JSON.stringify(ids)) +
              '&game_code=' + encodeURIComponent(game) +
              '&rounds=' + rounds
    })
    .then(r => r.json())
    .then(data => {
        var result = document.getElementById('sendResult');
        result.style.display = 'block';
        result.className = 'alert mt-3 ' + (data.success ? 'alert-success' : 'alert-danger');
        result.innerHTML = '<i class="ti ti-' + (data.success ? 'check' : 'x') + ' me-2"></i>' + data.message;
        showToast(data.success ? 'success' : 'error', data.message);
    })
    .catch(err => showToast('error', 'Erro: ' + err))
    .finally(() => {
        btn.disabled = selectedIds.size === 0;
        btn.innerHTML = '<i class="ti ti-send me-2"></i>Enviar Free Spins';
    });
});
</script>

</body>
</html>