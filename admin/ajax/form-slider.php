<?php
session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/checa_login_adm.php');
#expulsa user
checa_login_adm();
#------------------------------------------------------------#
#------------------------------------------------------------#
#capta dados do form
if (isset($_POST['nome']) && isset($_POST['_csrf'])) {
	$nome =   PHP_SEGURO($_POST['nome']);
	$CRSF =   PHP_SEGURO($_POST['_csrf']);
	$imgNome  = $_FILES['img']['name'];
	#------------------------------------------------------------#
	$fileLocal	= '../../uploads/';
	#------------------------------------------------------------#
	if(empty($CRSF)) {
		echo alertas_toaster('aviso','Houve um erro ao obter dados atualize sua página.',3500);
	}
	if(empty($nome) && empty($imgNome)) {
		echo alertas_toaster('aviso','Insira os dados no formulário.',3500);
	}
	
	#------------------------------------------------------------#	
	$query = "SELECT * FROM banner WHERE titulo = '$nome'";
	$result = mysqli_query($mysqli , $query) or die (mysqli_error($mysqli));
	if (mysqli_num_rows($result) > 0) {
		echo alertas_toaster('aviso','Insira outro nome de Slider.',3500);
	}else{
		$fileExt = strtolower(pathinfo($imgNome, PATHINFO_EXTENSION));
		$formatosaceitos = array('jpg', 'png', 'jpeg', 'svg', 'webp', 'ico', 'gif');
		if (!in_array($fileExt, $formatosaceitos)){
			echo alertas_toaster('aviso','Aceitamos apenas imagens em formatos .jpg, .png, .jpeg, .svg, .webp, .ico, .gif');
			return true;
		}
		
		$nomeRandom         = rand(0, 99999999999) + rand(0, 99999999999) + rand(0, 99999999999); 
		$novoNome 		    = "slider-".$nomeRandom.".".$fileExt;

		$tamanho = $_FILES['img']['size'];
		if($tamanho > 20000000) {
			echo alertas_toaster('aviso','O arquivo de imagem execedeu os 20MB permitidos para upload.');
			return true;
		}
		if(move_uploaded_file($_FILES['img']['tmp_name'], $fileLocal.$novoNome)) {
			$bdnome = $novoNome;
			$sql1 = $mysqli->prepare("INSERT INTO banner (titulo,img) VALUES (?,?)");
			$sql1->bind_param("ss",$nome,$bdnome);
			if($sql1->execute()){
				echo  alertas_toaster('ok','Slider adicionado com sucesso.');
				echo  "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 3000); </script>";
			}else{
				echo  alertas_toaster('aviso','algo errado.');
				return true;
			}
		}else{
			echo alertas_toaster('aviso','Não foi possível mover imagem.',3500);
		}
	
	
	
	
	
	}
}



