<?php
session_start();
include_once "services/checa_login_adm.php";
checa_login_adm();
include_once "validar_2fa.php";
?>
<?php include 'partials/html.php' ?>

<head>
    <?php $title = "dash";
    include 'partials/title-meta.php' ?>

    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->


    <div class="page-wrapper">

        <!-- Page Content-->
        <div class="page-content">
            <div class="container-xxl">

                <div class="row justify-content-center">
                    <div class="col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">

                                    <div class="col" style="display: flex;align-content: center;align-items: center;">
                                        <div class="tag" style="background: red !important;"></div>
                                        <h4 class="card-title">Saques Recusados</h4>
                                    </div><!--end col-->
                                </div> <!--end row-->
                            </div><!--end card-header-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table  mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Id</th>
                                                <th>Usuario</th>
                                                <th>Transação ID</th>
                                                <th>Valor</th>
                                                <th>Data/Hora</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            global $mysqli;
                                            // Consultar os dados da tabela usuarios
                                            $query_usuarios = "SELECT * FROM solicitacao_saques WHERE status = '2' ORDER BY id DESC";
                                            $result_usuarios = mysqli_query($mysqli, $query_usuarios);

                                            if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                while ($usuario = mysqli_fetch_assoc($result_usuarios)) {

                                                    // ID do usuário para consulta
                                                    $id_user = $usuario['id_user'];

                                                    // Consulta para buscar informações do usuário
                                                    $query_puxarmobile = "SELECT mobile FROM usuarios WHERE id = ?";
                                                    $stmt = $mysqli->prepare($query_puxarmobile);

                                                    if ($stmt) {
                                                        $stmt->bind_param("i", $id_user);
                                                        $stmt->execute();
                                                        $result_puxarmobile = $stmt->get_result();

                                                        // Verifica se houve resultado
                                                        if ($result_puxarmobile && $result_puxarmobile->num_rows > 0) {
                                                            $user_data = $result_puxarmobile->fetch_assoc();
                                                            $mobile = $user_data['mobile'];
                                                        } else {
                                                            $mobile = "N/A"; // Caso o usuário não seja encontrado
                                                        }

                                                        $stmt->close();
                                                    } else {
                                                        echo "Erro na preparação da consulta: " . $mysqli->error;
                                                    }
                                                    // Definir o cargo com base nos dados da tabela (exemplo para afiliado)
                                                    $cargo_badge = ($usuario['status'] == '2') ? "<span class='badge bg-danger'>Recusado</span>" : "<span class='badge bg-secondary'>Usuário</span>";
                                                    ?>
                                                    <tr>
                                                        <td><?= $usuario['id_user']; ?></td>
                                                        <td>
                                                            <?= $mobile; ?>
                                                            <a href="<?= $painel_adm_ver_usuarios . encodeAll($usuario['id_user']); ?>" 
                                                              class="btn btn-primary ms-2">
                                                            <i class="fa-solid fa-square-arrow-up-right"></i>
                                                            </a>
                                                        </td>
                                                        <td><?= $usuario['transacao_id']; ?></td>
                                                        <td>R$ <?= number_format($usuario['valor'], 2, ',', '.'); ?></td>
                                                        <td><?= $usuario['data_registro']; ?></td>



                                                        <td><?= $cargo_badge; ?></td>

                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>Sem dados disponíveis!</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table><!--end /table-->
                                </div><!--end /tableresponsive-->
                            </div><!--end card-body-->

                        </div><!-- container -->
                        <!--Start Rightbar-->
                        <?php include 'partials/endbar.php' ?>
                        <!--end Rightbar-->
                        <!--Start Footer-->
                        <?php include 'partials/footer.php' ?>
                        <!--end footer-->
                    </div>
                    <!-- end page content -->
                </div>
                <!-- end page-wrapper -->

                <!-- Javascript  -->
                <!-- vendor js -->
                <?php include 'partials/vendorjs.php' ?>

                <script src="assets/js/app.min.js"></script>
                <script src="assets/libs/clipboard/clipboard.min.js"></script>
                <script src="assets/js/pages/clipboard.init.js"></script>
                <script src="assets/js/app.min.js"></script>
</body>
<!--end body-->

</html>
