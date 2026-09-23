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

// Verificar se os dados foram recebidos via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter os dados enviados
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['user_id']) && isset($data['action'])) {
        $user_id = intval($data['user_id']); // Certifique-se de que o ID do usuário é seguro
        $action = $data['action']; // 'banir' ou 'desbanir'

        // Definir a query com base na ação
        if ($action === 'banir') {
            $query = "UPDATE usuarios SET banido = 1 WHERE id = ?";
        } else if ($action === 'desbanir') {
            $query = "UPDATE usuarios SET banido = 0 WHERE id = ?";
        } else {
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            exit;
        }

        // Preparar a query de atualização
        if ($stmt = $mysqli->prepare($query)) {
            // Vincular o parâmetro
            $stmt->bind_param('i', $user_id);

            // Executar a query
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    if ($action === 'banir') {
                        echo json_encode(['success' => true, 'message' => 'Usuário banido com sucesso.']);
                    } else {
                        echo json_encode(['success' => true, 'message' => 'Usuário desbanido com sucesso.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Nenhuma alteração feita.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao processar a solicitação.']);
            }

            // Fechar o statement
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos fornecidos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido de requisição.']);
}

// Fechar a conexão com o banco de dados
$mysqli->close();
?>
