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

?>

<head>
    <?php $title = "Histórico de Níveis Ganhos";
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
                            <h4 class="card-title">Tutoriais Administrativos</h4>
                        </div>
                        
                        <div class="card-body">
                            <div class="accordion" id="tutorialAccordion">
                            <!-- Tutorial 1 -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        ALTERANDO LOGIN ADMINISTRATIVO
                                    </button>
                                </h2>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#tutorialAccordion">
                                    <div class="accordion-body">
                                        <video class="w-100 mb-3" controls>
                                            <source src="videos/alterando_admin.mp4" type="video/mp4">
                                            Seu navegador não suporta o vídeo.
                                        </video>
                                        <p>
                                            Neste tutorial, você aprenderá como alterar os dados administrativos de forma segura e prática. 
                                            Certifique-se de ter permissões adequadas antes de realizar qualquer alteração.
                                        </p>
                                    </div>
                                </div>
                            </div>


                                <!-- Tutorial 2 -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            CONFIGURANDO RTP E MODO INFLUENCIADOR (API CLONES)
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#tutorialAccordion">
                                        <div class="accordion-body">
                                            <video class="w-100 mb-3" controls>
                                                <source src="videos/configurando_rtp_modo_influenciador.mp4" type="video/mp4">
                                                Seu navegador não suporta o vídeo.
                                            </video>
                                            <p>
                                                Neste tutorial, exploramos as melhores práticas para gerenciar rtp e ativação do modo influenciador na API Clones.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tutorial 3 -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            CONFIGURANDO VALORES DA CASA (SAQUES, DEPÓSITOS, BAÚS E OUTROS)
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#tutorialAccordion">
                                        <div class="accordion-body">
                                            <video class="w-100 mb-3" controls>
                                                <source src="videos/valores_da_plataforma.mp4" type="video/mp4">
                                                Seu navegador não suporta o vídeo.
                                            </video>
                                            <p>
                                                Veja como configurar os valores das casas como depósitos, saques, quantidades de baús e outras funcionalidades.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tutorial 4 -->
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            CONFIGURANDO WEBHOOK (RECEBER NOTIFICAÇÕES DE CADASTROS, PIXS GERADOS E PAGOS) NO TELEGRAM
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#tutorialAccordion">
                                        <div class="accordion-body">
                                            <video class="w-100 mb-3" controls>
                                                <source src="videos/configurando_webhook.mp4" type="video/mp4">
                                                Seu navegador não suporta o vídeo.
                                            </video>
                                            <p>
                                                Veja como configurar o sistema de notificações para ser notificado de cadastros, pixs gerados ou pagos dentro da sua plataforma.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
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

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
