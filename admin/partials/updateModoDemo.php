<?php
// updateModoDemo.php

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

if (!isset($data['modo_demo']) || !isset($data['mobile'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos: modo_demo ou mobile não fornecidos.']);
    exit;
}

$modo_demo = intval($data['modo_demo']); // Deve ser 0 ou 1
$mobile = $data['mobile']; // Será usado como user_code

// Validação: modo_demo deve ser 0 ou 1
if ($modo_demo !== 0 && $modo_demo !== 1) {
    echo json_encode(['success' => false, 'message' => 'Valor de Modo Demo inválido.']);
    exit;
}

// Define is_demo igual a modo_demo (0 ou 1)
$is_demo = $modo_demo;

// Consulta a tabela igamewin para obter os dados da API
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

// Prepara os dados para a chamada à API iGameWin para definir o modo demo
$apiData = [
    "method"       => "set_demo",
    "agent_code"   => $agent_code,
    "agent_token"  => $agent_token,
    "user_code"    => $mobile,
    "is_demo"      => $is_demo
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

$apiResponseData = json_decode($apiResponse, true);

// Se a API retornar erro, capturamos a mensagem
$apiErrorMsg = null;
if (!isset($apiResponseData['status']) || intval($apiResponseData['status']) !== 1) {
    $apiErrorMsg = isset($apiResponseData['msg']) ? $apiResponseData['msg'] : 'Resposta inválida da API';
}

// Se a API retornar "INVALID_USER", interrompe a atualização local
if ($apiErrorMsg !== null && strtoupper(trim($apiErrorMsg)) === 'INVALID_USER') {
    echo json_encode(['success' => false, 'message' => 'O usuário ainda não fez nenhuma bet.']);
    exit;
}

// Atualiza o valor de modo_demo no banco de dados
$stmt = $mysqli->prepare("UPDATE usuarios SET modo_demo = ? WHERE mobile = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro no prepare: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('is', $modo_demo, $mobile);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $stmt->error]);
    exit;
}

echo json_encode([
    'success'     => true,
    'message'     => 'Modo Demo atualizado com sucesso.',
    'modo_demo'   => $modo_demo,
    'apiResponse' => $apiResponseData
]);
exit;
?>