<?php
/**
 * Arquivo: ajax/toggle_navbar.php
 * Endpoint AJAX para alternar o status do Menu Navbar
 */

session_start();

// Verifica se está logado
include_once "../services/checa_login_adm.php";
checa_login_adm();

// Include apenas o database
include_once "../services/database.php";

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

/**
 * Busca o status atual do menu navbar
 */
function buscar_status_menu_navbar() {
    global $mysqli;
    
    try {
        $query = "SELECT menu_navbar_ativo FROM config LIMIT 1";
        $result = $mysqli->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            return (bool) $row['menu_navbar_ativo'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar status menu navbar: " . $e->getMessage());
    }
    
    return true;
}

/**
 * Atualiza o status do menu navbar
 */
function atualizar_status_menu_navbar($ativo) {
    global $mysqli;
    
    try {
        $status = $ativo ? 1 : 0;
        $query = "UPDATE config SET menu_navbar_ativo = ?";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Erro ao preparar query: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $status);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Erro ao atualizar status menu navbar: " . $e->getMessage());
        return false;
    }
}

// Busca o status atual e inverte
$status_atual = buscar_status_menu_navbar();
$novo_status = !$status_atual;

// Atualiza no banco
header('Content-Type: application/json');

if (atualizar_status_menu_navbar($novo_status)) {
    echo json_encode([
        'success' => true,
        'ativo' => $novo_status,
        'message' => $novo_status ? 'Menu Navbar ativado com sucesso!' : 'Menu Navbar desativado com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar. Verifique se a coluna menu_navbar_ativo existe na tabela config.'
    ]);
}
?>