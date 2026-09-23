<?php
include 'partials/html.php';
?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
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
#======================================#
# Expulsa usuário caso não esteja ativo
checa_login_adm();

#======================================#
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// Pegando a pesquisa do formulário, caso exista
$search = isset($_GET['search']) ? mysqli_real_escape_string($mysqli, $_GET['search']) : '';
?>

<head>
    <?php $title = "Histórico de Partidas";
    include 'partials/title-meta.php'; ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <?php include 'partials/topbar.php'; ?>
    <?php include 'partials/startbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">


                    <!-- Card Principal -->
                    <div class="col-12">
                        <div class="card rounded-4 shadow-sm">
                            <div class="card-header bg-transparent border-bottom">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h5 class="card-title mb-0 text-primary">
                                            <i class="iconoir-list me-2"></i>Registros de Partidas
                                        </h5>
                                        <p class="text-muted mb-0 small">Visualize o histórico completo das partidas</p>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-primary-subtle text-primary px-3 py-2">
                                            <i class="iconoir-activity me-1"></i>Dados em Tempo Real
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Formulário de Pesquisa Melhorado -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="search-boxrounded-3 p-3">
                                            <form method="get" action="" class="d-flex gap-3">
                                                <div class="flex-grow-1">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-transparent border-end-0 pe-3">
                                                            <i class="iconoir-search text-muted"></i>
                                                        </span>
                                                        <input type="text" name="search" class="form-control border-start-0 ps-3" 
                                                               placeholder="Pesquisar por ID, usuário, jogo, valor ou data..." 
                                                               value="<?= htmlspecialchars($search); ?>">
                                                    </div>
                                                </div>
                                                <button class="btn btn-primary px-4" type="submit">
                                                Pesquisar
                                                </button>
                                                <?php if($search): ?>
                                                <a href="?" class="btn btn-outline-secondary">
                                                    Limpar
                                                </a>
                                                <?php endif; ?>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estatísticas Rápidas -->
                                <?php
                                global $mysqli;
                                
                                // Estatísticas básicas
                                $stats_query = "SELECT 
                                    COUNT(*) as total_partidas,
                                    SUM(bet_money) as total_apostado,
                                    SUM(win_money) as total_ganho,
                                    COUNT(DISTINCT id_user) as usuarios_unicos
                                FROM historico_play";
                                $stats_result = mysqli_query($mysqli, $stats_query);
                                $stats = mysqli_fetch_assoc($stats_result);
                                ?>
                                
                                <div class="row mb-4">
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <div class="stats-card bg-primary-subtle rounded-3 p-3 text-center">
                                            <div class="stats-icon mb-2">
                                                <i class="iconoir-gamepad h3 text-primary mb-0"></i>
                                            </div>
                                            <h5 class="mb-1"><?= number_format($stats['total_partidas']); ?></h5>
                                            <p class="text-muted mb-0 small">Total de Partidas</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <div class="stats-card bg-success-subtle rounded-3 p-3 text-center">
                                            <div class="stats-icon mb-2">
                                                <i class="iconoir-dollar h3 text-success mb-0"></i>
                                            </div>
                                            <h5 class="mb-1">R$ <?= number_format($stats['total_apostado'], 2, ',', '.'); ?></h5>
                                            <p class="text-muted mb-0 small">Total Apostado</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <div class="stats-card bg-warning-subtle rounded-3 p-3 text-center">
                                            <div class="stats-icon mb-2">
                                                <i class="iconoir-trophy h3 text-warning mb-0"></i>
                                            </div>
                                            <h5 class="mb-1">R$ <?= number_format($stats['total_ganho'], 2, ',', '.'); ?></h5>
                                            <p class="text-muted mb-0 small">Total Ganho</p>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <div class="stats-card bg-info-subtle rounded-3 p-3 text-center">
                                            <div class="stats-icon mb-2">
                                                <i class="iconoir-user h3 text-info mb-0"></i>
                                            </div>
                                            <h5 class="mb-1"><?= number_format($stats['usuarios_unicos']); ?></h5>
                                            <p class="text-muted mb-0 small">Usuários Únicos</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tabela Responsiva -->
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="border-0 text-center">
                                                    <span class="badge bg-primary-subtle text-primary">ID</span>
                                                </th>
                                                <th class="border-0">
                                                    <i class="iconoir-user me-1 text-muted"></i>Usuário
                                                </th>
                                                <th class="border-0">
                                                    <i class="iconoir-gamepad me-1 text-muted"></i>Jogo
                                                </th>
                                                <th class="border-0 text-end">
                                                    <i class="iconoir-dollar me-1 text-muted"></i>Apostado
                                                </th>
                                                <th class="border-0 text-end">
                                                    <i class="iconoir-trophy me-1 text-muted"></i>Ganho
                                                </th>
                                                <th class="border-0">
                                                    <i class="iconoir-credit-card me-1 text-muted"></i>Transação
                                                </th>
                                                <th class="border-0">
                                                    <i class="iconoir-calendar me-1 text-muted"></i>Data
                                                </th>
                                                <th class="border-0 text-center">
                                                    <i class="iconoir-check-circle me-1 text-muted"></i>Status
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Definir a página atual
                                            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                                            $qnt_result_pg = 20; // Reduzido para melhor UX
                                            $inicio = ($pagina - 1) * $qnt_result_pg;

                                            // Consultar com filtro de pesquisa
                                            $sql_condition = '';
                                            if ($search) {
                                                $sql_condition = "WHERE id LIKE '%$search%' OR id_user LIKE '%$search%' OR nome_game LIKE '%$search%' OR bet_money LIKE '%$search%' OR win_money LIKE '%$search%' OR txn_id LIKE '%$search%' OR created_at LIKE '%$search%' OR status_play LIKE '%$search%'";
                                            }

                                            // Contar total de registros
                                            $result_logs_count = "SELECT COUNT(*) AS total FROM historico_play $sql_condition";
                                            $resultado_logs_count = mysqli_query($mysqli, $result_logs_count);
                                            $row = mysqli_fetch_assoc($resultado_logs_count);
                                            $total_registros = $row['total'];

                                            // Calcular total de páginas
                                            $total_paginas = ceil($total_registros / $qnt_result_pg);

                                            // Consultar os dados
                                            $result_logs = "SELECT * FROM historico_play $sql_condition ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
                                            $resultado_logs = mysqli_query($mysqli, $result_logs);

                                            if ($resultado_logs && mysqli_num_rows($resultado_logs) > 0) {
                                                while ($log = mysqli_fetch_assoc($resultado_logs)) {
                                                    // Definir cor do status
                                                    $status_class = '';
                                                    $status_icon = '';
                                                    switch($log['status_play']) {
                                                        case '1':
                                                            $status_class = 'bg-success-subtle text-success';
                                                            $status_icon = 'iconoir-check-circle';
                                                            $status_text = 'Concluída';
                                                            break;
                                                        case '0':
                                                            $status_class = 'bg-warning-subtle text-warning';
                                                            $status_icon = 'iconoir-clock';
                                                            $status_text = 'Pendente';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-danger-subtle text-danger';
                                                            $status_icon = 'iconoir-cancel';
                                                            $status_text = 'Cancelada';
                                                    }

                                                    // Calcular resultado da partida
                                                    $resultado = $log['win_money'] - $log['bet_money'];
                                                    $resultado_class = $resultado > 0 ? 'text-success' : ($resultado < 0 ? 'text-danger' : 'text-muted');
                                                    $resultado_icon = $resultado > 0 ? 'iconoir-arrow-up' : ($resultado < 0 ? 'iconoir-arrow-down' : 'iconoir-minus');
                                            ?>
                                                <tr class="table-row-hover">
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-dark fw-bold">#<?= $log['id']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center me-2">
                                                                <i class="iconoir-user text-primary"></i>
                                                            </div>
                                                            <span class="fw-medium"><?= $log['id_user']; ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="game-icon me-2">
                                                                <i class="iconoir-gamepad text-primary"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-medium text-truncate" style="max-width: 150px;">
                                                                    <?= $log['nome_game']; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="fw-bold text-danger">
                                                            R$ <?= number_format($log['bet_money'], 2, ',', '.'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-flex align-items-center justify-content-end">
                                                            <span class="fw-bold text-success me-1">
                                                                R$ <?= number_format($log['win_money'], 2, ',', '.'); ?>
                                                            </span>
                                                            <i class="<?= $resultado_icon; ?> <?= $resultado_class; ?>"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <code class="text-muted small"><?= substr($log['txn_id'], 0, 15); ?>...</code>
                                                    </td>
                                                    <td>
                                                        <div class="small text-muted">
                                                            <?= date('d/m/Y', strtotime($log['created_at'])); ?><br>
                                                            <span class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])); ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge <?= $status_class; ?> px-2 py-1">
                                                            <i class="<?= $status_icon; ?> me-1"></i><?= $status_text; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='8' class='text-center py-5'>";
                                                echo "<div class='empty-state'>";
                                                echo "<i class='iconoir-search text-muted' style='font-size: 3rem;'></i>";
                                                echo "<h5 class='text-muted mt-3'>Nenhum resultado encontrado</h5>";
                                                echo "<p class='text-muted'>Tente ajustar os termos de pesquisa ou remover filtros.</p>";
                                                echo "</div>";
                                                echo "</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação Melhorada -->
                                <?php if($total_paginas > 1): ?>
                                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                    <div class="text-muted">
                                        <small>
                                            Mostrando <?= min($inicio + 1, $total_registros); ?> - <?= min($inicio + $qnt_result_pg, $total_registros); ?> 
                                            de <?= number_format($total_registros); ?> registros
                                        </small>
                                    </div>
                                    
                                    <nav aria-label="Navegação de páginas">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?= ($pagina <= 1) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?= $pagina - 1; ?>&search=<?= urlencode($search); ?>">
                                                    <i class="iconoir-nav-arrow-left"></i>
                                                </a>
                                            </li>

                                            <?php 
                                            $start = max(1, $pagina - 2);
                                            $end = min($total_paginas, $pagina + 2);
                                            
                                            if($start > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=1&search=<?= urlencode($search); ?>">1</a>
                                                </li>
                                                <?php if($start > 2): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                                <li class="page-item <?= ($i == $pagina) ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?pagina=<?= $i; ?>&search=<?= urlencode($search); ?>"><?= $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if($end < $total_paginas): ?>
                                                <?php if($end < $total_paginas - 1): ?>
                                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                                <?php endif; ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?pagina=<?= $total_paginas; ?>&search=<?= urlencode($search); ?>"><?= $total_paginas; ?></a>
                                                </li>
                                            <?php endif; ?>

                                            <li class="page-item <?= ($pagina >= $total_paginas) ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?= $pagina + 1; ?>&search=<?= urlencode($search); ?>">
                                                    <i class="iconoir-nav-arrow-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php'; ?>
    <script src="assets/js/app.js"></script>

    <!-- Função de Toast -->
    <script>
        function showToast(type, message) {
            var toastPlacement = document.getElementById('toastPlacement');
            var toast = document.createElement('div');
            toast.className = `toast align-items-center bg-light border-0 fade show`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="toast-header">
                    <img src="assets/images/logo-sm.png" alt="" height="20" class="me-1">
                    <h5 class="me-auto my-0">Dashboard</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function() {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if (isset($toastType) && isset($toastMessage) && $toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

    <style>
    .stats-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .table-row-hover:hover {
        background-color: rgba(0,123,255,0.05);
        transform: scale(1.01);
        transition: all 0.2s ease;
    }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
    }
    
    .input-group-text {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .form-control {
        padding-left: 15px;
    }
    
    .empty-state {
        padding: 3rem 0;
    }
    
    .game-icon {
        opacity: 0.7;
    }
    
    .page-link {
        border-radius: 6px;
        margin: 0 2px;
        border: 1px solid #dee2e6;
    }
    
    .page-item.active .page-link {
        background: linear-gradient(135deg, #007bff, #0056b3);
        border-color: #007bff;
    }
    
    .card {
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    code {
        background: rgba(0,0,0,0.05);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
    }
    </style>

</body>
</html>