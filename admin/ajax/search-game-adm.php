<style>
	.phborder-danger{
	border: 1px solid red;
	}
	.phborder-success{
	border: 1px solid green;
	}
	</style>
<?php
session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php');
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');
#expulsa user
checa_login_adm();

global $mysqli;
if(isset($_POST['query']) AND !empty($_POST['query'])){
      $buscar = PHP_SEGURO($_POST['query']);
      $sql = "SELECT * FROM games WHERE game_name LIKE '%$buscar%'";
      $res = mysqli_query($mysqli,$sql);
      if(mysqli_num_rows($res)>0){
		while($data = mysqli_fetch_assoc($res)){
			if($data['status'] == 1){
				$status_view = 'success';
			}else{
				$status_view = 'danger';  
			}
	?>
		<div class="mg-bottom-16px">
				<div class="small-details-card-grid">
					<div class="-<?=$status_view;?>">
						<div class="box-header with-border">
							<br>
						<div
                    id="w-node-_9745c905-0e47-203d-ac6e-d1bee1ec357d-e1ec357d"
                    class="card top-details phborder-<?=$status_view;?>"
                  >
						<div class="paragraph-large color-neutral-100"><?=$data['game_name'];?> (Pesquisado)</div>
						</div>
						<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center" style="justify-content: center;">
						<br>
							<img src="<?=$data['banner'];?>" alt="AvatarGame" class="img-rounded" width="189" height="145" style="border-radius: 6px;margin: 10px;">
						</div>
						<div class="mg-left-16px" style="justify-content: center;display:flex;">
							<div class="paragraph-large color-neutral-100">Provedor: <?=strtoupper($data['provider']);?></div>
							
							</div>
							<a href="<?=$painel_adm_view_game.encodeAll($data['id']);?>" class="btn-primary w-inline-block" style="display: flex;"><i class="fa fa-eye"></i> Editar Game</a>
					</div>
					</div>
					</div>
		
	<?php }}else{ ?>
	<div class="alert alert-danger alert-dismissible">
		<h4><i class="icon fa fa-ban"></i> Aviso ! Nenhum game foi encontrado.</h4>
	</div>
	<?php } ?>
	
<?php } ?>