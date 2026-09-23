<?php include 'partials/html.php' ?>

<?php

ini_set('display_errors', 0);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$toastType = '';
$toastMessage = '';

// Processar ações (responder, aprovar, recusar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $feedback_id = intval($_POST['feedback_id']);
    
    // Pega o ID do admin da sessão
    $admin_id = null;
    $admin_email = null;
    if (isset($_SESSION['data_adm']['id'])) {
        $admin_id = $_SESSION['data_adm']['id'];
        $admin_email = $_SESSION['data_adm']['email'] ?? 'admin@sistema.com';
    }
    
    // Se não encontrou ID do admin, registra erro e para
    if (!$admin_id) {
        error_log("ID do admin não encontrado na sessão.");
        $toastType = 'error';
        $toastMessage = 'Erro: Sessão de admin inválida.';
    } else {
        if ($_POST['action'] === 'reply') {
            $reply = mysqli_real_escape_string($mysqli, $_POST['reply']);
            $bonus = floatval($_POST['bonus_amount']);
            
            $update_qry = "UPDATE customer_feedback 
                          SET reply = '$reply', 
                              reply_time = NOW(), 
                              reply_by = $admin_id,
                              status = 'replied',
                              bonus_amount = $bonus,
                              updated_at = NOW()
                          WHERE id = $feedback_id";
            
            if (mysqli_query($mysqli, $update_qry)) {
                $toastType = 'success';
                $toastMessage = 'Resposta enviada com sucesso!';
                registrarLog($mysqli, $admin_email, 'Respondeu feedback ID: ' . $feedback_id, $admin_id);
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao enviar resposta.';
                error_log("Erro ao responder feedback: " . mysqli_error($mysqli));
            }
        }
        
        if ($_POST['action'] === 'approve') {
            $update_qry = "UPDATE customer_feedback 
                          SET status = 'approved',
                              reply_by = $admin_id,
                              updated_at = NOW()
                          WHERE id = $feedback_id";
            
            if (mysqli_query($mysqli, $update_qry)) {
                $toastType = 'success';
                $toastMessage = 'Feedback aprovado com sucesso!';
                registrarLog($mysqli, $admin_email, 'Aprovou feedback ID: ' . $feedback_id, $admin_id);
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao aprovar feedback.';
                error_log("Erro ao aprovar feedback: " . mysqli_error($mysqli));
            }
        }
        
        if ($_POST['action'] === 'reject') {
            $update_qry = "UPDATE customer_feedback 
                          SET status = 'rejected',
                              reply_by = $admin_id,
                              updated_at = NOW()
                          WHERE id = $feedback_id";
            
            if (mysqli_query($mysqli, $update_qry)) {
                $toastType = 'success';
                $toastMessage = 'Feedback rejeitado.';
                registrarLog($mysqli, $admin_email, 'Rejeitou feedback ID: ' . $feedback_id, $admin_id);
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao rejeitar feedback.';
                error_log("Erro ao rejeitar feedback: " . mysqli_error($mysqli));
            }
        }
        
        if ($_POST['action'] === 'close') {
            $update_qry = "UPDATE customer_feedback 
                          SET status = 'closed',
                          updated_at = NOW()
                          WHERE id = $feedback_id";
            
            if (mysqli_query($mysqli, $update_qry)) {
                $toastType = 'success';
                $toastMessage = 'Feedback fechado.';
                registrarLog($mysqli, $admin_email, 'Fechou feedback ID: ' . $feedback_id, $admin_id);
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao fechar feedback.';
                error_log("Erro ao fechar feedback: " . mysqli_error($mysqli));
            }
        }
    }
}

