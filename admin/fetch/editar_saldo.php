<?php
// Incluir a conexão com o banco de dados
include '../services/database.php';
include '../services/crud.php';

include_once '../services/checa_login_adm.php';
include_once "../services/CSRF_Protect.php";
$csrf = new CSRF_Protect();
#======================================#
#expulsa user
checa_login_adm();

if (!isset($_SESSION['data_adm']) || empty($_SESSION['data_adm']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// Função para registrar mensagens de log
// (A função logMessage deve estar definida em crud.php)

// Verificar se os dados foram recebidos via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logMessage("Recebendo dados via POST.");

    // Obter os dados enviados
    $data = json_decode(file_get_contents('php://input'), true);
    logMessage("Dados recebidos: " . print_r($data, true));

    if (isset($data['user_id'])) {
        $user_id = intval($data['user_id']);
        $adicionar = floatval($data['adicionar']) ?? 0;
        $remover = floatval($data['remover']) ?? 0;

        // Consulta para obter o saldo atual do usuário
        $query = "SELECT saldo FROM usuarios WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $resposta = $stmt->get_result();
        $usuario = $resposta->fetch_assoc();

        if (!$usuario) {
            logMessage("Usuário não encontrado: ID $user_id");
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }

        $saldoAtual = $usuario['saldo'];
        logMessage("Saldo atual do usuário: " . $saldoAtual);

        // Atualizar saldo - adicionar ou remover
        if ($adicionar > 0) {
            $novoSaldo = $saldoAtual + $adicionar;

            $data_time = date('Y-m-d H:i:s'); // Obtém a data e hora atual
            adicao_saldo($user_id, $adicionar, 'adicao', $data_time);

            logMessage("Adicionando $adicionar ao saldo do usuário $user_id. Novo saldo: $novoSaldo");
        } elseif ($remover > 0) {
            if ($saldoAtual >= $remover) {

                $novoSaldo = $saldoAtual - $remover;

                $data_time = date('Y-m-d H:i:s'); // Obtém a data e hora atual
                adicao_saldo($user_id, $remover, 'remocao', $data_time);

                logMessage("Removendo $remover do saldo do usuário $user_id. Novo saldo: $novoSaldo");
            } else {

                logMessage("Erro: Saldo insuficiente para remover $remover do usuário $user_id.");
                echo json_encode(['success' => false, 'message' => 'Saldo insuficiente.']);
                exit;
            }
        } else {

            echo json_encode(['success' => false, 'message' => 'Valor inválido para adição ou remoção de saldo.']);
            exit;
        }

        // Atualizar o saldo no banco de dados
        $query = "UPDATE usuarios SET saldo = ? WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("di", $novoSaldo, $user_id);

        if ($stmt->execute()) {
            logMessage("Saldo atualizado com sucesso para o usuário $user_id.");
            echo json_encode(['success' => true, 'message' => 'Saldo atualizado com sucesso.']);
        } else {
            logMessage("Erro ao atualizar o saldo para o usuário $user_id: " . $stmt->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o saldo.']);
        }
    } else {
        logMessage("Dados inválidos fornecidos.");
        echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
    }
} else {
    logMessage("Método inválido de requisição.");
    echo json_encode(['success' => false, 'message' => 'Método inválido de requisição.']);
}

// Fechar a conexão com o banco de dados
$mysqli->close();
