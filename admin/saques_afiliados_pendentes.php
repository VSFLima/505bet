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
                                        <div class="tag" style="background: yellow !important;"></div>
                                        <h4 class="card-title">Saques De Afiliados Pendentes</h4>
                                    </div><!--end col-->
                                </div> <!--end row-->
                            </div><!--end card-header-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table  mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Id</th>
                                                <th>Transação ID</th>
                                                <th>Valor</th>
                                                <th>Data/Hora</th>
                                                <th>Chave Pix</th>
                                                <th>Status</th>
                                                <th>Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            global $mysqli;
                                            // Consultar os dados da tabela usuarios
                                            $query_usuarios = "SELECT * FROM solicitacao_saques WHERE status = '0' AND tipo_saque = '1' ORDER BY id DESC";
                                            $result_usuarios = mysqli_query($mysqli, $query_usuarios);


                                            if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                                    $cargo_badge = ($usuario['status'] == '0') ? "<span class='badge bg-danger'>Em Analise</span>" : "<span class='badge bg-secondary'>Usuário</span>";

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

                                                    $chaveatt = localizarchavepix($usuario['pix']);
                                            ?>
                                                    <tr>
                                                        <td><?= $usuario['id']; ?></td>
                                                        <td><?= $usuario['transacao_id']; ?></td>
                                                        <td>R$ <?= number_format($usuario['valor'], 2, ',', '.'); ?></td>
                                                        <td><?= $usuario['data_registro']; ?></td>
                                                        <td><?= $chaveatt['chave'] ?></td>
                                                        <td><?= $cargo_badge; ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-edit-saque"
                                                                data-id="<?= $usuario['id']; ?>"
                                                                data-transacao="<?= $usuario['transacao_id']; ?>"
                                                                data-valor="<?= number_format($usuario['valor'], 2, ',', '.'); ?>"
                                                                data-chave="<?= $chaveatt['chave']; ?>"
                                                                data-usuario="<?= $mobile; ?>"
                                                                data-status="<?= $usuario['status']; ?>">Editar</button>
                                                        </td>
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
                        </div>
                    </div><!-- container -->
                    
                                                <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
                    
                </div>
            </div>
        </div>

        <!-- Modal Editar Saque -->
        <div class="modal fade" id="modalEditarSaque" tabindex="-1" aria-labelledby="modalEditarSaqueLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarSaqueLabel">Editar Saque</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditarSaque">
                            <input type="hidden" id="saqueId" name="saqueId">
                            <input type="hidden" id="_csrf" name="_csrf" value="<?= md5(uniqid()) ?>">
                            <!-- CSRF token -->
                            <div class="mb-3">
                                <label for="transacaoId" class="form-label">Transação ID</label>
                                <input type="text" class="form-control" id="transacaoId" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="valorSaque" class="form-label">Valor</label>
                                <input type="text" class="form-control" id="valorSaque" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="chavePix" class="form-label">Chave Pix</label>
                                <input type="text" class="form-control" id="chavePix" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="2fa" class="form-label">2FA</label>
                                <input type="text" class="form-control" id="2fa" name="2fa">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" id="btnAprovarSaque">Aprovar</button>
                        <button type="button" class="btn btn-danger" id="btnRecusarSaque">Recusar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <?php include 'partials/vendorjs.php' ?>
        <script src="assets/js/app.js"></script>
        <script src="assets/libs/clipboard/clipboard.min.js"></script>
        <script src="assets/js/pages/clipboard.init.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Evento para abrir o modal e preencher as informações do saque
                const btnsEdit = document.querySelectorAll('.btn-edit-saque');
                btnsEdit.forEach(btn => {
                    btn.addEventListener('click', function () {
                        const saqueId = this.getAttribute('data-id');
                        const transacaoId = this.getAttribute('data-transacao');
                        const valor = this.getAttribute('data-valor');
                        const chavePix = this.getAttribute('data-chave');

                        // Preenche os campos do modal
                        document.getElementById('saqueId').value = saqueId;
                        document.getElementById('transacaoId').value = transacaoId;
                        document.getElementById('valorSaque').value = valor;
                        document.getElementById('chavePix').value = chavePix;

                        // Exibe o modal
                        const modal = new bootstrap.Modal(document.getElementById('modalEditarSaque'));
                        modal.show();
                    });
                });

                // Evento para aprovar saque
                document.getElementById('btnAprovarSaque').addEventListener('click', function () {
                    const transacaoId = document.getElementById('transacaoId').value;
                    const codigo2fa = document.getElementById('2fa').value;

                    // Enviar os dados via POST para payment_manual.php
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", `services-gateway/payment_manual.php?id=${transacaoId}&codigo_2fa=${codigo2fa}`, true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);

                            // Verifica o status da resposta e exibe a mensagem apropriada
                            if (response.success) {
                                showToast('success', response.message || 'Saque aprovado com sucesso!');
                                // Fechar o modal após a resposta
                                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarSaque'));
                                modal.hide();
                                // Recarregar a página após o toast
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showToast('danger', response.message || 'Erro ao aprovar o saque.');
                            }
                        } else if (xhr.readyState === 4) {
                            showToast('danger', 'Erro ao aprovar o saque.');
                        }
                    };
                    xhr.send();  // Não precisamos enviar parâmetros via body, já que o transacao_id vai na URL.
                });

                // Evento para recusar saque
                document.getElementById('btnRecusarSaque').addEventListener('click', function () {
                    const saqueId = document.getElementById('saqueId').value;
                    const _csrf = document.getElementById('_csrf').value;
                    const email_reprovado = document.getElementById('chavePix').value;
                    const valor_reprovado = document.getElementById('valorSaque').value;

                    // Enviar os dados via AJAX para o servidor
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "ajax/recusar_saque.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'success') {
                                showToast('success', response.message);
                                // Fechar o modal após a resposta
                                const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarSaque'));
                                modal.hide();
                                // Recarregar a página após o toast
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000); // Delay para o toast ser exibido antes de recarregar
                            } else {
                                showToast('danger', response.message);
                            }
                        }
                    };
                    xhr.send(`att-pay=1&_csrf=${_csrf}&id_pay=${saqueId}&email_reprovado=${email_reprovado}&valor_reprovado=${valor_reprovado}`);
                });

                function showToast(type, message){window.showToast(type,message);}
            });
        </script>


</body>
<!--end body-->

</html>
