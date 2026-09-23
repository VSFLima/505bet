<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');
include_once('database.php');
include_once('funcao.php');
#=====================================================#
# DATA CONFIG
function data_config()
{
	global $mysqli;
	$qry = "SELECT * FROM config WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$dataconfig = data_config();
#=====================================================#
# DATA POPUPS
function data_popups($id)
{
	global $mysqli;
	$id = intval($id);
	$qry = "SELECT * FROM popups WHERE id = $id";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return "/uploads/" . $data['img'];
}
#=====================================================#
# DATA BANNERS
function data_banners($id)
{
    global $mysqli;
    
    // Usa prepared statement para segurança
    $stmt = $mysqli->prepare("SELECT img FROM banner WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    // Retorna com rawurlencode para tratar espaços
    return $data ? rawurlencode($data['img']) : '';
}
#=====================================================#
# DATA POPOPS INICIO
function data_popsinicio($id, $retorno)
{
    global $mysqli;
    
    // CORREÇÃO: Adicionar verificação de status = 1
    $qry = "SELECT * FROM mensagens WHERE id = '" . $id . "' AND texto = 1 AND status = 1";
    $res = mysqli_query($mysqli, $qry);
    
    // SEGURANÇA: Verificar se encontrou resultado
    if ($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
        
        if ($retorno == 1) {
            return $data['titulo'];
        } else {
            return $data['content'];
        }
    }
    
    // Retorna vazio se não encontrar mensagem ativa
    return '';
}

#=====================================================#
# DATA PROMOCOES
function data_promocoes($id)
{
	global $mysqli;
	$id = intval($id);
	$qry = "SELECT img FROM promocoes WHERE id = $id";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['img'];
}
#=====================================================#
# DATA FLOATS
function data_floats($id)
{
	global $mysqli;
	$id = intval($id);
	$qry = "SELECT img FROM floats WHERE id = $id";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['img'];
}
function data_floats2($id)
{
	global $mysqli;
	$id = intval($id);
	$qry = "SELECT redirect FROM floats WHERE id = $id";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['redirect'];
}

function data_greepay()
{
	global $mysqli;
	$qry = "SELECT * FROM greepay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_greepay = data_greepay();
#=====================================================#

#=====================================================#
# DATA CONFIG
function data_pgclone()
{
	global $mysqli;
	$qry = "SELECT * FROM pgclone WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_pgclone = data_pgclone();

function data_ppclone()
{
	global $mysqli;
	$qry = "SELECT * FROM ppclone WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	
	// Limpar espaços em branco e quebras de linha
	if ($data) {
		$data['agent_code'] = trim($data['agent_code']);
		$data['agent_token'] = trim($data['agent_token']);
		$data['agent_secret'] = trim($data['agent_secret']);
		$data['url'] = trim($data['url']);
	}
	
	return $data;
}
$data_ppclone = data_ppclone();

function data_playfiver()
{
	global $mysqli;
	$qry = "SELECT * FROM playfiver WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_playfiver = data_playfiver();

function data_igamewin()
{
    global $mysqli;
    $qry = "SELECT * FROM igamewin WHERE id=1";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res);
    return $data;
}
$data_igamewin = data_igamewin();

// Adicione isso temporariamente para debug
function data_drakon()
{
    static $cached_data = null;
    
    if ($cached_data !== null) {
        return $cached_data;
    }
    
    global $mysqli;
    $qry = "SELECT * FROM drakon WHERE id=1";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res);
    
    $cached_data = $data;
    return $data;
}
$data_drakon = data_drakon();



function enviarSaldo($email, $saldo)
{
    global $mysqli;

    $saldo = floatval($saldo);
    if($saldo <= 0){ return false; }

    $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE mobile = ?");
    $stmt->bind_param("ds", $saldo, $email);

    if($stmt->execute()){
        return true;
    }
    return false;
}

#diminuir saldo na api da fiverscan
function withdrawSaldo($email, $saldo)
{
    global $mysqli;

    $saldo = floatval($saldo);
    if($saldo <= 0){ return 0; }

    $stmt = $mysqli->prepare("SELECT saldo FROM usuarios WHERE mobile = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $saldoAtual = $row['saldo'];

        if ($saldoAtual >= $saldo) {
            $stmt2 = $mysqli->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE mobile = ?");
            $stmt2->bind_param("ds", $saldo, $email);
            return $stmt2->execute() ? 1 : 0;
        }
    }

    return 0;
}

function data_expfypay()
{
	global $mysqli;
	$qry = "SELECT * FROM expfypay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_expfypay = data_expfypay();

function data_aurenpay()
{
	global $mysqli;
	$qry = "SELECT * FROM aurenpay WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_aurenpay = data_aurenpay();

#=====================================================#
function afiliado_de_quem($refer)
{
	global $mysqli;
	$qry = "SELECT real_name FROM usuarios WHERE invite_code='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	$dinheiro = 'Sem afiliação'; // Valor padrão

	if ($res) {
		while ($row = mysqli_fetch_assoc($res)) {
			if (!empty($row['real_name'])) {
				$dinheiro = $row['real_name'];
			}
		}
	}

	return $dinheiro;
}

#=====================================================#

# DATA CONFIG BSPAY
function data_bspay()
{
    global $mysqli;
    $qry = "SELECT * FROM bspay WHERE id=1";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res);
    return $data;
}
$data_bspay = data_bspay();

function data_versell()
{
    global $mysqli;
    $qry = "SELECT * FROM versell WHERE id=1";
    $res = mysqli_query($mysqli, $qry);
    $data = mysqli_fetch_assoc($res);
    return $data;
}
$data_versell = data_versell();

#=====================================================#

# DATA CONFIG MIDASBANK
// function data_midasbank()
// {
// 	global $mysqli;
// 	$qry = "SELECT * FROM midasbank WHERE id=1";
// 	$res = mysqli_query($mysqli, $qry);
// 	$data = mysqli_fetch_assoc($res);
// 	return $data;
// }
// $data_midasbank = data_midasbank();
#=====================================================#

# DATA CONFIG
function data_afiliados_cpa_rev()
{
	global $mysqli;
	$qry = "SELECT * FROM afiliados_config WHERE id=1";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}
$data_afiliados_cpa_rev = data_afiliados_cpa_rev();
function criarAuditFlowDeposito($user_id, $amount) {
    global $mysqli;
    $qryConfig = "SELECT rollover FROM config WHERE id=1";
    $resConfig = mysqli_query($mysqli, $qryConfig);
    $config = mysqli_fetch_assoc($resConfig);
    $rollover = isset($config['rollover']) ? $config['rollover'] : 1;
    $need_flow = $amount * $rollover;
    $query = "INSERT INTO audit_flows (user_id, amount, flow_multiple, need_flow, current_flow, status, flow_type, created_at) VALUES (?, ?, ?, ?, 0, 'notStarted', 'RECHARGE', NOW())";
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iddd", $user_id, $amount, $rollover, $need_flow);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    } else {
        return false;
    }
}

function criar_financeiro($id)
{
	global $mysqli;
	$sql1 = $mysqli->prepare("INSERT INTO financeiro (usuario,saldo,bonus) VALUES (?,0,0)");
	$sql1->bind_param("i", $id);
	if ($sql1->execute()) {
		$tr = 1; //certo
	} else {
		$tr = 0; //erro
	}
	return $tr;
}

# count saque
function tabelasaldouser($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . intval($id) . "'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['saldo'] > 0) {
			$dinheiro = $row['saldo'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
#criar financeiro
function criar_tokenrefer($id)
{
    global $mysqli;
    if (function_exists('token_aff')) {
        $token_part = token_aff();
    } else {
        return 0;
    }
    $aftoken = 'af' . $id . $token_part;
    $sql = $mysqli->prepare("UPDATE usuarios SET token_refer=? WHERE id=?");
    $sql->bind_param("si", $aftoken, $id);
    if ($sql->execute()) {
        $tr = 1;
    } else {
        $tr = 0;
    }
    return $tr;
}
#=====================================================#
// request curl (fiverscan)
function enviarRequest($url, $config)
{
    $ch = curl_init();
    $headerArray = ['Content-Type: application/json'];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $config);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Forçar IPv4

    $response = curl_exec($ch);

    if ($response === false) {
        logMessage("Erro cURL: " . curl_error($ch));
    }

    curl_close($ch);
    return $response;
}


function logMessage($message) {
    $logFile = __DIR__ . '/crud.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function logPlayFiver($message) {
    $logFile = __DIR__ . '/crud.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] [PlayFiver] $message" . PHP_EOL, FILE_APPEND);
}


#=====================================================#
// saldo atual do user
function saldo_user($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . intval($id) . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$saldo_arr = array(
			"saldo" => $data['saldo'],
			"saldo_afiliado" => $data['saldo_afiliados']
		);
	} else {
		$saldo_arr = array(
			"saldo" => 0,
			"saldo_afiliado" => 0
		);
	}
	return $saldo_arr;
}

function saldo_user_email($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE mobile='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$saldo_arr = array(
			"saldo" => $data['saldo'],
			"user_id" => $data['id'],
			"saldo_afiliado" => $data['saldo_afiliados']
		);
	} else {
		$saldo_arr = array(
			"saldo" => 0,
			"user_id" => 0,
			"saldo_afiliado" => 0
		);
	}
	return $saldo_arr;
}

function saldo_user_pix($id)
{
	global $mysqli;
	$qry = "SELECT * FROM metodos_pagamentos WHERE chave='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$saldo = saldo_user($data['user_id'])['saldo'];
		
		$info = array(
		    "saldo" => $saldo,
		    "user_id" => $data['user_id']
		);
		
		return $info;
	}
}

