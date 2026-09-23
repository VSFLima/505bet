<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Handler AJAX — precisa rodar ANTES de qualquer HTML
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';

function gamesLog($msg) {
    $f    = __DIR__ . '/import-games-playfiver.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($f, "[$date] $msg" . PHP_EOL, FILE_APPEND);
}

// Detecta prefixo de provedor embutido no game_code (ex.: "dt.", "cc.", "rp.")
// e monta as 2 URLs candidatas de banner: uma com o code completo (padrão
// atual, compatível com o comportamento de sempre) e outra sem o prefixo.
// Esta função NUNCA decide sozinha qual usar — a escolha é sempre do admin,
// jogo por jogo (ver ação "check_prefix_games" e o parâmetro prefix_decisions
// da ação "import_games").
function buildImportBannerCandidates($cdnName, $cdnPrefix, $gameCode) {
    $hasCodePrefix  = (bool) preg_match('/^[a-z]+\./i', $gameCode);
    $codeSemPrefixo = $hasCodePrefix ? preg_replace('/^[a-z]+\./i', '', $gameCode) : $gameCode;

    return [
        'has_prefix'    => $hasCodePrefix,
        'code_full'     => $gameCode,
        'code_stripped' => $codeSemPrefixo,
        'url_full'      => "https://imagensfivers.com/Games/{$cdnName}/{$cdnPrefix}{$gameCode}.webp",
        'url_stripped'  => $hasCodePrefix ? "https://imagensfivers.com/Games/{$cdnName}/{$cdnPrefix}{$codeSemPrefixo}.webp" : null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    checa_login_adm();

    // ============================================================
    // AÇÃO: check_prefix_games — roda ANTES da importação de verdade.
    // Busca os jogos na API, filtra só os que ainda não existem no banco
    // e devolve a lista dos que têm prefixo "letras." embutido no code
    // (ex.: dt.wrathofthor), com as 2 URLs candidatas, para o admin
    // decidir jogo por jogo (ou aplicar a escolha a todos) antes de gravar.
    // ============================================================
    if ($_POST['action'] === 'check_prefix_games') {
        header('Content-Type: application/json');
        global $mysqli;

        $provLocalId = intval($_POST['provider_local_id'] ?? 0);
        $cdnName     = trim($_POST['cdn_name']   ?? '');
        $cdnPrefix   = trim($_POST['cdn_prefix'] ?? '');

        if (!$provLocalId || !$cdnName) {
            echo json_encode(['success' => false, 'message' => 'Selecione o provedor e informe o nome CDN.']);
            exit;
        }

        $stmt = $mysqli->prepare("SELECT id, code, name, playfiver_id FROM provedores WHERE id = ?");
        $stmt->bind_param("i", $provLocalId);
        $stmt->execute();
        $prov = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prov) { echo json_encode(['success' => false, 'message' => 'Provedor não encontrado.']); exit; }
        if (empty($prov['playfiver_id'])) { echo json_encode(['success' => false, 'message' => 'Provedor sem ID PlayFiver. Importe os provedores primeiro.']); exit; }

        $res  = $mysqli->query("SELECT url, agent_token, agent_secret FROM playfiver WHERE id = 1 LIMIT 1");
        $cred = $res ? $res->fetch_assoc() : null;
        if (!$cred || empty($cred['agent_token'])) {
            echo json_encode(['success' => false, 'message' => 'Credenciais PlayFiver não configuradas.']); exit;
        }

        $apiUrl = rtrim($cred['url'], '/') . '/api/v2/games?provider=' . $prov['playfiver_id'];

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) { echo json_encode(['success' => false, 'message' => 'Erro cURL: ' . $curlError]); exit; }

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['data'])) {
            echo json_encode(['success' => false, 'message' => 'Resposta inválida da API (HTTP ' . $httpCode . ')']); exit;
        }

        $games      = $data['data'];
        $ambiguous  = [];
        $totalNovos = 0;
        $autoFromApi = 0;

        foreach ($games as $game) {
            $gameCode = trim($game['game_code'] ?? ($game['code'] ?? ''));
            $gameName = trim($game['name'] ?? 'Sem nome');
            if (empty($gameCode)) continue;

            $chk = $mysqli->prepare("SELECT id FROM games WHERE game_code = ?");
            $chk->bind_param("s", $gameCode); $chk->execute(); $chk->store_result();
            $exists = $chk->num_rows > 0;
            $chk->close();
            if ($exists) continue;

            $totalNovos++;

            // A própria API da PlayFiver já manda o image_url pronto e correto
            // pra cada jogo (às vezes com prefixo, às vezes sem). Sempre que
            // ela mandar, é essa URL que vale — não precisa perguntar nada.
            $apiImageUrl = trim($game['image_url'] ?? '');
            if ($apiImageUrl !== '') {
                $autoFromApi++;
                continue;
            }

            // Só entra em "ambíguo" (precisa o admin decidir) o jogo que a API
            // NÃO mandou image_url e cujo code tem prefixo embutido (ex.: dt.xxx).
            $cand = buildImportBannerCandidates($cdnName, $cdnPrefix, $gameCode);
            if ($cand['has_prefix']) {
                $ambiguous[] = [
                    'game_code'    => $gameCode,
                    'game_name'    => $gameName,
                    'url_full'     => $cand['url_full'],
                    'url_stripped' => $cand['url_stripped'],
                ];
            }
        }

        echo json_encode([
            'success'       => true,
            'total_novos'   => $totalNovos,
            'auto_from_api' => $autoFromApi,
            'ambiguous'     => $ambiguous,
        ]);
        exit;
    }

    if ($_POST['action'] === 'import_games') {
        header('Content-Type: application/json');
        global $mysqli;

        $provLocalId  = intval($_POST['provider_local_id'] ?? 0);
        $cdnName      = trim($_POST['cdn_name']      ?? '');
        $cdnPrefix    = trim($_POST['cdn_prefix']    ?? '');
        $gameType     = trim($_POST['game_type']     ?? 'ELECTRONIC');
        $gameStatus   = intval($_POST['game_status'] ?? 0);
        $newCategory  = trim($_POST['new_category']  ?? '');

        if (!$provLocalId || !$cdnName) {
            echo json_encode(['success' => false, 'message' => 'Selecione o provedor e informe o nome CDN.']);
            exit;
        }

        if ($newCategory) {
            $slug = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $newCategory));
            $maxSort = $mysqli->query("SELECT COALESCE(MAX(sort),0)+1 as s FROM game_categories")->fetch_assoc()['s'];
            $insC = $mysqli->prepare("INSERT IGNORE INTO game_categories (name, slug, sort, status) VALUES (?, ?, ?, 1)");
            $insC->bind_param("ssi", $newCategory, $slug, $maxSort);
            $insC->execute();
            $gameType = $slug;
            gamesLog("Nova categoria criada: $newCategory ($slug)");
        }

        $stmt = $mysqli->prepare("SELECT id, code, name, playfiver_id FROM provedores WHERE id = ?");
        $stmt->bind_param("i", $provLocalId);
        $stmt->execute();
        $prov = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$prov) { echo json_encode(['success' => false, 'message' => 'Provedor não encontrado.']); exit; }
        if (empty($prov['playfiver_id'])) { echo json_encode(['success' => false, 'message' => 'Provedor sem ID PlayFiver. Importe os provedores primeiro.']); exit; }

        $res  = $mysqli->query("SELECT url, agent_token, agent_secret FROM playfiver WHERE id = 1 LIMIT 1");
        $cred = $res ? $res->fetch_assoc() : null;
        if (!$cred || empty($cred['agent_token'])) {
            gamesLog("ERRO: Credenciais PlayFiver não encontradas.");
            echo json_encode(['success' => false, 'message' => 'Credenciais PlayFiver não configuradas.']); exit;
        }

        gamesLog("=== Importando jogos do provedor: {$prov['name']} (PlayFiver ID: {$prov['playfiver_id']}) ===");
        gamesLog("Categoria (game_type): $gameType | Status: $gameStatus | Nova categoria: " . ($newCategory ?: 'não'));

        $apiUrl = rtrim($cred['url'], '/') . '/api/v2/games?provider=' . $prov['playfiver_id'];
        gamesLog("URL: $apiUrl");

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        ]);
        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) { gamesLog("ERRO cURL: $curlError"); echo json_encode(['success' => false, 'message' => 'Erro cURL: ' . $curlError]); exit; }
        gamesLog("HTTP $httpCode — Response: " . substr($response, 0, 300));

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['data'])) {
            gamesLog("ERRO: Resposta inválida. " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Resposta inválida da API (HTTP ' . $httpCode . ')']); exit;
        }

        // Decisões do admin (por game_code) vindas da tela de revisão de prefixo:
        // 'full' = mantém o code completo na URL (padrão/comportamento de sempre),
        // 'stripped' = remove o prefixo "letras." do code na URL.
        // Jogo que não aparece aqui (não ambíguo, ou admin não decidiu) usa 'full'.
        $prefixDecisionsRaw = $_POST['prefix_decisions'] ?? '{}';
        $prefixDecisions    = json_decode($prefixDecisionsRaw, true);
        if (!is_array($prefixDecisions)) { $prefixDecisions = []; }

        $games = $data['data']; $total = count($games); $inserted = 0; $skipped = 0; $errors = 0;
        gamesLog("Total de jogos recebidos: $total");
        gamesLog("CDN Name: $cdnName | Prefix: " . ($cdnPrefix ?: 'nenhum'));

        foreach ($games as $game) {
            $gameCode = trim($game['game_code'] ?? ($game['code'] ?? ''));
            $gameName = trim($game['name'] ?? 'Sem nome');

            if (empty($gameCode)) { gamesLog("  AVISO: Jogo sem game_code ignorado"); $skipped++; continue; }

            $chk = $mysqli->prepare("SELECT id FROM games WHERE game_code = ?");
            $chk->bind_param("s", $gameCode); $chk->execute(); $chk->store_result();
            if ($chk->num_rows > 0) { gamesLog("  EXISTE: $gameCode ($gameName)"); $chk->close(); $skipped++; continue; }
            $chk->close();

            // 1ª opção, sempre: o image_url que a própria API da PlayFiver manda
            // pronto pra esse jogo (ela já resolve o prefixo certo por jogo).
            $apiImageUrl = trim($game['image_url'] ?? '');

            if ($apiImageUrl !== '') {
                $banner = $apiImageUrl;
                gamesLog("  BANNER (image_url da API): $banner");
            } else {
                // Fallback: API não mandou image_url pra esse jogo -> monta na mão
                // com CDN+prefixo, respeitando a escolha do admin quando o code
                // tinha prefixo embutido (revisão de prefixo).
                $cand    = buildImportBannerCandidates($cdnName, $cdnPrefix, $gameCode);
                $escolha = $prefixDecisions[$gameCode] ?? 'full';
                $banner  = ($escolha === 'stripped' && $cand['url_stripped']) ? $cand['url_stripped'] : $cand['url_full'];
                if ($cand['has_prefix']) { gamesLog("  PREFIXO detectado em $gameCode (sem image_url da API) — escolha do admin: $escolha"); }
                gamesLog("  BANNER (fallback CDN+prefixo): $banner");
            }

            $ins = $mysqli->prepare("INSERT INTO games (game_code, game_name, banner, status, provider, popular, type, game_type, api) VALUES (?, ?, ?, ?, ?, 0, 'slot', ?, 'playfiver')");
            $ins->bind_param("sssiss", $gameCode, $gameName, $banner, $gameStatus, $prov['name'], $gameType);
            if ($ins->execute()) { gamesLog("  INSERIDO: $gameCode | $gameName | status=$gameStatus | type=$gameType"); $inserted++; }
            else { gamesLog("  ERRO INSERT: " . $ins->error . " — $gameCode"); $errors++; }
            $ins->close();
        }

        gamesLog("Finalizado. Inseridos: $inserted | Já existiam: $skipped | Erros: $errors");
        echo json_encode(['success' => true, 'message' => "$inserted jogos importados! ($skipped já existiam" . ($errors ? ", $errors erros" : "") . ")", 'inserted' => $inserted, 'skipped' => $skipped, 'errors' => $errors]);
        exit;
    }
}
?>
<?php include 'partials/html.php' ?>
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ============================================================
// BUSCAR PROVEDORES PLAYFIVER PARA SELECT
// ============================================================
$providers = [];
$stmt = $mysqli->query("SELECT id, code, name, playfiver_id FROM provedores WHERE playfiver = 1 ORDER BY name ASC");
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $providers[] = $row;
    }
}

