<?php

/* Dependencias Da Api */
include_once "../config.php";
include_once "../" . DASH . "/services-prod/prod.php";
include_once "../" . DASH . "/services/database.php";
include_once "../" . DASH . "/services/funcao.php";
include_once "../" . DASH . "/services/crud.php";

ini_set('display_errors', 1);
date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);

const ENABLE_LOGS = false;
define('API_SECRET_KEY', 'Mz8WFJiXznKjh0NF16Eno0V9qXNA6vGXIpPRV2crRp8M59u4Cw');

function isExternalToolRequest() {
    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

    return (
        strpos($userAgent, 'PostmanRuntime') !== false || 
        strpos($userAgent, 'curl') !== false || 
        strpos(strtolower($userAgent), 'okhttp') !== false || 
        empty($userAgent)
    );
}

function registrarLog($requestData, $responseData)
{
    if (!ENABLE_LOGS) return;
    
    $logFile = 'game_log.json';
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request' => $requestData,
        'response' => $responseData
    ];

    if (file_exists($logFile)) {
        $currentLogs = json_decode(file_get_contents($logFile), true);
        if (!is_array($currentLogs)) {
            $currentLogs = [];
        }
    } else {
        $currentLogs = [];
    }

    $currentLogs[] = $logData;
    
    if (file_put_contents($logFile, json_encode($currentLogs, JSON_PRETTY_PRINT))) {
        error_log("Log registrado com sucesso: " . json_encode($logData));
    } else {
        error_log("Erro ao registrar log no arquivo: $logFile");
    }
}

