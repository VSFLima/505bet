<?php
session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');

# Expulsa usuário
checa_login_adm();

# Função para atualizar/inserir provedores no banco de dados
function att_providers($code, $name, $type) {
    global $mysqli;
    
    // Verificar se o provedor já existe no banco de dados
    $query = "SELECT * FROM provedores WHERE code=? AND name=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $code, $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // O provedor já existe, então atualizamos o status se necessário
        $sql = "UPDATE provedores SET status=?, type=? WHERE code=? AND name=?";
        $stmt = $mysqli->prepare($sql);
        $providerStatus = 1; // Pode ajustar conforme necessário
        $stmt->bind_param("isss", $providerStatus, $type, $code, $name);
        if ($stmt->execute()) {
            return 1; // Sucesso
        } else {
            return 0; // Falha na atualização
        }
    } else {
        // O provedor não existe, então inserimos no banco de dados
        $sql = "INSERT INTO provedores (code, name, type, status) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $providerStatus = 1; // Pode ajustar conforme necessário
        $stmt->bind_param("sssi", $code, $name, $type, $providerStatus);
        if ($stmt->execute()) {
            return 1; // Sucesso
        } else {
            return 0; // Falha na inserção
        }
    }
}

# Função para obter a lista de provedores
function obterListaProvedores($data_fiverscan) {
    $postData = [
        "method" => "provider_list",
        'agent_code' => 'chinesa', 
        'agent_token' => 'chinesa-tk:f2a75869-e094-4a86-819d-d59fb32814d9'
    ];

    // Converter os dados para o formato JSON
    $jsonData = json_encode($postData);

    // Configurar o cabeçalho da solicitação
    $headerArray = ['Content-Type: application/json'];

    // Iniciar a sessão cURL
    $ch = curl_init();

    // Configurar as opções da sessão cURL
    curl_setopt($ch, CURLOPT_URL, $data_fiverscan['url']);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executar a solicitação cURL e obter a resposta
    $response = curl_exec($ch);

    // Verificar se ocorreu algum erro durante a solicitação
    if ($response === false) {
        die('Erro ao fazer a solicitação: ' . curl_error($ch));
    }

    // Fechar a sessão cURL
    curl_close($ch);

    // Retornar os dados brutos da resposta da API
    return $response;
}

# Capta dados do formulário (se necessário)
if (isset($_POST['_csrf'])) {
    // Chamar a função para obter a lista de provedores
    $responseData = obterListaProvedores($data_fiverscan);

    // Decodificar a resposta JSON
    $data = json_decode($responseData, true);

    // Verificar se a decodificação foi bem-sucedida
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        die('Erro na decodificação JSON: ' . json_last_error_msg());
    }

    // Verificar o status da resposta
    if ($data['status'] == 1) {
        // Se a resposta for bem-sucedida, obter os fornecedores
        $vendors = $data['providers'];
        $count = 0;
        $success_count = 0;
    
        // Iterar sobre os fornecedores e acessar os dados
        foreach ($vendors as $vendor) {
            $vendorCode = $vendor['code']; // Código do fornecedor
            $vendorName = $vendor['name']; // Nome do fornecedor
            $type = ($vendor['gameType'] == 1) ? 'slot' : 'live'; // Tipo de jogo do fornecedor
            $count++;
        
            // Se o nome do fornecedor estiver em coreano, use o vendorCode no lugar do vendorName
            if (preg_match('/[\x{1100}-\x{11FF}\x{3130}-\x{318F}\x{AC00}-\x{D7A3}]/u', $vendorName)) {
                $vendorName = $vendorCode;
        
                // Se o nome do fornecedor contiver "_", remova-o e tudo o que vier depois
                if (strpos($vendorName, '_') !== false) {
                    $vendorName = strstr($vendorName, '_', true);
                }
            }
        
            // Chamar a função para inserir/atualizar o provedor no banco de dados
            $success_count += att_providers($vendorCode, $vendorName, $type);
        }

        
        
        if ($count == $success_count) {
            echo "<div class='alert alert-success' role='alert'><i class='fa fa-check-circle'></i> Dados atualizados com sucesso.</div><script>  setTimeout('window.location.href=\"".$painel_adm_provedores_games."\";', 3000); </script>";
        } else {
            echo "<div class='alert alert-warning' role='alert'><i class='fa fa-exclamation-circle'></i> Houve um problema ao atualizar os dados dos provedores.</div><script>  setTimeout('window.location.href=\"".$painel_adm_provedores_games."\";', 3000); </script>";
        }
    } else {
        echo "<div class='alert alert-warning' role='alert'><i class='fa fa-exclamation-circle'></i> Revise seus dados de Api..</div><script>  setTimeout('window.location.href=\"".$painel_adm_provedores_games."\";', 3000); </script>";
    }
}
?>
