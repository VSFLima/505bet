<?php
/**
 * Arquivo: ajax/toggle_theme_natal.php
 * Endpoint AJAX para alternar o status do Tema Natal
 */

session_start();

// Verifica se está logado
include_once "../services/checa_login_adm.php";
checa_login_adm();

// Include apenas o database
include_once __DIR__ . "/../services/database.php";

// Verifica se é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

/**
 * Busca o status atual do tema natal
 */
function buscar_status_tema_natal() {
    global $mysqli;
    
    try {
        $query = "SELECT natal_theme_active FROM config LIMIT 1";
        $result = $mysqli->query($query);
        
        if ($result && $row = $result->fetch_assoc()) {
            return (bool) $row['natal_theme_active'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar status tema natal: " . $e->getMessage());
    }
    
    return false; // Padrão inativo se não existir a coluna
}

/**
 * Atualiza o status do tema natal
 */
function atualizar_status_tema_natal($ativo) {
    global $mysqli;
    
    try {
        $status = $ativo ? 1 : 0;
        $query = "UPDATE config SET natal_theme_active = ?";
        $stmt = $mysqli->prepare($query);
        
        if (!$stmt) {
            // Verifica se o erro é de coluna inexistente (1054)
            if ($mysqli->errno == 1054) {
                // Tenta criar a coluna
                $mysqli->query("ALTER TABLE config ADD COLUMN natal_theme_active TINYINT(1) DEFAULT 0");
                
                // Tenta preparar novamente
                $stmt = $mysqli->prepare($query);
                if (!$stmt) {
                    throw new Exception("Erro ao preparar query após adicionar coluna: " . $mysqli->error);
                }
            } else {
                throw new Exception("Erro ao preparar query: " . $mysqli->error);
            }
        }
        
        $stmt->bind_param("i", $status);
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Erro ao atualizar status tema natal: " . $e->getMessage());
        return false;
    }
}

// Busca o status atual e inverte
$status_atual = buscar_status_tema_natal();
$novo_status = !$status_atual;

// Atualiza no banco
header('Content-Type: application/json');

if (atualizar_status_tema_natal($novo_status)) {
    echo json_encode([
        'success' => true,
        'ativo' => $novo_status,
        'message' => $novo_status ? 'Tema Natal ativado com sucesso!' : 'Tema Natal desativado com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao atualizar. Verifique se a coluna natal_theme_active existe na tabela config.'
    ]);
}
?>