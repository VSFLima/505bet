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
// HELPER — normaliza URL do logo para o dominio atual
// ============================================================
function resolveLogoUrl($logo) {
    if (empty($logo)) return '';
    if (strpos($logo, 'http') === 0) {
        $parsed = parse_url($logo);
        $path   = $parsed['path'] ?? '';
        if (!empty($path)) {
            $proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $base  = $proto . '://' . $_SERVER['HTTP_HOST'];
            return rtrim($base, '/') . $path;
        }
        return '';
    }
    return $logo;
}
// ============================================================
// LOG
// ============================================================
function pf_log($msg) {
    $f    = __DIR__ . '/provedores.log';
    $date = date('Y-m-d H:i:s');
    file_put_contents($f, "[$date] $msg" . PHP_EOL, FILE_APPEND);
}

// ============================================================
// AJAX HANDLERS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    header('Content-Type: application/json');

    // Ativar/desativar selecionados
    if ($_POST['action'] === 'toggle_selected') {
        $ids    = array_map('intval', json_decode($_POST['ids'] ?? '[]', true));
        $status = intval($_POST['status'] ?? 0);
        if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Nenhum provedor selecionado.']); exit; }
        $in  = implode(',', $ids);
        $res = $mysqli->query("UPDATE provedores SET status = $status WHERE id IN ($in)");
        $label = $status ? 'ativados' : 'desativados';
        echo json_encode($res
            ? ['success' => true,  'message' => count($ids) . " provedores $label."]
            : ['success' => false, 'message' => 'Erro: ' . $mysqli->error]);
        exit;
    }

    // Excluir selecionados
    if ($_POST['action'] === 'delete_selected') {
        $ids = array_map('intval', json_decode($_POST['ids'] ?? '[]', true));
        if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Nenhum provedor selecionado.']); exit; }
        $in  = implode(',', $ids);
        $res = $mysqli->query("DELETE FROM provedores WHERE id IN ($in)");
        echo json_encode($res
            ? ['success' => true,  'message' => count($ids) . ' provedor(es) excluído(s).']
            : ['success' => false, 'message' => 'Erro: ' . $mysqli->error]);
        exit;
    }

    // Desativar todos
    if ($_POST['action'] === 'disable_all') {
        $res = $mysqli->query("UPDATE provedores SET status = 0");
        echo json_encode($res
            ? ['success' => true,  'message' => $mysqli->affected_rows . ' provedores desativados.']
            : ['success' => false, 'message' => 'Erro: ' . $mysqli->error]);
        exit;
    }

    // Importar da PlayFiver
    if ($_POST['action'] === 'import_playfiver') {
        echo json_encode(import_providers_playfiver());
        exit;
    }

    // Buscar lista da PlayFiver (NÃO grava nada, só lista para o admin escolher)
    if ($_POST['action'] === 'fetch_playfiver') {
        echo json_encode(fetch_providers_playfiver());
        exit;
    }

    // Importar SOMENTE os selecionados (com ou sem logo)
    if ($_POST['action'] === 'import_selected') {
        $items    = json_decode($_POST['items'] ?? '[]', true);
        $withLogo = intval($_POST['with_logo'] ?? 0) === 1;
        echo json_encode(import_selected_playfiver($items, $withLogo));
        exit;
    }
}

