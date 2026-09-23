<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');

require_once "config.php";
require_once DASH . "/services/database.php";

if (!isset($mysqli)) {
    echo json_encode(['active' => 1, 'prizes' => []]);
    exit;
}

// Verifica se a roleta está ativa
$active = 1;
$res = $mysqli->query("SELECT active FROM welcome_roulette_settings WHERE id=1 LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $active = (int)$row['active'];
}

// Busca prêmios ativos
$prizes = [];
$res = $mysqli->query(
    "SELECT uuid, type, amount, weight FROM roulette_prizes 
     WHERE active=1 ORDER BY sort_order ASC, id ASC"
);
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $prizes[] = [
            'uuid'   => $row['uuid'],
            'type'   => $row['type'],
            'amount' => (float)$row['amount'],
            'weight' => (int)$row['weight'],
        ];
    }
}

echo json_encode(['active' => $active, 'prizes' => $prizes]);