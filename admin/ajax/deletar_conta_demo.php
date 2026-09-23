<?php
session_start();
include_once "../services/checa_login_adm.php";
checa_login_adm();
include_once "../services/database.php";
header('Content-Type: application/json');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Valida ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID não fornecido']);
    exit;
}

$id = intval($_POST['id']);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Verifica se é uma conta demo antes de deletar (aceita: demo, demopg, demoigw)
$check_stmt = $mysqli->prepare("SELECT mobile FROM usuarios WHERE id = ? AND (mobile LIKE 'demo%')");
$check_stmt->bind_param('i', $id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Conta não encontrada ou não é uma conta demo']);
    $check_stmt->close();
    exit;
}

$check_stmt->close();

// Deletar a conta
$stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ? AND (mobile LIKE 'demo%')");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Conta deletada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma conta foi deletada']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao deletar conta: ' . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>