// ============================================================
// IMPORTAR PROVEDORES PLAYFIVER
// ============================================================
function import_providers_playfiver() {
    global $mysqli;
    pf_log("=== Iniciando importação de provedores PlayFiver ===");

    $res  = $mysqli->query("SELECT url, agent_token, agent_secret FROM playfiver WHERE ativo = 1 OR id = 1 LIMIT 1");
    $cred = $res ? $res->fetch_assoc() : null;
    if (!$cred || empty($cred['agent_token'])) {
        pf_log("ERRO: Credenciais não encontradas.");
        return ['success' => false, 'message' => 'Credenciais PlayFiver não encontradas na tabela playfiver.'];
    }
    pf_log("Credenciais carregadas. URL: " . $cred['url']);

    $mysqli->query("ALTER TABLE provedores ADD COLUMN IF NOT EXISTS playfiver TINYINT(1) NOT NULL DEFAULT 0");
    $mysqli->query("ALTER TABLE provedores ADD COLUMN IF NOT EXISTS playfiver_id INT DEFAULT NULL");

    // Garantir UNIQUE KEY
    $existeIdx = $mysqli->query("SELECT COUNT(*) as cnt FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'provedores' AND index_name = 'uq_prov_code'");
    $idxRow = $existeIdx ? $existeIdx->fetch_assoc() : ['cnt' => 1];
    if ($idxRow['cnt'] == 0) {
        $mysqli->query("DELETE p1 FROM provedores p1 INNER JOIN provedores p2 WHERE p1.id > p2.id AND p1.code = p2.code");
        $mysqli->query("ALTER TABLE provedores ADD UNIQUE KEY uq_prov_code (code)");
        pf_log("UNIQUE KEY uq_prov_code criada.");
    }

    $apiUrl = rtrim($cred['url'], '/') . '/api/v2/providers?agentToken=' . urlencode($cred['agent_token']) . '&secretKey=' . urlencode($cred['agent_secret']);
    pf_log("Chamando API: " . preg_replace('/agentToken=[^&]+/', 'agentToken=***', $apiUrl));

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_TIMEOUT => 20]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) { pf_log("ERRO cURL: $curlError"); return ['success' => false, 'message' => 'Erro cURL: ' . $curlError]; }
    pf_log("HTTP $httpCode — Response: " . substr($response, 0, 500));

    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['data'])) {
        pf_log("ERRO: Resposta inválida. " . json_last_error_msg());
        return ['success' => false, 'message' => 'Resposta inválida da API (HTTP ' . $httpCode . ')'];
    }

    $total = count($data['data']); $inserted = 0; $updated = 0;
    pf_log("Total recebidos: $total");

    // URL base do servidor para montar URLs absolutas das logos
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $siteBase = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? parse_url($cred['url'] ?? '', PHP_URL_HOST) ?? '');

    foreach ($data['data'] as $prov) {
        $name         = $prov['name']      ?? '';
        $code         = $prov['code']      ?? ($prov['provider_code'] ?? strtoupper(preg_replace('/[^A-Z0-9_]/i', '_', $name)));
        $type         = $prov['type']      ?? 'slot';
        $playfiver_id = isset($prov['id']) ? intval($prov['id']) : null;
        $logoRemota   = $prov['image_url'] ?? null; // URL da logo retornada pela PlayFiver

        // Tentar baixar logo localmente
        $logo = $logoRemota; // fallback: URL remota
        if ($logoRemota) {
            $uploadDir = __DIR__ . '/../uploads/provedores/imagens/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext      = pathinfo(parse_url($logoRemota, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'webp';
            $filename = 'prov_' . ($playfiver_id ?? 'x') . '_' . strtolower(preg_replace('/[^a-z0-9]/i', '_', $name)) . '.' . $ext;
            $dest     = $uploadDir . $filename;
            if (!file_exists($dest) || filesize($dest) < 100) {
                $chLogo = curl_init($logoRemota);
                curl_setopt_array($chLogo, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                    CURLOPT_USERAGENT      => 'Mozilla/5.0',
                    CURLOPT_HTTPHEADER     => ['Referer: https://imagensfivers.com/'],
                ]);
                $imgData  = curl_exec($chLogo);
                $httpCode = curl_getinfo($chLogo, CURLINFO_HTTP_CODE);
                curl_close($chLogo);
                if ($httpCode === 200 && $imgData && strlen($imgData) > 100) {
                    file_put_contents($dest, $imgData);
                    $logo = $siteBase ? "$siteBase/uploads/provedores/imagens/$filename" : $logoRemota;
                    pf_log("  LOGO baixada: $filename → $logo");
                }
            } else {
                // Já existe localmente, usar URL local
                $logo = $siteBase ? "$siteBase/uploads/provedores/imagens/$filename" : $logoRemota;
            }
        }

        if (empty($name)) { pf_log("AVISO: sem nome ignorado"); continue; }

        $check = $mysqli->prepare("SELECT id FROM provedores WHERE code = ?");
        $check->bind_param("s", $code); $check->execute(); $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            // Atualizar logo mesmo se já existe
            $upd = $mysqli->prepare("UPDATE provedores SET logo = ?, playfiver_id = ? WHERE code = ? AND (logo IS NULL OR logo = '')");
            $upd->bind_param("sis", $logo, $playfiver_id, $code);
            $upd->execute();
            pf_log("  JÁ EXISTE (logo atualizada se vazia): $code | $name");
            $updated++;
        } else {
            $check->close();
            $statusInativo = 0;
            $ins = $mysqli->prepare("INSERT IGNORE INTO provedores (code, name, type, status, playfiver, playfiver_id, logo) VALUES (?, ?, ?, ?, 1, ?, ?)");
            $ins->bind_param("sssii" . "s", $code, $name, $type, $statusInativo, $playfiver_id, $logo);
            $ok = $ins->execute();
            if ($ok && $ins->affected_rows > 0) { pf_log("  INSERIDO (desativado): $code | $name"); $inserted++; }
            elseif ($ok && $ins->affected_rows === 0) { pf_log("  IGNORADO (UNIQUE): $code"); $updated++; }
            else { pf_log("  ERRO INSERT: " . $ins->error . " | $code"); }
            $ins->close();
        }
    }

    pf_log("Finalizado. Inseridos: $inserted | Já existiam: $updated");
    return ['success' => true, 'message' => "Concluído! $inserted inseridos, $updated já existiam.", 'inserted' => $inserted, 'updated' => $updated];
}

