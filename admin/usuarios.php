<?php
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once "services/checa_login_adm.php";
checa_login_adm();
include_once "validar_2fa.php";
?>
<?php include 'partials/html.php' ?>
<head>
    <?php $title = admin_t('page_users_title'); ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <?php
    global $mysqli;

    // MODIFICAÇÃO: Filtro base para exibir apenas usuários (statusaff = 0)
    $statusaff_filter = " AND statusaff = 0";

    $search_query = '';
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_query = mysqli_real_escape_string($mysqli, $_GET['search']);
    }

    if (isset($_GET['status']) && $_GET['status'] !== '') {
        $status_filter = (int) $_GET['status'];
        if ($status_filter == 2) {
            // Banidos (mas ainda com statusaff = 0)
            $statusaff_filter = " AND banido = 1 AND statusaff = 0";
        } elseif ($status_filter == 0) {
            // Usuários ativos (não banidos)
            $statusaff_filter = " AND statusaff = 0 AND banido = 0";
        }
        // Se o filtro for 1 (afiliado), ainda mantém statusaff = 0 para não exibir afiliados
    }

    $limit = 50;
    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $offset = ($page - 1) * $limit;

    $query_total_usuarios = "SELECT COUNT(*) AS total_usuarios FROM usuarios WHERE 1=1 $statusaff_filter";
    if (!empty($search_query)) {
        $query_total_usuarios .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
    }
    
    $result_total_usuarios = mysqli_query($mysqli, $query_total_usuarios);
    $total_usuarios = mysqli_fetch_assoc($result_total_usuarios)['total_usuarios'];

    $total_pages = ceil($total_usuarios / $limit);

    $query_usuarios = "SELECT * FROM usuarios WHERE 1=1 $statusaff_filter";
    if (!empty($search_query)) {
        $query_usuarios .= " AND (id LIKE '%$search_query%' OR mobile LIKE '%$search_query%')";
    }
    
    $query_usuarios .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result_usuarios = mysqli_query($mysqli, $query_usuarios);
    ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Header -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-primary">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="card-title text-white mb-0">Todos Usuários (<?= $total_usuarios; ?> no total)</h4>
                                    </div>
                                    <div class="col text-end">
                                        <a href="export/exportar_usuarios.php" class="btn btn-light text-primary">Exportar Dados</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row">
                    <div class="col-md-6 col-xl-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-soft-primary rounded-circle mx-auto mb-3">
                                    <i class="bi bi-wallet2 fs-24 align-middle text-primary"></i>
                                </div>
                                        <h3 class="mb-1">R$ <?= number_format(total_saldos_usuarios(), 2, ',', '.'); ?></h3>
                                        <p class="text-muted mb-0"><?= admin_t('users_total_balance') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-soft-success rounded-circle mx-auto mb-3">
                                    <i class="bi bi-arrow-down-circle fs-24 align-middle text-success"></i>
                                </div>
                                        <h3 class="mb-1">R$ <?= number_format(total_dep_pagos_usuarios(), 2, ',', '.'); ?></h3>
                                        <p class="text-muted mb-0"><?= admin_t('users_total_deposited') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-soft-danger rounded-circle mx-auto mb-3">
                                    <i class="bi bi-arrow-up-circle fs-24 align-middle text-danger"></i>
                                </div>
                                        <h3 class="mb-1">R$ <?= number_format(total_saques_usuarios(), 2, ',', '.'); ?></h3>
                                        <p class="text-muted mb-0"><?= admin_t('users_total_withdrawn') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-soft-info rounded-circle mx-auto mb-3">
                                    <i class="bi bi-graph-up fs-24 align-middle text-info"></i>
                                </div>
                                <h3 class="mb-1">R$ <?= number_format(media_saldo_usuarios(), 2, ',', '.'); ?></h3>
                                <p class="text-muted mb-0"><?= admin_t('users_average_balance') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                            
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="<?= admin_t('users_search_placeholder') ?>"
                                        value="<?= htmlspecialchars($search_query) ?>">
                                </div>
                                <div class="col-md-4">
                                    <select name="status" class="form-select">
                                        <option value=""><?= admin_t('users_filter_status_all') ?></option>
                                        <option value="2" <?= (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : ''; ?>><?= admin_t('users_filter_status_banned') ?></option>
                                        <option value="0" <?= (isset($_GET['status']) && $_GET['status'] == '0') ? 'selected' : ''; ?>><?= admin_t('status_active') ?></option>
                                    </select>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="submit" class="btn btn-success"><?= admin_t('button_filter') ?></button>
                                    <a href="?" class="btn btn-secondary"><?= admin_t('button_clear') ?></a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= admin_t('table_id') ?></th>
                                        <th><?= admin_t('table_user') ?></th>
                                        <th><?= admin_t('table_balance') ?></th>
                                        <th><?= admin_t('table_deposited') ?></th>
                                        <th><?= admin_t('table_withdrawn') ?></th>
                                        <th><?= admin_t('table_role') ?></th>
                                        <th><?= admin_t('table_referrals') ?></th>
                                        <th><?= admin_t('status') ?></th>
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
                                    if ($result_usuarios && mysqli_num_rows($result_usuarios) > 0) {
                                        while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
                                            // Definir o cargo com base nos dados da tabela
                                            if ($usuario['banido'] == 1) {
                                                $cargo_badge = "<span class='badge bg-dark'>" . admin_t('users_badge_banned') . "</span>";
                                            } else {
                                                $cargo_badge = "<span class='badge bg-secondary'>" . admin_t('users_badge_user') . "</span>";
                                            }

                                            $query_sacado = "SELECT SUM(valor) AS total_sacado FROM solicitacao_saques WHERE id_user = {$usuario['id']} AND status = 1";
                                            $result_sacado = mysqli_query($mysqli, $query_sacado);
                                            $sacado = ($result_sacado && mysqli_num_rows($result_sacado) > 0) ? mysqli_fetch_assoc($result_sacado)['total_sacado'] : 0;

                                            $query_depositado = "SELECT SUM(valor) AS total_depositado FROM transacoes WHERE usuario = {$usuario['id']} AND status = 'pago'";
                                            $result_depositado = mysqli_query($mysqli, $query_depositado);
                                            $depositado = ($result_depositado && mysqli_num_rows($result_depositado) > 0) ? mysqli_fetch_assoc($result_depositado)['total_depositado'] : 0;

                                            // Contar indicados (quem foi convidado por este usuário)
                                            $query_indicados = "SELECT COUNT(*) AS total_indicados FROM usuarios WHERE invitation_code = '{$usuario['invite_code']}'";
                                            $result_indicados = mysqli_query($mysqli, $query_indicados);
                                            $total_indicados = ($result_indicados && mysqli_num_rows($result_indicados) > 0) ? mysqli_fetch_assoc($result_indicados)['total_indicados'] : 0;

                                            // Status
                                            if ($usuario['banido'] == 1) {
                                                $status_badge = "<span class='badge bg-danger'>" . admin_t('users_status_banned') . "</span>";
                                            } else {
                                                $status_badge = "<span class='badge bg-success'>" . admin_t('status_active') . "</span>";
                                            }

                                            // Obter valores de modo_demo e rtp_individual
                                            $modo_demo = $usuario['modo_demo'] ?? 0;
                                            $rtp_individual = $usuario['rtp_individual'] ?? 95;
                                            ?>
                                            <tr>
                                                <td class="text-center text-nowrap"><?= $usuario['id']; ?></td>
                                                <td><?= htmlspecialchars($usuario['mobile']); ?></td>
                                                <td class="text-center text-nowrap">R$ <?= number_format($usuario['saldo'], 2, ',', '.'); ?></td>
                                                <td class="text-center text-nowrap">R$ <?= number_format($depositado, 2, ',', '.'); ?></td>
                                                <td class="text-center text-nowrap">R$ <?= number_format($sacado, 2, ',', '.'); ?></td>
                                                <td class="text-center"><?= $cargo_badge; ?></td>
                                                <td class="text-center"><?= $total_indicados; ?></td>
                                                <td class="text-center"><?= $status_badge; ?></td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input modo-demo-switch" 
                                                               type="checkbox" 
                                                               id="modoDemo_<?= $usuario['id']; ?>" 
                                                               data-mobile="<?= htmlspecialchars($usuario['mobile']); ?>"
                                                               <?= $modo_demo == 1 ? 'checked' : ''; ?>>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="rtp-individual">
                                                        <label for="rtpSlider_<?= $usuario['id']; ?>">
                                                            <span id="rtpValueDisplay_<?= $usuario['id']; ?>"><?= $rtp_individual; ?>%</span>
                                                        </label>
                                                        <input type="range" 
                                                               class="form-range rtp-slider" 
                                                               min="0" 
                                                               max="100" 
                                                               step="1" 
                                                               value="<?= $rtp_individual; ?>" 
                                                               id="rtpSlider_<?= $usuario['id']; ?>"
                                                               data-mobile="<?= htmlspecialchars($usuario['mobile']); ?>">
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <a class="btn btn-sm btn-primary" title="<?= admin_t('table_details') ?>"
                                                       href="<?= $painel_adm_ver_usuarios . encodeAll($usuario['id']); ?>">
                                                        <i class="ti ti-arrow-up-right"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                    } else {
                                        echo "<tr><td colspan='11' class='text-center'>Sem dados disponíveis!</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination justify-content-center mt-3">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>" aria-label="Anterior">
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
                                            <a class="page-link" href="?page=<?= $i ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search_query) ? '&search=' . urlencode($search_query) : '' ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>" aria-label="Próximo">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
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
                    } else {
                        console.warn('Aviso PGClone:', pgcloneJson.message);
                    }
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
            });
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
function total_dep_pagos_usuarios()
{
    global $mysqli;
    $qry = "SELECT SUM(valor) as total_soma FROM transacoes WHERE status = 'pago' AND tipo = 'deposito'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_soma'] ?? 0;
}

function total_saques_usuarios()
{
    global $mysqli;
    $qry = "SELECT SUM(valor) as total_soma FROM solicitacao_saques WHERE status = 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total_soma'] ?? 0;
}

function media_saldo_usuarios()
{
    global $mysqli;
    $qry = "SELECT AVG(saldo) as media_saldo FROM usuarios";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['media_saldo'] ?? 0;
}
?>