function get_feedbacks($limit, $offset, $search_query = null, $status_filter = null)
{
    global $mysqli;
    
    $conditions = [];
    
    if (!empty($search_query)) {
        $search_query = mysqli_real_escape_string($mysqli, $search_query);
        $conditions[] = "(cf.id LIKE '%$search_query%' OR cf.user_id LIKE '%$search_query%' OR cf.content LIKE '%$search_query%')";
    }
    
    if (!empty($status_filter) && $status_filter !== 'all') {
        $status_filter = mysqli_real_escape_string($mysqli, $status_filter);
        $conditions[] = "cf.status = '$status_filter'";
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    $qry = "
        SELECT 
            cf.id,
            cf.user_id,
            cf.type,
            cf.content,
            cf.file_link,
            cf.source,
            cf.status,
            cf.reply,
            cf.reply_time,
            cf.reply_by,
            cf.bonus_amount,
            cf.bonus_received,
            cf.client_time,
            cf.created_at,
            cf.updated_at,
            COALESCE(u.real_name, u.mobile) as user_name,
            a.nome as admin_name
        FROM 
            customer_feedback cf
        LEFT JOIN usuarios u ON cf.user_id = u.id
        LEFT JOIN admin_users a ON cf.reply_by = a.id
        $where_clause
        ORDER BY 
            cf.created_at DESC 
        LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($mysqli, $qry);
    
    // Verifica se a query foi executada com sucesso
    if (!$result) {
        error_log("Erro na query get_feedbacks: " . mysqli_error($mysqli));
        return [];
    }
    
    $feedbacks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $feedbacks[] = $row;
    }
    return $feedbacks;
}

function count_feedbacks($search_query = null, $status_filter = null)
{
    global $mysqli;
    
    $conditions = [];
    
    if (!empty($search_query)) {
        $search_query = mysqli_real_escape_string($mysqli, $search_query);
        $conditions[] = "(id LIKE '%$search_query%' OR user_id LIKE '%$search_query%' OR content LIKE '%$search_query%')";
    }
    
    if (!empty($status_filter) && $status_filter !== 'all') {
        $status_filter = mysqli_real_escape_string($mysqli, $status_filter);
        $conditions[] = "status = '$status_filter'";
    }
    
    $where_clause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    
    $qry = "SELECT COUNT(*) as total FROM customer_feedback $where_clause";
    $result = mysqli_query($mysqli, $qry);
    
    // Verifica se a query foi executada com sucesso
    if (!$result) {
        error_log("Erro na query count_feedbacks: " . mysqli_error($mysqli));
        return 0;
    }
    
    $row = mysqli_fetch_assoc($result);
    return $row ? $row['total'] : 0;
}

function get_feedback_types()
{
    return [
        1 => 'Perguntas do jogo',
        2 => 'Faça login do registro',
        3 => 'Questões do evento',
        4 => 'Questões do agente',
        5 => 'Perguntas de depósito',
        6 => 'Problemas no Saque',
        7 => 'Sugestão de otimização',
        8 => 'Outra sugestão'
    ];
}