function consultarSaldoAgente($email)
{
    global $data_fiverscanpanel;
    $keys = $data_fiverscanpanel;
    
    $dataRequest = array(
        "agentToken" => $keys['agent_code'],
        "secretKey" => $keys['agent_token'],
    );

    $json_data = json_encode($dataRequest);

    $url = 'https://api.playfivers.com/api/v2/balance';
    $response = enviarRequest($url, $json_data);

    $data = json_decode($response, true);

    if (isset($data['data']['balance'])) {
        $saldoAgente = $data['data']['balance'];
        return $saldoAgente;
    } else {
        return '0.00';
    }
}

function logIgamewin($message) {
    $logFile = 'error.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] [IGAMEWIN] $message" . PHP_EOL, FILE_APPEND);
}

/**
 * Função para lançar jogos da iGameWin
 */
function pegarLinkJogoigamewin($provider, $game, $email)
{
    global $data_igamewin; 
    
    logIgamewin("--------------------------------------------------");
    logIgamewin("Iniciando solicitação de jogo. Provider: $provider, Game: $game, Email: $email");

    if (!$data_igamewin || !isset($data_igamewin['agent_code'])) {
        logIgamewin("ERRO: Configuração iGameWin não encontrada ou incompleta.");
        if ($data_igamewin) {
            logIgamewin("Dump config: " . json_encode($data_igamewin));
        } else {
            logIgamewin("Dump config: NULL");
        }
        return array('gameURL' => null, 'error' => 'Configuração iGameWin não encontrada no banco');
    }
    
    $homeUrl = url_sistema();
    $providerCode = trim($provider);
    if (strtoupper($providerCode) === 'JDB') {
        $providerCode = 'slot-jdb';
    }
    
    $dataRequest = array(
        "method"        => "game_launch",
        "agent_code"    => $data_igamewin['agent_code'],
        "agent_token"   => $data_igamewin['agent_token'],
        "user_code"     => $email,
        "provider_code" => $providerCode,
        "game_code"     => $game,
        "lang"          => "pt",
        // Enviar todas as variações possíveis para garantir compatibilidade com JDB e outros
        "home_url"      => $homeUrl,
        "homeUrl"       => $homeUrl,
        "return_url"    => $homeUrl,
        "returnUrl"     => $homeUrl,
        "exit_url"      => $homeUrl,
        "exitUrl"       => $homeUrl,
        "quit_url"      => $homeUrl,
        "quitUrl"       => $homeUrl,
        "back_url"      => $homeUrl,
        "backUrl"       => $homeUrl,
        "lobby_url"     => $homeUrl
    );
    
    $json_data = json_encode($dataRequest);
    $url = $data_igamewin['url'];
    
    logIgamewin("URL Request: $url");
    logIgamewin("Payload enviado: $json_data");

    $response = enviarRequest($url, $json_data);
    
    logIgamewin("Resposta RAW recebida: $response");
    
    $data = json_decode($response, true);
    
    if (isset($data['launch_url'])) {
        $launch_url = $data['launch_url'];
        logIgamewin("SUCESSO: Launch URL obtida: " . $launch_url);
        
        if (strpos($launch_url, 'homeUrl=&') !== false || strpos($launch_url, 'homeUrl&') !== false) {
            logIgamewin("ALERTA: homeUrl parece vazio na resposta da API, mesmo enviando múltiplos parâmetros.");
            $encodedHome = urlencode($homeUrl);
            $launch_url = str_replace('homeUrl&', 'homeUrl='.$encodedHome.'&', $launch_url);
            $launch_url = str_replace('homeUrl=&', 'homeUrl='.$encodedHome.'&', $launch_url);
        }
        
        $games = array('gameURL' => $launch_url, 'error' => null);
    } else {
        $msg = $data['msg'] ?? 'Erro desconhecido ou resposta inválida';
        logIgamewin("ERRO API: $msg");
        $games = array('gameURL' => null, 'error' => $msg);
    }
    
    return $games;
}

