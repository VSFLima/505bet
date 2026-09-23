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


header('Content-Type: application/json'); // Para enviar uma resposta JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $data['usuario_id'];

    // Verifique se o ID do usuário é válido
    if (!empty($usuario_id) && is_numeric($usuario_id)) {
        $query_excluir = "UPDATE usuarios SET invitation_code = NULL WHERE id = $usuario_id";

        if (mysqli_query($mysqli, $query_excluir)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($mysqli)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de usuário inválido.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}

?>