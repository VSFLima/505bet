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

<?php
include_once "validar_2fa.php";
?>

<?php
// ...existing code...

global $mysqli;

// Defina o invite_code alvo conforme o admin logado
$invite_code_bspay_1 = '';
$invite_code_bspay_2 = '';
$email_adm = $_SESSION['data_adm']['email'] ?? '';

// Busca os invite_codes das BSPay id 1 e 2
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

// Buscar todos os usuários da rede de afiliados cujo invitation_code na hierarquia leve ao invite_code_bspay
function getUsuariosDaRede($mysqli, $invite_code_bspay)
{
    $ids = [];
    $sql = "SELECT id, invite_code, invitation_code FROM usuarios";
    $res = $mysqli->query($sql);
    $usuarios = [];
    while ($row = $res->fetch_assoc()) {
        $usuarios[$row['id']] = $row;
    }
    foreach ($usuarios as $id => $user) {
        $current_code = $user['invitation_code'];
        $max_depth = 10;
        while ($current_code && $max_depth-- > 0) {
            if ($current_code == $invite_code_bspay) {
                $ids[] = $id;
                break;
            }
            // Procurar o próximo na hierarquia
            $found = false;
            foreach ($usuarios as $u) {
                if ($u['invite_code'] == $current_code) {
                    $current_code = $u['invitation_code'];
                    $found = true;
                    break;
                }
            }
            if (!$found) break;
        }
    }
    return $ids;
}

// IDs dos usuários das redes BSPay 1 e 2
$ids_rede_1 = getUsuariosDaRede($mysqli, $invite_code_bspay_1);
$ids_rede_2 = getUsuariosDaRede($mysqli, $invite_code_bspay_2);

// IDs dos usuários que não pertencem a nenhuma das redes
$sql = "SELECT id FROM usuarios";
$res = $mysqli->query($sql);
$ids_todos = [];
while ($row = $res->fetch_assoc()) {
    $ids_todos[] = $row['id'];
}
$ids_outros = array_diff($ids_todos, $ids_rede_1, $ids_rede_2);

// Seleção de IDs conforme a sessão
if ($email_adm === 'vxciian@gmail.com') {
    $ids_rede = $ids_rede_2;
} else {
    // Mostra todos os saques dos usuários que NÃO estão nas redes BSPay 1 e 2
    $ids_rede = $ids_outros;
}

$ids_placeholder = implode(',', array_fill(0, count($ids_rede), '?'));

// Seleção de IDs conforme a sessão
if ($email_adm === 'vxciian@gmail.com') {
    $ids_rede = $ids_rede_2;
} else {
    $ids_rede = array_merge($ids_outros, $ids_rede_1);
}

