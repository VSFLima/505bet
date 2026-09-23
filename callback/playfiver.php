<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* Dependencias Da Api */
include_once "../config.php";
include_once "../" . DASH . "/services-prod/prod.php";
include_once "../" . DASH . "/services/database.php";
include_once "../" . DASH . "/services/funcao.php";
include_once "../" . DASH . "/services/crud.php";

const ENABLE_LOGS = false;
const LOG_FILE = __DIR__ . '/playfiver_webhook.log';

function writeLog($message) {
    if (!ENABLE_LOGS) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
}

function sendErrorResponse($httpCode, $message) {
    http_response_code($httpCode);
    echo json_encode(['msg' => $message]);
    exit;
}

function sendSuccessResponse($data) {
    echo json_encode($data);
    exit;
}

function authenticateAgent($agentCode, $agentSecret) {
    global $mysqli;
    
    $agentCode = trim($agentCode);
    $agentSecret = trim($agentSecret);
    
    if (empty($agentCode) || empty($agentSecret)) {
        writeLog("ERRO PlayFiver: Agent code ou secret ausente");
        return false;
    }
    
    writeLog("Autenticando - agent_code: $agentCode");
    
    $query = "SELECT agent_code, agent_secret FROM playfiver WHERE id = 1 AND ativo = 1";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO SQL prepare: " . $mysqli->error);
        return false;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $dbCode = trim($row['agent_code']);
        $dbSecret = trim($row['agent_secret']);
        
        if ($agentCode === $dbCode && $agentSecret === $dbSecret) {
            writeLog("AUTENTICAÇÃO OK");
            $stmt->close();
            return true;
        }
    }
    
    writeLog("AUTENTICAÇÃO FALHOU");
    $stmt->close();
    return false;
}

function handleBalance($request) {
    global $mysqli;
    
    writeLog("=== BALANCE REQUEST ===");
    
    $userCode = trim($request['user_code'] ?? '');
    writeLog("USER_CODE: $userCode");
    
    if (empty($userCode)) {
        sendErrorResponse(400, 'USER_CODE_REQUIRED');
    }
    
    $query = "SELECT saldo FROM usuarios WHERE mobile = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO SQL: " . $mysqli->error);
        sendErrorResponse(500, 'Erro interno no servidor');
    }
    
    $stmt->bind_param("s", $userCode);
    
    if (!$stmt->execute()) {
        writeLog("ERRO EXECUTE: " . $stmt->error);
        sendErrorResponse(500, 'Erro interno no servidor');
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        writeLog("USUÁRIO NÃO ENCONTRADO: $userCode");
        $stmt->close();
        sendErrorResponse(404, 'Usuário não encontrado');
    }
    
    $row = $result->fetch_assoc();
    $balance = floatval($row['saldo']);
    
    writeLog("SALDO ENCONTRADO: $balance");
    $stmt->close();
    
    return ['msg' => '', 'balance' => $balance];
}

function handleTransaction($request) {
    global $mysqli;
    
    writeLog("=== TRANSACTION REQUEST ===");
    
    $type = trim($request['type'] ?? '');
    $userCode = trim($request['user_code'] ?? '');
    $userBalance = floatval($request['user_balance'] ?? 0);
    $gameType = trim($request['game_type'] ?? 'slot');
    $gameOriginal = $request['game_original'] ?? true;
    
    $slotData = $request[$gameType] ?? [];
    $gameCode = trim($slotData['game_code'] ?? '');
    $bet = floatval($slotData['bet'] ?? 0);
    $win = floatval($slotData['win'] ?? 0);
    $txnId = trim($slotData['txn_id'] ?? '');
    $userAfterBalance = floatval($slotData['user_after_balance'] ?? $userBalance);
    
    writeLog("Type: $type | User: $userCode | Game: $gameCode | Bet: $bet | Win: $win | TxnID: $txnId | AfterBalance: $userAfterBalance");
    
    if (empty($userCode) || empty($txnId)) {
        writeLog("CAMPOS FALTANDO");
        sendErrorResponse(400, 'Campos obrigatórios faltando');
    }
    
    // Buscar usuário
    $query = "SELECT id, saldo, invitation_code FROM usuarios WHERE mobile = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO SQL: " . $mysqli->error);
        sendErrorResponse(500, 'Erro interno');
    }
    
    $stmt->bind_param("s", $userCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        writeLog("USUÁRIO NÃO ENCONTRADO: $userCode");
        $stmt->close();
        sendErrorResponse(404, 'Usuário não encontrado');
    }
    
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    $userId = $userData['id'];
    $currentBalance = floatval($userData['saldo']);
    $invitationCode = $userData['invitation_code'] ?? '';
    
    writeLog("ID: $userId | Saldo Atual: $currentBalance");
    
    // Verificar duplicata
    $queryDup = "SELECT id FROM historico_play WHERE txn_id = ? LIMIT 1";
    $stmtDup = $mysqli->prepare($queryDup);
    $stmtDup->bind_param("s", $txnId);
    $stmtDup->execute();
    $stmtDup->store_result();
    
    if ($stmtDup->num_rows > 0) {
        writeLog("TRANSAÇÃO DUPLICADA: $txnId");
        $stmtDup->close();
        sendSuccessResponse(['msg' => '', 'balance' => $currentBalance]);
    }
    $stmtDup->close();
    
    $newBalance = $userAfterBalance;
    
    if ($newBalance < 0) {
        writeLog("SALDO INSUFICIENTE");
        sendErrorResponse(400, 'Saldo insuficiente');
    }
    
    // Transação
    $mysqli->autocommit(false);
    
    try {
        // Inserir histórico
        $queryInsert = "INSERT INTO historico_play (id_user, nome_game, bet_money, win_money, txn_id, created_at, status_play) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 1)";
        $stmtInsert = $mysqli->prepare($queryInsert);
        
        if (!$stmtInsert) {
            throw new Exception("Erro prepare insert: " . $mysqli->error);
        }
        
        $stmtInsert->bind_param("isdds", $userId, $gameCode, $bet, $win, $txnId);
        
        if (!$stmtInsert->execute()) {
            throw new Exception("Erro insert: " . $stmtInsert->error);
        }
        $stmtInsert->close();
        
        writeLog("HISTÓRICO INSERIDO");
        
        // Atualizar saldo
        $queryUpdate = "UPDATE usuarios SET saldo = ? WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($queryUpdate);
        
        if (!$stmtUpdate) {
            throw new Exception("Erro prepare update: " . $mysqli->error);
        }
        
        $stmtUpdate->bind_param("di", $newBalance, $userId);
        
        if (!$stmtUpdate->execute()) {
            throw new Exception("Erro update: " . $stmtUpdate->error);
        }
        $stmtUpdate->close();
        
        writeLog("SALDO ATUALIZADO: $newBalance");
        
        // Comissão afiliado
        if (!empty($invitationCode) && $gameOriginal) {
            processAffiliateCommission($invitationCode, $bet, $win);
        }
        
        $mysqli->commit();
        writeLog("TRANSAÇÃO CONCLUÍDA COM SUCESSO");
        
        return ['msg' => '', 'balance' => floatval($newBalance)];
        
    } catch (Exception $e) {
        $mysqli->rollback();
        writeLog("ERRO TRANSAÇÃO: " . $e->getMessage());
        sendErrorResponse(500, 'Erro interno no servidor');
    } finally {
        $mysqli->autocommit(true);
    }
}

