<?php
/**
 * PAYMENT MANUAL — Ecompag
 * Aprovação de saques PIX via Ecompag API v2
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php');

// ── 2FA ──────────────────────────────────────────────────────
function validar_2fa_admin($codigo_2fa) {
    global $mysqli;
    if (empty($_SESSION['data_adm']['id'])) return false;
    $admin_id = intval($_SESSION['data_adm']['id']);
    $qry = $mysqli->prepare("SELECT `2fa` FROM admin_users WHERE id = ?");
    $qry->bind_param("i", $admin_id);
    $qry->execute();
    $res = $qry->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!$row || empty($row['2fa'])) return false;
    $stored = $row['2fa'];
    $codigo_2fa = trim($codigo_2fa);
    if (strlen($stored) >= 60) return password_verify($codigo_2fa, $stored);
    return hash_equals($stored, $codigo_2fa);
}

$codigo2fa = $_GET['codigo_2fa'] ?? $_POST['codigo_2fa'] ?? '';
if ($codigo2fa === '') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Código 2FA obrigatório']);
    exit;
}
if (!validar_2fa_admin($codigo2fa)) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Código 2FA inválido']);
    exit;
}

// ── Helpers ──────────────────────────────────────────────────
function logAction($message, $data = []) {
    $log = date('Y-m-d H:i:s') . " - $message - " . json_encode($data, JSON_UNESCAPED_UNICODE);
    error_log("[PAYMENT_ECOMPAG] " . $log);
}

function sendResponse($success, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    $r = ['success' => $success, 'message' => $message, 'timestamp' => date('c')];
    if ($data !== null) $r['data'] = $data;
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}

function identificarTipoChavePix($chavepix) {
    $cpf_length = preg_replace('/[^0-9]/', '', $chavepix);
    if (preg_match('/^\d{10,11}$/', $chavepix)) {
        $cpf = preg_replace('/[^0-9]/is', '', $chavepix);
        if (strlen($cpf) == 11) return 'document';
        return 'phoneNumber';
    } elseif (preg_match('/^\d{14}$/', $chavepix)) return 'document';
    elseif (filter_var($chavepix, FILTER_VALIDATE_EMAIL)) return 'email';
    elseif (preg_match('/^[0-9a-f]{32}$/i', $chavepix)) return 'randomKey';
    return 'invalid';
}

// ── Ecompag: buscar credenciais ───────────────────────────────
function getEcompagCredentials() {
    global $mysqli;
    $res = $mysqli->query("SELECT client_id, client_secret FROM bspay WHERE id = 1 AND ativo = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (!empty($row['client_id']) && !empty($row['client_secret'])) return $row;
    }
    return null;
}

// ── Ecompag: processar saque ──────────────────────────────────
function processEcompag($config, $transacao_id, $valor, $chavepix, $cpf_usuario, $nome_real) {
    $endpoint  = 'https://api.ecompag.com/v2/pix/payment.php';
    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_usuario);

    $params = http_build_query([
        'client_id'     => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'nome'          => $nome_real ?: 'Cliente',
        'cpf'           => $cpf_limpo,
        'valor'         => number_format((float)$valor, 2, '.', ''),
        'chave_pix'     => $chavepix,
        'descricao'     => 'Saque - ID: ' . $transacao_id,
        'urlnoty'       => ''
    ]);

    logAction('Ecompag: enviando saque', [
        'transacao_id' => $transacao_id,
        'valor'        => $valor,
        'chave_pix'    => $chavepix,
        'cpf'          => $cpf_limpo,
        'nome'         => $nome_real
    ]);

    $curl = curl_init($endpoint);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $params,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    ]);

    $response   = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($curl_error) {
        logAction('Ecompag: falha de comunicação', ['error' => $curl_error]);
        return ['success' => false, 'message' => 'Ecompag: Falha de comunicação — ' . $curl_error];
    }

    $data = json_decode($response, true);
    logAction('Ecompag: resposta', ['http_code' => $http_code, 'response' => $data]);

    if (isset($data['statusCode']) && $data['statusCode'] === 200) {
        return [
            'success'                => true,
            'gateway_transaction_id' => $data['transactionId'] ?? $transacao_id,
            'message'                => 'Saque processado via Ecompag com sucesso',
            'response_data'          => $data
        ];
    }

    $error_msg = $data['message'] ?? ('Erro desconhecido (HTTP ' . $http_code . ')');
    return [
        'success'       => false,
        'message'       => 'Ecompag: ' . $error_msg,
        'response_data' => $data,
        'raw_response'  => $response
    ];
}

// ── Processamento principal ───────────────────────────────────

if (!isset($_GET['id'])) {
    sendResponse(false, 'ID da transação não informado', null, 400);
}

$id            = PHP_SEGURO($_GET['id']);
$usuario_param = isset($_GET['usuario']) ? PHP_SEGURO($_GET['usuario']) : null;

// Buscar dados do saque
$stmt = $mysqli->prepare(
    "SELECT ss.valor, ss.pix, ss.tipo, ss.id_user FROM solicitacao_saques ss WHERE ss.transacao_id = ?"
);
if (!$stmt) sendResponse(false, 'Erro ao preparar consulta', null, 500);
$stmt->bind_param("s", $id);
$stmt->execute();
$stmt->bind_result($valor, $chavepix1, $tipo_chave_db, $id_user_saque);
$stmt->fetch();
$stmt->close();

if (!$valor || !$chavepix1) {
    sendResponse(false, 'Saque não encontrado', null, 404);
}

// Buscar CPF e nome do usuário (obrigatório para Ecompag)
$cpf_usuario  = '';
$nome_usuario = '';
if ($id_user_saque) {
    $stmt_u = $mysqli->prepare("SELECT cpf, real_name FROM usuarios WHERE id = ? LIMIT 1");
    $stmt_u->bind_param("i", $id_user_saque);
    $stmt_u->execute();
    $stmt_u->bind_result($cpf_usuario, $nome_usuario);
    $stmt_u->fetch();
    $stmt_u->close();
}

// Resolver chave PIX
$chavepix2 = localizarchavepix($chavepix1);
$chavepix  = $chavepix2['chave']    ?? $chavepix1;
$nome_real = $chavepix2['realname'] ?: $nome_usuario;

if (identificarTipoChavePix($chavepix) === 'invalid') {
    sendResponse(false, 'Chave PIX inválida', null, 400);
}

// ── MODO MANUAL: marca como pago sem chamar nenhuma API ──
if (isset($_GET['manual']) && $_GET['manual'] == '1') {
    $stmt_m = $mysqli->prepare(
        "UPDATE solicitacao_saques SET status = 1, data_att = CONVERT_TZ(NOW(), '+00:00', '-03:00') WHERE transacao_id = ?"
    );
    $stmt_m->bind_param("s", $id);
    $stmt_m->execute();
    if ($stmt_m->affected_rows > 0) {
        logAction('Saque marcado como pago manualmente', ['transacao_id' => $id]);
        sendResponse(true, 'Saque marcado como pago manualmente', ['transaction_id' => $id]);
    } else {
        sendResponse(false, 'Erro ao atualizar status (manual)', null, 500);
    }
}

// ── MODO ECOMPAG: chamar API ──────────────────────────────────
$cred = getEcompagCredentials();
if (!$cred) {
    sendResponse(false, 'Ecompag não configurado ou desativado', null, 500);
}

$result = processEcompag($cred, $id, $valor, $chavepix, $cpf_usuario, $nome_real);

if (!$result['success']) {
    logAction('Ecompag falhou', ['transacao_id' => $id, 'error' => $result['message']]);
    sendResponse(false, $result['message'], ['response' => $result['response_data'] ?? null], 500);
}

// Atualizar banco
$stmt_up = $mysqli->prepare(
    "UPDATE solicitacao_saques SET status = 1, data_att = CONVERT_TZ(NOW(), '+00:00', '-03:00') WHERE transacao_id = ?"
);
$stmt_up->bind_param("s", $id);
$stmt_up->execute();

if ($stmt_up->affected_rows > 0) {
    logAction('Saque aprovado com sucesso', [
        'transacao_id'           => $id,
        'gateway_transaction_id' => $result['gateway_transaction_id']
    ]);
    sendResponse(true, 'Saque aprovado via Ecompag!', [
        'transaction_id'         => $id,
        'gateway_transaction_id' => $result['gateway_transaction_id'],
        'valor'                  => 'R$ ' . number_format($valor, 2, ',', '.')
    ]);
} else {
    sendResponse(false, 'Erro ao atualizar status no banco', null, 500);
}