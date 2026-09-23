<?php
session_start();
include_once('../services/database.php');
include_once('../services/funcao.php');
include_once('../services/crud.php');
include_once('../services/crud-adm.php');
include_once('../services/checa_login_adm.php');

checa_login_adm();

global $mysqli;

$pagina = filter_input(INPUT_POST, 'pagina', FILTER_SANITIZE_NUMBER_INT);
$qnt_result_pg = filter_input(INPUT_POST, 'qnt_result_pg', FILTER_SANITIZE_NUMBER_INT);
$inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;

$stmt = mysqli_prepare($mysqli, "SELECT * FROM usuarios WHERE id AND statusaff='1' ORDER BY id DESC");
mysqli_stmt_execute($stmt);
$resultado_usuario = mysqli_stmt_get_result($stmt);

if ($resultado_usuario->num_rows != 0) {
    ?>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-body table-responsive">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">Nome</th>
                                <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">E-mail</th>
                                 <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">Saldo Atual</th>
                                <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">Total Depósitos</th>
                                <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">Total Saques</th>
                                <th class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">Ação </th>
                            </tr>
                            <?php
                            while ($data = mysqli_fetch_assoc($resultado_usuario)) {
                                $RET_SAQUES = total_saques_id($data['id']);
                                $RET_DEPOSITOS = total_dep_pagos_id($data['id']);
                                //$RET_SALDO = saldo_user($data['id']);
                                ?>
                                <tr>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;"><?= $data['nome']; ?></td>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;"><?= $data['email']; ?></td>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">R$ <?= Reais2($RET_SALDO); ?></td>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">R$ <?= Reais2($RET_DEPOSITOS); ?></td>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;">R$ <?= Reais2($RET_SAQUES); ?></td>
                                    <td class="text-center" style="color: white;background-color: #1a1c23;font-family: Inter,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,Apple Color Emoji,Segoe UI Emoji,Segoe UI Symbol,Noto Color Emoji;"><a href="<?= $painel_adm_ver_usuarios . encodeAll($data['id']); ?>" class="flex items-center justify-between px-2 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple"><i class="fa fa-eye"></i> Editar Usuário</a></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php
    $result_pg = "SELECT COUNT(id) AS num_result FROM usuarios";
    $resultado_pg = mysqli_query($mysqli, $result_pg);
    $row_pg = mysqli_fetch_assoc($resultado_pg);
    $quantidade_pg = ceil($row_pg['num_result'] / $qnt_result_pg);
    $max_links = 2;

    echo '<br>';
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center" style="display: flex;flex: 1;justify-content: flex-start;align-items: baseline;">';
    echo '<li class="page-item">';
    echo "<span class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-left: 10px;'><a href='#' onclick='listar_afiliado(1, $qnt_result_pg)'>Primeira</a> </span>";
    echo '</li>';
    for ($pag_ant = $pagina - $max_links; $pag_ant <= $pagina - 1; $pag_ant++) {
        if ($pag_ant >= 1) {
            echo "<li class='page-item'><a class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-left: 10px;' href='#' onclick='listar_afiliado($pag_ant, $qnt_result_pg)'>$pag_ant </a></li>";
        }
    }
    echo '<li class="page-item active">';
    echo '<span class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple" style="width: 12vw;margin-top:5px;margin-left: 10px;">';
    echo "$pagina";
    echo '</span>';
    echo '</li>';
    
    for ($pag_dep = $pagina + 1; $pag_dep <= $pagina + $max_links; $pag_dep++) {
        if ($pag_dep <= $quantidade_pg) {
            echo "<li class='page-item'><a class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw;margin-top:5px;' href='#' onclick='listar_afiliado($pag_dep, $qnt_result_pg)'>$pag_dep</a></li>";
        }
    }
    echo '<li class="page-item">';
    echo "<span class='flex items-center justify-between w-full px-4 py-2 text-sm font-medium leading-5 text-white transition-colors duration-150 bg-purple-600 border border-transparent rounded-lg active:bg-purple-600 hover:bg-purple-700 focus:outline-none focus:shadow-outline-purple' style='width: 12vw; margin-top:5px;margin-left: 10px;'><a href='#' onclick='listar_afiliado($quantidade_pg, $qnt_result_pg)'>Última</a></span>";
    echo '</li>';
    echo '</ul>';
    echo '</nav>';
    } else {
        echo "<div class='text-300 medium color-neutral-100' style='display: flex;align-items: center;margin: 20px;'><div class='tag'></div><h4 style='margin-top: 10px;'>Sem dado disponivel!</span></h4></div>";
    }