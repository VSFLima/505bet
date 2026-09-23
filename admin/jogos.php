<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once 'logs/registrar_logs.php';
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
include_once 'services/checa_login_adm.php';
$csrf = new CSRF_Protect();

checa_login_adm();

// Handler AJAX — antes de qualquer HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_selected') {
    header('Content-Type: application/json');
    $ids = array_map('intval', json_decode($_POST['ids'] ?? '[]', true));
    if (empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum jogo selecionado.']);
        exit;
    }
    $in  = implode(',', $ids);
    $res = $mysqli->query("DELETE FROM games WHERE id IN ($in) AND api = 'playfiver'");
    if ($res) {
        echo json_encode(['success' => true, 'message' => count($ids) . ' jogo(s) excluído(s) com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $mysqli->error]);
    }
    exit;
}
?>
<?php include 'partials/html.php' ?>
<?php

// Função para buscar jogos com filtros
function get_games($limit, $offset, $search = '', $filter_api = '', $filter_provider = '')
{
    global $mysqli;
    
    $where_conditions = ["1=1"];
    
    if (!empty($search)) {
        $search_safe = $mysqli->real_escape_string($search);
        $where_conditions[] = "game_name LIKE '%$search_safe%'";
    }
    
    // Sempre filtrar apenas jogos da PlayFiver
    $where_conditions[] = "api = 'playfiver'";
    
    if (!empty($filter_provider)) {
        $filter_provider_safe = $mysqli->real_escape_string($filter_provider);
        $where_conditions[] = "provider = '$filter_provider_safe'";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $qry = "SELECT * FROM games WHERE $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($mysqli, $qry);
    $games = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
    return $games;
}

function count_games($search = '', $filter_api = '', $filter_provider = '')
{
    global $mysqli;
    
    $where_conditions = ["1=1"];
    
    if (!empty($search)) {
        $search_safe = $mysqli->real_escape_string($search);
        $where_conditions[] = "game_name LIKE '%$search_safe%'";
    }
    
    // Sempre filtrar apenas jogos da PlayFiver
    $where_conditions[] = "api = 'playfiver'";
    
    if (!empty($filter_provider)) {
        $filter_provider_safe = $mysqli->real_escape_string($filter_provider);
        $where_conditions[] = "provider = '$filter_provider_safe'";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $qry = "SELECT COUNT(*) as total FROM games WHERE $where_clause";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

function add_game($data)
{
    global $mysqli;
    
    $qry = $mysqli->prepare("INSERT INTO games (game_name, game_code, banner, provider, type, game_type, api, status, popular) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $qry->bind_param(
        "sssssssii",
        $data['game_name'],
        $data['game_code'],
        $data['banner'],
        $data['provider'],
        $data['type'],
        $data['game_type'],
        $data['api'],
        $data['status'],
        $data['popular']
    );
    
    return $qry->execute();
}

function update_game($data)
{
    global $mysqli;
    
    $qry = $mysqli->prepare("UPDATE games SET 
        game_name = ?, 
        game_code = ?, 
        banner = ?, 
        provider = ?, 
        type = ?, 
        game_type = ?, 
        api = ?,
        status = ?,
        popular = ?
        WHERE id = ?");
    
    $qry->bind_param(
        "ssssssssii",
        $data['game_name'],
        $data['game_code'],
        $data['banner'],
        $data['provider'],
        $data['type'],
        $data['game_type'],
        $data['api'],
        $data['status'],
        $data['popular'],
        $data['id']
    );
    
    return $qry->execute();
}

function delete_game($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM games WHERE id = ?");
    $qry->bind_param("i", $id);
    return $qry->execute();
}

// Função para buscar providers únicos
function get_providers()
{
    global $mysqli;
    $qry = "SELECT DISTINCT provider FROM games WHERE api = 'playfiver' AND provider IS NOT NULL AND provider != '' ORDER BY provider ASC";
    $result = mysqli_query($mysqli, $qry);
    $providers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $providers[] = $row['provider'];
    }
    return $providers;
}

$toastType = null;
$toastMessage = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_game'])) {
        $game_id = intval($_POST['game_id']);
        if (delete_game($game_id)) {
            $toastType = 'success';
            $toastMessage = 'Jogo excluído com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao excluir o jogo.';
        }
    } elseif (isset($_POST['add_game'])) {
        $data = [
            'game_name' => $_POST['game_name'],
            'game_code' => $_POST['game_code'],
            'banner'    => $_POST['banner'],
            'provider'  => $_POST['provider'],
            'type'      => 'slot',
            'game_type' => $_POST['game_type'],
            'api'       => 'playfiver',
            'status'    => intval($_POST['status']),
            'popular'   => intval($_POST['popular']),
        ];
        
        if (add_game($data)) {
            $toastType = 'success';
            $toastMessage = 'Jogo adicionado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao adicionar o jogo.';
        }
    } elseif (isset($_POST['update_game'])) {
        $data = [
            'id' => intval($_POST['game_id']),
            'game_name' => $_POST['game_name'],
            'game_code' => $_POST['game_code'],
            'banner' => $_POST['banner'],
            'provider' => $_POST['provider'],
            'type' => $_POST['type'],
            'game_type' => $_POST['game_type'],
            'api' => $_POST['api'],
            'status' => intval($_POST['status']),
            'popular' => intval($_POST['popular'])
        ];
        
        if (update_game($data)) {
            $toastType = 'success';
            $toastMessage = 'Jogo atualizado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar o jogo.';
        }
    }
}

// Paginação e filtros
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter_api = isset($_GET['filter_api']) ? $_GET['filter_api'] : '';
$filter_provider = isset($_GET['filter_provider']) ? $_GET['filter_provider'] : '';
$show_all = isset($_GET['show_all']) && $_GET['show_all'] == '1';
$limit    = $show_all ? 99999 : 12;
$page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset   = $show_all ? 0 : ($page - 1) * $limit;

$total_games = count_games($search, $filter_api, $filter_provider);
$total_pages = ceil($total_games / $limit);
$games = get_games($limit, $offset, $search, $filter_api, $filter_provider);
$providers = get_providers();
?>

<head>
    <?php $title = "Gerenciamento de Jogos API"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .game-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        tr.img-broken {
            background-color: rgba(255, 193, 7, 0.08) !important;
        }
        tr.img-broken td {
            opacity: 0.75;
        }
    </style>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h4 class="card-title mb-0">
                                    <i class="ti ti-device-gamepad-2 me-2"></i>Jogos PlayFiver
                                    <span class="badge bg-secondary ms-2"><?= $total_games ?> jogos</span>
                                </h4>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['show_all' => $show_all ? '0' : '1', 'page' => 1])) ?>"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="ti ti-<?= $show_all ? 'list' : 'stack' ?> me-1"></i>
                                        <?= $show_all ? 'Paginado' : 'Ver todos' ?>
                                    </a>
                                    <button id="btnDeleteSelected" class="btn btn-danger btn-sm" disabled>
                                        <i class="ti ti-trash me-1"></i>Excluir selecionados
                                    </button>
                                    <button id="btnSelectBroken" class="btn btn-outline-warning btn-sm">
                                        <i class="ti ti-photo-off me-1"></i>Selecionar sem imagem
                                    </button>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addGameModal">
                                        <i class="ti ti-plus me-1"></i>Adicionar Jogo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                
                                <!-- Filtros -->
                                <form method="GET" action="" class="row mb-3 g-2">
                                    <input type="hidden" name="show_all" value="<?= $show_all ? '1' : '0' ?>">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" placeholder="Buscar por nome do jogo..." value="<?= htmlspecialchars($search) ?>">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="ti ti-search me-1"></i>Buscar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="filter_provider" class="form-select" onchange="this.form.submit()">
                                            <option value="">Todos os Providers</option>
                                            <?php foreach ($providers as $provider): ?>
                                                <option value="<?= htmlspecialchars($provider) ?>" <?= $filter_provider == $provider ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($provider) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </form>

                                <!-- Tabela de Jogos -->
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="checkAll" title="Selecionar todos"></th>
                                                <th>Banner</th>
                                                <th>Nome</th>
                                                <th>Código</th>
                                                <th>Provider</th>
                                                <th>Status</th>
                                                <th>Popular</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($games)): ?>
                                            <tr>
                                                <td colspan="9" class="text-center">Nenhum jogo encontrado</td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td><input type="checkbox" class="chk-game" value="<?= $game['id'] ?>"></td>
                                                <td>
                                                    <img src="<?= $game['banner'] ?>"
                                                         alt="<?= htmlspecialchars($game['game_name']) ?>"
                                                         class="game-image"
                                                         onerror="this.closest('tr').classList.add('img-broken'); this.style.opacity='0.2';">
                                                </td>
                                                <td><?= htmlspecialchars($game['game_name']) ?></td>
                                                <td><code><?= htmlspecialchars($game['game_code']) ?></code></td>
                                                <td><?= htmlspecialchars($game['provider']) ?></td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                            <?= $game['status'] == 1 ? 'checked' : '' ?>
                                                            onchange="updateGameField(<?= $game['id'] ?>, 'status', this.checked ? 1 : 0)">
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" 
                                                            <?= $game['popular'] == 1 ? 'checked' : '' ?>
                                                            onchange="updateGameField(<?= $game['id'] ?>, 'popular', this.checked ? 1 : 0)">
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editGameModal<?= $game['id'] ?>">
                                                        <i class="ti ti-pencil me-1"></i>Editar
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $game['id'] ?>)">
                                                        <i class="ti ti-trash me-1"></i>Deletar
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal Editar Jogo -->
                                            <div class="modal fade" id="editGameModal<?= $game['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Editar Jogo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="update_game" value="1">
                                                                <input type="hidden" name="game_id" value="<?= $game['id'] ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nome do Jogo</label>
                                                                    <input type="text" name="game_name" class="form-control" value="<?= htmlspecialchars($game['game_name']) ?>" required>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Código do Jogo</label>
                                                                    <input type="text" name="game_code" class="form-control" value="<?= htmlspecialchars($game['game_code']) ?>" required>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">URL da Imagem (Banner)</label>
                                                                    <div class="d-flex gap-2 align-items-center mb-2">
                                                                        <img id="previewBanner<?= $game['id'] ?>"
                                                                             src="<?= htmlspecialchars($game['banner']) ?>"
                                                                             class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;"
                                                                             onerror="this.style.opacity='0.2';">
                                                                        <small class="text-muted text-break" style="font-size:11px"><?= htmlspecialchars($game['banner']) ?></small>
                                                                    </div>
                                                                    <input type="text" name="banner" class="form-control"
                                                                           value="<?= htmlspecialchars($game['banner']) ?>"
                                                                           placeholder="https://imagensfivers.com/Games/Pgsoft/126.webp"
                                                                           oninput="document.getElementById('previewBanner<?= $game['id'] ?>').src=this.value"
                                                                           required>
                                                                    <small class="text-muted">Altere a URL e a pré-visualização atualiza em tempo real.</small>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Provider</label>
                                                                    <input type="text" name="provider" class="form-control" value="<?= htmlspecialchars($game['provider']) ?>" required>
                                                                </div>
                                                                
                                                                <input type="hidden" name="api" value="playfiver">
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Tipo</label>
                                                                            <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($game['type']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Game Type</label>
                                                                            <input type="text" name="game_type" class="form-control" value="<?= htmlspecialchars($game['game_type']) ?>" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Status</label>
                                                                            <select name="status" class="form-select">
                                                                                <option value="1" <?= $game['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                                                                <option value="0" <?= $game['status'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-3">
                                                                            <label class="form-label">Popular</label>
                                                                            <select name="popular" class="form-select">
                                                                                <option value="1" <?= $game['popular'] == 1 ? 'selected' : '' ?>>Sim</option>
                                                                                <option value="0" <?= $game['popular'] == 0 ? 'selected' : '' ?>>Não</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                <button type="submit" class="btn btn-primary">
                                                                    <i class="ti ti-device-floppy me-1"></i>Salvar Alterações
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Paginação -->
                                <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center flex-wrap">
                                        <!-- Anterior -->
                                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&filter_api=<?= urlencode($filter_api) ?>&filter_provider=<?= urlencode($filter_provider) ?>" aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>

                                        <?php
                                        // Lógica de paginação inteligente
                                        $range = 2; // Quantas páginas mostrar antes e depois da atual
                                        $start = max(1, $page - $range);
                                        $end = min($total_pages, $page + $range);

                                        // Primeira página
                                        if ($start > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=1&search=<?= urlencode($search) ?>&filter_api=<?= urlencode($filter_api) ?>&filter_provider=<?= urlencode($filter_provider) ?>">1</a>
                                            </li>
                                            <?php if ($start > 2): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <!-- Páginas do meio -->
                                        <?php for ($i = $start; $i <= $end; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&filter_api=<?= urlencode($filter_api) ?>&filter_provider=<?= urlencode($filter_provider) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <!-- Última página -->
                                        <?php if ($end < $total_pages): ?>
                                            <?php if ($end < $total_pages - 1): ?>
                                                <li class="page-item disabled"><span class="page-link">...</span></li>
                                            <?php endif; ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $total_pages ?>&search=<?= urlencode($search) ?>&filter_api=<?= urlencode($filter_api) ?>&filter_provider=<?= urlencode($filter_provider) ?>"><?= $total_pages ?></a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <!-- Próximo -->
                                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&filter_api=<?= urlencode($filter_api) ?>&filter_provider=<?= urlencode($filter_provider) ?>" aria-label="Próximo">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <?php include 'partials/endbar.php' ?>
            <?php include 'partials/footer.php' ?>
        </div>
    </div>

    <!-- Modal Adicionar Jogo -->
    <div class="modal fade" id="addGameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-plus me-2"></i>Adicionar Jogo PlayFiver</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="add_game" value="1">
                        <input type="hidden" name="api" value="playfiver">
                        <input type="hidden" name="type" value="slot">

                        <div class="mb-3">
                            <label class="form-label">Nome do Jogo</label>
                            <input type="text" name="game_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Código do Jogo</label>
                            <input type="text" name="game_code" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Banner (URL)</label>
                            <input type="text" name="banner" class="form-control" placeholder="https://imagensfivers.com/Games/Pgsoft/126.webp" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" class="form-control" placeholder="Ex: PGSOFT" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Game Type (Categoria)</label>
                            <input type="text" name="game_type" class="form-control" value="ELECTRONIC" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="0" selected>Desativado</option>
                                        <option value="1">Ativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Popular</label>
                                    <select name="popular" class="form-select">
                                        <option value="0" selected>Não</option>
                                        <option value="1">Sim</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>Adicionar Jogo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form oculto para delete -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_game" value="1">
        <input type="hidden" name="game_id" id="deleteGameId">
    </form>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

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
                    
                    <h5 class="me-auto my-0">Atualização</h5>
                    <small>Agora</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">${message}</div>
            `;
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        function updateGameField(gameId, field, value) {
            const formData = new FormData();
            formData.append('action', 'update_field');
            formData.append('game_id', gameId);
            formData.append('field', field);
            formData.append('value', value);

            fetch('ajax/att_jogos_all.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    showToast('error', data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao atualizar.');
            });
        }

        // Selecionar jogos com imagem quebrada
        document.getElementById('btnSelectBroken').addEventListener('click', function() {
            var count = 0;
            document.querySelectorAll('tr.img-broken .chk-game').forEach(function(chk) {
                chk.checked = true;
                count++;
            });
            updateDeleteBtn();
            if (count === 0) {
                showToast('success', 'Nenhuma imagem quebrada encontrada (aguarde a página carregar completamente).');
            } else {
                showToast('success', count + ' jogo(s) com imagem quebrada selecionados.');
            }
        });

        function confirmDelete(gameId) {
            if (confirm('Deseja realmente excluir este jogo?')) {
                document.getElementById('deleteGameId').value = gameId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Selecionar todos
        document.getElementById('checkAll').addEventListener('change', function() {
            document.querySelectorAll('.chk-game').forEach(c => c.checked = this.checked);
            updateDeleteBtn();
        });
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('chk-game')) updateDeleteBtn();
        });
        function updateDeleteBtn() {
            const count = document.querySelectorAll('.chk-game:checked').length;
            const btn = document.getElementById('btnDeleteSelected');
            btn.disabled = count === 0;
            btn.innerHTML = count > 0
                ? '<i class="ti ti-trash me-1"></i>Excluir ' + count + ' selecionado(s)'
                : '<i class="ti ti-trash me-1"></i>Excluir selecionados';
        }
        document.getElementById('btnDeleteSelected').addEventListener('click', function() {
            const ids = Array.from(document.querySelectorAll('.chk-game:checked')).map(c => c.value);
            if (!ids.length) return;
            if (!confirm('Excluir ' + ids.length + ' jogo(s)? Esta ação não pode ser desfeita.')) return;
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=delete_selected&ids=' + encodeURIComponent(JSON.stringify(ids))
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { showToast('success', data.message); setTimeout(() => location.reload(), 1500); }
                else showToast('error', data.message);
            })
            .catch(err => showToast('error', 'Erro: ' + err));
        });
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>