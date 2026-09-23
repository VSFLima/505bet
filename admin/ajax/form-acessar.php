<?php
session_set_cookie_params(60 * 60 * 24 * 5); // 5 dias em segundos
session_start();
include_once('../logs/registrar_logs.php');
include_once('../services/database.php');
include_once('../services/funcao.php');

function exibirAlerta($tipo, $titulo, $mensagem) {
    echo "<div class='alert alert-$tipo alert-dismissible fade show border-start border-2 border-$tipo mb-0' role='alert'>
            <div class='d-flex align-items-center gap-2'>
                <i class='fas fa-".($tipo == 'danger' ? 'skull-crossbones' : 'check-circle')." align-self-center fs-30 text-$tipo'></i>
                <div class='flex-grow-1 ms-2 text-truncate'>
                    <h5 class='mb-1 fw-bold mt-0'>$titulo</h5>
                    <p class='mb-0'>$mensagem</p>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
            </div>
          </div>";
}


if (isset($_POST['email'], $_POST['senha'], $_POST['_csrf'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = PHP_SEGURO($_POST['senha']);
    $CRSF = PHP_SEGURO($_POST['_csrf']);

    if (empty($CRSF)) {
        exibirAlerta('danger', 'Erro', 'Oops! Houve um erro ao obter dados. Atualize sua página.');
        exit;
    }
    if (empty($email)) {
        exibirAlerta('danger', 'Erro', 'Oops! Insira um e-mail no formulário.');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exibirAlerta('danger', 'Erro', 'Oops! Insira um e-mail válido no formulário.');
        exit;
    }
    if (empty($senha)) {
        exibirAlerta('danger', 'Erro', 'Oops! Insira sua senha no formulário.');
        exit;
    }

    $stmt = $mysqli->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($senha, $row['senha'])) {
            $user_idx = $row['id'];
            $emailbd = $row['email'];
            $data = date('Y-m-d H:i:s');
            $_token_easy = md5(sha1(mt_rand()) . $data . $emailbd . $user_idx);
            $_token = CRIPT_AES('encrypt', $_token_easy);
            
            $_SESSION['token_adm_encrypted'] = CRIPT_AES('encrypt', preg_replace("/[^0-9]+/", "", $user_idx));
            $_SESSION['crsf_token_adm'] = $_token;
            $_SESSION['anti_crsf_token_adm'] = $CRSF;
            
            // Previne Session Fixation
            session_regenerate_id(true);

            registrarLog($mysqli, $email, "<span class='status-badge green' style='display: inline-block;'><i class='fa fa-sign-out'></i></span> Logou no painel admin");
            
            $_SESSION['2fa_verified'] = false;

            exibirAlerta('success', 'Sucesso', 'Acessando Conta, aguarde....');
            echo "<script>setTimeout(() => window.location.href = '{$painel_adm}', 3000);</script>";
        } else {
            exibirAlerta('danger', 'Erro', 'Revise os dados inseridos.');
        }
    } else {
        exibirAlerta('danger', 'Erro', 'Seus dados não foram encontrados.');
    }
} else {
    exibirAlerta('danger', 'Erro', 'Dados incompletos no formulário.');
}
?>