//  CRIAR USER API FIVERSCAN
function criarUsuarioAPI($email)
{
    return 1;
	global $data_fiverscanpanel;

	$keys = $data_fiverscanpanel;

	$postArray = [
		'agent_code' => $keys['agent_code'],
		'agent_token' => $keys['agent_token'],
		'user_code' => $email
	];
	$jsonData = json_encode($postArray);
	$headerArray = ['Content-Type: application/json'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.dash.net/api/v1/user_create');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	curl_close($ch);
	// Verifique se houve algum erro durante a solicitação
	//$json = '{"status":1,"msg":"SUCCESS","fc_code":"fc104688","user_code":"claudio.web.dev@gmail.com","user_balance":0}';
	$data = json_decode($res, true);

	//var_dump($data);
	// Verifica se a decodificação foi bem-sucedida
	if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
		$SF = 0;
		die('Erro na decodificação JSON: ' . json_last_error_msg());
	}
	if ($data['status'] == 1 and $data['msg'] == "SUCCESS") {
		$SF = 1;
	} else {
		$SF = 0;
	}
	return $SF;
}
#=====================================================#
// atualiza saldo do user
function att_saldo_user($saldo, $id)
{
	global $mysqli;
	$id_user = intval($id);
	$sql = $mysqli->prepare("UPDATE usuarios SET saldo= ? WHERE id=?");
	$sql->bind_param("si", $saldo, $id_user);
	if ($sql->execute()) {
		$rt = 1;
	} else {
		$rt = 0;

	}
	return $rt;
}
#=====================================================#
// financeiro user atual do user
function financeiro_saldo_user($id)
{
	global $mysqli;
	$qry = "SELECT * FROM financeiro WHERE usuario='" . intval($id) . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$saldo = mysqli_fetch_assoc($res);
	} else {
		$saldo = 0;
	}
	return $saldo;
}
#=====================================================#
//  se exisitr refer 1
function pegar_refer($refer)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE token_refer='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$ex_refer = 1;
	} else {
		$ex_refer = 0;
	}
	return $ex_refer;
}
#=====================================================#
#=====================================================#
//  DELETAR USER
function deletar_user($id)
{
	global $mysqli;
	$sql = $mysqli->prepare("DELETE FROM  usuarios WHERE id=?");
	$sql->bind_param("i", $id);
	$sql->execute();

	$sql99 = $mysqli->prepare("DELETE FROM  financeiro WHERE usuario=?");
	$sql99->bind_param("i", $id);
	$sql99->execute();
}
#=====================================================#
function enviarRequest_PAYMENT($url, $header, $data = null)
{
	$ch = curl_init();
	$data_json = json_encode($data);

	// Configurando as opções do cURL
	curl_setopt($ch, CURLOPT_URL, $url);
	if (!$data == null) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Executando a requisição e obtendo a resposta
	$response = curl_exec($ch);

	// Fechando a conexão cURL
	curl_close($ch);

	return $response;
}
#=====================================================#
function requestToken_PAYMENT($url, $header, $data)
{
	$ch = curl_init();

	// Configurando as opções do cURL
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Executando a requisição e obtendo a resposta
	$response = curl_exec($ch);

	// Fechando a conexão cURL
	curl_close($ch);

	return $response;
}
#=====================================================#
#request pix
function request_paymentPIX($transactionId)
{
	global $data_suitpay, $tipoAPI_SUITPAY;
	if ($tipoAPI_SUITPAY == 0) {
		$url = 'https://sandbox.ws.suitpay.app/api/v1/gateway/consult-status-transaction';
		$data = array(
			'typeTransaction' => "PIX",
			'idTransaction' => $transactionId
		);
		$header = array(
			'ci: testesandbox_1687443996536',
			'cs: 5b7d6ed3407bc8c7efd45ac9d4c277004145afb96752e1252c2082d3211fe901177e09493c0d4f57b650d2b2fc1b062d',
			'Content-Type: application/json',
		);
	} else {
		$url = $data_suitpay['url'] . '/api/v1/gateway/consult-status-transaction';
		$data = array(
			'typeTransaction' => "PIX",
			'idTransaction' => $transactionId
		);
		$header = array(
			'ci: ' . $data_suitpay['client_id'],
			'cs: ' . $data_suitpay['client_secret'],
			'Content-Type: application/json'
		);

	}
	$response = enviarRequest_PAYMENT($url, $header, $data);
	$dados = json_decode($response, true);
	return $dados;
}
#=====================================================#
# coun refer direto
function count_refer_direto($refer)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE invitation_code='" . $refer . "'";
	$res = mysqli_query($mysqli, $qry);
	$ex_refer = mysqli_num_rows($res);
	return $ex_refer;
}
#=====================================================#
# count saque
function total_saques_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE id_user='" . $id . "'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
# count depositos
function total_dep_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_dep_pagos_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_dep_afiliado($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE usuario IN (SELECT id FROM usuarios where invitation_code = '" . $id . "') AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}
#=====================================================#
# SUM TOTAL ID CPA/REV
function total_CPA_REV_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=0 OR tipo=1";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_CPA_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=0";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

