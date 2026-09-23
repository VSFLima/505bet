<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();
checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// ── Garante coluna support_icon_active na tabela config ─────────────────────
$mysqli->query("ALTER TABLE config ADD COLUMN IF NOT EXISTS support_icon_active TINYINT(1) NOT NULL DEFAULT 1");

function get_afiliados_config()
{
    global $mysqli;
    $qry = "SELECT * FROM config WHERE id=1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE config SET 
        telegram = ?, 
        atendimento = ?,
        whatsapp = ?,
        instagram = ?,
        facebook = ?
        WHERE id = 1");

    $qry->bind_param(
        "sssss",
        $data['telegram'],
        $data['atendimento'],
        $data['whatsapp'],
        $data['instagram'],
        $data['facebook']
    );
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Toggle ícone de suporte ──────────────────────────────────────────────
    if ($action === 'toggle_support_icon') {
        $val = (int)$_POST['support_icon_active'];
        $mysqli->query("UPDATE config SET support_icon_active=$val WHERE id=1");
        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    // ── Salvar canais ────────────────────────────────────────────────────────
    $data = [
        'telegram'    => trim(htmlspecialchars($_POST['telegram']    ?? '')),
        'whatsapp'    => trim(htmlspecialchars($_POST['whatsapp']    ?? '')),
        'facebook'    => trim(htmlspecialchars($_POST['facebook']    ?? '')),
        'instagram'   => trim(htmlspecialchars($_POST['instagram']   ?? '')),
        'atendimento' => trim(htmlspecialchars($_POST['atendimento'] ?? ''))
    ];

    if (update_config($data)) {
        $toastType    = 'success';
        $toastMessage = 'Configurações de canais atualizadas com sucesso!';
    } else {
        $toastType    = 'error';
        $toastMessage = 'Erro ao atualizar as configurações. Tente novamente.';
    }
}

$config        = get_afiliados_config();
$supportActive = (int)($config['support_icon_active'] ?? 1);
?>

<head>
    <?php $title = "Configurações de Canais De Atendimento";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">

                        <!-- ── CARD: Ícone de Suporte ──────────────────────── -->
                        <div class="card mt-3">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h4 class="card-title mb-0">Ícone de Suporte Flutuante</h4>
                                <span class="badge <?= $supportActive ? 'bg-success' : 'bg-secondary' ?> fs-6">
                                    <?= $supportActive ? 'ATIVO' : 'INATIVO' ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="p-3 rounded border <?= $supportActive ? 'border-success bg-light' : 'border-secondary bg-light' ?> d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1 fw-bold">Status do Ícone de Atendimento</h5>
                                        <p class="mb-0 text-muted small">
                                            <?= $supportActive
                                                ? 'O ícone de suporte (<code>support-icon</code>) está visível no frontend para todos os usuários.'
                                                : 'O ícone de suporte está oculto no frontend para todos os usuários.' ?>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_support_icon">
                                            <input type="hidden" name="support_icon_active" value="1">
                                            <button class="btn btn-success" <?= $supportActive ? 'disabled' : '' ?>>Ativar</button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_support_icon">
                                            <input type="hidden" name="support_icon_active" value="0">
                                            <button class="btn btn-danger" <?= !$supportActive ? 'disabled' : '' ?>>Desativar</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── CARD: Canais de Atendimento ─────────────────── -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Canais De Atendimento</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">

                                        <!-- Telegram -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-telegram-circle"></i> Canal Do Telegram
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o grupo do telegram ou chat do atendente, ele será visualizado na página de suporte do cassino.
                                                    </p>
                                                    <input type="text" name="telegram" class="form-control"
                                                        value="<?= $config['telegram'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Telegram -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                    <i class="fa-brands fa-square-instagram"></i> Instagram
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o usuario do instagram de suporte, ele será visualizado na página de suporte do cassino.
                                                    </p>
                                                    <input type="text" name="instagram" class="form-control"
                                                        value="<?= $config['instagram'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Telegram -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                    <i class="fa-brands fa-square-whatsapp"></i> WhatsApp
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o grupo do whatsapp ou numero do atendente, ele será visualizado na página de suporte do cassino.
                                                    </p>
                                                    <input type="text" name="whatsapp" class="form-control"
                                                        value="<?= $config['whatsapp'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Telegram -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                    <i class="fa-brands fa-square-facebook"></i> Facebook
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o grupo do facebook, ele será visualizado na página de suporte do cassino.
                                                    </p>
                                                    <input type="text" name="facebook" class="form-control"
                                                        value="<?= $config['facebook'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Atendimento -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-chat-bubble"></i> Atendimento
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o seu canal de atendimento, webchat ou chatbot, ele será visualizado na página de suporte do cassino.
                                                    </p>
                                                    <input type="text" name="atendimento" class="form-control"
                                                        value="<?= $config['atendimento'] ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success mb-3">Salvar Configurações</button>
                                    </div>
                                </form>
                            </div>
                        </div><!-- /card canais -->
                    </div><!-- /col -->
                </div><!-- /row -->
            </div><!-- /container -->

            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>

        </div>
    </div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
    <script>
        function showToast(type, message){window.showToast(type,message);}
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>

</html>