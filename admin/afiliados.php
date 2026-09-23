<?php
session_start();
include_once "services/checa_login_adm.php";
checa_login_adm();
include_once "validar_2fa.php";
include_once "services/database.php";
global $mysqli;

// Definir variáveis de filtro
$search_query = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = mysqli_real_escape_string($mysqli, $_GET['search']);
}

// Configuração da paginação
$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Consulta para contar o total de afiliados (statusaff = 1)
$query_total_afiliados = "SELECT COUNT(*) AS total_afiliados FROM usuarios WHERE statusaff = 1";
if (!empty($search_query)) {
    $query_total_afiliados .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
}
$result_total_afiliados = mysqli_query($mysqli, $query_total_afiliados);
$total_afiliados = mysqli_fetch_assoc($result_total_afiliados)['total_afiliados'];

// Cálculo do total de páginas
$total_pages = ceil($total_afiliados / $limit);

// Consulta para exibir os afiliados com paginação e filtro
$query_afiliados = "SELECT * FROM usuarios WHERE statusaff = 1";
if (!empty($search_query)) {
    $query_afiliados .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
}
$query_afiliados .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result_afiliados = mysqli_query($mysqli, $query_afiliados);
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title = admin_t('page_affiliates_title'); ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .rtp-individual {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .rtp-individual label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .rtp-individual input[type="range"] {
            width: 100px;
        }
        .modo-demo-switch {
            width: 40px;
            height: 20px;
        }
        .info-icon {
            margin-left: 5px;
            cursor: pointer;
        }
    </style>
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
                                    <div class="col">
                                        <h4 class="card-title"><?= admin_t('page_affiliates_title') ?> (<?= $total_afiliados; ?>)</h4>
                                    </div>
                                    <div class="col text-end">
                                        <a href="export/exportar_usuarios.php" class="btn btn-primary"><?= admin_t('button_export_data') ?></a>
                                    </div>
                                </div>
                            </div><!--end card-header-->

                            <!-- Filtros e Busca -->
                            <div class="card-body pt-0">
                                <form method="GET" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="<?= admin_t('affiliates_search_placeholder') ?>"
                                                value="<?= htmlspecialchars($search_query) ?>">
                                        </div>
                                        <div class="col-md-4 text-start">
                                            <button type="submit" class="btn btn-success mt-2"><?= admin_t('button_filter') ?></button>
                                            <a href="?" class="btn btn-secondary mt-2"><?= admin_t('button_clear') ?></a>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= admin_t('table_id') ?></th>
                                                <th><?= admin_t('table_user') ?></th>
                                                <th><?= admin_t('table_balance') ?></th>
                                                <th><?= admin_t('table_deposited') ?></th>
                                                <th><?= admin_t('table_withdrawn') ?></th>
                                                <th><?= admin_t('table_role') ?></th>
                                                <th><?= admin_t('table_referrals') ?></th>
                                                <th>
                                                    <?= admin_t('users_demo_mode') ?>
                                                    <i class="fa fa-info-circle text-info info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= admin_t('users_demo_mode_help') ?>"></i>
                                                </th>
                                                <th><?= admin_t('users_individual_rtp') ?></th>
                                                <th class="text-end"><?= admin_t('table_details') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($result_afiliados && mysqli_num_rows($result_afiliados) > 0) {
                                                while ($afiliado = mysqli_fetch_assoc($result_afiliados)) {
                                                    // Consultar total sacado DOS INDICADOS do afiliado
                                                    $query_sacado = "SELECT SUM(valor) AS total_sacado FROM solicitacao_saques WHERE id_user IN (SELECT id FROM usuarios WHERE invitation_code = '{$afiliado['invite_code']}') AND status = 1";
                                                    $result_sacado = mysqli_query($mysqli, $query_sacado);
                                                    $sacado = ($result_sacado && mysqli_num_rows($result_sacado) > 0) ? mysqli_fetch_assoc($result_sacado)['total_sacado'] : 0;

                                                    // Consultar total depositado DOS INDICADOS do afiliado
                                                    $query_depositado = "SELECT SUM(valor) AS total_depositado FROM transacoes WHERE usuario IN (SELECT id FROM usuarios WHERE invitation_code = '{$afiliado['invite_code']}') AND status = 'pago'";
                                                    $result_depositado = mysqli_query($mysqli, $query_depositado);
                                                    $depositado = ($result_depositado && mysqli_num_rows($result_depositado) > 0) ? mysqli_fetch_assoc($result_depositado)['total_depositado'] : 0;

                                                    // Contar quantos usuários foram indicados por este afiliado
                                                    $query_indicados = "SELECT COUNT(*) AS total_indicados FROM usuarios WHERE invitation_code = '{$afiliado['invite_code']}'";
                                                    $result_indicados = mysqli_query($mysqli, $query_indicados);
                                                    $total_indicados = ($result_indicados && mysqli_num_rows($result_indicados) > 0) ? mysqli_fetch_assoc($result_indicados)['total_indicados'] : 0;

                                                    $cargo_badge = "<span class='badge bg-danger'>" . admin_t('affiliates_badge') . "</span>";

                                                    // Obter valores de modo_demo e rtp_individual
                                                    $modo_demo = $afiliado['modo_demo'] ?? 0;
                                                    $rtp_individual = $afiliado['rtp_individual'] ?? 95;
                                                    ?>
                                                    <tr>
                                                        <td><?= $afiliado['id']; ?></td>
                                                        <td><?= htmlspecialchars($afiliado['mobile']); ?></td>
                                                        <td>R$ <?= number_format($afiliado['saldo'], 2, ',', '.'); ?></td>
                                                        <td>R$ <?= number_format($depositado, 2, ',', '.'); ?></td>
                                                        <td>R$ <?= number_format($sacado, 2, ',', '.'); ?></td>
                                                        <td><?= $cargo_badge; ?></td>
                                                        <td><?= $total_indicados; ?></td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input modo-demo-switch" 
                                                                       type="checkbox" 
                                                                       id="modoDemo_<?= $afiliado['id']; ?>" 
                                                                       data-mobile="<?= htmlspecialchars($afiliado['mobile']); ?>"
                                                                       <?= $modo_demo == 1 ? 'checked' : ''; ?>>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="rtp-individual">
                                                                <label for="rtpSlider_<?= $afiliado['id']; ?>">
                                                                    <span id="rtpValueDisplay_<?= $afiliado['id']; ?>"><?= $rtp_individual; ?>%</span>
                                                                </label>
                                                                <input type="range" 
                                                                       class="form-range rtp-slider" 
                                                                       min="0" 
                                                                       max="100" 
                                                                       step="1" 
                                                                       value="<?= $rtp_individual; ?>" 
                                                                       id="rtpSlider_<?= $afiliado['id']; ?>"
                                                                       data-mobile="<?= htmlspecialchars($afiliado['mobile']); ?>">
                                                            </div>
                                                        </td>
                                                        <td class="text-end">
                                                            <div class="dropdown d-inline-block">
                                                                <a class="dropdown-toggle arrow-none" id="dLabel11"
                                                                    data-bs-toggle="dropdown" href="#" role="button"
                                                                    aria-haspopup="false" aria-expanded="false">
                                                                    <i class="las la-ellipsis-v fs-20 text-muted"></i>
                                                                </a>
                                                                <div class="dropdown-menu dropdown-menu-end">
                                                                    <a class="dropdown-item text-success"
                                                                        href="<?= $painel_adm_ver_usuarios . encodeAll($afiliado['id']); ?>">
                                                                        <i class="las la-info-circle"></i> <?= admin_t('table_details') ?>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='10' class='text-center'>" . admin_t('table_no_data') . "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table><!--end /table-->
                                </div><!--end /tableresponsive-->

                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination justify-content-center mt-3">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" aria-label="Anterior">
                                                        <span aria-hidden="true">&laquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>

                                            <?php 
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++): 
                                            ?>
                                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?>" aria-label="Próximo">
                                                        <span aria-hidden="true">&raquo;</span>
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div><!--end card-body-->
                        </div>

                        <!-- Resumo Financeiro Afiliados -->
                        <div class="row mt-4">
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= admin_t('affiliates_total_brought') ?></h5>
                                        <p class="text-muted mb-0">R$
                                            <?= number_format(total_trazido_afiliados(), 2, ',', '.'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= admin_t('affiliates_total_people') ?></h5>
                                        <p class="text-muted mb-0"><?= total_pessoas_trazidas(); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= admin_t('affiliates_top_with_referrals') ?></h5>
                                        <p class="text-muted mb-0"><?= top_afiliado_indicados(); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!--end col-->
                </div><!--end row-->
            </div><!-- container -->

            <!--Start Rightbar-->
            <?php include 'partials/endbar.php' ?>
            <!--end Rightbar-->
            <!--Start Footer-->
            <?php include 'partials/footer.php' ?>
            <!--end footer-->
        </div><!-- end page content -->
    </div><!-- end page-wrapper -->

    <!-- Javascript  -->
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
        
        function updateRtpIndividual(mobile, rtpValue) {
            fetch('partials/updateRtpIndividual.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ mobile: mobile, rtp: rtpValue })
            })
            .then(response => response.json())
            .then(json => {
                if (json.success) {
                    console.log('RTP atualizado com sucesso');
                } else {
                    console.error('Erro ao atualizar RTP');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
            });
        }

        function updateModoDemo(mobile, modoDemoValue) {
            // 1. Atualizar modo demo na iGameWin
            fetch('partials/updateModoDemo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ mobile: mobile, modo_demo: modoDemoValue })
            })
            .then(response => response.json())
            .then(json => {
                if (json.success) {
                    console.log('Modo Demo atualizado com sucesso na iGameWin');
                    
                    // 2. Sempre chamar PGClone (tanto para ativar quanto desativar)
                    return fetch('partials/updateDemoPGClone.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ mobile: mobile, modo_demo: modoDemoValue })
                    });
                } else {
                    console.error('Erro ao atualizar Modo Demo na iGameWin:', json.message);
                    throw new Error(json.message);
                }
            })
            .then(response => {
                if (response) {
                    return response.json();
                }
            })
            .then(pgcloneJson => {
                if (pgcloneJson) {
                    if (pgcloneJson.success) {
                        const action = modoDemoValue === 1 ? 'ativado' : 'desativado';
                        console.log(`Influencer ${action} com sucesso na PGClone`);
                        showNotification('success', `Modo Demo ${action} com sucesso na iGameWin e PGClone!`);
                    } else {
                        console.warn('Aviso PGClone:', pgcloneJson.message);
                        const action = modoDemoValue === 1 ? 'ativado' : 'desativado';
                        showNotification('warning', `Modo Demo ${action} na iGameWin, mas houve um problema na PGClone: ` + pgcloneJson.message);
                    }
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                showNotification('error', 'Erro ao processar a requisição: ' + error.message);
            });
        }

        // Função para mostrar notificações
        function showNotification(type, message) {
            // Você pode implementar um sistema de notificações toast aqui
            // Por enquanto, vamos usar um alert simples
            if (type === 'error') {
                alert('❌ ' + message);
            } else if (type === 'warning') {
                alert('⚠️ ' + message);
            } else if (type === 'success') {
                // Sucesso silencioso, apenas log
                console.log('✓ ' + message);
            }
        }

        document.querySelectorAll('.rtp-slider').forEach(function(slider) {
            slider.addEventListener('input', function() {
                var userId = this.id.replace('rtpSlider_', '');
                var rtpValue = parseInt(this.value);
                document.getElementById('rtpValueDisplay_' + userId).textContent = rtpValue + '%';
            });
            slider.addEventListener('change', function() {
                var userId = this.id.replace('rtpSlider_', '');
                var rtpValue = parseInt(this.value);
                var mobile = this.getAttribute('data-mobile');
                updateRtpIndividual(mobile, rtpValue);
            });
        });

        document.querySelectorAll('.modo-demo-switch').forEach(function(switchElem) {
            switchElem.addEventListener('change', function() {
                var mobile = this.getAttribute('data-mobile');
                var modoDemoValue = this.checked ? 1 : 0;
                updateModoDemo(mobile, modoDemoValue);
            });
        });
    </script>
</body>
</html>
<?php
// Funções de totalização para afiliados

// Total depositado pelos usuários que foram indicados (invitation_code preenchido)
function total_trazido_afiliados() {
    global $mysqli;
    $qry = "SELECT SUM(t.valor) as total_trazido 
            FROM transacoes t 
            INNER JOIN usuarios u ON t.usuario = u.id 
            WHERE u.invitation_code IS NOT NULL 
            AND u.invitation_code != '' 
            AND t.status = 'pago' 
            AND t.tipo = 'deposito'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_trazido'] ?? 0;
}

// Total de pessoas trazidas (usuários com invitation_code preenchido)
function total_pessoas_trazidas() {
    global $mysqli;
    $qry = "SELECT COUNT(*) as total_pessoas 
            FROM usuarios 
            WHERE invitation_code IS NOT NULL 
            AND invitation_code != ''";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_pessoas'] ?? 0;
}

// Top afiliado com mais indicados
function top_afiliado_indicados() {
    global $mysqli;
    $qry = "SELECT u.mobile, u.invite_code, COUNT(indicados.id) as total_indicados
            FROM usuarios u
            LEFT JOIN usuarios indicados ON indicados.invitation_code = u.invite_code
            WHERE u.statusaff = 1
            GROUP BY u.id, u.mobile, u.invite_code
            ORDER BY total_indicados DESC
            LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $top = mysqli_fetch_assoc($result);
        return htmlspecialchars($top['mobile']) . " (" . $top['total_indicados'] . " indicados)";
    }
    
    return "Nenhum afiliado encontrado";
}
?>