// ============================================================
// BUSCAR PROVEDORES (apenas lista, não grava nada)
// ============================================================
function fetch_providers_playfiver() {
    global $mysqli;
    pf_log("=== Buscando lista de provedores PlayFiver (sem gravar) ===");

    $res  = $mysqli->query("SELECT url, agent_token, agent_secret FROM playfiver WHERE ativo = 1 OR id = 1 LIMIT 1");
    $cred = $res ? $res->fetch_assoc() : null;
    if (!$cred || empty($cred['agent_token'])) {
        return ['success' => false, 'message' => 'Credenciais PlayFiver não encontradas na tabela playfiver.'];
    }

    $apiUrl = rtrim($cred['url'], '/') . '/api/v2/providers?agentToken=' . urlencode($cred['agent_token']) . '&secretKey=' . urlencode($cred['agent_secret']);
    pf_log("Chamando API (fetch): " . preg_replace('/agentToken=[^&]+/', 'agentToken=***', $apiUrl));

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'], CURLOPT_TIMEOUT => 20]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) { return ['success' => false, 'message' => 'Erro cURL: ' . $curlError]; }
    $data = json_decode($response, true);
    if ($httpCode !== 200 || empty($data['data'])) {
        pf_log("ERRO (fetch): HTTP $httpCode — " . substr($response, 0, 300));
        return ['success' => false, 'message' => 'Resposta inválida da API (HTTP ' . $httpCode . '). ' . ($data['msg'] ?? '')];
    }

    // Descobrir quais códigos já existem no banco (para marcar na tela)
    $existentes = [];
    $rx = $mysqli->query("SELECT code FROM provedores");
    if ($rx) { while ($r = $rx->fetch_assoc()) { $existentes[$r['code']] = true; } }

    $list = [];
    foreach ($data['data'] as $prov) {
        $name = $prov['name'] ?? '';
        if (empty($name)) continue;
        $code = $prov['code'] ?? ($prov['provider_code'] ?? strtoupper(preg_replace('/[^A-Z0-9_]/i', '_', $name)));
        $list[] = [
            'playfiver_id' => isset($prov['id']) ? intval($prov['id']) : null,
            'name'         => $name,
            'code'         => $code,
            'type'         => $prov['type'] ?? 'slot',
            'logo'         => $prov['image_url'] ?? '',
            'exists'       => isset($existentes[$code]),
        ];
    }
    pf_log("Fetch OK — " . count($list) . " provedores listados.");
    return ['success' => true, 'providers' => $list];
}

