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
if (isset($_POST['nome']) && isset($_POST['id_slider']) && isset($_POST['status']) && isset($_POST['_csrf'])) {
	$nome =   PHP_SEGURO($_POST['nome']);
	$id_slider =   PHP_SEGURO($_POST['id_slider']);
	$status =   PHP_SEGURO($_POST['status']);
	$CRSF =   PHP_SEGURO($_POST['_csrf']);
	$imgNome  = $_FILES['img']['name'];
	$format_img  = PHP_SEGURO($_POST['format_img']);
	#------------------------------------------------------------#
	$fileLocal	= '../../uploads/';
	#------------------------------------------------------------#
	if(empty($CRSF)) {
		echo alertas_toaster('aviso','Houve um erro ao obter dados atualize sua página.',3500);
	}
	if(empty($nome)) {
		echo alertas_toaster('aviso','Insira os dados no formulário.',3500);
	}
	if(empty($imgNome)) {
		if($status == 2){
			if(empty($imgNome)){
				unlink($fileLocal.$format_img);
			}
			$sql = $mysqli->prepare("DELETE FROM  banner WHERE id=?");
			$sql->bind_param("i",$id_slider);
			if($sql->execute()) {
				echo alertas_toaster('ok','Ok! Conteúdo deletado com sucesso.');
				echo "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 2000); </script>";
			}
		}else{
			$sql = $mysqli->prepare("UPDATE banner SET titulo=?,status=? WHERE id=?");
			$sql->bind_param("ssi",$nome,$status,$id_slider);
			if($sql->execute()) {
				echo alertas_toaster('ok','Dados atualizados com sucesso.',3500);
				echo "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 3000); </script>";
			}else{
				echo alertas_toaster('aviso','Houve um erro ao atualizar novos dados..',3500);
				echo "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 3000); </script>";
				
			}
		}
	}
	if(!empty($imgNome)) {
		unlink($fileLocal.$format_img);
		$fileExt = strtolower(pathinfo($imgNome, PATHINFO_EXTENSION));
		$formatosaceitos = array('jpg', 'png', 'jpeg', 'svg', 'webp', 'ico', 'gif');
		if (!in_array($fileExt, $formatosaceitos)){
			echo alertas_toaster('aviso','Aceitamos apenas imagens em formatos .jpg, .png, .jpeg, .svg, .webp, .ico, .gif');
			return true;
		}
		// Nova lógica para definir o nome da imagem com base no ID do slider
		$imgNomeMap = [
			1 => "1.6f821486.png",
			2 => "2.a3fb130e.png",
			3 => "3.606d3ac3.png"
		];

		$bdnome = isset($imgNomeMap[$id_slider]) ? $imgNomeMap[$id_slider] : "slider-".rand(0, 99999999999).".".$fileExt;

		$tamanho = $_FILES['img']['size'];
		if($tamanho > 20000000) {
			echo alertas_toaster('aviso','O arquivo de imagem execedeu os 20MB permitidos para upload.');
			return true;
		}
		if(move_uploaded_file($_FILES['img']['tmp_name'], $fileLocal.$bdnome)) {
			$sql = $mysqli->prepare("UPDATE banner SET titulo=?,img=?,status=? WHERE id=?");
			$sql->bind_param("sssi",$nome,$bdnome,$status,$id_slider);
			if($sql->execute()) {
			  echo alertas_toaster('ok','Ok! Conteúdo atualizado com sucesso.',3500);
			  echo "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 2000); </script>";
			}else{
			  echo alertas_toaster('aviso','Não foi possível atualizar dados.',3500);
			  echo "<script>  setTimeout('window.location.href=\"".$painel_adm_slider_front."\";', 2000); </script>";
			}
		}else{
			echo alertas_toaster('aviso','Não foi possível mover imagem.',3500);
		}
	}
}
?>