function total_REV_id($id)
{
	global $mysqli;
	$qry = "SELECT SUM(valor) as total_soma FROM pay_valores_cassino WHERE id_user='" . $id . "' AND tipo=1";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_soma'] > 0) {
			$dinheiro = $row['total_soma'];
		} else {
			$dinheiro = '0.00';
		}
	}
	return $dinheiro;
}

#=====================================================#
# DATA USER ID
function data_user_id($id)
{
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}

function distribution($code) {
    global $mysqli;
    
    // Primeiro tenta buscar pelo game_code
    $game_code = gamecode($code);
    
    $qry = "SELECT api FROM games WHERE game_code = '" . mysqli_real_escape_string($mysqli, $game_code) . "' LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['api'];
    }
    
    // Se não encontrar pela game_code, tenta pela ID
    $qry2 = "SELECT api FROM games WHERE id = '" . intval($code) . "' LIMIT 1";
    $result2 = mysqli_query($mysqli, $qry2);
    
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $row2 = mysqli_fetch_assoc($result2);
        return $row2['api'];
    }
    
    return 'PlayFiver'; // Padrão se não encontrar
}

/**
 * Função para lançar jogos da PlayFiver
 */
function pegarLinkJogoApiPlayFiver($provedor, $game, $email, $saldo)
{
    global $data_playfiver;
    $keys = $data_playfiver;

    logPlayFiver("=== game_launch ===");
    logPlayFiver("Provedor: $provedor | Game: $game | User: $email | Saldo: $saldo");

    // Validar credenciais
    if (!$keys || empty(trim($keys['url'] ?? '')) || empty(trim($keys['agent_token'] ?? '')) || empty(trim($keys['agent_secret'] ?? ''))) {
        logPlayFiver("ERRO: Credenciais PlayFiver ausentes ou incompletas. Keys: " . json_encode($keys));
        return ['gameURL' => null, 'error' => 'jogos em manutenção'];
    }

    $provedor    = strtoupper(trim($provedor));
    $game        = trim((string)$game);
    $baseUrl     = rtrim(trim($keys['url']), '/');
    $agentToken  = trim($keys['agent_token']);
    $secretKey   = trim($keys['agent_secret']);
    $agentCode   = trim($keys['agent_code'] ?? '');

    // Payload correto conforme documentação PlayFiver
    // Campo: "provider" (não "provider_code")
    $payload = [
        'agentToken'   => $agentToken,
        'secretKey'    => $secretKey,
        'user_code'    => $email,
        'provider'     => $provedor,
        'game_code'    => $game,
        'user_balance' => floatval($saldo),
        'lang'         => 'pt',
    ];

    $endpoint = $baseUrl . '/api/v2/game_launch';
    $jsonData  = json_encode($payload);

    logPlayFiver("Endpoint: $endpoint");
    logPlayFiver("Payload: " . json_encode(array_merge($payload, ['agentToken' => '***', 'secretKey' => '***'])));

    $response  = enviarRequest($endpoint, $jsonData);
    $curlError = ($response === false) ? 'cURL retornou false' : null;

    logPlayFiver("HTTP Response RAW: " . substr($response, 0, 800));

    if ($curlError || empty($response)) {
        logPlayFiver("ERRO cURL: " . ($curlError ?? 'resposta vazia'));
        return ['gameURL' => null, 'error' => 'jogos em manutenção'];
    }

    $data_response = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        logPlayFiver("ERRO: Resposta não é JSON válido — " . json_last_error_msg());
        logPlayFiver("Conteúdo bruto: " . $response);
        return ['gameURL' => null, 'error' => 'jogos em manutenção'];
    }

    logPlayFiver("Resposta decodificada: " . json_encode($data_response));

    if (isset($data_response['status']) && intval($data_response['status']) === 1) {
        $game_url = $data_response['launch_url']
                 ?? $data_response['gameURL']
                 ?? $data_response['url']
                 ?? $data_response['data']['launch_url']
                 ?? null;

        if ($game_url) {
            logPlayFiver("SUCESSO — URL: $game_url");
            return ['gameURL' => $game_url];
        }

        logPlayFiver("ERRO: status=1 mas URL não encontrada na resposta. Campos: " . implode(', ', array_keys($data_response)));
        return ['gameURL' => null, 'error' => 'URL do jogo não encontrada'];
    }

    $msg = $data_response['msg'] ?? $data_response['message'] ?? $data_response['error'] ?? 'Erro desconhecido';
    logPlayFiver("ERRO API: status=" . ($data_response['status'] ?? 'N/A') . " | msg: $msg");
    return ['gameURL' => null, 'error' => 'jogos em manutenção'];
}

/**
 * Função para lançar jogos da PGClone
 */
function pegarLinkJogoPGClone($provedor, $game, $email, $saldo)
{
    global $data_pgclone;
    $keys = $data_pgclone;

    $data = array(
        'agentToken'    => $keys['agent_token'],
        'secretKey'     => $keys['agent_secret'],
        'user_code'     => $email,
        'provider_code' => 'PGSOFT',
        'game_code'     => $game,
        'user_balance'  => floatval($saldo)
    );

    $json_data = json_encode($data);
    
    // Log para debug
    error_log("[PGClone] Request: " . $json_data);
    
    $response = enviarRequest($keys['url'] . '/api/v1/game_launch', $json_data);
    $data_response = json_decode($response, true);
    
    error_log("[PGClone] Response: " . $response);

    if (isset($data_response['launch_url'])) {
        $games = array('gameURL' => $data_response['launch_url']);
    } else {
        error_log("[PGClone] Erro - Resposta inválida");
        $games = array('gameURL' => null, 'error' => $data_response['msg'] ?? 'Erro desconhecido');
    }
    
    return $games;
}

/**
 * Função para lançar jogos da PPClone
 */