// ============================================================
// IMPORTAR SOMENTE OS SELECIONADOS (com ou sem logo)
// ============================================================
function import_selected_playfiver($items, $withLogo) {
    global $mysqli;
    if (empty($items) || !is_array($items)) {
        return ['success' => false, 'message' => 'Nenhum provedor selecionado.'];
    }
    pf_log("=== Importando selecionados (" . count($items) . ") | com_logo=" . ($withLogo ? '1' : '0') . " ===");

    $mysqli->query("ALTER TABLE provedores ADD COLUMN IF NOT EXISTS playfiver TINYINT(1) NOT NULL DEFAULT 0");
    $mysqli->query("ALTER TABLE provedores ADD COLUMN IF NOT EXISTS playfiver_id INT DEFAULT NULL");

    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
    $siteBase = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? '');

    $inserted = 0; $updated = 0; $skipped = 0;

    foreach ($items as $prov) {
        $name         = trim($prov['name'] ?? '');
        if (empty($name)) { continue; }
        $code         = $prov['code'] ?? strtoupper(preg_replace('/[^A-Z0-9_]/i', '_', $name));
        $type         = $prov['type'] ?? 'slot';
        $playfiver_id = isset($prov['playfiver_id']) ? intval($prov['playfiver_id']) : null;
        $logoRemota   = $prov['logo'] ?? '';

        // Definir a logo conforme a escolha do admin
        $logo = '';
        if ($withLogo && $logoRemota) {
            $uploadDir = __DIR__ . '/../uploads/provedores/imagens/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext      = pathinfo(parse_url($logoRemota, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'webp';
            $filename = 'prov_' . ($playfiver_id ?? 'x') . '_' . strtolower(preg_replace('/[^a-z0-9]/i', '_', $name)) . '.' . $ext;
            $dest     = $uploadDir . $filename;
            if (!file_exists($dest) || filesize($dest) < 100) {
                $chLogo = curl_init($logoRemota);
                curl_setopt_array($chLogo, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_TIMEOUT        => 10,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                    CURLOPT_USERAGENT      => 'Mozilla/5.0',
                    CURLOPT_HTTPHEADER     => ['Referer: https://imagensfivers.com/'],
                ]);
                $imgData  = curl_exec($chLogo);
                $httpCodeL = curl_getinfo($chLogo, CURLINFO_HTTP_CODE);
                curl_close($chLogo);
                if ($httpCodeL === 200 && $imgData && strlen($imgData) > 100) {
                    file_put_contents($dest, $imgData);
                    $logo = $siteBase ? "$siteBase/uploads/provedores/imagens/$filename" : $logoRemota;
                    pf_log("  LOGO baixada: $filename");
                } else {
                    // Falhou o download: mantém sem logo (não usa URL remota)
                    $logo = '';
                    pf_log("  LOGO falhou (HTTP $httpCodeL) → importado sem logo: $code");
                }
            } else {
                $logo = $siteBase ? "$siteBase/uploads/provedores/imagens/$filename" : '';
            }
        }
        // Se withLogo = false, $logo continua '' (sem logo, sem baixar nada)

        $check = $mysqli->prepare("SELECT id FROM provedores WHERE code = ?");
        $check->bind_param("s", $code); $check->execute(); $check->store_result();

        if ($check->num_rows > 0) {
            $check->close();
            if ($withLogo && $logo !== '') {
                $upd = $mysqli->prepare("UPDATE provedores SET logo = ?, playfiver_id = ? WHERE code = ?");
                $upd->bind_param("sis", $logo, $playfiver_id, $code);
                $upd->execute(); $upd->close();
            }
            pf_log("  JÁ EXISTE: $code | $name");
            $skipped++;
        } else {
            $check->close();
            $statusInativo = 0;
            $ins = $mysqli->prepare("INSERT IGNORE INTO provedores (code, name, type, status, playfiver, playfiver_id, logo) VALUES (?, ?, ?, ?, 1, ?, ?)");
            $ins->bind_param("sssiis", $code, $name, $type, $statusInativo, $playfiver_id, $logo);
            $ok = $ins->execute();
            if ($ok && $ins->affected_rows > 0) { pf_log("  INSERIDO (desativado): $code | $name"); $inserted++; }
            else { pf_log("  IGNORADO/ERRO: $code | " . $ins->error); $skipped++; }
            $ins->close();
        }
    }

    $comSem = $withLogo ? 'com logo' : 'sem logo';
    pf_log("Selecionados finalizado. Inseridos: $inserted | Já existiam: $skipped");
    return ['success' => true, 'message' => "Importado $comSem: $inserted novo(s), $skipped já existia(m)."];
}

// ============================================================
// CRUD
// ============================================================
$toastType = null; $toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !isset($_POST['action'])) {
    $id     = intval($_POST['id']);
    $code   = $_POST['code'];
    $name   = $_POST['name'];
    $type   = $_POST['type'];
    $status = intval($_POST['status']);

    // Upload de logo
    $logoPath = $_POST['logo_atual'] ?? null;
    if (!empty($_FILES['logo']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../uploads/provedores/imagens/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        if (!in_array($ext, $allowed)) {
            $toastType    = 'error';
            $toastMessage = 'Formato inválido. Use JPG, PNG, WEBP ou SVG.';
        } else {
            $filename = 'prov_' . $id . '.' . $ext;
            $dest     = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                $logoPath = '../uploads/provedores/imagens/' . $filename;
            } else {
                $toastType    = 'error';
                $toastMessage = 'Erro ao mover o arquivo. Verifique permissões da pasta.';
            }
        }
    }

    if (!$toastType) {
        $qry = $mysqli->prepare("UPDATE provedores SET code = ?, name = ?, type = ?, status = ?, logo = ? WHERE id = ?");
        $qry->bind_param("sssisi", $code, $name, $type, $status, $logoPath, $id);
        if ($qry->execute()) { $toastType = 'success'; $toastMessage = 'Provedor atualizado!'; }
        else                 { $toastType = 'error';   $toastMessage = 'Erro ao atualizar.'; }
    }
}

// ============================================================
// PAGINAÇÃO E FILTROS
// ============================================================
$search          = trim($_GET['search']          ?? '');
$filter_type     = trim($_GET['filter_type']     ?? '');
$filter_origin   = trim($_GET['filter_origin']   ?? '');
$filter_status   = $_GET['filter_status'] ?? '';
$show_all        = isset($_GET['show_all']) && $_GET['show_all'] == '1';
$limit           = $show_all ? 99999 : 25;
$page            = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset          = $show_all ? 0 : ($page - 1) * $limit;

