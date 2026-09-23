<?php
session_start();
include_once "../services/database.php";
include_once "../services/funcao.php";
include_once '../services/checa_login_adm.php';
checa_login_adm();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] == 'update_field') {
        $game_id = intval($_POST['game_id']);
        $field = $_POST['field'];
        $value = intval($_POST['value']);
        
        $allowed_fields = ['status', 'popular'];
        if (!in_array($field, $allowed_fields)) {
            echo json_encode(['success' => false, 'message' => 'Campo inválido']);
            exit;
        }
        
        $sql = "UPDATE games SET `$field` = ? WHERE id = ?";
        $qry = $mysqli->prepare($sql);
        
        if (!$qry) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar query: ' . $mysqli->error]);
            exit;
        }
        
        $qry->bind_param("ii", $value, $game_id);
        
        if ($qry->execute()) {
            $field_name = $field == 'status' ? 'Status' : 'Popular';
            $status_text = $value == 1 ? 'ativado' : 'desativado';
            echo json_encode([
                'success' => true, 
                'message' => "$field_name $status_text com sucesso!"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $qry->error]);
        }
        
        $qry->close();
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
?>