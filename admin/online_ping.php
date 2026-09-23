<?php
session_start();
include_once __DIR__ . "/services/checa_login_adm.php";
checa_login_adm();
include_once __DIR__ . "/services/database.php";
include_once __DIR__ . "/services/crud.php";

header('Content-Type: application/json');

$is_count = isset($_GET['count']) ? true : false;
if ($is_count) {
    echo json_encode(['success' => true, 'count' => get_online_count(120)]);
    exit;
}

$is_offline = isset($_POST['offline']) || isset($_GET['offline']);

$user = '';
if (!empty($_POST['user_code'])) {
    $user = trim($_POST['user_code']);
} elseif (!empty($_SESSION['data']['user_code'])) {
    $user = trim($_SESSION['data']['user_code']);
} elseif (!empty($_SESSION['data_user']['email'])) {
    $user = trim($_SESSION['data_user']['email']);
}

if ($user === '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $suffix = substr(sha1($ua), 0, 8);
    $user = $ip ? ($ip . '-' . $suffix) : ('anon-' . $suffix);
}

if ($is_offline) {
    unregister_user_online($user, 120);
    echo json_encode(['success' => true, 'offline' => true]);
} else {
    register_user_online($user, 120);
    echo json_encode(['success' => true, 'offline' => false]);
}
?>