$where = ["1=1"];
if ($search !== '')        $where[] = "(code LIKE '%" . $mysqli->real_escape_string($search) . "%' OR name LIKE '%" . $mysqli->real_escape_string($search) . "%')";
if ($filter_type !== '')   $where[] = "type = '" . $mysqli->real_escape_string($filter_type) . "'";
if ($filter_origin === 'playfiver') $where[] = "playfiver = 1";
if ($filter_origin === 'manual')    $where[] = "(playfiver = 0 OR playfiver IS NULL)";
if ($filter_status !== '') $where[] = "status = " . intval($filter_status);
$whereStr = implode(" AND ", $where);

$total_providers = $mysqli->query("SELECT COUNT(*) as t FROM provedores WHERE $whereStr")->fetch_assoc()['t'];
$total_pages     = $show_all ? 1 : max(1, ceil($total_providers / $limit));
$res = $mysqli->query("SELECT * FROM provedores WHERE $whereStr ORDER BY name ASC LIMIT $limit OFFSET $offset");
$providers = [];
while ($row = $res->fetch_assoc()) $providers[] = $row;

$types = [];
$tr = $mysqli->query("SELECT DISTINCT type FROM provedores WHERE type IS NOT NULL AND type != '' ORDER BY type ASC");
while ($row = $tr->fetch_assoc()) $types[] = $row['type'];
?>
<?php include 'partials/html.php' ?>
<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
?>