if (empty($ids_rede)) {
    $result_usuarios = false;
} else {
    $ids_placeholder = implode(',', array_fill(0, count($ids_rede), '?'));
    $query_usuarios = "
        SELECT ss.*, u.statusaff
        FROM solicitacao_saques ss
        INNER JOIN usuarios u ON ss.id_user = u.id
        WHERE ss.status IN (0, 3, 4)
          AND ss.id_user IN ($ids_placeholder)
        ORDER BY ss.id DESC
    ";
    $stmt = $mysqli->prepare($query_usuarios);
    $types = str_repeat('i', count($ids_rede));
    $stmt->bind_param($types, ...$ids_rede);
    $stmt->execute();
    $result_usuarios = $stmt->get_result();
}
?>

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
                                        <h4 class="card-title">Saques Pendentes</h4>
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
                                                <th>Chave Pix</th>
                                                <th>Influenciador</th>
                                                <th>Status</th>
                                                <th>Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // global $mysqli;
                                            // // Consultar os dados da tabela solicitacao_saques com os status 0, 3 e 4
                                            // $query_usuarios = "SELECT * FROM solicitacao_saques WHERE status IN (0, 3, 4) ORDER BY id DESC";
                                            // $result_usuarios = mysqli_query($mysqli, $query_usuarios);

                                            if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                                while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                                    // Definir o cargo_badge com base no status do saque
                                                    switch ($usuario['status']) {
                                                        case 0:
                                                            $cargo_badge = "<span class='badge bg-danger'>Em Análise</span>";
                                                            break;
                                                        case 3:
                                                            $cargo_badge = "<span class='badge bg-warning'>Em Processamento</span>";
                                                            break;
                                                        case 4:
                                                            $cargo_badge = "<span class='badge bg-success'>Aceitando</span>";
                                                            break;
                                                        default:
                                                            $cargo_badge = "<span class='badge bg-secondary'>Desconhecido</span>";
                                                            break;
                                                    }
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

                                                    // Buscar CPF do usuário cadastrado
                                                    $cpf_user_saque = '';
                                                    $stmt_cpf = $mysqli->prepare("SELECT cpf FROM usuarios WHERE id = ? LIMIT 1");
                                                    if ($stmt_cpf) {
                                                        $stmt_cpf->bind_param("i", $id_user);
                                                        $stmt_cpf->execute();
                                                        $stmt_cpf->bind_result($cpf_user_saque);
                                                        $stmt_cpf->fetch();
                                                        $stmt_cpf->close();
                                                    }

                                            ?>
                                                    <tr>
                                                        <td><?= $usuario['id']; ?></td>
                                                        <td>
                                                            <?= $mobile; ?>
                                                            <a href="<?= $painel_adm_ver_usuarios . encodeAll($usuario['id_user']); ?>"
                                                                class="btn btn-primary btn-icon ms-2">
                                                                <i class="ti ti-arrow-up-right"></i>
                                                            </a>
                                                        </td>
                                                        <td><?= $usuario['transacao_id']; ?></td>
                                                        <td>R$ <?= number_format($usuario['valor'], 2, ',', '.'); ?></td>
                                                        <td><?= $usuario['data_registro']; ?></td>
                                                        <td><?= $chaveatt['chave'] ?></td>
                                                        <td><?php
                                                            if ($usuario['statusaff'] == 1) {
                                                                echo "<span class='badge bg-danger'>Sim</span>";
                                                            } else {
                                                                echo "<span class='badge bg-warning'>Não</span>";
                                                            }
                                                            ?></td>
                                                        <td><?= $cargo_badge; ?></td>
                                                        <td>
                                                            <button class="btn btn-warning btn-edit-saque"
                                                                data-id="<?= $usuario['id']; ?>"
                                                                data-id-user="<?= $usuario['id_user']; ?>"
                                                                data-transacao="<?= $usuario['transacao_id']; ?>"
                                                                data-valor="<?= number_format($usuario['valor'], 2, ',', '.'); ?>"
                                                                data-chave="<?= $chaveatt['chave']; ?>"
                                                                data-usuario="<?= $mobile; ?>"
                                                                data-nome="<?= $chaveatt['realname']; ?>"
                                                                data-cpf="<?= htmlspecialchars($cpf_user_saque); ?>"
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
        <div class="modal fade" id="modalEditarSaque" tabindex="-1" aria-labelledby="modalEditarSaqueLabel" aria-hidden="true">
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
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="nome" readonly>
                            </div>

                            <div class="mb-3">
                                <label for="chavePix" class="form-label">Chave Pix</label>
                                <input type="text" class="form-control" id="chavePix" readonly>
                            </div>

                            <input type="hidden" id="usuario" name="usuario">

                            <!-- Select para Status -->
                            <div class="mb-3">
                                <label for="statusSaque" class="form-label">Status</label>
                                <select class="form-select" id="statusSaque" name="statusSaque">
                                    <option value="processando">Em Processamento</option>
                                    <option value="aceitando">Aceitando</option>
                                    <option value="aprovado">Aprovado</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="2fa" class="form-label">2FA</label>
                                <input type="text" class="form-control" id="2fa" name="2fa">
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" id="btnAprovarEcompag">
                            <i class="ti ti-credit-card me-1"></i>Aprovar Ecompag
                        </button>
                        <button type="button" class="btn btn-outline-success" id="btnMarcarPago">
                            <i class="ti ti-check me-1"></i>Marcar Pago
                        </button>
                        <button type="button" class="btn btn-warning" id="btnEstornar">
                            <i class="ti ti-arrow-back-up me-1"></i>Estornar
                        </button>
                        <button type="button" class="btn btn-secondary" id="btnAtualizarSaque">
                            <i class="ti ti-pencil me-1"></i>Atualizar Status
                        </button>
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
            document.addEventListener('DOMContentLoaded', function() {

                // ── Abrir modal ──
                document.querySelectorAll('.btn-edit-saque').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.getElementById('saqueId').value     = this.dataset.id;
                        document.getElementById('transacaoId').value = this.dataset.transacao;
                        document.getElementById('valorSaque').value  = this.dataset.valor;
                        document.getElementById('chavePix').value    = this.dataset.chave;
                        document.getElementById('usuario').value     = this.dataset.usuario;
                        document.getElementById('nome').value        = this.dataset.nome;
                        document.getElementById('statusSaque').value = this.dataset.status;
                        // Guardar dados extras no modal
                        document.getElementById('modalEditarSaque').dataset.idUser = this.dataset.idUser;
                        document.getElementById('modalEditarSaque').dataset.cpf    = this.dataset.cpf;
                        new bootstrap.Modal(document.getElementById('modalEditarSaque')).show();
                    });
                });

                function fecharModal() {
                    const m = bootstrap.Modal.getInstance(document.getElementById('modalEditarSaque'));
                    if (m) m.hide();
                }
                function recarregar(delay = 2000) {
                    setTimeout(() => window.location.reload(), delay);
                }
                function ajaxPost(url, body, cb) {
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", url, true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) cb(xhr.status, xhr.responseText);
                    };
                    xhr.send(body);
                }
                function ajaxGet(url, cb) {
                    const xhr = new XMLHttpRequest();
                    xhr.open("GET", url, true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) cb(xhr.status, xhr.responseText);
                    };
                    xhr.send();
                }

                // ── Aprovar via Ecompag ──
                document.getElementById('btnAprovarEcompag').addEventListener('click', function() {
                    const transacaoId = document.getElementById('transacaoId').value;
                    const usuario     = document.getElementById('usuario').value;
                    const codigo2fa   = document.getElementById('2fa').value;
                    if (!codigo2fa) { showToast('danger', 'Informe o código 2FA.'); return; }
                    ajaxGet(`services-gateway/payment_manual.php?id=${transacaoId}&usuario=${usuario}&gateway=ecompag&codigo_2fa=${codigo2fa}`, function(status, text) {
                        try {
                            const r = JSON.parse(text);
                            if (r.success) {
                                showToast('success', r.message || 'Saque aprovado via Ecompag!');
                                fecharModal(); recarregar();
                            } else {
                                showToast('danger', r.message || 'Erro ao aprovar via Ecompag.');
                            }
                        } catch(e) { showToast('danger', 'Resposta inválida do servidor.'); }
                    });
                });

                // ── Marcar Pago (manual, sem API) ──
                document.getElementById('btnMarcarPago').addEventListener('click', function() {
                    const transacaoId = document.getElementById('transacaoId').value;
                    const usuario     = document.getElementById('usuario').value;
                    const codigo2fa   = document.getElementById('2fa').value;
                    if (!codigo2fa) { showToast('danger', 'Informe o código 2FA.'); return; }
                    if (!confirm('Marcar este saque como pago manualmente (sem enviar pela API)?')) return;
                    ajaxGet(`services-gateway/payment_manual.php?id=${transacaoId}&usuario=${usuario}&manual=1&codigo_2fa=${codigo2fa}`, function(status, text) {
                        try {
                            const r = JSON.parse(text);
                            if (r.success) {
                                showToast('success', r.message || 'Saque marcado como pago!');
                                fecharModal(); recarregar();
                            } else {
                                showToast('danger', r.message || 'Erro ao marcar como pago.');
                            }
                        } catch(e) { showToast('danger', 'Resposta inválida do servidor.'); }
                    });
                });

                // ── Estornar saque ──
                document.getElementById('btnEstornar').addEventListener('click', function() {
                    const saqueId = document.getElementById('saqueId').value;
                    if (!confirm('Estornar este saque? O saldo será devolvido ao usuário e o saque será cancelado.')) return;
                    ajaxPost('ajax/estornar_saque.php', `saque_id=${saqueId}`, function(status, text) {
                        try {
                            const r = JSON.parse(text);
                            if (r.success) {
                                showToast('success', r.message || 'Saque estornado com sucesso!');
                                fecharModal(); recarregar();
                            } else {
                                showToast('danger', r.message || 'Erro ao estornar saque.');
                            }
                        } catch(e) { showToast('danger', 'Resposta inválida do servidor.'); }
                    });
                });

                // ── Atualizar Status ──
                document.getElementById('btnAtualizarSaque').addEventListener('click', function() {
                    const saqueId    = document.getElementById('saqueId').value;
                    const statusSaque = document.getElementById('statusSaque').value;
                    ajaxPost("ajax/atualizar_status_saque.php", `id=${saqueId}&status=${statusSaque}`, function(status, text) {
                        try {
                            const r = JSON.parse(text);
                            if (r.success) {
                                showToast('success', r.message || 'Status atualizado!');
                                fecharModal(); recarregar();
                            } else {
                                showToast('danger', r.message || 'Erro ao atualizar status.');
                            }
                        } catch(e) { showToast('danger', 'Resposta inválida do servidor.'); }
                    });
                });


                // Função para exibir toast com o estilo fornecido
                function showToast(type, message) {
                    var toastPlacement = document.getElementById('toastPlacement');
                    var toast = document.createElement('div');
                    toast.className = `toast align-items-center bg-light border-0 fade show`;
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');
                    toast.innerHTML = `
                <div class="toast-header">
                    
                    <h5 class="me-auto my-0">Atualização</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
                    toastPlacement.appendChild(toast);

                    // Inicializar o Toast do Bootstrap
                    var bootstrapToast = new bootstrap.Toast(toast);
                    bootstrapToast.show();

                    setTimeout(function() {
                        bootstrapToast.hide(); // Esconder o toast após 3 segundos
                        setTimeout(() => toast.remove(), 500); // Remove o toast após ele sumir
                    }, 3000);
                }
            });
        </script>

        <!-- Adicione um elemento de posicionamento para o Toast -->
        <div id="toastPlacement" class="position-fixed bottom-0 end-0 p-3" style="z-index: 11"></div>


</body>
<!--end body-->

</html>