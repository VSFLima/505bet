<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');
#expulsa user
checa_login_adm();
#------------------------------------------------------------#
function att_game_slots_providers($gameCode,$gameName,$banner,$gameStatus,$provedor){
	global $mysqli;
	//$provedorxx = preg_replace("/[^a-zA-Z]/", "", $provedor);
	$stmt = $mysqli->prepare("SELECT * FROM games WHERE game_code = ? AND game_name = ? AND provider = ?");
	$stmt->bind_param("sss", $gameCode,$gameName,$provedor);
	$stmt->execute();
	$result = $stmt->get_result();
	if($result->num_rows > 0){
		$row = $result->fetch_assoc();
		$id = $row['id'];
		$sql = $mysqli->prepare("UPDATE games SET banner=?,status=? WHERE id=?");
		$sql->bind_param("ssi",$banner,$gameStatus,$id);
		if($sql->execute()) {
			$r_data = 1;
		}else{
			$r_data = 0;
		}
	}else{
		$sql1 = $mysqli->prepare("INSERT INTO games (game_code,game_name,banner,status,provider) VALUES (?,?,?,?,?)");
		$sql1->bind_param("sssss",$gameCode,$gameName,$banner,$gameStatus,$provedor);
		if($sql1->execute()){
			$r_data = 1;
		}else{
			$r_data = 0;
		}
	}
	
	return $r_data;
}