<head>
    <?php $title = "Gerenciamento de Provedores"; include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .provider-logo { width: 40px; height: 40px; object-fit: contain; border-radius: 4px; background: #f8f9fa; padding: 2px; }
    </style>
</head>
<body>

<?php include 'partials/topbar.php' ?>
<?php include 'partials/startbar.php' ?>

<div class="page-wrapper">
    <div class="page-content">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="card">

                        <!-- CABEÇALHO -->
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h4 class="card-title mb-0">
                                <i class="ti ti-building-arch me-2"></i>Provedores
                                <span class="badge bg-secondary ms-2"><?= $total_providers ?></span>
                            </h4>
                            <div class="d-flex gap-2 flex-wrap">
                                <button id="btnFetchPlayfiver" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#selectProvidersModal">
                                    <i class="ti ti-search me-1"></i>Buscar provedores
                                </button>
                                <button id="btnImportPlayfiver" class="btn btn-outline-success btn-sm">
                                    <i class="ti ti-cloud-download me-1"></i>Importar todos
                                </button>
                                <a href="import-games-playfiver.php" class="btn btn-primary btn-sm">
                                    <i class="ti ti-device-gamepad-2 me-1"></i>Importar Jogos
                                </a>
                                <button id="btnEnableSelected" class="btn btn-outline-success btn-sm" disabled>
                                    <i class="ti ti-check me-1"></i>Ativar selecionados
                                </button>
                                <button id="btnDisableSelected" class="btn btn-outline-warning btn-sm" disabled>
                                    <i class="ti ti-x me-1"></i>Desativar selecionados
                                </button>
                                <button id="btnDeleteSelected" class="btn btn-danger btn-sm" disabled>
                                    <i class="ti ti-trash me-1"></i>Excluir selecionados
                                </button>
                                <a href="?<?= http_build_query(array_merge($_GET, ['show_all' => $show_all ? '0' : '1', 'page' => 1])) ?>" class="btn btn-outline-secondary btn-sm">
                                    <i class="ti ti-<?= $show_all ? 'list' : 'stack' ?> me-1"></i><?= $show_all ? 'Paginado' : 'Ver todos' ?>
                                </a>
                                <button id="btnDisableAll" class="btn btn-outline-danger btn-sm">
                                    <i class="ti ti-ban me-1"></i>Desativar todos
                                </button>
                            </div>
                        </div>

                        <div class="card-body">

                            <!-- FILTROS -->
                            <form method="GET" action="" class="row g-2 mb-3">
                                <input type="hidden" name="show_all" value="<?= $show_all ? '1' : '0' ?>">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Buscar por código ou nome..." value="<?= htmlspecialchars($search) ?>">
                                        <button class="btn btn-primary" type="submit"><i class="ti ti-search"></i></button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <select name="filter_type" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todos os tipos</option>
                                        <?php foreach ($types as $t): ?>
                                            <option value="<?= htmlspecialchars($t) ?>" <?= $filter_type === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="filter_origin" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todas origens</option>
                                        <option value="playfiver" <?= $filter_origin === 'playfiver' ? 'selected' : '' ?>>PlayFiver</option>
                                        <option value="manual"    <?= $filter_origin === 'manual'    ? 'selected' : '' ?>>Manual</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="filter_status" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todos os status</option>
                                        <option value="1" <?= $filter_status === '1' ? 'selected' : '' ?>>Ativo</option>
                                        <option value="0" <?= $filter_status === '0' ? 'selected' : '' ?>>Desativado</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <?php if ($search || $filter_type || $filter_origin || $filter_status !== ''): ?>
                                        <a href="provedores.php" class="btn btn-outline-secondary w-100">
                                            <i class="ti ti-x me-1"></i>Limpar filtros
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <!-- TABELA -->
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" id="checkAll" title="Selecionar todos"></th>
                                            <th>Logo</th>
                                            <th>Código</th>
                                            <th>Nome</th>
                                            <th>Tipo</th>
                                            <th>Origem</th>
                                            <th>Status</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($providers)): ?>
                                            <tr><td colspan="8" class="text-center text-muted py-4">Nenhum provedor encontrado</td></tr>
                                        <?php else: ?>
                                        <?php foreach ($providers as $p): ?>
                                            <tr>
                                                <td><input type="checkbox" class="chk-provider" value="<?= $p['id'] ?>"></td>
                                                <td>
                                                    <?php if (!empty($p['logo'])): ?>
                                                        <img src="<?= htmlspecialchars(resolveLogoUrl($p['logo'])) ?>" class="provider-logo" alt="">
                                                    <?php else: ?>
                                                        <div class="provider-logo d-flex align-items-center justify-content-center bg-light text-muted" style="font-size:10px">N/A</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><code><?= htmlspecialchars($p['code']) ?></code></td>
                                                <td><?= htmlspecialchars($p['name']) ?></td>
                                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($p['type']) ?></span></td>
                                                <td>
                                                    <?php if (!empty($p['playfiver']) && $p['playfiver'] == 1): ?>
                                                        <span class="badge bg-primary-subtle text-primary"><i class="ti ti-plug me-1"></i>PlayFiver</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary">Manual</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" role="switch"
                                                            <?= $p['status'] == 1 ? 'checked' : '' ?>
                                                            onchange="toggleStatus(<?= $p['id'] ?>, this.checked ? 1 : 0)">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $p['id'] ?>">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal Editar -->
                                            <div class="modal fade" id="editModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Editar Provedor</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                                <input type="hidden" name="logo_atual" value="<?= htmlspecialchars($p['logo'] ?? '') ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Código</label>
                                                                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($p['code']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nome</label>
                                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Tipo</label>
                                                                    <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($p['type']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="1" <?= $p['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                                                        <option value="0" <?= $p['status'] == 0 ? 'selected' : '' ?>>Desativado</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Logo</label>
                                                                    <?php if (!empty($p['logo'])): ?>
                                                                        <div class="mb-2 d-flex align-items-center gap-2">
                                                                            <img src="<?= htmlspecialchars(resolveLogoUrl($p['logo'])) ?>" style="width:48px;height:48px;object-fit:contain;border-radius:4px;background:#f8f9fa;padding:2px;" alt="logo">
                                                                            <small class="text-muted">Logo atual</small>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <input type="file" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                                                                    <small class="text-muted">Formatos: JPG, PNG, WEBP, SVG. Deixe vazio para manter a logo atual.</small>
                                                                    <small class="text-muted d-block">Salvo em: <code>uploads/provedores/imagens/</code></small>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Salvar</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINAÇÃO -->
                            <?php if ($total_pages > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination justify-content-center flex-wrap">
                                    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">&laquo;</a>
                                    </li>
                                    <?php
                                    $range = 2; $start = max(1, $page - $range); $end = min($total_pages, $page + $range);
                                    if ($start > 1) { echo '<li class="page-item"><a class="page-link" href="?page=1&' . http_build_query(array_diff_key($_GET, ['page' => ''])) . '">1</a></li>'; if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end < $total_pages) { if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) . '">' . $total_pages . '</a></li>'; } ?>
                                    <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>">&raquo;</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/endbar.php' ?>
        <?php include 'partials/footer.php' ?>
    </div>
</div>

<!-- MODAL: Selecionar provedores para importar -->
<div class="modal fade" id="selectProvidersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-cloud-download me-1"></i>Importar provedores da PlayFiver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" id="fpCheckAll" class="form-check-input mt-0">
                        <label for="fpCheckAll" class="mb-0">Selecionar todos</label>
                        <span id="fpCount" class="badge bg-secondary ms-2">0 selecionados</span>
                    </div>
                    <input type="text" id="fpSearch" class="form-control form-control-sm" style="max-width:220px" placeholder="Filtrar por nome...">
                </div>
                <div id="fpListWrap" class="border rounded p-2" style="min-height:120px">
                    <div id="fpLoading" class="text-center text-muted py-4">
                        <i class="ti ti-loader me-1"></i>Buscando provedores...
                    </div>
                    <div id="fpList" class="row g-2"></div>
                    <div id="fpEmpty" class="text-center text-muted py-4 d-none">Nenhum provedor encontrado.</div>
                </div>
                <small class="text-muted d-block mt-2">Provedores marcados como <span class="badge bg-info text-dark">já existe</span> não serão duplicados.</small>
            </div>
            <div class="modal-footer flex-wrap">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" id="btnImportWithoutLogo" class="btn btn-outline-primary" disabled>
                    <i class="ti ti-photo-off me-1"></i>Importar SEM logo
                </button>
                <button type="button" id="btnImportWithLogo" class="btn btn-primary" disabled>
                    <i class="ti ti-photo me-1"></i>Importar COM logo
                </button>
            </div>
        </div>
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
    t.innerHTML = '<div class="toast-header"><h5 class="me-auto my-0">Provedores</h5><small>Agora</small><button type="button" class="btn-close" data-bs-dismiss="toast"></button></div><div class="toast-body">' + message + '</div>';
    c.appendChild(t);
    new bootstrap.Toast(t).show();
    setTimeout(function() { t.remove(); }, 3500);
}

function ajax(body, onSuccess) {
    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body })
    .then(r => r.json())
    .then(d => { if (d.success) { showToast('success', d.message); setTimeout(() => location.reload(), 1500); } else showToast('error', d.message); })
    .catch(e => showToast('error', 'Erro: ' + e));
}

