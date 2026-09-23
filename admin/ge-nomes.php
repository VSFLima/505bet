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
        nome = ?, 
        nome_site = ?, 
        descricao = ?, 
        keyword = ?,
        marquee = ?,
        painel_rolante = ?
        WHERE id = 1");

    $qry->bind_param(
        "ssssss",
        $data['nome'],
        $data['nome_site'],
        $data['descricao'],
        $data['keyword'],
        $data['marquee'],
        $data['painel_rolante']
    );
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'nome' => trim(htmlspecialchars($_POST['nome'])),
        'nome_site' => trim(htmlspecialchars($_POST['nome_site'])),
        'descricao' => trim(htmlspecialchars($_POST['descricao'])),
        'keyword' => trim(htmlspecialchars($_POST['keyword'])),
        'marquee' => trim(htmlspecialchars($_POST['marquee'])),
        'painel_rolante' => trim(htmlspecialchars($_POST['painel_rolante']))
    ];

    if (update_config($data)) {
        $toastType = 'success';
        $toastMessage = 'Configurações de nomes atualizadas com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar as configurações. Tente novamente.';
    }
}

$config = get_afiliados_config();
?>

<head>
    <?php $title = "Configurações de Afiliados";
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
                                <h4 class="card-title">Gerenciamento de nomes da plataforma</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <!-- Nome -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-user"></i> Nome da plataforma
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o nome da plataforma, ele será visualizado dentro no dashboard e página inicial do cassino.
                                                    </p>
                                                    <input type="text" name="nome" class="form-control"
                                                        value="<?= $config['nome'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- 
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-group"></i> Nome do Site
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o valor de saque automático, caso o saque seja no valor, ele é enviado diretamente ao jogador.
                                                    </p>
                                                    <input type="text" name="nome_site" class="form-control"
                                                        value="<?= $config['nome_site'] ?>" required>
                                                </div>
                                            </div>
                                        </div>  Nome do Site -->

                                        <!-- Descrição -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-community"></i> Descrição
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite a sua descrição de plataforma, ele será visualizado na página inicial do cassino ao ser compartilhado.
                                                    </p>
                                                    <input type="text" name="descricao" class="form-control"
                                                        value="<?= $config['descricao'] ?>" required>
                                                </div>
                                            </div>
                                        </div>



                                        <!-- Popup Texto Slide (Marquee) -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-percentage-circle"></i> Texto Slide
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o seu texto slide, ele será visualizado na página início do cassino abaixo dos banners.
                                                    </p>
                                                    <input type="text" name="marquee" class="form-control"
                                                        value="<?= $config['marquee'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Painel Rolante (Marquee) -->
                                        <!-- <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-percentage-circle"></i> Painel Rolante
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Digite o seu texto, ele será visualizado na página Centro de mensagens -> Painel Rolante.
                                                    </p>
                                                    <input type="text" name="painel_rolante" class="form-control"
                                                        value="<?= $config['painel_rolante'] ?>" required>
                                                </div>
                                            </div>
                                        </div> -->

                                        <!-- Keyword/SEO -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">
                                                        <i class="iconoir-percentage-circle"></i> Keyword/SEO
                                                    </h5>
                                                    <p class="card-subtitle text-muted mb-2">
                                                        Caso queira aumentar a visibilidade da sua plataforma no Google, crie palavras-chaves que o público geralmente usa. Ex: Tigrinho pagante, casa slots, chinesa pagante.
                                                    </p>
                                                    <input type="text" name="keyword" class="form-control"
                                                        value="<?= $config['keyword'] ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success mb-3">Salvar Configurações</button>
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
