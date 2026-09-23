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



$pagina = filter_input(INPUT_POST, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$qnt_result_pg = filter_input(INPUT_POST, 'qnt_result_pg', FILTER_SANITIZE_NUMBER_INT);
$inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;

$stmt = mysqli_prepare($mysqli, "SELECT * FROM usuarios WHERE statusaff = 1 ORDER BY id DESC LIMIT ?, ?");
//$stmt = mysqli_prepare($mysqli, "SELECT * FROM usuarios INNER JOIN financeiro ON (financeiro.usuario) WHERE usuarios.id ORDER BY usuarios.id DESC LIMIT ?, ?");
mysqli_stmt_bind_param($stmt, "ii", $inicio, $qnt_result_pg);
mysqli_stmt_execute($stmt);
$resultado_usuario = mysqli_stmt_get_result($stmt);
    

if ($resultado_usuario->num_rows != 0) {
    ?>

<div class="table-main-container">
	<?php while ($data = mysqli_fetch_assoc($resultado_usuario)) {
		$RET_SAQUES = total_saques_id($data['id']);
		$RET_DEPOSITOS = total_dep_pagos_id($data['id']);
		$RET_SALDO2 = tabelasaldouser($data['id']);
	?>
		<div class="recent-orders-table-row" style="display: flex; justify-content: space-between;">
			<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
				<div class="paragraph-small color-neutral-100" style="max-width:70px;white-space: nowrap;  overflow: hidden;text-overflow: ellipsis;"><?=$data['mobile'];?></div>
			</div>
			<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
				<div class="paragraph-small color-neutral-100" style="text-align: center;max-width:70px;white-space: nowrap;  overflow: hidden;text-overflow: ellipsis;">R$ <?= Reais2($RET_SALDO2); ?></div>
			</div>
			<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
				<div class="paragraph-small color-neutral-100">R$ <?= Reais2($RET_DEPOSITOS); ?></div>
			</div>
			<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
				<div class="paragraph-small color-neutral-100">R$ <?= Reais2($RET_SAQUES); ?></div>
			</div>
			<div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
				<div class="paragraph-small color-neutral-100"><a href="<?= $painel_adm_ver_usuarios . encodeAll($data['id']); ?>" class="btn-primary w-inline-block"
                      ><div class="flex-horizontal gap-column-6px">
                        <div>Detalhes</div>
                      </div></a></div>
			</div>
		</div>
	<?php }?>
</div>
    

	</div>
	<!-- /.row -->
	<?php
    $result_pg = "SELECT COUNT(id) AS num_result FROM usuarios";
    $resultado_pg = mysqli_query($mysqli, $result_pg);
    $row_pg = mysqli_fetch_assoc($resultado_pg);
    $quantidade_pg = ceil($row_pg['num_result'] / $qnt_result_pg);
    $max_links = 2;

    echo '<br>';
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center" style="display: flex;flex: 1;justify-content: flex-start;align-items: baseline;margin-left: -25px;">';
    echo '<li class="page-item">';
    echo "<span class='btn-primary w-inline-block'><a href='#' style='color: white;' onclick='listar_usuarios(1, $qnt_result_pg)'>Primeira</a> </span>";
    echo '</li>';
    for ($pag_ant = $pagina - $max_links; $pag_ant <= $pagina - 1; $pag_ant++) {
        if ($pag_ant >= 1) {
            echo "<li class='page-item'><a class='btn-primary w-inline-block' style='width: 12vw;margin-left: 10px;' href='#' onclick='listar_usuarios($pag_ant, $qnt_result_pg)'>$pag_ant </a></li>";
        }
    }
    echo '<li class="page-item active">';
    echo '<span class="btn-primary w-inline-block" style="width: 12vw;margin-top:5px;margin-left: 10px;">';
    echo "$pagina";
    echo '</span>';
    echo '</li>';
    
    for ($pag_dep = $pagina + 1; $pag_dep <= $pagina + $max_links; $pag_dep++) {
        if ($pag_dep <= $quantidade_pg) {
            echo "<li class='page-item'><a class='btn-primary w-inline-block' style='width: 12vw;margin-top:5px;' href='#' onclick='listar_usuarios($pag_dep, $qnt_result_pg)'>$pag_dep</a></li>";
        }
    }
    echo '<li class="page-item">';
    echo "<span class='btn-primary w-inline-block'><a href='#' style='color: white;' onclick='listar_usuarios($quantidade_pg, $qnt_result_pg)'>Última</a></span>";
    echo '</li>';
    echo '</ul>';
    echo '</nav>';
    } else {
        echo "<div class='btn-primary w-inline-block' role='alert'>Sem dado disponivel!</div>";
    }