function pegarLinkJogoPPClone($provedor, $game, $email, $saldo)
{
    global $data_ppclone;
    $keys = $data_ppclone;
    
    $data = array(
        'method'        => 'game_launch',
        'agent_code'    => $keys['agent_code'],
        'agent_token'   => $keys['agent_token'],
        'user_code'     => $email,
        'provider_code' => 'PRAGMATIC',
        'game_code'     => $game,
        'lang'          => 'pt'
    );
    $json_data = json_encode($data);
    
    // Log para debug
    error_log("[PPClone] Request: " . $json_data);
    
    $response = enviarRequest($keys['url'], $json_data);
    $data_response = json_decode($response, true);
    
    error_log("[PPClone] Response: " . $response);
    
    if (isset($data_response['launch_url'])) {
        $games = array('gameURL' => $data_response['launch_url']);
    } else {
        error_log("[PPClone] Erro - Resposta inválida");
        $games = array('gameURL' => null, 'error' => $data_response['msg'] ?? 'Erro desconhecido');
    }
    
    return $games;
}

function authenticateDrakon()
{
    $data_drakon = data_drakon();
    
    if (!$data_drakon || $data_drakon['ativo'] != 1) {
        error_log("[DRAKON] Configuração não encontrada ou inativa");
        return null;
    }
    
    $agent_token = $data_drakon['agent_token'];
    $agent_secret_key = $data_drakon['agent_secret_key'];
    $api_base = $data_drakon['api_base'];
    
    error_log("[DRAKON] === INICIANDO AUTENTICAÇÃO ===");
    error_log("[DRAKON] Agent Token: $agent_token");
    error_log("[DRAKON] Secret Key: " . substr($agent_secret_key, 0, 8) . "...");
    
    // MÉTODO 1: Basic Auth tradicional
    error_log("[DRAKON] Tentando Método 1: Basic Auth");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base/auth/authentication");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$agent_token:$agent_secret_key");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[DRAKON] Método 1 - HTTP: $httpCode | Response: $response");
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            error_log("[DRAKON] ✓ Autenticação bem-sucedida (Método 1)");
            return $data['access_token'];
        }
    }
    
    // MÉTODO 2: Bearer com base64
    error_log("[DRAKON] Tentando Método 2: Bearer base64");
    $authString = "$agent_token:$agent_secret_key";
    $encodedToken = base64_encode($authString);
    error_log("[DRAKON] Base64 Token: $encodedToken");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base/auth/authentication");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $encodedToken,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[DRAKON] Método 2 - HTTP: $httpCode | Response: $response");
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            error_log("[DRAKON] ✓ Autenticação bem-sucedida (Método 2)");
            return $data['access_token'];
        }
    }
    
    // MÉTODO 3: Credenciais no body
    error_log("[DRAKON] Tentando Método 3: Body JSON");
    $payload = json_encode([
        'agent_token' => $agent_token,
        'agent_secret_key' => $agent_secret_key
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$api_base/auth/authentication");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Content-Length: ' . strlen($payload)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[DRAKON] Método 3 - HTTP: $httpCode | Response: $response");
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            error_log("[DRAKON] ✓ Autenticação bem-sucedida (Método 3)");
            return $data['access_token'];
        }
    }
    
    error_log("[DRAKON] ✗ Todos os métodos de autenticação falharam");
    return null;
}

function pegarLinkJogoDrakon($provider_real, $game_id, $user_id, $isMobile)
{
    global $mysqli;
    
    $data_drakon = data_drakon();
    
    if (!$data_drakon || $data_drakon['ativo'] != 1) {
        error_log("[DRAKON] Configuração não encontrada ou inativa");
        return [
            'gameURL' => '',
            'error' => 'Configuração Drakon não encontrada'
        ];
    }
    
    // Autenticar
    $token = authenticateDrakon();
    
    if (!$token) {
        error_log("[DRAKON] Falha na autenticação");
        return [
            'gameURL' => '',
            'error' => 'Falha na autenticação Drakon'
        ];
    }
    
    $agent_code = $data_drakon['agent_code'];
    $agent_token = $data_drakon['agent_token'];
    $api_base = rtrim($data_drakon['api_base'], '/');
    
    // Buscar dados do usuário
    $stmt = $mysqli->prepare("SELECT id, mobile, real_name FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$row = $res->fetch_assoc()) {
        error_log("[DRAKON] Usuário não encontrado: $user_id");
        return [
            'gameURL' => '',
            'error' => 'Usuário não encontrado'
        ];
    }
    
    $user_name = !empty($row['real_name']) ? $row['real_name'] : $row['mobile'];
    $stmt->close();
    
    // Montar parâmetros com currency
    $params = [
        'agent_code' => $agent_code,
        'agent_token' => $agent_token,
        'game_id' => $game_id,
        'type' => 'CHARGED',
        'lang' => 'pt_BR',
        'user_id' => (string)$user_id,
        'user_name' => $user_name,
        'currency' => 'BRL'  // ADICIONADO
    ];
    
    $queryString = http_build_query($params);
    $url = "$api_base/games/game_launch?$queryString";
    
    error_log("[DRAKON] Launch URL: $url");
    
    // Fazer requisição
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("[DRAKON] Launch Response - HTTP Code: $httpCode, Response: $response");
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['game_url']) && !empty($data['game_url'])) {
            error_log("[DRAKON] Jogo lançado com sucesso: " . $data['game_url']);
            return [
                'gameURL' => $data['game_url'],
                'error' => null
            ];
        }
    }
    
    error_log("[DRAKON] Falha ao lançar jogo. HTTP Code: $httpCode");
    return [
        'gameURL' => '',
        'error' => 'Falha ao lançar jogo Drakon: ' . $response
    ];
}

function gamecode($id)
{
	global $mysqli;
	$qry = "SELECT game_code FROM games WHERE game_code='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['game_code'];
}

function gameprovider($id)
{
	global $mysqli;
	$qry = "SELECT provider FROM games WHERE game_code='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['provider'];
}

function gameapi($id)
{
	global $mysqli;
	$qry = "SELECT api FROM games WHERE game_code='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data['api'];
}

