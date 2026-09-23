<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();

include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/checa_login_adm.php');

header('Content-Type: application/json');

checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit;
}

$saque_id = isset($_POST['saque_id']) ? (int)$_POST['saque_id'] : 0;

if ($saque_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de saque inválido.']);
    exit;
}

// Buscar dados do saque
$stmt = $mysqli->prepare(
    "SELECT id, id_user, valor, status FROM solicitacao_saques WHERE id = ? LIMIT 1"
);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Erro ao preparar consulta.']);
    exit;
}
$stmt->bind_param("i", $saque_id);
$stmt->execute();
$stmt->bind_result($sid, $id_user, $valor, $status_atual);
$stmt->fetch();
$stmt->close();

if (!$sid) {
    echo json_encode(['success' => false, 'message' => 'Saque não encontrado.']);
    exit;
}

// Só permite estornar saques pendentes/em processamento (status 0, 3, 4)
if (!in_array($status_atual, [0, 3, 4])) {
    echo json_encode(['success' => false, 'message' => 'Este saque não pode ser estornado (status atual: ' . $status_atual . ').']);
    exit;
}

$mysqli->begin_transaction();

try {
    // 1) Devolver saldo ao usuário
    $stmt_saldo = $mysqli->prepare(
        "UPDATE usuarios SET saldo = saldo + ? WHERE id = ?"
    );
    if (!$stmt_saldo) throw new Exception('Erro ao preparar UPDATE saldo.');
    $stmt_saldo->bind_param("di", $valor, $id_user);
    if (!$stmt_saldo->execute()) throw new Exception('Erro ao devolver saldo: ' . $stmt_saldo->error);
    $stmt_saldo->close();

    // 2) Cancelar o saque (status = 2)
    $stmt_cancel = $mysqli->prepare(
        "UPDATE solicitacao_saques SET status = 2, data_att = CONVERT_TZ(NOW(), '+00:00', '-03:00') WHERE id = ?"
    );
    if (!$stmt_cancel) throw new Exception('Erro ao preparar UPDATE saque.');
    $stmt_cancel->bind_param("i", $saque_id);
    if (!$stmt_cancel->execute()) throw new Exception('Erro ao cancelar saque: ' . $stmt_cancel->error);
    $stmt_cancel->close();

    // 3) Registrar na tabela adicao_saldo (auditoria)
    $tipo = 'estorno_saque';
    $data_reg = date('Y-m-d H:i:s');
    $stmt_audit = $mysqli->prepare(
        "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)"
    );
    if ($stmt_audit) {
        $stmt_audit->bind_param("idss", $id_user, $valor, $tipo, $data_reg);
        $stmt_audit->execute();
        $stmt_audit->close();
    }

    $mysqli->commit();

    error_log("[ESTORNO_SAQUE] Saque ID=$saque_id estornado. User=$id_user, Valor=$valor, Admin=" . ($_SESSION['data_adm']['id'] ?? 'N/A'));

    echo json_encode([
        'success' => true,
        'message' => 'Saque estornado! R$ ' . number_format($valor, 2, ',', '.') . ' devolvido ao usuário.'
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("[ESTORNO_SAQUE] ERRO: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao estornar: ' . $e->getMessage()]);
}