// Mapeamento de sugestões CDN por nome do provedor
$cdnSuggestions = [
    'PGSOFT'                  => ['cdn' => 'Pgsoft',      'prefix' => 'PGS_'],
    'PRAGMATIC'               => ['cdn' => 'Pragmatic',   'prefix' => 'vs'],
    'HABANERO'                => ['cdn' => 'Habanero',    'prefix' => ''],
    'SPRIBE'                  => ['cdn' => 'Spribe',      'prefix' => ''],
    'CQ9'                     => ['cdn' => 'Cq9',         'prefix' => ''],
    'EVOPLAY'                 => ['cdn' => 'Evoplay',     'prefix' => ''],
    'PLAYSON'                 => ['cdn' => 'Playson',     'prefix' => ''],
    'BOOONGO'                 => ['cdn' => 'Booongo',     'prefix' => ''],
    'REELKINGDOM'             => ['cdn' => 'Reelkingdom', 'prefix' => ''],
    'DREAMTECH'               => ['cdn' => 'Dreamtech',   'prefix' => ''],
    'TOPTREND'                => ['cdn' => 'Toptrend',    'prefix' => ''],
    'FATPANDA'                => ['cdn' => 'FatPanda',    'prefix' => ''],
];
?>

<head>
    <?php $title = "Importar Jogos PlayFiver";
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
                    <div class="col-lg-8">

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">
                                    <i class="ti ti-device-gamepad-2 me-2"></i>Importar Jogos da PlayFiver
                                </h4>
                                <a href="provedores.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-arrow-left me-1"></i>Voltar
                                </a>
                            </div>

                            <div class="card-body">
                                <p class="text-muted mb-4">
                                    Selecione um provedor importado da PlayFiver, configure o nome e prefixo do CDN para montar as URLs das imagens e importe todos os jogos automaticamente.
                                </p>

                                <?php if (empty($providers)): ?>
                                    <div class="alert alert-warning">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        Nenhum provedor PlayFiver encontrado. Acesse <a href="provedores.php">Provedores</a> e clique em <strong>Importar Provedores</strong> primeiro.
                                    </div>
                                <?php else: ?>

                                <!-- SEÇÃO 1: PROVEDOR -->
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">
                                        <span class="badge bg-dark me-2">1</span>Selecione o Provedor
                                    </label>
                                    <select id="providerSelect" class="form-select">
                                        <option value="">-- Escolha um provedor --</option>
                                        <?php foreach ($providers as $p): ?>
                                            <option
                                                value="<?= $p['id'] ?>"
                                                data-code="<?= htmlspecialchars($p['code']) ?>"
                                                data-name="<?= htmlspecialchars($p['name']) ?>">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Apenas provedores importados da PlayFiver aparecem aqui.</small>
                                </div>

                                <!-- SEÇÃO 2: CDN -->
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">
                                        <span class="badge bg-dark me-2">2</span>Nome do Provedor no CDN
                                    </label>
                                    <input type="text" id="cdnName" class="form-control font-monospace" placeholder="Ex: Pgsoft">
                                    <small class="text-muted">Primeira letra maiúscula, resto minúsculo. Você pode editar livremente.</small>

                                    <div class="bg-light border-start border-3 border-secondary p-3 mt-2">
                                        <strong class="d-block mb-2 small">Exemplos de formatação:</strong>
                                        <div class="d-flex gap-3 flex-wrap small">
                                            <span><code>PGSOFT</code> → <code class="text-primary">Pgsoft</code></span>
                                            <span><code>PRAGMATIC</code> → <code class="text-primary">Pragmatic</code></span>
                                            <span><code>CQ9</code> → <code class="text-primary">Cq9</code></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEÇÃO 3: PREFIXO -->
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">
                                        <span class="badge bg-dark me-2">3</span>Prefixo do Código <span class="text-muted fw-normal">(opcional)</span>
                                    </label>
                                    <input type="text" id="cdnPrefix" class="form-control font-monospace" placeholder="Ex: PGS_">
                                    <small class="text-muted">Deixe vazio se o provedor não usar prefixo.</small>

                                    <div class="bg-light border-start border-3 border-secondary p-3 mt-2">
                                        <strong class="d-block mb-2 small">Exemplos de prefixos:</strong>
                                        <div class="d-flex gap-3 flex-wrap small">
                                            <span>PG Soft → <code class="text-primary">PGS_</code></span>
                                            <span>Pragmatic → <code class="text-primary">vs</code></span>
                                            <span>Habanero → <code class="text-muted">(vazio)</code></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEÇÃO 4: STATUS -->
                                <div class="mb-4 pb-4 border-bottom">
                                    <label class="form-label fw-bold">
                                        <span class="badge bg-dark me-2">4</span>Status dos Jogos Importados
                                    </label>
                                    <select id="gameStatus" class="form-select">
                                        <option value="0" selected>Desativado (ativar manualmente depois)</option>
                                        <option value="1">Ativo</option>
                                    </select>
                                    <small class="text-muted">Recomendado importar desativado e ativar após revisar.</small>
                                </div>

                                <!-- PREVIEW -->
                                <div class="mb-4 alert alert-light border">
                                    <small class="text-muted d-block mb-1"><i class="ti ti-link me-1"></i>Preview da URL da imagem:</small>
                                    <code id="urlPreview" class="text-break" style="font-size:.8rem">
                                        https://imagensfivers.com/Games/<span id="pvCdn" class="text-primary">Pgsoft</span>/<span id="pvPrefix" class="text-info"></span>{game_code}.webp
                                    </code>
                                    <br>
                                    <small class="text-muted mt-1 d-block">Exemplo real: https://imagensfivers.com/Games/<span id="pvCdn2" class="text-primary">Pgsoft</span>/<span id="pvPrefix2" class="text-info"></span>126.webp</small>
                                </div>

                                <!-- BOTÃO -->
                                <button id="btnImportGames" class="btn btn-success w-100 py-3">
                                    <i class="ti ti-download me-2"></i>Importar Jogos
                                </button>

                                <!-- REVISÃO DE PREFIXO (aparece só quando há jogos com code prefixado, ex.: dt.xxx) -->
                                <div id="prefixReviewSection" class="mt-4 border rounded p-3" style="display:none">
                                    <h6 class="fw-bold mb-1"><i class="ti ti-alert-circle me-2 text-warning"></i>Confirme a URL da imagem destes jogos</h6>
                                    <p class="text-muted small mb-3">
                                        A API da PlayFiver não mandou <code>image_url</code> pronto pra estes jogos, e o code deles tem um prefixo de provedor (ex.: <code>dt.</code>). Escolha, por jogo, se a imagem usa o code completo ou o code sem o prefixo. A pré-visualização atualiza em tempo real.
                                    </p>

                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <label class="form-label fw-bold mb-0 small">Aplicar a todos:</label>
                                        <select id="prefixApplyAll" class="form-select form-select-sm" style="max-width:260px">
                                            <option value="">-- escolher --</option>
                                            <option value="full">Com prefixo (code completo)</option>
                                            <option value="stripped">Sem prefixo</option>
                                        </select>
                                    </div>

                                    <div id="prefixReviewList" class="mb-3" style="max-height:420px; overflow-y:auto"></div>

                                    <div class="d-flex gap-2">
                                        <button id="btnConfirmImport" class="btn btn-success flex-grow-1">
                                            <i class="ti ti-check me-2"></i>Confirmar e Importar
                                        </button>
                                        <button id="btnCancelPrefixReview" class="btn btn-outline-secondary">Cancelar</button>
                                    </div>
                                </div>

                                <!-- RESULTADO -->
                                <div id="importResult" class="mt-3" style="display:none"></div>

                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- INFO BOX -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>O que acontece na importação</h6>
                                <ul class="mb-0 small text-muted">
                                    <li class="mb-1">Busca todos os jogos do provedor selecionado via API PlayFiver</li>
                                    <li class="mb-1">Usa o <code>image_url</code> que a própria API já manda pronto pra cada jogo (é ele que resolve o prefixo certo por jogo)</li>
                                    <li class="mb-1">Só quando a API não manda <code>image_url</code>, monta a URL na mão usando o CDN: <code>imagensfivers.com/Games/{CDN}/{Prefixo}{GameCode}.webp</code> — e nesse caso, se o code tiver prefixo embutido, pergunta pra você</li>
                                    <li class="mb-1">Insere na tabela <code>games</code> com <code>api = 'playfiver'</code></li>
                                    <li class="mb-1">Jogos duplicados (mesmo <code>game_code</code>) são ignorados</li>
                                    <li>Log completo em <code>import-games-playfiver.log</code></li>
                                </ul>
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
    // Sugestões CDN por nome do provedor
    const cdnSuggestions = <?= json_encode($cdnSuggestions) ?>;

    function toTitleFirst(str) {
        if (!str) return '';
        str = str.trim().replace(/\s+/g, '');
        return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
    }

    document.getElementById('providerSelect').addEventListener('change', function() {
        const opt  = this.options[this.selectedIndex];
        const name = opt.getAttribute('data-name') || '';

        const suggestion = cdnSuggestions[name] || {
            cdn: toTitleFirst(name.split(' ')[0]),
            prefix: ''
        };

        document.getElementById('cdnName').value   = suggestion.cdn;
        document.getElementById('cdnPrefix').value = suggestion.prefix;
        updatePreview();
    });

    document.getElementById('cdnName').addEventListener('input', updatePreview);
    document.getElementById('cdnPrefix').addEventListener('input', updatePreview);

    function updatePreview() {
        const cdn    = document.getElementById('cdnName').value   || '{Provider}';
        const prefix = document.getElementById('cdnPrefix').value || '';
        ['pvCdn','pvCdn2'].forEach(id => document.getElementById(id).textContent = cdn);
        ['pvPrefix','pvPrefix2'].forEach(id => document.getElementById(id).textContent = prefix);
    }

    // Guarda as decisões de prefixo escolhidas pelo admin: { game_code: 'full'|'stripped' }
    let prefixDecisions = {};

    function currentImportParams() {
        return {
            provLocalId: document.getElementById('providerSelect').value,
            cdnName:     document.getElementById('cdnName').value.trim(),
            cdnPrefix:   document.getElementById('cdnPrefix').value.trim(),
        };
    }

    document.getElementById('btnImportGames').addEventListener('click', function() {
        const btn = this;
        const { provLocalId, cdnName, cdnPrefix } = currentImportParams();

        if (!provLocalId) { showToast('error', 'Selecione um provedor!'); return; }
        if (!cdnName)     { showToast('error', 'Informe o nome CDN!');    return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-2"></i>Verificando jogos...';

        const body = new URLSearchParams({
            action: 'check_prefix_games',
            provider_local_id: provLocalId,
            cdn_name: cdnName,
            cdn_prefix: cdnPrefix,
        });

        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body })
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showToast('error', data.message); return; }

            if (data.auto_from_api > 0) {
                showToast('success', `${data.auto_from_api} jogo(s) já vêm com a imagem certa da própria API.`);
            }

            if (data.ambiguous && data.ambiguous.length > 0) {
                openPrefixReview(data.ambiguous);
            } else {
                doImport();
            }
        })
        .catch(err => showToast('error', 'Erro: ' + err))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-download me-2"></i>Importar Jogos';
        });
    });

    function openPrefixReview(ambiguous) {
        prefixDecisions = {};
        const list = document.getElementById('prefixReviewList');
        list.innerHTML = '';

        ambiguous.forEach((g, i) => {
            prefixDecisions[g.game_code] = 'full'; // padrão: mantém o comportamento atual

            const row = document.createElement('div');
            row.className = 'border-bottom py-2';
            row.innerHTML = `
                <div class="fw-bold small">${g.game_name} <span class="text-muted fw-normal">(${g.game_code})</span></div>
                <div class="form-check form-check-inline mt-1">
                    <input class="form-check-input prefix-choice" type="radio" name="prefix_${i}" id="prefix_${i}_full" value="full" checked>
                    <label class="form-check-label small" for="prefix_${i}_full">Com prefixo (code completo)</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input prefix-choice" type="radio" name="prefix_${i}" id="prefix_${i}_stripped" value="stripped">
                    <label class="form-check-label small" for="prefix_${i}_stripped">Sem prefixo</label>
                </div>
                <div><code class="text-break small" id="prefix_${i}_preview" style="font-size:.75rem">${g.url_full}</code></div>
            `;
            list.appendChild(row);

            row.querySelectorAll('.prefix-choice').forEach(input => {
                input.addEventListener('change', function() {
                    prefixDecisions[g.game_code] = this.value;
                    document.getElementById(`prefix_${i}_preview`).textContent =
                        this.value === 'stripped' ? g.url_stripped : g.url_full;
                });
            });
        });

        document.getElementById('prefixApplyAll').value = '';
        document.getElementById('prefixReviewSection').style.display = 'block';
    }

    document.getElementById('prefixApplyAll').addEventListener('change', function() {
        const choice = this.value;
        if (!choice) return;
        document.querySelectorAll(`#prefixReviewList input[value="${choice}"]`).forEach(input => {
            input.checked = true;
            input.dispatchEvent(new Event('change'));
        });
    });

    document.getElementById('btnCancelPrefixReview').addEventListener('click', function() {
        document.getElementById('prefixReviewSection').style.display = 'none';
        prefixDecisions = {};
    });

    document.getElementById('btnConfirmImport').addEventListener('click', function() {
        document.getElementById('prefixReviewSection').style.display = 'none';
        doImport();
    });

    function doImport() {
        const btn = document.getElementById('btnImportGames');
        const { provLocalId, cdnName, cdnPrefix } = currentImportParams();
        const gameStatusEl = document.getElementById('gameStatus');

        btn.disabled = true;
        btn.innerHTML = '<i class="ti ti-loader me-2"></i>Importando... aguarde';

        const body = new URLSearchParams({
            action: 'import_games',
            provider_local_id: provLocalId,
            cdn_name: cdnName,
            cdn_prefix: cdnPrefix,
            game_type: 'ELECTRONIC',
            game_status: gameStatusEl.value,
            new_category: '',
            prefix_decisions: JSON.stringify(prefixDecisions),
        });

        fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body })
        .then(r => r.json())
        .then(data => {
            const result = document.getElementById('importResult');
            result.style.display = 'block';
            if (data.success) {
                result.className = 'alert alert-success mt-3';
                result.innerHTML = '<i class="ti ti-check me-2"></i>' + data.message;
                showToast('success', data.message);
            } else {
                result.className = 'alert alert-danger mt-3';
                result.innerHTML = '<i class="ti ti-x me-2"></i>' + data.message;
                showToast('error', data.message);
            }
        })
        .catch(err => showToast('error', 'Erro: ' + err))
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-download me-2"></i>Importar Jogos';
            prefixDecisions = {};
        });
    }

    function showToast(type, message) {
        const c     = document.getElementById('toastPlacement');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center bg-light border-0 fade show`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <h5 class="me-auto my-0">PlayFiver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>`;
        c.appendChild(toast);
        const t = new bootstrap.Toast(toast);
        t.show();
        setTimeout(() => { t.hide(); setTimeout(() => toast.remove(), 500); }, 4000);
    }

    updatePreview();
    </script>
</body>
</html>