function localizarchavepix($id)
{
	global $mysqli;
	$qry = "SELECT chave, tipo, cpf, realname FROM metodos_pagamentos WHERE chave='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}

function localizarchavepix2($id)
{
	global $mysqli;
	$qry = "SELECT chave, tipo, cpf, realname FROM metodos_pagamentos WHERE id='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	return $data;
}



function localizarusuarioporpix($id)
{
    error_log("ID-->>".$id);
    
	global $mysqli;
	$qry = "SELECT * FROM usuarios WHERE id = (SELECT user_id FROM metodos_pagamentos WHERE id = '" . $id . "')";
	
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	
	return $data['real_name'];
}

function localizarpix($id)
{
    error_log("ID-->>".$id);
    
	global $mysqli;
	$qry = "SELECT * FROM metodos_pagamentos WHERE pix_id = '" . $id . "'";
	
	$res = mysqli_query($mysqli, $qry);
	$data = mysqli_fetch_assoc($res);
	
	return $data;
}

#=====================================================#
#inserir saldo
function adicionarsaldo($id, $valor)
{
	global $mysqli;
	$qry = "UPDATE financeiro SET saldo= saldo + '" . $valor . "' WHERE usuario='" . $id . "'";
	$res = mysqli_query($mysqli, $qry);
	
	if ($res) {
		logMessage("Saldo atualizado na tabela financeiro para usuário $id: +$valor");
		return true;
	} else {
		logMessage("Erro ao atualizar saldo na tabela financeiro para usuário $id: " . $mysqli->error);
		return false;
	}
}

function requestaddsaldo($email, $valor)
{
	$data = array(
		'user_code' => $email,
		'valor' => $valor
	);
	$json_data = json_encode($data);
	$response = enviarRequest('https://api.zenbet.online/api/v1/adicionarsaldo', $json_data);
	$dados = json_decode($response, true);
	return $dados;
}

#=====================================================#
#inserir saldo
function insert_payment_adm($id, $email, $valor)
{
	global $mysqli;
	$tokentrans = '#pixdinamic-' . rand(99, 99999);
	$data_hora = date('Y-m-d H:i:s');
	$sql1 = $mysqli->prepare("INSERT INTO transacoes (transacao_id,usuario,valor,data_hora,tipo,status,code) VALUES (?,?,?,?,'deposito','pago','dinamico')");
	$sql1->bind_param("ssss", $tokentrans, $id, $valor, $data_hora);
	#ENVIA SALDO VIA API
	$retorna_insert_saldo_suit_pay = enviarSaldo($email, $valor);
	if ($retorna_insert_saldo_suit_pay['status'] == 1 and $retorna_insert_saldo_suit_pay['msg'] == "SUCCESS" and $sql1->execute()) {
		$ert = 1;
	} else {
		$ert = 0;
	}
	return $ert;
}


function numero_total_dep($id)
{
	global $mysqli;
	$qry = "SELECT COUNT(*) as total_count FROM transacoes WHERE usuario IN (SELECT id FROM usuarios WHERE invitation_code = '" . $id . "') AND tipo='deposito' AND status='pago'";
	$result = mysqli_query($mysqli, $qry);
	while ($row = mysqli_fetch_assoc($result)) {
		if ($row['total_count'] > 0) {
			$total_count = $row['total_count'];
		} else {
			$total_count = 0;
		}
	}
	return $total_count;
}

#retirar saldo
function retirarsaldo($email, $valor)
{
	$data = array(
		'user_code' => $email,
		'valor' => $valor
	);
	$json_data = json_encode($data);
	$response = enviarRequest('https://api.zenbet.online/api/v1/removersaldo', $json_data);
	$dados = json_decode($response, true);
	return $dados;
}
#=====================================================#
#contar visitas
function visitas_count($tipo)
{
    global $mysqli;
    $data_hoje = date("Y-m-d");
    $data_90_dias = date("Y-m-d", strtotime("-90 days"));

    if ($tipo == 'diario') {
        $qry = "SELECT * FROM visita_site WHERE data_cad = '$data_hoje'";
        $res = mysqli_query($mysqli, $qry);
        $count = mysqli_num_rows($res);
    } elseif ($tipo == 'total') {
        $qry = "SELECT * FROM visita_site";
        $res = mysqli_query($mysqli, $qry);
        $count = mysqli_num_rows($res);
    } elseif ($tipo == '90d') {
        $qry = "SELECT * FROM visita_site WHERE data_cad >= '$data_90_dias'";
        $res = mysqli_query($mysqli, $qry);
        $count = mysqli_num_rows($res);
    } else {
        $count = 0;
    }
    
    return $count;
}

#=====================================================#
function visitas_count2($tipo)
{
    global $mysqli;
    $data_hoje = date("Y-m-d");
    $data_90_dias = date("Y-m-d", strtotime("-90 days"));

    if ($tipo === 'diario') {
        $where = "WHERE data_cad = '$data_hoje'";
    } elseif ($tipo === '90d') {
        $where = "WHERE data_cad >= '$data_90_dias'";
    } else {
        $where = "";
    }

    $qry = "SELECT 
                NULLIF(TRIM(cidade), '') AS cidade,
                NULLIF(TRIM(estado), '') AS estado,
                NULLIF(TRIM(mac_os), '') AS mac_os,
                COUNT(*) AS total
            FROM visita_site 
            $where
            GROUP BY cidade, estado, mac_os
            ORDER BY total DESC
            LIMIT 1";

    $res = mysqli_query($mysqli, $qry);
    $dados = [
        'cidade' => null,
        'estado' => null,
        'mac_os' => null,
        'total'  => 0
    ];

    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $dados['cidade'] = $row['cidade'];
        $dados['estado'] = $row['estado'];
        $dados['mac_os'] = $row['mac_os'];
        $dados['total']  = intval($row['total']);
    }

    return $dados;
}

