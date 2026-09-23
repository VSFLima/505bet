<?php
// updateDemoPGClone.php
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

if (!isset($data['mobile']) || !isset($data['modo_demo'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos: mobile ou modo_demo não fornecidos.']);
    exit;
}

$mobile = $data['mobile'];
$modo_demo = intval($data['modo_demo']); // 0 ou 1

// Validação: modo_demo deve ser 0 ou 1
if ($modo_demo !== 0 && $modo_demo !== 1) {
    echo json_encode(['success' => false, 'message' => 'Valor de Modo Demo inválido.']);
    exit;
}

// Ativar ou desativar influencer na PGClone
try {
    if ($modo_demo === 1) {
        // Ativar influencer
        $pgclone_result = registrarInfluencerPGClone($mobile, 1);
        
        echo json_encode([
            'success' => true,
            'message' => 'Influencer ativado com sucesso na PGClone',
            'pgclone_status' => 'ativo',
            'pgclone_data' => $pgclone_result
        ]);
    } else {
        // Desativar influencer
        $pgclone_result = registrarInfluencerPGClone($mobile, 0);
        
        echo json_encode([
            'success' => true,
            'message' => 'Influencer desativado com sucesso na PGClone',
            'pgclone_status' => 'inativo',
            'pgclone_data' => $pgclone_result
        ]);
    }
    
} catch (Exception $e) {
    $action = $modo_demo === 1 ? 'ativar' : 'desativar';
    
    echo json_encode([
        'success' => false,
        'message' => "Erro ao {$action} influencer na PGClone",
        'pgclone_status' => 'erro',
        'pgclone_error' => $e->getMessage()
    ]);
    
    // Log do erro
    //error_log("Erro ao {$action} influencer na PGClone para $mobile: " . $e->getMessage());
}

exit;

/**
 * Função para registrar/atualizar influencer na PGClone
 * @param string $username - Nome do usuário
 * @param int $isinfluencer - 1 para ativar, 0 para desativar
 */
function registrarInfluencerPGClone($username, $isinfluencer = 1)
{
    global $mysqli;
    
    // Buscar configurações da PGClone
    $config_stmt = $mysqli->prepare("SELECT * FROM pgclone WHERE id = 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();
    
    if (!$config) {
        throw new Exception("Configuração da PGClone não encontrada");
    }
    
    if ($config['ativo'] != 1) {
        throw new Exception("PGClone não está ativa");
    }
    
    // Preparar dados para a API
    $data = [
        'credentials' => [
            'agentCode' => $config['agent_code'],
            'agentToken' => $config['agent_token'],
            'secretKey' => $config['agent_secret']
        ],
        'user' => [
            'username' => $username,
            'isinfluencer' => $isinfluencer
        ]
    ];
    
    $json_data = json_encode($data);
    
    // Obter origin do servidor
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $origin = $protocol . "://" . $host;
    
    $action = $isinfluencer === 1 ? 'ACTIVATION' : 'DEACTIVATION';
    
    // Log da requisição
    //error_log("==================== INFLUENCER {$action} ====================");
    ////error_log("[INFLUENCER] Username: $username");
    ////error_log("[INFLUENCER] Action: " . ($isinfluencer === 1 ? 'Ativar' : 'Desativar'));
    ////error_log("[INFLUENCER] Origin: $origin");
    ////error_log("[INFLUENCER] Endpoint: https://pgclone.com/api/update");
    ////error_log("[INFLUENCER] Request Body: " . $json_data);
    
    // Fazer requisição para PGClone API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://pgclone.com/api/update');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Origin: ' . $origin,
        'Referer: ' . $origin . '/callback',
        'Content-Length: ' . strlen($json_data)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log da resposta
    ////error_log("[INFLUENCER] HTTP Code: $http_code");
    if ($curl_error) {
        ////error_log("[INFLUENCER] cURL Error: $curl_error");
    }
    ////error_log("[INFLUENCER] Response: " . ($response ?: 'Empty'));
    //error_log("==============================================================");
    
    // Processar resposta
    if ($http_code == 200) {
        $data_response = json_decode($response, true);
        
        if (isset($data_response['success']) && $data_response['success'] === true) {
            $message = $isinfluencer === 1 ? 'ativado' : 'desativado';
            return [
                'success' => true,
                'message' => "Influencer {$message} com sucesso na PGClone",
                'data' => $data_response
            ];
        } else {
            $error_msg = $data_response['error'] ?? 'Erro desconhecido';
            throw new Exception("API Error: " . $error_msg);
        }
    } else {
        throw new Exception("HTTP $http_code" . ($curl_error ? " - $curl_error" : "") . " - Response: " . substr($response, 0, 200));
    }
}
?>