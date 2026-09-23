<?php
session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');
#expulsa user
checa_login_adm();
#------------------------------------------------------------#

#capta dados do form
if (isset($_POST['_csrf'])) {
	#------------------------------------------------------------#
	if(!empty($data_fiverscan['agent_code']) AND !empty($data_fiverscan['agent_token'])){
	
		$postArray = [
			'agent_code' => $data_fiverscan['agent_code'], 
			'agent_token' => $data_fiverscan['agent_token'],
			'user_code' => 'ferteste@gmail.com',
			'amount' => '114.05',
		];
		$jsonData = json_encode($postArray);
	
		$headerArray = ['Content-Type: application/json'];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.worldslotgame.com/api/v2/user_withdraw');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($res, true);
		if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
			die('Erro na decodificação JSON: ' . json_last_error_msg());
		}
		if($data['status'] == 1){
			$status = $data['status'];
			$msg = $data['msg'];
			}
			
			
		}else{
			echo "<div class='alert alert-warning' role='alert'><i class='fa fa-exclamation-circle'></i> Revise seus dados de Api..</div><script>  setTimeout('window.location.href=\"".$painel_adm_provedores_games."\";', 3000); </script>";
			
		}
	}


