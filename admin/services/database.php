<?php
date_default_timezone_set("America/Sao_Paulo");

if (!defined('SITE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    define('SITE_URL', $protocol . $host);
}

if (!defined('DATABASE_LOADED')) {
    $bd = array(
        'local' => 'localhost',
        'usuario' => 'u930079134_novo777',
        'senha' => 'Resident4567',
        'banco' => 'u930079134_novo777'
    );

    // Try connecting with configured credentials
    try {
        $mysqli = new mysqli($bd['local'], $bd['usuario'], $bd['senha'], $bd['banco']);
    } catch (Exception $e) {
        // Fallback or error handling if needed
        error_log("Database connection error: " . $e->getMessage());
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => ['message' => 'Banco de dados temporariamente indisponivel']]);
        exit;
    }
    
    $mysqli->set_charset("utf8mb4");

    if ($mysqli->connect_errno) {
        error_log("Database connect_errno: " . $mysqli->connect_errno);
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => ['message' => 'Banco de dados temporariamente indisponivel']]);
        exit;
    }

    if (!$mysqli->set_charset("utf8mb4")) {
        $mysqli->set_charset("utf8");
    }
    
    // Check for table collation only if connection is successful
    try {
        $res = $mysqli->query("SELECT T.table_collation FROM information_schema.TABLES T WHERE T.table_schema = DATABASE() AND T.table_name = 'config' LIMIT 1");
        if ($res) {
            $row = $res->fetch_assoc();
            if ($row && strpos($row['table_collation'], 'utf8mb4') === false) {
                $mysqli->query("ALTER TABLE `config` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    } catch (Exception $e) {
        // Ignore collation check errors
    }
    
    define('DATABASE_LOADED', true);
}
?>