function get_status_badge($status)
{
    $badges = [
        'pending' => '<span class="badge bg-warning">Pendente</span>',
        'replied' => '<span class="badge bg-info">Respondido</span>',
        'approved' => '<span class="badge bg-success">Aprovado</span>',
        'rejected' => '<span class="badge bg-danger">Rejeitado</span>',
        'closed' => '<span class="badge bg-secondary">Fechado</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Desconhecido</span>';
}

$limit = 15;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

$total_feedbacks = count_feedbacks($search_query, $status_filter);
$total_pages = $total_feedbacks > 0 ? ceil($total_feedbacks / $limit) : 1;
$feedbacks = get_feedbacks($limit, $offset, $search_query, $status_filter);
$feedback_types = get_feedback_types();

// Estatísticas
$stats_qry = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM customer_feedback";
$stats_result = mysqli_query($mysqli, $stats_qry);

// Valores padrão caso a query falhe
$stats = [
    'total' => 0,
    'pending' => 0,
    'replied' => 0,
    'approved' => 0,
    'rejected' => 0
];

if ($stats_result) {
    $stats = mysqli_fetch_assoc($stats_result);
}
?>

<head>
    <?php $title = "Gerenciar Feedbacks de Clientes";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
    
    <style>
        .feedback-content {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .modal-dialog-scrollable .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Estatísticas -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <div class="card">
                            <div class="card-body text-center">
                                <h3 class="mb-0"><?= $stats['total'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Total</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-warning"><?= $stats['pending'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Pendentes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-info"><?= $stats['replied'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Respondidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-success"><?= $stats['approved'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Aprovados</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-danger">
                            <div class="card-body text-center">
                                <h3 class="mb-0 text-danger"><?= $stats['rejected'] ?? 0 ?></h3>
                                <p class="text-muted mb-0">Rejeitados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Feedbacks de Clientes</h4>
                            </div>
                            
                            <div class="card-body pt-0">
                                <form method="GET" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-4 mb-2">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="Buscar por ID, usuário ou conteúdo"
                                                value="<?= htmlspecialchars($search_query) ?>">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <select name="status" class="form-select">
                                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Todos os Status</option>
                                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                                                <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Respondidos</option>
                                                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Aprovados</option>
                                                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejeitados</option>
                                                <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Fechados</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <button type="submit" class="btn btn-success w-100">Filtrar</button>
                                        </div>
                                        <div class="col-md-3 mb-2 text-end">
                                            <a href="?" class="btn btn-secondary w-100">Limpar Filtros</a>
                                        </div>
                                    </div>
                                </form>
                            
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuário</th>
                                                <th>Tipo</th>
                                                <th>Conteúdo</th>
                                                <th>Origem</th>
                                                <th>Status</th>
                                                <th>Bônus</th>
                                                <th>Data</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($feedbacks)): ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">Nenhum feedback encontrado</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($feedbacks as $feedback): ?>
                                                    <tr>
                                                        <td><?= $feedback['id'] ?></td>
                                                        <td>
                                                            <strong><?= $feedback['user_id'] ?></strong><br>
                                                            <small class="text-muted"><?= htmlspecialchars($feedback['user_name'] ?? 'N/A') ?></small>
                                                        </td>
                                                        <td><?= $feedback_types[$feedback['type']] ?? 'N/A' ?></td>
                                                        <td>
                                                            <div class="feedback-content" title="<?= htmlspecialchars($feedback['content']) ?>">
                                                                <?= htmlspecialchars($feedback['content']) ?>
                                                            </div>
                                                        </td>
                                                        <td><?= $feedback['source'] ?></td>
                                                        <td><?= get_status_badge($feedback['status']) ?></td>
                                                        <td>R$ <?= number_format($feedback['bonus_amount'], 2, ',', '.') ?></td>
                                                        <td>
                                                            <?= date('d/m/Y', strtotime($feedback['created_at'])) ?><br>
                                                            <small class="text-muted"><?= date('H:i:s', strtotime($feedback['created_at'])) ?></small>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#viewModal<?= $feedback['id'] ?>">
                                                                <i class="fas fa-eye"></i> Ver
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <!-- Modal de Visualização e Ações -->
                                                    <div class="modal fade" id="viewModal<?= $feedback['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-scrollable modal-lg">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Feedback #<?= $feedback['id'] ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <strong>Usuário:</strong> <?= $feedback['user_id'] ?> - <?= htmlspecialchars($feedback['user_name'] ?? 'N/A') ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>Tipo:</strong> <?= $feedback_types[$feedback['type']] ?? 'N/A' ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row mb-3">
                                                                        <div class="col-md-6">
                                                                            <strong>Origem:</strong> <?= $feedback['source'] ?>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <strong>Status:</strong> <?= get_status_badge($feedback['status']) ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <strong>Data de Criação:</strong> 
                                                                        <?= date('d/m/Y H:i:s', strtotime($feedback['created_at'])) ?>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <strong>Conteúdo:</strong>
                                                                        <div class="border p-3 rounded bg-light">
                                                                            <?= nl2br(htmlspecialchars($feedback['content'])) ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <?php if ($feedback['file_link']): ?>
                                                                        <div class="mb-3">
                                                                            <strong>Arquivo:</strong>
                                                                            <a href="<?= htmlspecialchars($feedback['file_link']) ?>" target="_blank" class="btn btn-sm btn-info">
                                                                                <i class="fas fa-download"></i> Baixar
                                                                            </a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($feedback['reply']): ?>
                                                                        <hr>
                                                                        <div class="mb-3">
                                                                            <strong>Resposta:</strong>
                                                                            <div class="border p-3 rounded bg-info bg-opacity-10">
                                                                                <?= nl2br(htmlspecialchars($feedback['reply'])) ?>
                                                                            </div>
                                                                            <small class="text-muted">
                                                                                Respondido por: <?= htmlspecialchars($feedback['admin_name'] ?? 'Admin') ?> em 
                                                                                <?= date('d/m/Y H:i:s', strtotime($feedback['reply_time'])) ?>
                                                                            </small>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <strong>Bônus:</strong> R$ <?= number_format($feedback['bonus_amount'], 2, ',', '.') ?>
                                                                            <?php if ($feedback['bonus_received']): ?>
                                                                                <span class="badge bg-success">Recebido</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <hr>
                                                                    
                                                                    <!-- Formulário de Resposta -->
                                                                    <?php if ($feedback['status'] === 'pending' || $feedback['status'] === 'approved'): ?>
                                                                        <form method="POST" action="">
                                                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                                            <input type="hidden" name="action" value="reply">
                                                                            
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><strong>Responder Feedback:</strong></label>
                                                                                <textarea name="reply" class="form-control" rows="4" required 
                                                                                          placeholder="Digite sua resposta aqui..."><?= htmlspecialchars($feedback['reply'] ?? '') ?></textarea>
                                                                            </div>
                                                                            
                                                                            <div class="mb-3">
                                                                                <label class="form-label"><strong>Bônus (R$):</strong></label>
                                                                                <input type="number" name="bonus_amount" class="form-control" 
                                                                                       step="0.01" min="0" value="<?= $feedback['bonus_amount'] ?>" 
                                                                                       placeholder="0.00">
                                                                            </div>
                                                                            
                                                                            <button type="submit" class="btn btn-primary">
                                                                                <i class="fas fa-reply"></i> Enviar Resposta
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <?php if ($feedback['status'] === 'pending'): ?>
                                                                        <form method="POST" action="" class="d-inline">
                                                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                                            <input type="hidden" name="action" value="approve">
                                                                            <button type="submit" class="btn btn-success">
                                                                                <i class="fas fa-check"></i> Aprovar
                                                                            </button>
                                                                        </form>
                                                                        
                                                                        <form method="POST" action="" class="d-inline">
                                                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                                            <input type="hidden" name="action" value="reject">
                                                                            <button type="submit" class="btn btn-danger" 
                                                                                    onclick="return confirm('Tem certeza que deseja rejeitar este feedback?')">
                                                                                <i class="fas fa-times"></i> Rejeitar
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if ($feedback['status'] !== 'closed'): ?>
                                                                        <form method="POST" action="" class="d-inline">
                                                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                                            <input type="hidden" name="action" value="close">
                                                                            <button type="submit" class="btn btn-secondary">
                                                                                <i class="fas fa-lock"></i> Fechar
                                                                            </button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                    
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            
                                <!-- Paginação -->
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=1&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Primeira página">
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>
                                
                                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Página anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                
                                        <?php
                                        $range = 2;
                                        $start = max(1, $page - $range);
                                        $end = min($total_pages, $page + $range);
                                
                                        for ($i = $start; $i <= $end; $i++):
                                        ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                
                                        <?php if ($end < $total_pages): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                
                                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Próxima página">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                
                                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search_query) ?>&status=<?= $status_filter ?>" aria-label="Última página">
                                                <span aria-hidden="true">&raquo;&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div>
    </div>

    <?php include 'partials/vendorjs' . '.php'; ?>
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
