<?php

session_start();
include_once "../config.php";
include_once('../'.DASH.'/services/database.php');
include_once('../'.DASH.'/services/funcao.php');
include_once('../'.DASH.'/services/crud.php');
include_once('../'.DASH.'/services/afiliacao.php');
global $mysqli;

function busca_valor_ipn($transacao_id) {
    global $mysqli;
    $qry = "SELECT usuario, valor FROM transacoes WHERE transacao_id='" . $transacao_id . "'";
    $res = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        $retorna_insert_saldo = adicionarSaldoUsuario($data['usuario'], $data['valor']);
        if ($retorna_insert_saldo) {
            processarTodasComissoes($data['usuario'], $data['valor']);
        }
        return $retorna_insert_saldo;
    }
    return false;
}

function att_paymentpix($transacao_id) {
    global $mysqli;
    $sql = $mysqli->prepare("UPDATE transacoes SET status='1' WHERE transacao_id=?");
    $sql->bind_param("s", $transacao_id);
    if ($sql->execute()) {
        $buscar = busca_valor_ipn($transacao_id);
        $rf     = $buscar ? 1 : 0;
    } else {
        $rf = 0;
    }
    return $rf;
}

function webhook() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo 'erro404';
        return;
    }

    $json_data = file_get_contents('php://input');
    $data      = json_decode($json_data, true);

    // Ecompag webhook format:
    // { "transactionType": "RECEIVEPIX", "transactionId": "...", "amount": 150.99, "status": "PAID" }
    if (!isset($data['transactionId']) || !isset($data['transactionType']) || !isset($data['status'])) {
        echo json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
        return;
    }

    $idTransaction     = PHP_SEGURO($data['transactionId']);
    $typeTransaction   = PHP_SEGURO($data['transactionType']);
    $statusTransaction = PHP_SEGURO($data['status']);

    if ($typeTransaction === 'RECEIVEPIX' && $statusTransaction === 'PAID') {
        att_paymentpix($idTransaction);
    }

    echo json_encode(['status' => 'success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    webhook();
}