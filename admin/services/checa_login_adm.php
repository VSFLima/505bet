<?php
include_once __DIR__ . '/database.php';
include_once __DIR__ . '/funcao.php';
function checa_login_adm()
{
	global $painel_adm_acessar, $mysqli, $painel_adm;
	
    // 1. Verificação básica de tokens de sessão
	if (!isset($_SESSION['token_adm_encrypted']) || !isset($_SESSION["crsf_token_adm"]) || !isset($_SESSION["anti_crsf_token_adm"])) {
        session_destroy();
		header('Location: ' . $painel_adm_acessar . ''); 
		exit();
	}

    // 2. Validação contra o Banco de Dados (Segurança Reforçada)
    if (isset($_SESSION['token_adm_encrypted'])) {
        // Descriptografa o ID do usuário
        $view_id_user_decrypted = CRIPT_AES('decrypt', $_SESSION["token_adm_encrypted"]);
        
        // Sanitização do ID para prevenir SQL Injection
        $safe_id = mysqli_real_escape_string($mysqli, $view_id_user_decrypted);
        
        // Consulta buscando usuário ativo (status = 1)
        $query = "SELECT * FROM admin_users WHERE id = '$safe_id' AND status = 1 LIMIT 1";
        $result = mysqli_query($mysqli, $query);

        // Se não encontrar usuário ou status != 1
        if (!$result || mysqli_num_rows($result) === 0) {
            session_unset();
            session_destroy();
            
            // Determina URL de bloqueio (absoluta ou relativa)
            $blocked_url = isset($painel_adm) ? $painel_adm . 'bloqueado.php' : 'bloqueado.php';
            header('Location: ' . $blocked_url);
            exit();
        }

        // 3. Atualiza dados da sessão com informações frescas do banco
        $row = mysqli_fetch_assoc($result);
        $_SESSION['data_adm'] = $row;
        
        // 4. Verificação final de status (Redundância de segurança)
        if ($_SESSION['data_adm']['status'] != '1') {
             session_destroy();
             $blocked_url = isset($painel_adm) ? $painel_adm . 'bloqueado.php' : 'bloqueado.php';
             header('Location: ' . $blocked_url);
             exit();
        }
    }
}
