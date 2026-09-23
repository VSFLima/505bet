<?php
// updateRtp.php

header('Content-Type: application/json');

// Permite apenas requisições POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Inicia a sessão e verifica se o usuário está autenticado
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['data_adm'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Inclui o arquivo de conexão com o banco de dados
include_once __DIR__ . '/../services/database.php';

// Obtém e decodifica o JSON recebido
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['rtp'])) {
    echo json_encode(['success' => false, 'message' => 'Valor RTP não fornecido.']);
    exit;
}

$rtp = intval($data['rtp']);

// Validação: RTP deve estar entre 10 e 90 e ser múltiplo de 5
if ($rtp < 10 || $rtp > 90 || $rtp % 5 !== 0) {
    echo json_encode(['success' => false, 'message' => 'Valor de RTP inválido.']);
    exit;
}

// Consulta a tabela igamewin para obter os dados necessários para a chamada da API
$query  = "SELECT * FROM igamewin WHERE ativo = 1 LIMIT 1";
$result = mysqli_query($mysqli, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    echo json_encode(['success' => false, 'message' => 'Registro igamewin não encontrado ou inativo.']);
    exit;
}

$row = mysqli_fetch_assoc($result);
$igamewin_url = $row['url'];
$agent_code   = $row['agent_code'];
$agent_token  = $row['agent_token'];

// Prepara os dados para a chamada à API iGameWin
$apiData = [
    "method"       => "control_rtp",
    "agent_code"   => $agent_code,
    "agent_token"  => $agent_token,
    "rtp"          => $rtp
];

// Inicializa o cURL para chamada da API
$ch = curl_init($igamewin_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));

$apiResponse = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError   = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode([
        'success'    => false,
        'message'    => 'Erro na chamada da API iGameWin. Código HTTP: ' . $httpCode,
        'curl_error' => $curlError
    ]);
    exit;
}

// Decodifica a resposta da API (se necessário)
$apiResponseData = json_decode($apiResponse, true);

// Atualiza o valor de RTP no banco de dados da igamewin
$stmt = $mysqli->prepare("UPDATE igamewin SET rtp = ? WHERE ativo = 1 LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro no prepare (igamewin): ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('i', $rtp);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar igamewin: ' . $stmt->error]);
    exit;
}

$stmt2 = $mysqli->prepare("UPDATE usuarios SET rtp = ?");
if (!$stmt2) {
    echo json_encode(['success' => false, 'message' => 'Erro no prepare (usuarios): ' . $mysqli->error]);
    exit;
}
$stmt2->bind_param('i', $rtp);
if (!$stmt2->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar usuários: ' . $stmt2->error]);
    exit;
}

echo json_encode([
    'success'     => true,
    'message'     => 'RTP atualizado globalmente com sucesso.',
    'rtp'         => $rtp,
    'apiResponse' => $apiResponseData
]);
exit;
?>