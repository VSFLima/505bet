<?php
include 'partials/html.php';

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

//inicio do script expulsa usuario bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

function get_webhooks($limit, $offset)
{
    global $mysqli;
    $qry = "SELECT * FROM webhook LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $webhooks = [];
    while ($row = $result->fetch_assoc()) {
        $webhooks[] = $row;
    }
    return $webhooks;
}

function count_webhooks()
{
    global $mysqli;
    $qry = "SELECT COUNT(*) as total FROM webhook";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

function get_webhook_stats()
{
    global $mysqli;
    $stats = [
        'total' => 0,
        'ativos' => 0,
        'inativos' => 0
    ];
    
    // Total de webhooks
    $qry = "SELECT COUNT(*) as total FROM webhook";
    $result = mysqli_query($mysqli, $qry);
    $stats['total'] = mysqli_fetch_assoc($result)['total'];
    
    // Webhooks ativos
    $qry = "SELECT COUNT(*) as ativos FROM webhook WHERE status = 1";
    $result = mysqli_query($mysqli, $qry);
    $stats['ativos'] = mysqli_fetch_assoc($result)['ativos'];
    
    // Webhooks inativos
    $stats['inativos'] = $stats['total'] - $stats['ativos'];
    
    return $stats;
}

function update_webhook($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE webhook SET 
        bot_id = ?, 
        chat_id = ?, 
        status = ? 
        WHERE id = ?");

    $qry->bind_param(
        "ssii",
        $data['bot_id'],
        $data['chat_id'],
        $data['status'],
        $data['id']
    );
    return $qry->execute();
}

function insert_webhook($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("INSERT INTO webhook (nome, bot_id, chat_id, status) VALUES (?, ?, ?, ?)");
    $qry->bind_param("sssi", $data['nome'], $data['bot_id'], $data['chat_id'], $data['status']);
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'create') {
        $data = [
            'nome' => trim($_POST['nome']),
            'bot_id' => trim($_POST['bot_id']),
            'chat_id' => trim($_POST['chat_id']),
            'status' => intval($_POST['status']),
        ];

        if (insert_webhook($data)) {
            $toastType = 'success';
            $toastMessage = 'Webhook criado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao criar o webhook. Tente novamente.';
        }
    } else {
        $data = [
            'id' => intval($_POST['id']),
            'bot_id' => trim($_POST['bot_id']),
            'chat_id' => trim($_POST['chat_id']),
            'status' => intval($_POST['status']),
        ];

        if (update_webhook($data)) {
            $toastType = 'success';
            $toastMessage = 'Webhook atualizado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar o webhook. Tente novamente.';
        }
    }
}

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_webhooks = count_webhooks();
$total_pages = ceil($total_webhooks / $limit);
$webhook_stats = get_webhook_stats();

$webhooks = get_webhooks($limit, $offset);
?>