function processAffiliateCommission($invitationCode, $bet, $win) {
    global $mysqli;
    
    writeLog("Processando comissão: $invitationCode");
    
    $query = "SELECT id, tipo_pagamento FROM usuarios WHERE invite_code = ? LIMIT 1";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        writeLog("ERRO buscar afiliado: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("s", $invitationCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        writeLog("Afiliado não encontrado: $invitationCode");
        $stmt->close();
        return false;
    }
    
    $afiliado = $result->fetch_assoc();
    $stmt->close();
    
    if ($afiliado['tipo_pagamento'] == 2 || $afiliado['tipo_pagamento'] == 0) {
        $data_afiliados = data_afiliados_cpa_rev();
        
        if (isset($data_afiliados['revShareLvl1'])) {
            $porcentagem = $data_afiliados['revShareLvl1'] / 100;
            $ganho_liquido = floatval($win) - floatval($bet);
            $comissao = $ganho_liquido * $porcentagem;
            
            writeLog("Comissão: $comissao (RevShare: {$data_afiliados['revShareLvl1']}%)");
            
            if ($comissao > 0) {
                $updateQuery = "UPDATE usuarios SET saldo = saldo + ?, rev = rev + ?, total_rev = total_rev + ? WHERE invite_code = ?";
                $stmtUpdate = $mysqli->prepare($updateQuery);
                
                if ($stmtUpdate) {
                    $stmtUpdate->bind_param("ddds", $comissao, $comissao, $comissao, $invitationCode);
                    if ($stmtUpdate->execute()) {
                        writeLog("Comissão processada: $comissao");
                    }
                    $stmtUpdate->close();
                }
            }
        }
    }
    
    return true;
}

// ========== MAIN ==========

$rawInput = file_get_contents('php://input');
writeLog("RAW: " . $rawInput);

if (empty($rawInput)) {
    writeLog("BODY VAZIO");
    sendErrorResponse(400, 'Nenhum conteúdo recebido');
}

$requestData = json_decode($rawInput, true);

if (!$requestData) {
    writeLog("JSON INVÁLIDO: " . json_last_error_msg());
    sendErrorResponse(400, 'JSON inválido');
}

writeLog("DECODED: " . json_encode($requestData));

$type = trim($requestData['type'] ?? '');

if (empty($type)) {
    writeLog("TYPE NÃO ESPECIFICADO");
    sendErrorResponse(400, 'Type não especificado');
}

writeLog("TYPE: $type");

switch ($type) {
    case 'BALANCE':
        $response = handleBalance($requestData);
        writeLog("RESPOSTA BALANCE: " . json_encode($response));
        sendSuccessResponse($response);
        break;
        
    case 'WinBet':
    case 'Bet':
    case 'Win':
        $agentCode = $requestData['agent_code'] ?? '';
        $agentSecret = $requestData['agent_secret'] ?? '';
        
        if (!authenticateAgent($agentCode, $agentSecret)) {
            sendErrorResponse(401, 'Credenciais inválidas');
        }
        
        $response = handleTransaction($requestData);
        writeLog("RESPOSTA TRANSACTION: " . json_encode($response));
        sendSuccessResponse($response);
        break;
        
    default:
        writeLog("TYPE NÃO SUPORTADO: $type");
        sendErrorResponse(400, 'Type não suportado');
}
?>