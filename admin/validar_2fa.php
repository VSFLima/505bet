<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once "services/database.php";

function enviarNotificacaoTelegram($username)
{
    global $mysqli;

    // Busca credenciais no banco
    $qry = "SELECT bot_id, chat_id FROM webhook WHERE status = 1 AND bot_id <> '' AND chat_id <> '' LIMIT 1";
    $res = $mysqli->query($qry);

    if (!$res || $res->num_rows === 0) {
        return; // Sem webhook configurado
    }

    $config = $res->fetch_assoc();
    $tokenBot = $config['bot_id'];
    $chatId = $config['chat_id'];

    if (empty($tokenBot) || empty($chatId)) {
        return;
    }

    $urlSite = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
    $mensagem = "Novo 2FA autenticado:\n\n";
    $mensagem .= "Usuário: $username\n";
    $mensagem .= "URL do site: $urlSite/admin";

    $url = "https://api.telegram.org/bot$tokenBot/sendMessage";

    $dados = [
        'chat_id' => $chatId,
        'text' => $mensagem,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($dados));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resposta = curl_exec($ch);
    curl_close($ch);
}

if (basename($_SERVER['SCRIPT_NAME']) === 'validar_2fa.php' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $token = isset($_POST['token']) ? trim($_POST['token']) : '';

    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token não informado.']);
        exit;
    }

    // Check if user is logged in
    if (!isset($_SESSION['data_adm']['id'])) {
        echo json_encode(['success' => false, 'message' => 'Sessão inválida.']);
        exit;
    }

    $adminId = (int)$_SESSION['data_adm']['id'];
    global $mysqli;

    // Fetch user's 2FA hash
    $stmt = $mysqli->prepare("SELECT `2fa`, nome FROM admin_users WHERE id = ? LIMIT 1");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Erro interno.']);
        exit;
    }

    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
        exit;
    }

    $user = $result->fetch_assoc();
    $hash = $user['2fa'];
    $username = $user['nome'];

    if (!empty($hash)) {
        // Validate existing 2FA
        if ($token === $hash) {
            $_SESSION['2fa_verified'] = true;
            $_SESSION['2fa_user_id'] = $adminId;
            $_SESSION['2fa_username'] = $username;

            enviarNotificacaoTelegram($username);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Token incorreto.']);
        }
    } else {
        // First time setup: Set the token as the 2FA (plaintext)
        $newHash = $token;
        
        $update = $mysqli->prepare("UPDATE admin_users SET `2fa` = ? WHERE id = ?");
        $update->bind_param("si", $newHash, $adminId);
        
        if ($update->execute()) {
             $_SESSION['2fa_verified'] = true;
             $_SESSION['2fa_user_id'] = $adminId;
             $_SESSION['2fa_username'] = $username;
             
             enviarNotificacaoTelegram($username);
             echo json_encode(['success' => true, 'message' => '2FA configurado com sucesso.']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Erro ao configurar 2FA.']);
        }
    }
}
?>
