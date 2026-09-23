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
                  WHERE ts.status = 'processamento' $where_ids
                  ORDER BY ts.id DESC";

    $result_usuarios = mysqli_query($mysqli, $query_usuarios);
    ?>
    <!-- end leftbar-tab-menu-->


    <div class="page-wrapper">

        <div class="toast-container position-absolute p-3 top-0 end-0" id="toastPlacement"
            data-original-class="toast-container position-absolute p-3">
            <!-- Os toasts serão inseridos dinamicamente aqui -->
        </div>


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
                                        <h4 class="card-title">Depósitos Pendentes</h4>
                                    </div><!--end col-->
                                </div> <!--end row-->
                            </div><!--end card-header-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th><input type="checkbox" id="select-all"></th>
                                                <!-- Checkbox para selecionar todos -->
                                                <th>Id</th>
                                                <th>Usuário</th>
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
                                                    $cargo_badge = ($usuario['status'] == 'processamento') ? "<span class='badge bg-danger'>Pendente</span>" : "<span class='badge bg-secondary'>Usuário</span>";
                                                    ?>
                                                    <tr>
														<td>
                                                            <input type="checkbox" name="ids[]" value="<?= $usuario['id']; ?>">
                                                            <!-- Checkbox para cada registro -->
                                                        </td>
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
                                            }  else {
                                                echo "<tr><td colspan='7' class='text-center'>Sem dados disponíveis!</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table><!--end /table-->

                                    <div class="mt-3">
                                        <button type="button" id="mark-expired" class="btn btn-warning">Marcar como
                                            Expirado</button> <!-- Botão para submeter os selecionados -->
                                    </div>
                                </div><!--end /tableresponsive-->
                            </div><!--end card-body-->
                            <!--end card-body-->

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

                <script src="assets/js/app.js"></script>
                <script src="assets/libs/clipboard/clipboard.min.js"></script>
                <script src="assets/js/pages/clipboard.init.js"></script>
                <script src="assets/js/pages/toast.init.js"></script>
                <script>
                    document.getElementById('select-all').onclick = function () {
                        var checkboxes = document.querySelectorAll('input[name="ids[]"]');
                        for (var checkbox of checkboxes) {
                            checkbox.checked = this.checked;
                        }
                    }

                    document.getElementById('mark-expired').onclick = function () {
                        var checkboxes = document.querySelectorAll('input[name="ids[]"]:checked');
                        var ids = [];
                        for (var checkbox of checkboxes) {
                            ids.push(checkbox.value);
                        }

                        if (ids.length > 0) {
                            // Fazer a requisição Ajax
                            var xhr = new XMLHttpRequest();
                            xhr.open("POST", "ajax/att_status_depositos.php", true);
                            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                            xhr.onreadystatechange = function () {
                                if (xhr.readyState === 4 && xhr.status === 200) {
                                    var response = JSON.parse(xhr.responseText);
                                    if (response.success) {
                                        showToast('success', 'Status atualizado com sucesso!');
                                        setTimeout(function () {
                                            location.reload(); // Atualiza a página após a alteração
                                        }, 2000); // Delay para o toast ser exibido antes de recarregar
                                    } else {
                                        showToast('danger', 'Erro ao atualizar o status.');
                                    }
                                }
                            };

                            // Enviar IDs selecionados
                            xhr.send("ids=" + JSON.stringify(ids));
                        } else {
                            showToast('warning', 'Por favor, selecione ao menos um depósito.');
                        }
                    };

                    function showToast(type, message){window.showToast(type,message);}
                </script>



</body>
<!--end body-->

</html>
