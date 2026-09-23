<?php
include_once __DIR__ . '/database.php';
include_once __DIR__ . '/funcao.php';
function checa_login_user()
{
    if (!isset($_SESSION['token_usuarios_encrypted'], $_SESSION["crsf_token_usuarios"], $_SESSION["anti_crsf_token_usuarios"])) {
        header('Location: ' . $_SESSION['url_base']);
        exit();
    }
}

if (isset($_SESSION['token_usuarios_encrypted'], $_SESSION["crsf_token_usuarios"], $_SESSION["anti_crsf_token_usuarios"])) {
    $view_id_user_decrypted = CRIPT_AES('decrypt', $_SESSION["token_usuarios_encrypted"]);
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($mysqli, $query);
    mysqli_stmt_bind_param($stmt, "s", $view_id_user_decrypted);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $_SESSION['data_user'] = mysqli_fetch_assoc($result);
    }
}
