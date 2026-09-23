<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud-adm.php');
include_once('../services/crud.php');
include_once('../logs/registrar_logs.php');
include_once('../services/checa_login_adm.php');

checa_login_adm();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$statusRaw = isset($_POST['status']) ? trim(strtolower($_POST['status'])) : '';

if ($id <= 0 || $statusRaw === '') {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit;
}

// Mapeia valores enviados no select para códigos internos
// Aceita termos em PT/EN para robustez e compatibilidade
$map = [
    // aprovado
    'aprovado' => 1, 'aprovada' => 1, 'approve' => 1, 'approved' => 1, 'success' => 1,
    // recusado
    'recusado' => 2, 'recusada' => 2, 'refuse' => 2, 'rejected' => 2, 'reject' => 2,
    // em processamento
    'processando' => 3, 'em processamento' => 3, 'em_processamento' => 3, 'processing' => 3, 'lock' => 3,
    // aceitando
    'aceitando' => 4, 'accepting' => 4,
    // pendente
    'pendente' => 0, 'pendente/analise' => 0, 'analise' => 0, 'em analise' => 0, 'apply' => 0
];

// Normaliza espaços extras
$statusKey = preg_replace('/\s+/', ' ', $statusRaw);
$codigoStatus = $map[$statusKey] ?? null;

if ($codigoStatus === null) {
    echo json_encode(['success' => false, 'message' => 'Status inválido']);
    exit;
}

// Atualiza registro
$dataAgora = date('Y-m-d H:i:s');

if (!$stmt = $mysqli->prepare("UPDATE solicitacao_saques SET status = ?, data_att = ? WHERE id = ?")) {
    echo json_encode(['success' => false, 'message' => 'Falha ao preparar atualização']);
    exit;
}

$stmt->bind_param('isi', $codigoStatus, $dataAgora, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    $emailAdm = $_SESSION['data_adm']['email'] ?? 'admin';
    registrarLog($mysqli, $emailAdm, 'Atualizou status do saque ' . $id . ' para ' . $codigoStatus);
    echo json_encode(['success' => true, 'message' => 'Status atualizado com sucesso']);
} else {
    echo json_encode(['success' => false, 'message' => 'Não foi possível atualizar o status']);
}

exit;
?>

