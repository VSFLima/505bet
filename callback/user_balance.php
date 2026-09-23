<?php
/* Dependencias Da Api */
include_once "../config.php";
include_once "../" . DASH . "/services-prod/prod.php";
include_once "../" . DASH . "/services/database.php";
include_once "../" . DASH . "/services/funcao.php";
include_once "../" . DASH . "/services/crud.php";

function registrarLog($requestData, $responseData) {
    $logFile = 'user_balance.json';
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'request' => $requestData,
        'response' => $responseData
    ];
    
    $currentLogs = [];
    if (file_exists($logFile)) {
        $currentLogs = json_decode(file_get_contents($logFile), true);
        if (!is_array($currentLogs)) {
            $currentLogs = [];
        }
    }
    
    $currentLogs[] = $logData;
    
    if (file_put_contents($logFile, json_encode($currentLogs, JSON_PRETTY_PRINT))) {
        error_log("Log registrado com sucesso: " . json_encode($logData));
    } else {
        error_log("Erro ao registrar log no arquivo: $logFile");
    }
}

function getUserBalance($userCode) {
    global $mysqli;
    
    $query = "SELECT saldo FROM usuarios WHERE mobile = ?";
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("Erro ao preparar a consulta: " . $mysqli->error);
        return ['msg' => 'ERROR_PREPARING_QUERY'];
    }
    
    $stmt->bind_param("s", $userCode);
    
    if (!$stmt->execute()) {
        $response = [
            'msg' => 'ERROR_QUERY',
            'error' => $stmt->error
        ];
        error_log("Erro ao executar a consulta para user_code $userCode: " . $stmt->error);
        return $response;
    }
    
    $result = $stmt->get_result();
    $stmt->close();
    
    if ($result->num_rows === 0) {
        return [
            'msg' => 'INVALID_USER',
            'user_code' => $userCode
        ];
    }
    
    $row = $result->fetch_assoc();
    return [
        'status' => 1,
        'user_balance' => floatval($row['saldo'])
    ];
}

function callbackUserBalance($request) {
    $userCode = $request['user_code'];
    $response = getUserBalance($userCode);
    
    registrarLog(['user_code' => $userCode], $response);
    
    return json_encode($response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    $response = callbackUserBalance($requestData);
    
    echo $response;
}