#=====================================================#
# busca por token retorn o id
function busca_id_por_refer($token)
{
	global $mysqli;

	$qry = "SELECT * FROM usuarios WHERE token_refer='" . $token . "'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$count = $data['id'];
	} else {
		$count = 0;
	}
	return $count;
}
#=====================================================#
function generateQRCode_pix($data)
{
	if (function_exists('imagecreate')) {
		// Carregue a biblioteca PHP QR Code
		require_once __DIR__ . '/../libraries/phpqrcode/qrlib.php';
		// Caminho onde você deseja salvar o arquivo PNG do QRCode (opcional)
		$file = __DIR__ . '/../../uploads/qrcode.png';
		// Gere o QRCode
		QRcode::png($data, $file);
		// Carregue o arquivo PNG do QRCode
		if (file_exists($file)) {
			$qrCodeImage = file_get_contents($file);
			// Converta a imagem para base64
			$base64QRCode = base64_encode($qrCodeImage);
			return $base64QRCode;
		} else {
			error_log("generateQRCode_pix: Falha ao gerar arquivo qrcode.png em $file");
			return null;
		}
	} else {
		// Fallback para SVG usando a biblioteca em api/v1 que suporta SVG
		// e nao requer GD
		require_once __DIR__ . '/../../api/v1/phpqrcode.php';
		
		ob_start();
		QRcode::svg($data, false, QR_ECLEVEL_L, 3, 4);
		$svg = ob_get_clean();
		
		if (!empty($svg)) {
			return base64_encode($svg);
		} else {
			error_log("generateQRCode_pix: Falha ao gerar SVG do qrcode");
			return null;
		}
	}
}
#=====================================================#
# busca por ALERT DEP PENDENTES id
function busca_dep_pendentes($id)
{
	global $mysqli;
	$qry = "SELECT * FROM transacoes WHERE usuario='" . $id . "' AND tipo='deposito' AND status='processamento'";
	$res = mysqli_query($mysqli, $qry);
	if (mysqli_num_rows($res) > 0) {
		$data = 1;
	} else {
		$data = 0;
	}
	return $data;
}

// Função para buscar depósitos por dia
function depositos_por_dia() {
    global $mysqli;
    // Usamos DATE() para extrair apenas a data, ignorando a hora
    $qry = "SELECT DATE(data_registro) as dia, COUNT(*) as total FROM transacoes WHERE status = 'pago' AND tipo = 'deposito' GROUP BY DATE(data_registro) ORDER BY dia DESC LIMIT 7";
    $result = mysqli_query($mysqli, $qry);
    
    $dados = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dados[] = [
                'dia' => $row['dia'],          // Retorna a data no formato YYYY-MM-DD
                'total' => intval($row['total']) // Conta a quantidade de depósitos
            ];
        }
    }
    return $dados;
}



// Função para buscar saques por dia
function saques_por_dia() {
    global $mysqli;
    $qry = "SELECT DATE(data_registro) as dia, COUNT(*) as total FROM solicitacao_saques WHERE status = 1 GROUP BY DATE(data_registro) ORDER BY dia DESC LIMIT 7";
    $result = mysqli_query($mysqli, $qry);
    
    $dados = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $dados[] = [
                'dia' => $row['dia'],
                'total' => intval($row['total'])  // Conta a quantidade de saques
            ];
        }
    }
    return $dados;
}

/**
 * Função auxiliar para enviar mensagem ao Telegram de forma otimizada
 */
function enviarTelegram($bot_id, $chat_id, $message) {
    if (empty($bot_id) || empty($chat_id)) {
        return false;
    }

    $urlTelegram = "https://api.telegram.org/bot{$bot_id}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    // Usar cURL com timeout curto para não travar
    $ch = curl_init($urlTelegram);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout de 3 segundos
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Timeout de conexão de 2 segundos
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("[WEBHOOK TELEGRAM] Erro ao enviar: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    return $httpCode == 200;
}

function obterWebhooksAtivos($idPreferido, $nomePreferido)
{
    global $mysqli;
    $rows = [];
    $stmt = $mysqli->prepare("SELECT * FROM webhook WHERE status = 1 AND id = ? AND bot_id <> '' AND chat_id <> ''");
    if ($stmt) {
        $stmt->bind_param("i", $idPreferido);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
    }
    if (count($rows) === 0 && !empty($nomePreferido)) {
        $stmt = $mysqli->prepare("SELECT * FROM webhook WHERE status = 1 AND nome = ? AND bot_id <> '' AND chat_id <> ''");
        if ($stmt) {
            $stmt->bind_param("s", $nomePreferido);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $stmt->close();
        }
    }
    return $rows;
}

function WebhookPixGerado($nome_user, $url, $valor)
{
    global $mysqli;
    $valor = number_format((float)$valor, 2, ',', '.'); 

    $resultWebhook = obterWebhooksAtivos(1, 'pix');
    foreach ($resultWebhook as $webhook) {
        $bot_id = $webhook['bot_id'];
        $chat_id = $webhook['chat_id'];
        
        $message = "🔔 <b>Pix Gerado</b>\n\n";
        $message .= "💰 Valor: <b>R$ {$valor}</b>\n";
        $message .= "⏳ Status: <i>Pendente</i>\n";
        $message .= "🏷️ Nome: {$nome_user}\n";
        $message .= "🌐 Site: {$url}\n";
        $message .= "⏰ Horário: " . date('d/m/Y H:i:s');
        
        enviarTelegram($bot_id, $chat_id, $message);
    }
}

function WebhookPixPagos($nome_user, $url, $valor)
{
    global $mysqli;

    $valorFormatado = is_array($valor) || is_object($valor) ? $valor['valor'] : $valor;
    $valorFormatado = number_format((float)$valorFormatado, 2, ',', '.');

    $resultWebhook = obterWebhooksAtivos(1, 'pix');
    foreach ($resultWebhook as $webhook) {
        $bot_id = $webhook['bot_id'];
        $chat_id = $webhook['chat_id'];
        
        $message = "✅ <b>Pix Confirmado</b>\n\n";
        $message .= "💰 Valor: <b>R$ {$valorFormatado}</b>\n";
        $message .= "✔️ Status: <i>Pago</i>\n";
        $message .= "🏷️ Nome: {$nome_user}\n";
        $message .= "🌐 Site: {$url}\n";
        $message .= "⏰ Horário: " . date('d/m/Y H:i:s');
        
        enviarTelegram($bot_id, $chat_id, $message);
    }
}

function WebhookSaquesGerados($nome_user, $url, $valor)
{
    global $mysqli;

    $valorFormatado = is_array($valor) || is_object($valor) ? $valor['valor'] : $valor;
    $valorFormatado = number_format((float)$valorFormatado, 2, ',', '.');

    $resultWebhook = obterWebhooksAtivos(2, 'saques');
    foreach ($resultWebhook as $webhook) {
        $bot_id = $webhook['bot_id'];
        $chat_id = $webhook['chat_id'];
        
        $message = "🔔 <b>Saque Solicitado</b>\n\n";
        $message .= "💰 Valor: <b>R$ {$valorFormatado}</b>\n";
        $message .= "⏳ Status: <i>Pendente</i>\n";
        $message .= "🏷️ Usuário: {$nome_user}\n";
        $message .= "🌐 Site: {$url}\n";
        $message .= "⏰ Horário: " . date('d/m/Y H:i:s');
        
        enviarTelegram($bot_id, $chat_id, $message);
    }
}

function WebhookSaquesPagos($nome_user, $url, $valor)
{
    global $mysqli;

    $valorFormatado = is_array($valor) || is_object($valor) ? $valor['valor'] : $valor;
    $valorFormatado = number_format((float)$valorFormatado, 2, ',', '.');

    $resultWebhook = obterWebhooksAtivos(2, 'saques');
    foreach ($resultWebhook as $webhook) {
        $bot_id = $webhook['bot_id'];
        $chat_id = $webhook['chat_id'];
        
        $message = "✅ <b>Saque Aprovado</b>\n\n";
        $message .= "💰 Valor: <b>R$ {$valorFormatado}</b>\n";
        $message .= "✔️ Status: <i>Pago</i>\n";
        $message .= "🏷️ Usuário: {$nome_user}\n";
        $message .= "🌐 Site: {$url}\n";
        $message .= "⏰ Horário: " . date('d/m/Y H:i:s');
        
        enviarTelegram($bot_id, $chat_id, $message);
    }
}

function WebhookCadastro($nome_user, $url, $email = '')
{
    global $mysqli;

    $resultWebhook = obterWebhooksAtivos(3, 'cadastro');
    foreach ($resultWebhook as $webhook) {
        $bot_id = $webhook['bot_id'];
        $chat_id = $webhook['chat_id'];
        
        $message = "🆕 <b>Novo Cadastro</b>\n\n";
        $message .= "🏷️ Nome: {$nome_user}\n";
        if (!empty($email)) {
            $message .= "📧 Email: {$email}\n";
        }
        $message .= "🌐 Site: {$url}\n";
        $message .= "⏰ Horário: " . date('d/m/Y H:i:s');
        
        enviarTelegram($bot_id, $chat_id, $message);
    }
}

function online_users_file_path()
{
    return __DIR__ . '/../online_users.json';
}

function get_online_count($ttl_seconds = 120)
{
    $file = online_users_file_path();
    $now = time();
    $users = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) {
            foreach ($data as $key => $last_seen) {
                if (($now - intval($last_seen)) <= $ttl_seconds) {
                    $users[$key] = $last_seen;
                }
            }
        }
    }
    return count($users);
}

