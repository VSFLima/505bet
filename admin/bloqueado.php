<?php
include 'partials/html.php';
?>
<head>
    <meta charset="utf-8" />
    <title>Acesso Bloqueado</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Acesso Bloqueado" name="description" />
    <meta content="" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- App favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico">

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/app.min.css" rel="stylesheet" type="text/css" />
</head>

<body class="account-body accountbg">
    <!-- Log In page -->
    <div class="container">
        <div class="row vh-100 d-flex justify-content-center">
            <div class="col-12 align-self-center">
                <div class="row">
                    <div class="col-lg-5 mx-auto">
                        <div class="card">
                            <div class="card-body p-0 auth-header-box">
                                <div class="text-center p-3">
                                    <a href="/" class="logo logo-admin">
                                        <img src="assets/images/logo-sm.png" height="50" alt="logo" class="auth-logo">
                                    </a>
                                    <h4 class="mt-3 mb-1 fw-semibold text-white font-18">Acesso Bloqueado</h4>
                                    <p class="text-muted  mb-0">Sua conta não tem permissão para acessar esta área.</p>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="p-3">
                                    <div class="alert alert-danger border-0" role="alert">
                                        <strong>Atenção!</strong> Você não tem permissão ou sua conta foi desativada. Entre em contato com o suporte.
                                    </div>
                                    <div class="form-group mb-0 row">
                                        <div class="col-12">
                                            <a href="login" class="btn btn-primary w-100 waves-effect waves-light">Voltar para Login <i class="fas fa-sign-in-alt ms-1"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/feather.min.js"></script>
    <script src="assets/js/simplebar.min.js"></script>
</body>
</html>