function gameCallback($req)
{
    global $mysqli;

    // Se a requisição tem apenas user_code, é uma verificação de saldo simples
    if (count($req) == 1 && isset($req['user_code'])) {
        $user_code = $req['user_code'];
        
        $qry = "SELECT saldo FROM usuarios WHERE mobile = ?";
        $stmt = $mysqli->prepare($qry);
        
        if (!$stmt) {
            $response = ['status' => 0, 'msg' => 'Erro interno'];
            registrarLog(['action' => 'simple_balance_error', 'user_code' => $user_code], $response);
            return json_encode($response);
        }

        $stmt->bind_param("s", $user_code);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $saldo = floatval($row['saldo']);

                $response = [
                    'status' => 1,
                    'user_balance' => $saldo
                ];
            } else {
                $response = [
                    'status' => 0,
                    'msg' => 'Usuário não encontrado',
                    'user_code' => $user_code
                ];
            }

            $stmt->close();
            registrarLog(['action' => 'simple_balance_check', 'user_code' => $user_code], $response);
            return json_encode($response);
        } else {
            $response = ['status' => 0, 'msg' => 'Erro interno', 'error' => $stmt->error];
            registrarLog(['action' => 'simple_balance_error', 'user_code' => $user_code], $response);
            return json_encode($response);
        }
    }

    // Para todas as outras requisições, exige agent_secret
    if (!isset($req['agent_secret']) || empty($req['agent_secret'])) {
        $response = ["status" => 0, "msg" => "Não autorizado"];
        registrarLog(['action' => 'error', 'message' => 'Agent secret ausente', 'data' => $req], $response);
        return json_encode($response);
    }

    $agent_secret = $req['agent_secret'];
    $stmt2 = $mysqli->prepare("SELECT agent_secret FROM pgclone WHERE id = 1 AND ativo = 1");
    if ($stmt2->execute()) {
        $result2 = $stmt2->get_result();
        if ($result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $agent_db = $row2['agent_secret'];
            
            if ($agent_secret != $agent_db) {
                $response = ["status" => 0, "msg" => "Não autorizado"];
                registrarLog(['action' => 'error', 'message' => 'Agent secret inválido', 'data' => $req], $response);
                return json_encode($response);
            }
        } else {
            $response = ["status" => 0, "msg" => "Configuração não encontrada"];
            registrarLog(['action' => 'error', 'message' => 'Configuração pgclone não encontrada ou inativa', 'data' => $req], $response);
            return json_encode($response);
        }
        $stmt2->close();
    } else {
        $response = ["status" => 0, "msg" => "Erro interno"];
        registrarLog(['action' => 'error', 'message' => 'Erro ao verificar agent secret', 'data' => $req], $response);
        return json_encode($response);
    }

    if (isExternalToolRequest()) {
        if (!isset($req['api_token'])) {
            $response = ["status" => 0, "msg" => "Acesso negado"];
            registrarLog(['action' => 'error', 'message' => 'Token ausente em requisição externa', 'data' => $req], $response);
            return json_encode($response);
        }

        if ($req['api_token'] !== API_SECRET_KEY) {
            $response = ["status" => 0, "msg" => "Acesso negado"];
            registrarLog(['action' => 'error', 'message' => 'Token inválido em requisição externa', 'data' => $req], $response);
            return json_encode($response);
        }
    }

    registrarLog(['action' => 'request_received', 'data' => $req], null);

    try {
        $user_code = isset($req["user_code"]) ? $req["user_code"] : 'null';
        $method = isset($req["method"]) ? $req["method"] : 'transaction';

        if ($method == "user_balance") {
            $qry = "SELECT saldo FROM usuarios WHERE mobile = ?";
            
            $stmt = $mysqli->prepare($qry);
            if (!$stmt) {
                error_log("Erro ao preparar a consulta: " . $mysqli->error);
                $response = ['status' => 0, 'msg' => 'Erro interno'];
                registrarLog(['user_code' => $user_code], $response);
                return json_encode($response);
            }

            $stmt->bind_param("s", $user_code);

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $saldo = floatval($row['saldo']);

                    $response = [
                        'status' => 1,
                        'user_balance' => $saldo
                    ];
                } else {
                    $response = [
                        'status' => 0,
                        'msg' => 'Usuário não encontrado',
                        'user_code' => $user_code
                    ];
                }

                $stmt->close();
                registrarLog(['action' => 'user_balance_check', 'user_code' => $user_code], $response);
                return json_encode($response);
            } else {
                $response = ['status' => 0, 'msg' => 'Erro interno', 'error' => $stmt->error];
                registrarLog(['action' => 'user_balance_error', 'user_code' => $user_code], $response);
                return json_encode($response);
            }
        }

        if ($method == "transaction" || $method == "game_callback") {
            $game_type = isset($req["game_type"]) ? $req["game_type"] : 'slot';
            
            if ($game_type == "slot") {
                $slotData = $req["slot"];
                
                $bet = floatval($slotData['bet'] ?? 0);
                $win = floatval($slotData['win'] ?? 0);
                $txn_id = $slotData["txn_id"];
                $game_code = $slotData["game_code"];
                $created_at = $slotData["created_at"];

                $sql = "SELECT id, saldo, invitation_code FROM usuarios WHERE mobile = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("s", $user_code);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    $errorMsg = "Usuário não encontrado";
                    $response = ["status" => 0, "msg" => $errorMsg, "user_code" => $user_code];
                    registrarLog(['action' => 'error', 'message' => $errorMsg, 'data' => $req], $response);
                    return json_encode($response);
                }

                $usuario = $result->fetch_assoc();
                $id_user = $usuario["id"];
                $saldo_atual = floatval($usuario["saldo"]);
                $invitation_code = $usuario["invitation_code"];

                $checkTxn = $mysqli->prepare("SELECT txn_id FROM historico_play WHERE txn_id = ?");
                $checkTxn->bind_param("s", $txn_id);
                $checkTxn->execute();
                $txnResult = $checkTxn->get_result();
                
                if ($txnResult->num_rows > 0) {
                    $response = ['status' => 0, "msg" => 'Transação já processada', 'user_balance' => $saldo_atual];
                    registrarLog(['action' => 'error', 'message' => 'Transação duplicada', 'txn_id' => $txn_id], $response);
                    return json_encode($response);
                }

                $limiteMaximoWin = 1000000;
                if ($win > $limiteMaximoWin) {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_AGENT';
                    $endpoint = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN_URI';
                    
                    $response = ["status" => 0, "msg" => "Operação não permitida", "valor_win" => $win];
                
                    if (ENABLE_LOGS) {
                        $logLinha = "[" . date('Y-m-d H:i:s') . "] [FRAUDE BLOQUEADA] user: $user_code | game: $game_code | bet: $bet | win: $win | txn_id: $txn_id | IP: $ip | AGENT: $userAgent | ENDPOINT: $endpoint" . PHP_EOL;
                        file_put_contents("log.txt", $logLinha, FILE_APPEND);
                    }
                
                    registrarLog(['action' => 'win_blocked', 'reason' => 'win_money acima do limite', 'data' => $req], $response);
                    return json_encode($response);
                }

                $sqlInsert = "INSERT INTO historico_play (id_user, nome_game, bet_money, win_money, txn_id, created_at, status_play) 
                              VALUES (?, ?, ?, ?, ?, NOW(), ?)";
                $stmtInsert = $mysqli->prepare($sqlInsert);
                $status_play = 1;
                
                $stmtInsert->bind_param("isddsi", $id_user, $game_code, $bet, $win, $txn_id, $status_play);
                
                if ($stmtInsert->execute()) {
                    $ganho = $win - $bet;
                    $novo_saldo = $saldo_atual + $ganho;

                    $update_saldo_query = "UPDATE usuarios SET saldo = ? WHERE id = ?";
                    $stmtUpdate = $mysqli->prepare($update_saldo_query);
                    $stmtUpdate->bind_param("di", $novo_saldo, $id_user);
                    $stmtUpdate->execute();

                    if (!empty($invitation_code)) {
                        $sql_afiliado = "SELECT * FROM usuarios WHERE invite_code = ?";
                        $stmt_afiliado = $mysqli->prepare($sql_afiliado);
                        $stmt_afiliado->bind_param("s", $invitation_code);
                        $stmt_afiliado->execute();
                        $result_afiliado = $stmt_afiliado->get_result();

                        if ($result_afiliado->num_rows > 0) {
                            $afiliado = $result_afiliado->fetch_assoc();
                            
                            if ($afiliado['tipo_pagamento'] == 2 || $afiliado['tipo_pagamento'] == 0) {
                                $data_afiliados_cpa_rev = data_afiliados_cpa_rev();
                                
                                if (isset($data_afiliados_cpa_rev['revShareLvl1'])) {
                                    $porcentagem1 = $data_afiliados_cpa_rev['revShareLvl1'] / 100;
                                    $comissao_afiliado = $bet * $porcentagem1;
                                    
                                    if ($win > 0) {
                                        $comissao_afiliado -= $win;
                                    }
                                    
                                    $comissao_afiliado = floatval($comissao_afiliado);
                                    
                                    $atualizar_afiliado_saldo_query = "UPDATE usuarios SET saldo = saldo + ?, rev = rev + ?, total_rev = total_rev + ? WHERE invite_code = ?";
                                    $stmtafiliado = $mysqli->prepare($atualizar_afiliado_saldo_query);
                                    $stmtafiliado->bind_param("ddds", $comissao_afiliado, $comissao_afiliado, $comissao_afiliado, $invitation_code);
                                    $stmtafiliado->execute();
                                }
                            }
                        }
                    }

                    if (ENABLE_LOGS) {
                        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP';
                        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_AGENT';
                        $endpoint = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN_URI';
                        
                        $logLinha = "[" . date('Y-m-d H:i:s') . "] user: $user_code | game: $game_code | bet: $bet | win: $win | saldo final: $novo_saldo | txn_id: $txn_id | IP: $ip | AGENT: $userAgent | ENDPOINT: $endpoint" . PHP_EOL;
                        file_put_contents("log.txt", $logLinha, FILE_APPEND);
                    }

                    $response = ["status" => 1, "user_balance" => floatval($novo_saldo)];
                    registrarLog(['action' => 'update_balance_success', 'new_balance' => $novo_saldo, 'user_id' => $id_user], $response);
                    return json_encode($response);
                    
                } else {
                    $response = ['status' => 0, "msg" => 'Transação já processada', 'user_balance' => $saldo_atual];
                    return json_encode($response);
                }
            }
        }

        $response = ['status' => 0, "msg" => 'Método não suportado'];
        return json_encode($response);

    } catch (Exception $e) {
        $response = ["status" => 0, "msg" => "Erro interno", "error" => $e->getMessage()];
        registrarLog(['action' => 'exception', 'message' => $e->getMessage(), 'data' => $req], $response);
        return json_encode($response);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $reqData = json_decode($input, true);
    
    $response = gameCallback($reqData);
    header('Content-Type: application/json');
    echo $response;
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(["status" => 0, "msg" => "Método não permitido"]);
}
?>