function register_user_online($user_code, $ttl_seconds = 120)
{
    $file = online_users_file_path();
    $now = time();
    $data = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) { $data = $decoded; }
    }
    foreach ($data as $key => $last_seen) {
        if (($now - intval($last_seen)) > $ttl_seconds) {
            unset($data[$key]);
        }
    }
    if (!empty($user_code)) {
        $data[$user_code] = $now;
    }
    file_put_contents($file, json_encode($data));
    return true;
}

function unregister_user_online($user_code, $ttl_seconds = 120)
{
    $file = online_users_file_path();
    $now = time();
    $data = [];
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $decoded = json_decode($json, true);
        if (is_array($decoded)) { $data = $decoded; }
    }
    foreach ($data as $key => $last_seen) {
        if (($now - intval($last_seen)) > $ttl_seconds) {
            unset($data[$key]);
        }
    }
    if (!empty($user_code) && isset($data[$user_code])) {
        unset($data[$user_code]);
    }
    file_put_contents($file, json_encode($data));
    return true;
}

// Adiciona o log de adição de saldo no banco de dados
function adicao_saldo($id_user, $valor, $tipo, $data_time) {
    global $mysqli;
    
    $query = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("idss", $id_user, $valor, $tipo, $data_time);


	//$ft = 0;
    
    if ($stmt->execute()) {
		//$ft = 1;
        logMessage("Log de adição de saldo registrado para o usuário $id_user: $valor em $data_time");
    } else {
		//$ft = 0;
        logMessage("Erro ao registrar log de adição de saldo para o usuário $id_user: " . $stmt->error);
    }
}





function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host;
}

function adicionarSaldoUsuario($id_user, $valor) {
    global $mysqli;
    
    // Primeiro, obter o saldo atual do usuário
    $stmt = $mysqli->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    if (!$stmt) {
        logMessage("Erro ao preparar query para obter saldo: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        logMessage("Usuário não encontrado: $id_user");
        return false;
    }
    
    $row = $result->fetch_assoc();
    $saldo_atual = $row['saldo'];
    $novo_saldo = $saldo_atual + $valor;
    
    // Atualizar o saldo do usuário
    $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
    if (!$stmt) {
        logMessage("Erro ao preparar query para atualizar saldo: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("di", $novo_saldo, $id_user);
    
    if ($stmt->execute()) {
        logMessage("Saldo adicionado com sucesso para usuário $id_user: +$valor (saldo anterior: $saldo_atual, novo saldo: $novo_saldo)");
        
        // Registrar no log de adição de saldo (tabela adicao_saldo)
        $data_registro = date('Y-m-d H:i:s');
        $stmt_log = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro, observacao) VALUES (?, ?, ?, ?, ?)");
        if ($stmt_log) {
            $tipo = "deposito_pix";
            $observacao = "Depósito PIX confirmado";
            $stmt_log->bind_param("idsss", $id_user, $valor, $tipo, $data_registro, $observacao);
            $stmt_log->execute();
            logMessage("Log de adição de saldo registrado para usuário $id_user");
        }
        
        return true;
    } else {
        logMessage("Erro ao atualizar saldo do usuário $id_user: " . $mysqli->error);
        return false;
    }
}

?>