<!DOCTYPE html>
<html>
<head>
    <?php 
    $title = "Gerenciamento de Webhooks";
    include 'partials/title-meta.php'; 
    ?>
    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <?php include 'partials/topbar.php'; ?>
    <?php include 'partials/startbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="card-title text-primary">
                                            <i class="bi bi-globe me-2"></i>
                                            Gerenciamento de Webhooks
                                        </h4>
                                        <p class="card-title-desc mb-0 text-muted">Configure e monitore todos os webhooks do sistema • Total: <?= $total_webhooks; ?> webhooks</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-primary-subtle text-primary rounded-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(59, 130, 246, 0.1);">
                                    <i class="bi bi-globe fs-2"></i>
                                </div>
                                <h4 class="mb-1"><?= $webhook_stats['total']; ?></h4>
                                <p class="text-muted mb-0">Total Webhooks</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-success-subtle text-success rounded-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(16, 185, 129, 0.1);">
                                    <i class="bi bi-check-circle fs-2"></i>
                                </div>
                                <h4 class="mb-1"><?= $webhook_stats['ativos']; ?></h4>
                                <p class="text-muted mb-0">Webhooks Ativos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="avatar-md bg-danger-subtle text-danger rounded-3 mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background-color: rgba(239, 68, 68, 0.1);">
                                    <i class="bi bi-x-circle fs-2"></i>
                                </div>
                                <h4 class="mb-1"><?= $webhook_stats['inativos']; ?></h4>
                                <p class="text-muted mb-0">Webhooks Inativos</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">
                                    <div class="col d-flex align-items-center">
                                        <div class="border-start border-3 border-primary me-3" style="height: 24px;"></div>
                                        <h4 class="card-title mb-0">
                                            <i class="bi bi-list-ul me-2"></i>
                                            Lista de Webhooks
                                        </h4>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createWebhookModal">
                                            <i class="bi bi-plus-circle me-2"></i>Novo Webhook
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Bot ID</th>
                                                <th>Chat ID</th>
                                                <th>Status</th>
                                                <th>Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($webhooks)): ?>
                                                <?php foreach ($webhooks as $webhook): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?= htmlspecialchars($webhook['nome']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <code><?= htmlspecialchars($webhook['bot_id']) ?></code>
                                                        </td>
                                                        <td>
                                                            <code><?= htmlspecialchars($webhook['chat_id']) ?></code>
                                                        </td>
                                                        <td>
                                                            <?php if ($webhook['status'] == 1): ?>
                                                                <span class="badge bg-success">
                                                                    <i class="bi bi-check-circle me-1"></i>Ativo
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">
                                                                    <i class="bi bi-x-circle me-1"></i>Inativo
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editWebhookModal<?= $webhook['id'] ?>">
                                                                <i class="bi bi-pencil"></i> Editar
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center" style="color: var(--text-secondary); padding: 40px;">
                                                        <i class="bi bi-globe" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                                                        <strong>Nenhum webhook encontrado</strong><br>
                                                        <small>Configure seu primeiro webhook</small>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modais de Edição -->
                <div class="modal fade" id="createWebhookModal" tabindex="-1" aria-labelledby="createWebhookModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="createWebhookModalLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Novo Webhook
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="create">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="nome" class="form-label">Nome *</label>
                                        <input type="text" name="nome" class="form-control" required placeholder="Ex: Notificações de Depósito">
                                        <div class="form-text">Identificação do webhook no sistema</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bot_id" class="form-label">Bot Token *</label>
                                        <input type="text" name="bot_id" class="form-control" required placeholder="Ex: 123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                                        <div class="form-text">Token do bot do Telegram (BotFather)</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="chat_id" class="form-label">Chat ID *</label>
                                        <input type="text" name="chat_id" class="form-control" required placeholder="Ex: -1001234567890">
                                        <div class="form-text">ID do chat ou canal do Telegram</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Status *</label>
                                        <select name="status" class="form-select" required>
                                            <option value="1" selected>Ativo</option>
                                            <option value="0">Inativo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x"></i> Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle"></i> Criar Webhook
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php foreach ($webhooks as $webhook): ?>
                    <div class="modal fade" id="editWebhookModal<?= $webhook['id'] ?>" tabindex="-1" aria-labelledby="editWebhookModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editWebhookModalLabel">
                                        <i class="bi bi-pencil me-2"></i>Editar Webhook
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome</label>
                                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($webhook['nome']) ?>" readonly>
                                            <div class="form-text">O nome do webhook não pode ser alterado</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="bot_id" class="form-label">Bot ID *</label>
                                            <input type="text" name="bot_id" class="form-control" value="<?= htmlspecialchars($webhook['bot_id']) ?>" required>
                                            <div class="form-text">Token do bot do Telegram</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="chat_id" class="form-label">Chat ID *</label>
                                            <input type="text" name="chat_id" class="form-control" value="<?= htmlspecialchars($webhook['chat_id']) ?>" required>
                                            <div class="form-text">ID do chat ou canal do Telegram</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status *</label>
                                            <select name="status" class="form-select" required>
                                                <option value="1" <?= $webhook['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                                <option value="0" <?= $webhook['status'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                            </select>
                                            <div class="form-text">Define se o webhook está ativo ou inativo</div>
                                        </div>
                                        <input type="hidden" name="id" value="<?= $webhook['id'] ?>">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x"></i> Cancelar
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Salvar Alterações
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
            <?php include 'partials/endbar.php'; ?>
        </div>
    </div>
    
    <!-- Toast Container -->

    <?php include 'partials/vendorjs.php'; ?>
    <script src="assets/js/app.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animação inicial dos cards
            const cards = document.querySelectorAll('.stat-card, .card, .webhooks-header');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animação dos contadores
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const value = parseInt(stat.textContent);
                if (!isNaN(value) && value > 0) {
                    animateCounter(stat, value);
                }
            });

            // Hover effects
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-6px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Corrigir modal
            document.addEventListener('shown.bs.modal', function(e) {
                const modal = e.target;
                modal.style.zIndex = '1055';
            });
        });

        // Animação de contador
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 50;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 30);
        }

    </script>

</body>
</html>