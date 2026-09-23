<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
define('PROD_LOG_FILE', __DIR__ . '/prod.log');

function prodLog($msg) {
    $date = date('Y-m-d H:i:s');
    file_put_contents(PROD_LOG_FILE, "[$date] [PROD] $msg" . PHP_EOL, FILE_APPEND);
}

function next_sistemas_qrcode($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $mysqli;

    prodLog("Iniciando next_sistemas_qrcode. Valor: $valor, ID: $id, JoinBonus: $joinBonus");

    $resultado_ecompag = $mysqli->query("SELECT ativo FROM bspay WHERE id = 1");
    $ecompag_coluna    = $resultado_ecompag ? $resultado_ecompag->fetch_assoc() : ['ativo' => 0];
    $ecompag_ativo     = $ecompag_coluna['ativo'] ?? 0;

    if ($ecompag_ativo != 1) {
        prodLog("Ecompag não está ativo.");
        return null;
    }

    prodLog("Tentando gateway: ecompag");
    $res = criarQrEcompag($valor, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
    if (!empty($res) && isset($res['transacao_id'])) {
        prodLog("Sucesso no gateway: ecompag");
        return $res;
    }

    prodLog("Ecompag falhou ou retornou vazio.");
    return null;
}

// ==================== ECOMPAG ====================

function criarQrEcompag($valor, $nome, $id, $comissao = null, $afiliado_id = null, $payTypeSubListId = null, $joinBonus = true)
{
    global $mysqli, $url_base;

    prodLog("criarQrEcompag: Entrada - Valor: $valor, Nome: $nome, ID: $id");

    $res  = $mysqli->query("SELECT * FROM bspay WHERE id = 1");
    $cred = $res ? $res->fetch_assoc() : null;

    if (!$cred || empty($cred['client_id']) || empty($cred['client_secret'])) {
        prodLog("[ECOMPAG] Credenciais não configuradas.");
        return [];
    }

    $cpf  = cpfRandom(0); // sem máscara, só números
    $nome = trim($nome) ?: 'Cliente';

    $params = [
        'client_id'     => $cred['client_id'],
        'client_secret' => $cred['client_secret'],
        'nome'          => $nome,
        'cpf'           => $cpf,
        'valor'         => number_format((float) $valor, 2, '.', ''), // formato seguro ex: 150.99
        'descricao'     => 'Deposito ' . $id,
        'urlnoty'       => rtrim($url_base, '/') . '/callbackpayment/ecompag',
    ];

    prodLog("[ECOMPAG] Enviando requisição - Valor: $valor, Nome: $nome, ID: $id");
    prodLog("[ECOMPAG] Payload: " . json_encode(array_merge($params, ['client_secret' => '***'])));

    $ch = curl_init('https://api.ecompag.com/v2/pix/qrcode.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => http_build_query($params),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4, // força IPv4
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        prodLog("[ECOMPAG] Erro cURL: $error");
        curl_close($ch);
        return [];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    prodLog("[ECOMPAG] Response HTTP $httpCode: $response");

    $dados = json_decode($response, true);

    if ($httpCode === 200 && isset($dados['statusCode']) && $dados['statusCode'] === 200 && isset($dados['transactionId']) && isset($dados['qrcode'])) {
        $transacao_id    = $dados['transactionId'];
        $qr_code_content = $dados['qrcode'];
        $qr_code_image   = generateQRCode_pix($qr_code_content);

        $insert = [
            'transacao_id'        => $transacao_id,
            'usuario'             => $id,
            'valor'               => $valor,
            'tipo'                => 'deposito',
            'data_registro'       => date('Y-m-d H:i:s'),
            'qrcode'              => urlencode($qr_code_image),
            'status'              => 'processamento',
            'code'                => $qr_code_content,
            'comissao'            => $comissao,
            'afiliado_id'         => $afiliado_id,
            'pay_type_sub_list_id'=> $payTypeSubListId,
            'join_bonus'          => $joinBonus,
        ];

        $insert_paymentBD = insert_payment($insert);

        if ($insert_paymentBD == 1) {
            prodLog("[ECOMPAG] Transação inserida com sucesso: $transacao_id");
            return [
                'transacao_id'   => $transacao_id,
                'transaction_id' => $transacao_id,
                'external_id'    => $transacao_id,
                'qrcode'         => urlencode($qr_code_image),
                'qr_code_image'  => $qr_code_image,
                'amount'         => $valor,
                'status'         => 'processamento',
                'code'           => $qr_code_content,
            ];
        } else {
            prodLog("[ECOMPAG] Falha ao inserir transação no banco");
        }
    } else {
        prodLog("[ECOMPAG] Erro na resposta da API: " . ($dados['message'] ?? 'Resposta inválida'));
    }

    return [];
}

// ==================== FUNÇÕES AUXILIARES ====================

function insert_payment($insert)
{
    global $mysqli;
    $dataarray = $insert;

    prodLog("insert_payment: Iniciando inserção. Dados: " . json_encode($insert));

    $columns      = "transacao_id,usuario,valor,tipo,data_registro,qrcode,code,status";
    $placeholders = "?,?,?,?,?,?,?,?";
    $types        = "ssssssss";
    $values       = [
        $dataarray['transacao_id'],
        $dataarray['usuario'],
        $dataarray['valor'],
        $dataarray['tipo'],
        $dataarray['data_registro'],
        $dataarray['qrcode'],
        $dataarray['code'],
        $dataarray['status'],
    ];

    if (isset($dataarray['comissao']) && isset($dataarray['afiliado_id'])) {
        $columns      .= ",comissao,afiliado_id";
        $placeholders .= ",?,?";
        $types        .= "ss";
        $values[]      = $dataarray['comissao'];
        $values[]      = $dataarray['afiliado_id'];
    }

    if (isset($dataarray['pay_type_sub_list_id']) && !empty($dataarray['pay_type_sub_list_id'])) {
        $columns      .= ",pay_type_sub_list_id";
        $placeholders .= ",?";
        $types        .= "i";
        $values[]      = $dataarray['pay_type_sub_list_id'];
    }

    if (isset($dataarray['join_bonus'])) {
        $columns      .= ",join_bonus";
        $placeholders .= ",?";
        $types        .= "i";
        $values[]      = $dataarray['join_bonus'];
    }

    $sql  = "INSERT INTO transacoes ($columns) VALUES ($placeholders)";
    prodLog("insert_payment: SQL: $sql");
    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            $stmt->close();
            prodLog("insert_payment: Sucesso na inserção.");
            return 1;
        } else {
            prodLog("insert_payment: ERRO Execute: " . $stmt->error);
            $stmt->close();
            return 0;
        }
    } else {
        prodLog("insert_payment: ERRO Prepare: " . $mysqli->error);
        return 0;
    }
}

function mod($dividendo, $divisor)
{
    return round($dividendo - (floor($dividendo / $divisor) * $divisor));
}

function cpfRandom($mascara = "1")
{
    $n1 = rand(0, 9); $n2 = rand(0, 9); $n3 = rand(0, 9);
    $n4 = rand(0, 9); $n5 = rand(0, 9); $n6 = rand(0, 9);
    $n7 = rand(0, 9); $n8 = rand(0, 9); $n9 = rand(0, 9);
    $d1 = $n9*2+$n8*3+$n7*4+$n6*5+$n5*6+$n4*7+$n3*8+$n2*9+$n1*10;
    $d1 = 11 - (mod($d1, 11));
    if ($d1 >= 10) $d1 = 0;
    $d2 = $d1*2+$n9*3+$n8*4+$n7*5+$n6*6+$n5*7+$n4*8+$n3*9+$n2*10+$n1*11;
    $d2 = 11 - (mod($d2, 11));
    if ($d2 >= 10) $d2 = 0;
    if ($mascara == 1) {
        return "$n1$n2$n3.$n4$n5$n6.$n7$n8$n9-$d1$d2";
    }
    return "$n1$n2$n3$n4$n5$n6$n7$n8$n9$d1$d2";
}