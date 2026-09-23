<?php
/**
 * PAYMENT AUTO — Ecompag
 * Saque automático via Ecompag API v2
 * Limite diário configurável em config.limite_saque
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php');

function sendError($msg) {
    error_log("[PAYMENT_AUTO_ECOMPAG] " . $msg . ' IP: ' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    echo $msg;
    exit;
}

function logAction($message, $data = []) {
    $log = date('Y-m-d H:i:s') . " - $message - " . json_encode($data, JSON_UNESCAPED_UNICODE);
    error_log("[PAYMENT_AUTO_ECOMPAG] " . $log);
}

function identificarTipoChavePix($chavepix) {
    if (preg_match('/^\d{10,11}$/', $chavepix)) {
        $cpf = preg_replace('/[^0-9]/is', '', $chavepix);
        if (strlen($cpf) == 11) return 'document';
        return 'phoneNumber';
    } elseif (preg_match('/^\d{14}$/', $chavepix)) return 'document';
    elseif (filter_var($chavepix, FILTER_VALIDATE_EMAIL)) return 'email';
    elseif (preg_match('/^[0-9a-f]{32}$/i', $chavepix)) return 'randomKey';
    return 'invalid';
}

// ── Assinatura interna ────────────────────────────────────────
$company    = 'NEXTSISTEMAS';
$owner      = 'YARKAN';
$pepper     = 'NXTSYS-YRK-PIX-AUTO-V1';
$secret_key = hash_hmac('sha256', $company . '|' . $owner, $pepper);

$chavepix1        = $_POST['chavepix']  ?? '';
$valor            = $_POST['valor']     ?? '';
$id               = $_POST['id']        ?? '';
$usuario          = $_POST['usuario']   ?? '';
$assinatura       = $_POST['ASSINATURA_NEXTSISTEMAS_YARKAN'] ?? '';

if (!$chavepix1) sendError("Campo obrigatório ausente: chavepix");
if (!$valor)     sendError("Campo obrigatório ausente: valor");
if (!$id)        sendError("Campo obrigatório ausente: id");
if (!$assinatura) sendError("Campo obrigatório ausente: assinatura");

$payload           = $chavepix1 . '|' . $valor . '|' . $id;
$assinatura_esperada = hash_hmac('sha256', $payload, $secret_key);
if (!hash_equals($assinatura_esperada, $assinatura)) {
    sendError("Requisição não autorizada: assinatura inválida.");
}

// Anti-replay
$filename  = 'used_ids.json';
$used_ids  = [];
if (file_exists($filename)) {
    $fc = file_get_contents($filename);
    if ($fc) $used_ids = json_decode($fc, true);
}
if (in_array($id, $used_ids)) sendError("Anti-fraude: ID já processado.");
$used_ids[] = $id;
file_put_contents($filename, json_encode($used_ids, JSON_PRETTY_PRINT));

$valor = floatval($valor);
if ($valor <= 0) sendError("Valor inválido.");

// ── Configurações ─────────────────────────────────────────────
$res_cfg = $mysqli->query("SELECT saque_automatico, limite_saque FROM config WHERE id = 1");
$cfg     = $res_cfg ? $res_cfg->fetch_assoc() : [];

// Limite de valor por saque automático
if ($valor > ($cfg['saque_automatico'] ?? 0)) {
    sendError("Valor R$ " . number_format($valor, 2, ',', '.') . " acima do limite automático (R$ " . number_format($cfg['saque_automatico'], 2, ',', '.') . ").");
}

// Limite diário de saques automáticos
$limite_diario = (int)($cfg['limite_saque'] ?? 100);
$res_hoje      = $mysqli->query(
    "SELECT COUNT(*) as c FROM solicitacao_saques WHERE status = 1 AND DATE(data_att) = CURDATE() AND tipo_saque = 0"
);
$count_hoje = (int)($res_hoje ? $res_hoje->fetch_assoc()['c'] : 0);
if ($count_hoje >= $limite_diario) {
    sendError("Limite diário de saques automáticos atingido ($count_hoje/$limite_diario).");
}

// ── Buscar CPF e nome do usuário ──────────────────────────────
$cpf_usuario  = '';
$nome_usuario = '';
if ($usuario) {
    $stmt_u = $mysqli->prepare("SELECT cpf, real_name FROM usuarios WHERE mobile = ? OR id = ? LIMIT 1");
    $stmt_u->bind_param("si", $usuario, $usuario);
    $stmt_u->execute();
    $stmt_u->bind_result($cpf_usuario, $nome_usuario);
    $stmt_u->fetch();
    $stmt_u->close();
}

// ── Resolver chave PIX ────────────────────────────────────────
$chavepix2 = localizarchavepix($chavepix1);
if (!$chavepix2) $chavepix2 = localizarchavepix2($chavepix1);
$chavepix  = $chavepix2['chave']    ?? $chavepix1;
$nome_real = $chavepix2['realname'] ?: $nome_usuario;

if (identificarTipoChavePix($chavepix) === 'invalid') sendError("Chave Pix inválida.");

// ── Buscar credenciais Ecompag ────────────────────────────────
$res_cred = $mysqli->query("SELECT client_id, client_secret FROM bspay WHERE id = 1 AND ativo = 1 LIMIT 1");
if (!$res_cred || $res_cred->num_rows === 0) {
    sendError("Ecompag não configurado ou desativado.");
}
$cred = $res_cred->fetch_assoc();
if (empty($cred['client_id']) || empty($cred['client_secret'])) {
    sendError("Credenciais Ecompag incompletas.");
}

// ── Chamar API Ecompag ────────────────────────────────────────
$cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_usuario);

$params = http_build_query([
    'client_id'     => $cred['client_id'],
    'client_secret' => $cred['client_secret'],
    'nome'          => $nome_real ?: 'Cliente',
    'cpf'           => $cpf_limpo,
    'valor'         => number_format($valor, 2, '.', ''),
    'chave_pix'     => $chavepix,
    'descricao'     => 'Saque Auto - ID: ' . $id,
    'urlnoty'       => ''
]);

logAction('Ecompag: enviando saque automático', [
    'id'       => $id,
    'valor'    => $valor,
    'chavepix' => $chavepix,
    'cpf'      => $cpf_limpo,
    'nome'     => $nome_real,
    'saques_hoje' => "$count_hoje/$limite_diario"
]);

$curl = curl_init('https://api.ecompag.com/v2/pix/payment.php');
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
    sendError("Erro de comunicação com Ecompag: $curl_error");
}

$data = json_decode($response, true);
logAction('Ecompag: resposta', ['http_code' => $http_code, 'response' => $data]);

if (!isset($data['statusCode']) || $data['statusCode'] !== 200) {
    $msg = $data['message'] ?? "Erro desconhecido (HTTP $http_code)";
    logAction('Ecompag: saque recusado', ['id' => $id, 'msg' => $msg]);
    sendError("Ecompag recusou o pagamento: $msg");
}

// ── Sucesso: atualizar banco ──────────────────────────────────
logAction('Ecompag: saque aprovado', [
    'id'             => $id,
    'transactionId'  => $data['transactionId'] ?? 'N/A'
]);

echo "Pagamento realizado com sucesso";

if ($mysqli) $mysqli->close();