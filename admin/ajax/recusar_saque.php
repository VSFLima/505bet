<?php
  #======================================#
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  #======================================#
  session_start();
  include_once('../services/database.php');
  include_once('../services/funcao.php');
  include_once('../services/crud-adm.php');
  include_once('../services/crud.php');
  include_once('../logs/registrar_logs.php');
  include_once('../services/checa_login_adm.php');
  include_once("../services/CSRF_Protect.php");
  $csrf = new CSRF_Protect();
  #======================================#
  #expulsa user
  checa_login_adm();
  #======================================#

if (isset($_POST['att-pay']) && isset($_POST['_csrf']) && isset($_POST['id_pay']) && isset($_POST['email_reprovado']) && isset($_POST['valor_reprovado'])) {
    #----------------------------------------------#
    $id_pay =  PHP_SEGURO($_POST['id_pay']);
    $email_pay =  PHP_SEGURO($_POST['email_reprovado']);
    $valor_pay = $_POST['valor_reprovado'];

    // Remover os pontos (separador de milhar)
    $valor_pay = str_replace('.', '', $valor_pay);
    
    // Substituir a vírgula por ponto (para decimal)
    $valor_pay = str_replace(',', '.', $valor_pay);
    
    // Agora converte para float
    $valor_pay = floatval($valor_pay);
    $CSRF =   PHP_SEGURO($_POST['_csrf']);
    $data = date('Y-m-d H:i:s');
    #----------------------------------------------#

    // Verifica se o CSRF está vazio
    if (empty($CSRF)) {
        echo json_encode(['status' => 'error', 'message' => 'Houve um erro ao obter dados. Atualize sua página.']);
        exit;
    }

    // Executa a query de atualização
    $sql = $mysqli->prepare("UPDATE solicitacao_saques SET data_att=?,status=2 WHERE id=?");
    $sql->bind_param("si", $data, $id_pay);

    if ($sql->execute()) {
        // Se a query foi bem-sucedida, processa o saldo e loga a operação
        enviarSaldo($email_pay, $valor_pay);
        registrarLog($mysqli, $_SESSION['data_adm']['email'], 'Recusou o saque ' . $id_pay);

        // Responde com sucesso em JSON
        echo json_encode(['status' => 'success', 'message' => 'Saque recusado com sucesso!']);
    } else {
        // Responde com erro em JSON
        echo json_encode(['status' => 'error', 'message' => 'Não foi possível recusar o saque.']);
    }

    $mysqli->close();
    exit;
}
?>
