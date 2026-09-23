<?php
	session_start();
	include_once("logs/registrar_logs.php");
	include_once("services/database.php");
	include_once("services/funcao.php");
	global $mysqli;
	registrarLog($mysqli, $_SESSION['data_adm']['email'], "<span class='status-badge red'style='display: inline-block;'><i class='fa fa-sign-out'></i></span></i> Deslogou do painel admin");
	if(isset($_SESSION['token_adm_encrypted']) && isset($_SESSION["crsf_token_adm"]) && isset($_SESSION["anti_crsf_token_adm"])){
		unset($_SESSION["token_adm_encrypted"]);//destroy crsf_token_adm
		unset($_SESSION["crsf_token_adm"]); //destroy token_adm_encrypted
		unset($_SESSION["anti_crsf_token_adm"]); //destroy token_user_encrypted
		session_destroy();
		//Após destruir redireciona login
		header('Location: '.$painel_adm_acessar.''); //Redireciona para pagina de login
		exit();
    }
	
?>