function getSelectedIds() { return Array.from(document.querySelectorAll('.chk-provider:checked')).map(c => c.value); }

function updateBulkBtns() {
    var n = getSelectedIds().length;
    document.getElementById('btnEnableSelected').disabled  = n === 0;
    document.getElementById('btnDisableSelected').disabled = n === 0;
    document.getElementById('btnDeleteSelected').disabled  = n === 0;
    if (n > 0) {
        document.getElementById('btnDeleteSelected').innerHTML  = '<i class="ti ti-trash me-1"></i>Excluir (' + n + ')';
        document.getElementById('btnEnableSelected').innerHTML  = '<i class="ti ti-check me-1"></i>Ativar (' + n + ')';
        document.getElementById('btnDisableSelected').innerHTML = '<i class="ti ti-x me-1"></i>Desativar (' + n + ')';
    } else {
        document.getElementById('btnDeleteSelected').innerHTML  = '<i class="ti ti-trash me-1"></i>Excluir selecionados';
        document.getElementById('btnEnableSelected').innerHTML  = '<i class="ti ti-check me-1"></i>Ativar selecionados';
        document.getElementById('btnDisableSelected').innerHTML = '<i class="ti ti-x me-1"></i>Desativar selecionados';
    }
}

document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.chk-provider').forEach(c => c.checked = this.checked);
    updateBulkBtns();
});
document.addEventListener('change', function(e) { if (e.target.classList.contains('chk-provider')) updateBulkBtns(); });

document.getElementById('btnEnableSelected').addEventListener('click', function() {
    var ids = getSelectedIds(); if (!ids.length) return;
    ajax('action=toggle_selected&status=1&ids=' + encodeURIComponent(JSON.stringify(ids)));
});
document.getElementById('btnDisableSelected').addEventListener('click', function() {
    var ids = getSelectedIds(); if (!ids.length) return;
    ajax('action=toggle_selected&status=0&ids=' + encodeURIComponent(JSON.stringify(ids)));
});
document.getElementById('btnDeleteSelected').addEventListener('click', function() {
    var ids = getSelectedIds(); if (!ids.length) return;
    if (!confirm('Excluir ' + ids.length + ' provedor(es)? Esta ação não pode ser desfeita.')) return;
    ajax('action=delete_selected&ids=' + encodeURIComponent(JSON.stringify(ids)));
});
document.getElementById('btnDisableAll').addEventListener('click', function() {
    if (!confirm('Desativar TODOS os provedores?')) return;
    ajax('action=disable_all');
});
document.getElementById('btnImportPlayfiver').addEventListener('click', function() {
    var btn = this; btn.disabled = true; btn.innerHTML = '<i class="ti ti-loader me-1"></i>Importando...';
    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=import_playfiver' })
    .then(r => r.json())
    .then(d => { if (d.success) { showToast('success', d.message); setTimeout(() => location.reload(), 2000); } else showToast('error', d.message); })
    .catch(e => showToast('error', 'Erro: ' + e))
    .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ti ti-cloud-download me-1"></i>Importar PlayFiver'; });
});

