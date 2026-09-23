<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');
#expulsa user
checa_login_adm();

global $mysqli;
#capta dados do form
$pagina = filter_input(INPUT_POST, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$qnt_result_pg = filter_input(INPUT_POST, 'qnt_result_pg', FILTER_SANITIZE_NUMBER_INT);
//calcular o inicio visualização
$inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;
//consultar no banco de dados
$result_usuario = "SELECT * FROM provedores WHERE id ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
$resultado_usuario = mysqli_query($mysqli, $result_usuario);
//Verificar se encontrou resultado na tabela "usuarios"
if(($resultado_usuario) AND ($resultado_usuario->num_rows != 0)){?>
		<div class="table-main-container">
			<div class="box box-success box-solid">
				<div class="box-body">
			<?php
				while($data = mysqli_fetch_assoc($resultado_usuario)){
					  if($data['status'] == 1){
						$status_view = '(<small style="color: white;">Ativado</small>)';
					  }else{
						$status_view = '(<small style="color: red;">Desativado</small>)';  
					  }
					 
			?>
				<div class="mg-bottom-16px">
				<div class="small-details-card-grid">
					<div class="box box box-solid">
						<div class="box-header with-border">
							<br>
						<div
                    id="w-node-_9745c905-0e47-203d-ac6e-d1bee1ec357d-e1ec357d"
                    class="card top-details"
                  >
						<div class="paragraph-large color-neutral-100"><?=$data['code'];?></div>
						</div>
						<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center" style="justify-content: center;">
						<br>
							
						</div>
						<div class="mg-left-16px" style="justify-content: center;display:flex;">
							<div class="paragraph-large color-neutral-100">Provedor: <?=strtoupper($data['code']);?></div>
							
							</div>
							<div class="mg-left-16px" style="justify-content: center;display:flex;">
							<div class="paragraph-large color-neutral-100">Status: <?=$status_view;?></div>
							</div>
							
							<a class="btn-primary w-inline-block" style="display: flex;" data-toggle="modal" data-target="#modal-edit-<?=$data['id'];?>" ><i class="fa fa-eye"></i> Atualizar Jogos</a>
							                <!-- modal -->
											<div class="modal fade" id="modal-edit-<?=$data['id'];?>" style="display: none;">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h4 class="modal-title">[Atualizar Games]</h4>
                      </div>
                      <div class="modal-body">
                            <div id="loading-message<?=$data['id'];?>" class='alert alert-warning' role='alert' style="display: none;"> Aguarde, carregando dados...</div>
                            <form id="form-att-slots-games-<?=$data['id'];?>">
                            <div id="response<?=$data['id'];?>"></div>
                              <!-- /.box-body -->
                              <div class="box-footer">
                                  
                                <?php $csrf->echoInputField(); ?>
                                <input type="hidden" name="code" value="<?=$data['code'];?>" required />
                                <button type="submit" class="btn btn-success btn-block">Atualizar Games</button>
                              </div>
                                                     
        
                      
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Fechar</button>
                      </div>
                    </div>
                    <!-- /.modal-content -->
                  </div>
                  <!-- /.modal-dialog -->
                </div>
					</div>
					</div>
					</div>
			<?php } ?>
			<!-- /.box -->
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- /.row -->
<?php
	//Paginação - Somar a quantidade de usuários
	$result_pg = "SELECT COUNT(id) AS num_result FROM games";
	$resultado_pg = mysqli_query($mysqli, $result_pg);
	$row_pg = mysqli_fetch_assoc($resultado_pg);

	//Quantidade de pagina
	$quantidade_pg = ceil($row_pg['num_result'] / $qnt_result_pg);

	//Limitar os link antes depois
	$max_links = 2;


	echo '<br>';
	echo '<nav aria-label="Page navigation">';
	echo '<ul class="pagination justify-content-center" style="display: flex;flex: 1;justify-content: flex-start;align-items: baseline;">';
	echo '<li class="page-item">';
	echo "<span class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-left: 10px;'><a href='#' onclick='listar_usuario(1, $qnt_result_pg)'>Primeira</a> </span>";
	echo '</li>';
	for ($pag_ant = $pagina - $max_links; $pag_ant <= $pagina - 1; $pag_ant++) {
		if ($pag_ant >= 1) {
			echo "<li class='page-item'><a class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-left: 10px;' href='#' onclick='listar_usuario($pag_ant, $qnt_result_pg)'>$pag_ant </a></li>";
		}
	}
	echo '<li class="page-item active">';
	echo '<span class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple" style="width: 12vw;margin-top:5px;margin-left: 10px;">';
	echo "$pagina";
	echo '</span>';
	echo '</li>';
	
	for ($pag_dep = $pagina + 1; $pag_dep <= $pagina + $max_links; $pag_dep++) {
		if ($pag_dep <= $quantidade_pg) {
			echo "<li class='page-item'><a class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-top:5px;' href='#' onclick='listar_usuario($pag_dep, $qnt_result_pg)'>$pag_dep</a></li>";
		}
	}
	echo '<li class="page-item">';
	echo "<span class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw; margin-top:5px;margin-left: 10px;'><a href='#' onclick='listar_usuario($quantidade_pg, $qnt_result_pg)'>Última</a></span>";
	echo '</li>';
	echo '</ul>';
	echo '</nav>';
	} else {
		echo "<div class='text-300 medium color-neutral-100' style='display: flex;align-items: center;margin: 20px;'><div class='tag'></div><h4 style='margin-top: 10px;'>Sem dado disponivel!</span></h4></div>";
	}