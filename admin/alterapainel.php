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




$configFile = '../config.php';


$configContent = file_get_contents($configFile);


preg_match("/define\('DASH',\s*'([^']+)'\);/", $configContent, $matches);
$currentDirName = $matches[1] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newDirName = $_POST['dir_name'] ?? '';


    // Validação de segurança: permite apenas letras, números, hífens e underscores
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $newDirName)) {
        echo "<script>alert('Nome do diretório inválido! Apenas letras e números são permitidos.'); window.history.back();</script>";
        exit;
    }

    $newConfigContent = preg_replace(
        "/define\('DASH',\s*'([^']+)'\);/",
        "define('DASH', '$newDirName');",
        $configContent
    );

    file_put_contents($configFile, $newConfigContent);

    rename("../$currentDirName", "../$newDirName");
   exit;
}
?>



<head>
    <?php $title = admin_t('page_alter_panel_title');
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
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= admin_t('page_alter_panel_title') ?></h4>
                            </div>

                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">

                                        <!-- Atendimento -->
                                        <div class="col-md-12">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-chat-bubble"></i> <?= admin_t('page_alter_panel_title') ?>
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        <?= admin_t('page_alter_panel_subtitle') ?>
                                                    </p>
                                                    <input type="text" id="dir_name" name="dir_name" class="form-control" value="<?php echo htmlspecialchars($currentDirName); ?>" readonly = "">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success mb-3"><?= admin_t('button_save_settings') ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