// ===== Buscar / importar selecionados =====
var fpProviders = [];   // lista vinda da API
var fpLoaded    = false;

function fpUpdateCount() {
    var n = document.querySelectorAll('.fp-chk:checked').length;
    document.getElementById('fpCount').textContent = n + ' selecionados';
    document.getElementById('btnImportWithLogo').disabled    = n === 0;
    document.getElementById('btnImportWithoutLogo').disabled = n === 0;
}

function fpRender() {
    var term = (document.getElementById('fpSearch').value || '').toLowerCase();
    var wrap = document.getElementById('fpList');
    wrap.innerHTML = '';
    var shown = 0;
    fpProviders.forEach(function(p, i) {
        if (term && p.name.toLowerCase().indexOf(term) === -1 && (p.code || '').toLowerCase().indexOf(term) === -1) return;
        shown++;
        var badge = p.exists ? ' <span class="badge bg-info text-dark">já existe</span>' : '';
        var logo  = p.logo ? '<img src="' + p.logo + '" style="width:32px;height:32px;object-fit:contain;border-radius:4px;background:#f8f9fa" onerror="this.style.display=\'none\'">' : '';
        var col = document.createElement('div');
        col.className = 'col-md-6';
        col.innerHTML =
            '<label class="d-flex align-items-center gap-2 border rounded p-2 mb-0" style="cursor:pointer">' +
                '<input type="checkbox" class="form-check-input fp-chk mt-0" value="' + i + '">' +
                logo +
                '<span class="text-truncate"><strong>' + p.name + '</strong>' + badge + '<br><small class="text-muted">' + (p.code || '') + '</small></span>' +
            '</label>';
        wrap.appendChild(col);
    });
    document.getElementById('fpEmpty').classList.toggle('d-none', shown !== 0);
    document.querySelectorAll('.fp-chk').forEach(function(c) { c.addEventListener('change', fpUpdateCount); });
    fpUpdateCount();
}

function fpFetch() {
    document.getElementById('fpLoading').classList.remove('d-none');
    document.getElementById('fpList').innerHTML = '';
    document.getElementById('fpEmpty').classList.add('d-none');
    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=fetch_playfiver' })
    .then(r => r.json())
    .then(d => {
        document.getElementById('fpLoading').classList.add('d-none');
        if (!d.success) { showToast('error', d.message); return; }
        fpProviders = d.providers || [];
        fpLoaded = true;
        fpRender();
    })
    .catch(e => { document.getElementById('fpLoading').classList.add('d-none'); showToast('error', 'Erro: ' + e); });
}

function fpImport(withLogo) {
    var items = Array.from(document.querySelectorAll('.fp-chk:checked')).map(c => fpProviders[parseInt(c.value)]);
    if (!items.length) return;
    var b1 = document.getElementById('btnImportWithLogo'), b2 = document.getElementById('btnImportWithoutLogo');
    b1.disabled = true; b2.disabled = true;
    var active = withLogo ? b1 : b2, orig = active.innerHTML;
    active.innerHTML = '<i class="ti ti-loader me-1"></i>Importando...';
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=import_selected&with_logo=' + (withLogo ? 1 : 0) + '&items=' + encodeURIComponent(JSON.stringify(items))
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('success', d.message); setTimeout(() => location.reload(), 1800); }
        else { showToast('error', d.message); active.innerHTML = orig; fpUpdateCount(); }
    })
    .catch(e => { showToast('error', 'Erro: ' + e); active.innerHTML = orig; fpUpdateCount(); });
}

document.getElementById('selectProvidersModal').addEventListener('shown.bs.modal', function() {
    if (!fpLoaded) fpFetch();
});
document.getElementById('fpCheckAll').addEventListener('change', function() {
    var vis = document.querySelectorAll('.fp-chk');
    vis.forEach(c => c.checked = this.checked);
    fpUpdateCount();
});
document.getElementById('fpSearch').addEventListener('input', fpRender);
document.getElementById('btnImportWithLogo').addEventListener('click', function() { fpImport(true); });
document.getElementById('btnImportWithoutLogo').addEventListener('click', function() { fpImport(false); });

function toggleStatus(id, status) {
    fetch('', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'action=toggle_selected&status=' + status + '&ids=' + encodeURIComponent(JSON.stringify([id])) })
    .then(r => r.json())
    .then(d => showToast(d.success ? 'success' : 'error', d.message));
}
</script>

<?php if ($toastType && $toastMessage): ?>
    <script>showToast('<?= $toastType ?>', '<?= $toastMessage ?>');</script>
<?php endif; ?>

</body>
</html>