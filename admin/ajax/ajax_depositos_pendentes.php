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
#capta dados do form
$pagina = filter_input(INPUT_POST, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$qnt_result_pg = filter_input(INPUT_POST, 'qnt_result_pg', FILTER_SANITIZE_NUMBER_INT);
//calcular o inicio visualização
$inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;
//consultar no banco de dados
$result_usuario = "SELECT * FROM transacoes WHERE id AND status='processamento' ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
$resultado_usuario = mysqli_query($mysqli, $result_usuario);
//Verificar se encontrou resultado na tabela "usuarios"
if(($resultado_usuario) AND ($resultado_usuario->num_rows != 0)){?>

<div class="table-main-container">
                <?php
                    while($data = mysqli_fetch_assoc($resultado_usuario)){
						$data_return = data_user_id($data['usuario']);
                        if($data['status'] == 'pago'){
                          $status_view = '<span class="status-badge green"><div class="small-dot _4px bg-green-300"></div> PAGO</span>';
                        }else{
                          $status_view = '<span class="status-badge yellow"><div class="small-dot _4px bg-secondary-5"></div> PENDENTE</span>';  
                        }
                ?>
                <div class="recent-orders-table-row" style="display: flex; justify-content: space-between;">
	<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
		<div class="paragraph-small color-neutral-100"><?=$data_return['id'];?></div>
	</div>
	<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
		<div class="paragraph-small color-neutral-100"><?=$data_return['mobile'];?></div>
	</div>
	<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
		<div class="paragraph-small color-neutral-100"><?=ver_data($data['data_registro']);?></div>
	</div>
	<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
		<div class="paragraph-small color-neutral-100"><?=$status_view;?></div>
	</div>
	<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
		<div class="paragraph-small color-neutral-100">R$ <?=Reais2($data['valor']);?></div>
	</div>
</div>
                <?php }?>
    

	</div>
	<!-- /.row -->
<?php
	//Paginação - Somar a quantidade de usuários
	$result_pg = "SELECT COUNT(id) AS num_result FROM transacoes  WHERE status='processamento'";
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