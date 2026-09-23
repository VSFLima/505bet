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
$result_usuario = "SELECT * FROM logs WHERE id ORDER BY id DESC LIMIT 50";
$resultado_usuario = mysqli_query($mysqli, $result_usuario);
//Verificar se encontrou resultado na tabela "usuarios"
if(($resultado_usuario) AND ($resultado_usuario->num_rows != 0)){?>

<div class="table-main-container">
    <?php while ($data = mysqli_fetch_assoc($resultado_usuario)) { ?>
        <div class="recent-orders-table-row" style="display: flex; justify-content: space-between;">
            <div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
                <div class="paragraph-small color-neutral-100" style="font-size: 12px;"><?=$data['email'];?></div>
            </div>
            <div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
                <div class="paragraph-small color-neutral-100" style="font-size: 12px;"><?=$data['action'];?></div>
            </div>
            <div id="w-node-_0b909741-5d5c-5abc-5f7b-bcb01b609a5f-1b609a5a" class="flex align-center">
                <div class="paragraph-small color-neutral-100" style="font-size: 12px;"><?=ver_data($data['timestamp']);?></div>
            </div>
        </div>
    <?php } ?>
</div>

<?php
    //Paginação - Somar a quantidade de usuários
    $result_pg = "SELECT COUNT(id) AS num_result FROM transacoes WHERE status='processamento'";
    $resultado_pg = mysqli_query($mysqli, $result_pg);
    $row_pg = mysqli_fetch_assoc($resultado_pg);

    //Quantidade de pagina
    $quantidade_pg = ceil($row_pg['num_result'] / $qnt_result_pg);

    //Limitar os link antes depois
    $max_links = 2;

    //Código de paginação pode ser adicionado aqui

} else {
    echo "<div class='text-300 medium color-neutral-100' style='display: flex;align-items: center;margin: 20px;'><div class='tag'></div><h4 style='margin-top: 10px;'>Sem dado disponivel!</span></h4></div>";
}
?>
