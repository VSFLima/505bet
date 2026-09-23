<?php
session_start();
include_once('../services/database.php');
include_once('../services/checa_login_adm.php');
checa_login_adm();

if (isset($_POST['ids'])) {
    $ids = json_decode($_POST['ids'], true); // Decodificar JSON vindo da requisição
    $ids_str = implode(',', $ids); // Concatena todos os IDs em uma string separada por vírgulas

    // Atualizar o status para "expirado"
    $query = "UPDATE transacoes SET status = 'expirado' WHERE id IN ($ids_str)";
    if (mysqli_query($mysqli, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
