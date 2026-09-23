<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

require_once "config.php";
require_once DASH . "/services/database.php";

if (!isset($mysqli)) {
    echo json_encode(['active' => 1]);
    exit;
}

$res = $mysqli->query("SELECT active FROM red_envelope_settings WHERE id=1 LIMIT 1");

if (!$res || !($row = $res->fetch_assoc())) {
    echo json_encode(['active' => 1]);
    exit;
}

echo json_encode(['active' => (int)$row['active']]);