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

    <?php
    global $mysqli;
    $email_adm = $_SESSION['data_adm']['email'] ?? '';

    // Buscar invite_codes das BSPay id 1 e 2
    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }

    // Buscar todos os usuários
    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }

    // Identificar a qual rede pertence cada usuário
    $ids_rede_1 = [];
    $ids_rede_2 = [];
    $ids_sem_rede = [];

    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        $found_rede = null;

        if (empty($current_code)) {
            $found_rede = null;
        } else {
            while ($current_code && $max_depth-- > 0) {
                if ($current_code == $invite_code_bspay_1) {
                    $found_rede = 1;
                    break;
                }
                if ($current_code == $invite_code_bspay_2) {
                    $found_rede = 2;
                    break;
                }
                $found = false;
                foreach ($usuarios as $u) {
                    if ($u['invite_code'] == $current_code) {
                        $current_code = $u['invitation_code'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $found_rede = null;
                    break;
                }
            }
        }

        if ($found_rede === 1) {
            $ids_rede_1[] = $id;
        } elseif ($found_rede === 2) {
            $ids_rede_2[] = $id;
        } else {
            $ids_sem_rede[] = $id;
        }
    }

    // Filtro de IDs conforme a sessão
    if ($email_adm === 'vxciian@gmail.com') {
        $ids_filtro = array_diff($ids_rede_2, $ids_rede_1);
    } else {
        $ids_filtro = array_merge($ids_sem_rede, $ids_rede_1);
    }

    // Montar filtro SQL
    if (!empty($ids_filtro)) {
        $ids_implode = implode(',', $ids_filtro);
        $where_ids = "AND ts.usuario IN ($ids_implode)";
    } else {
        $where_ids = "AND 0";
    }

    // Consultar os dados da tabela usuarios com filtro de sessão
    $query_usuarios = "SELECT ts.*, us.mobile AS nomeusuario, us.id, 
                  (SELECT u2.mobile FROM usuarios AS u2 WHERE u2.id = us.invitation_code LIMIT 1) AS influencer 
                  FROM transacoes AS ts
                  INNER JOIN usuarios AS us ON us.id = ts.usuario  
                  WHERE ts.status = 'pago' $where_ids
                  ORDER BY ts.id DESC";

    $result_usuarios = mysqli_query($mysqli, $query_usuarios);
    ?>

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
                                        <div class="tag"></div>
                                        <h4 class="card-title">
                                            Depósitos Pagos
                                            <span style="font-size:16px;font-weight:normal;color:#666;">
                                                (Total: R$
                                                <?php
                                                // Calcular o total de depósitos pagos do resultado já filtrado
                                                $total_depositos = 0.00;
                                                if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                    mysqli_data_seek($result_usuarios, 0); // Garante início do ponteiro
                                                    while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                                        $total_depositos += floatval($usuario['valor']);
                                                    }
                                                    mysqli_data_seek($result_usuarios, 0); // Volta ponteiro para exibição da tabela
                                                }
                                                echo number_format($total_depositos, 2, ',', '.');
                                                ?>
                                                )
                                            </span>
                                        </h4>
                                    </div><!--end col-->
                                </div> <!--end row-->
                            </div><!--end card-header-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table  mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Id</th>
                                                <th>Nome</th>
                                                <th>Influencer</th>
                                                <th>Transação ID</th>
                                                <th>Valor</th>
                                                <th>Data/Hora</th>
                                                <th>Copia E Cola</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php


                                            if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                                    $cargo_badge = "<span class='badge bg-success'>Pago</span>";
                                            ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($usuario['usuario']); ?></td>
                                                        <td><?= htmlspecialchars($usuario['nomeusuario']); ?></td>
                                                        <td><?= htmlspecialchars($usuario['influencer']); ?></td>
                                                        <td><?= htmlspecialchars($usuario['transacao_id']); ?></td>
                                                        <td>R$ <?= number_format($usuario['valor'], 2, ',', '.'); ?></td>
                                                        <td><?= htmlspecialchars($usuario['data_registro']); ?></td>
                                                        <td>
                                                            <span><?= htmlspecialchars(substr($usuario['code'], 0, 10)); ?>...</span>
                                                            <button type="button" class="btn btn-primary btn-clipboard ms-2"
                                                                data-clipboard-text="<?= htmlspecialchars($usuario['code']); ?>">
                                                                <i class="far fa-copy"></i>
                                                            </button>
                                                        </td>
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
