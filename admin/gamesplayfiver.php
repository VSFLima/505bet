<?php include 'partials/html.php' ?>

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
#expulsa user
checa_login_adm();
#======================================#

# Função para buscar todos os jogos da tabela games com paginação e pesquisa
function get_games($limit, $offset, $search = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $qry = "SELECT * FROM games 
            WHERE game_name LIKE '%$search%' 
            AND api = 'PlayFiver' 
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($mysqli, $qry);
    $games = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
    return $games;
}

# Função para contar o total de jogos com pesquisa
function count_games($search = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $qry = "SELECT COUNT(*) as total 
            FROM games 
            WHERE game_name LIKE '%$search%' 
            AND api = 'PlayFiver'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

# Função para atualizar os dados do jogo
function update_game($data)
{
    global $mysqli;
    
    $data['type'] = 'slot';  // Valor fixo para 'Tipo'
    $data['game_type'] = 3;  // Valor fixo para 'Game Type'

    $qry = $mysqli->prepare("UPDATE games SET 
        game_name = ?, 
        game_code = ?, 
        banner = ?, 
        provider = ?, 
        type = ?, 
        game_type = ?, 
        api = ? 
        WHERE id = ?");
    
    $qry->bind_param(
        "sssssssi", 
        $data['game_name'], 
        $data['game_code'], 
        $data['banner'], 
        $data['provider'], 
        $data['type'], 
        $data['game_type'], 
        $data['api'], 
        $data['id']
    );
    
    return $qry->execute();
}

function add_game($data)
{
    global $mysqli;
    $data['status'] = 1;

    $qry = $mysqli->prepare("INSERT INTO games (game_name, game_code, banner, provider, type, game_type, api, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$qry) {
        var_dump($mysqli->error);
        die("Erro ao preparar a consulta.");
    }

    $qry->bind_param(
        "ssssssss",
        $data['game_name'],
        $data['game_code'],
        $data['banner'],
        $data['provider'],
        $data['type'],
        $data['game_type'],
        $data['api'],
        $data['status']
    );

    if (!$qry->execute()) {
        var_dump($qry->error);
        die("Erro ao executar a consulta.");
    }

    return true;
}

function delete_game($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM games WHERE id = ?");
    $qry->bind_param("i", $id);
    return $qry->execute();
}

$toastType = null; 
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_game'])) {
        $game_id = intval($_POST['id']);
        if (delete_game($game_id)) {
            $toastType = 'success';
            $toastMessage = 'Jogo excluído com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao excluir o jogo. Tente novamente.';
        }
    } else {
        $data = [
            'game_name' => $_POST['game_name'],
            'game_code' => $_POST['game_code'],
            'banner' => $_POST['banner'],
            'provider' => $_POST['provider'],
            'type' => $_POST['type'],
            'game_type' => $_POST['game_type'],
            'api' => $_POST['api'],
        ];

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $data['id'] = intval($_POST['id']);
            if (update_game($data)) {
                $toastType = 'success';
                $toastMessage = 'Jogo atualizado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao atualizar o jogo. Tente novamente.';
            }
        } else {
            if (add_game($data)) {
                $toastType = 'success';
                $toastMessage = 'Jogo adicionado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao adicionar o jogo. Tente novamente.';
            }
        }
    }
}

# Configurações de paginação e pesquisa
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 6;  // Número de jogos por página
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_games = count_games($search);
$total_pages = ceil($total_games / $limit);

# Buscar jogos com a pesquisa
$games = get_games($limit, $offset, $search);
?>

<head>
    <?php $title = "Gerenciamento de Jogos PlayFiver";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
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
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Jogos PlayFiver</h4>
                                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addGameModal">Adicionar novo Jogo</button>
                            </div>

                            <div class="card-body">
                                <!-- Formulário de Pesquisa -->
                                <form method="GET" action="" class="mb-3">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder="Pesquisar pelo nome do jogo" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                        <button class="btn btn-primary" type="submit">Buscar</button>
                                    </div>
                                </form>

                                <table class="table table-responsive">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Popular</th>
                                            <th>Categoria</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td><?= $game['game_name'] ?></td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="popular_<?= $game['id'] ?>" id="popular_sim_<?= $game['id'] ?>" value="1" 
                                                            <?= $game['popular'] == 1 ? 'checked' : '' ?> 
                                                            onclick="updateGameSetting(<?= $game['id'] ?>, 'popular', 1)">
                                                        <label class="form-check-label" for="popular_sim_<?= $game['id'] ?>">Sim</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="popular_<?= $game['id'] ?>" id="popular_nao_<?= $game['id'] ?>" value="0" 
                                                            <?= $game['popular'] == 0 ? 'checked' : '' ?> 
                                                            onclick="updateGameSetting(<?= $game['id'] ?>, 'popular', 0)">
                                                        <label class="form-check-label" for="popular_nao_<?= $game['id'] ?>">Não</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="status_<?= $game['id'] ?>" id="status_ativo_<?= $game['id'] ?>" value="1" 
                                                            <?= $game['status'] == 1 ? 'checked' : '' ?> 
                                                            onclick="updateGameSetting(<?= $game['id'] ?>, 'status', 1)">
                                                        <label class="form-check-label" for="status_ativo_<?= $game['id'] ?>">Sim</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="status_<?= $game['id'] ?>" id="status_inativo_<?= $game['id'] ?>" value="0" 
                                                            <?= $game['status'] == 0 ? 'checked' : '' ?> 
                                                            onclick="updateGameSetting(<?= $game['id'] ?>, 'status', 0)">
                                                        <label class="form-check-label" for="status_inativo_<?= $game['id'] ?>">Não</label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editGameModal<?= $game['id'] ?>">Editar</button>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="editGameModal<?= $game['id'] ?>" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editGameModalLabel">Editar Jogo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="game_name" class="form-label">Nome do Jogo</label>
                                                                    <input type="text" name="game_name" class="form-control" value="<?= $game['game_name'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="game_code" class="form-label">Código do Jogo</label>
                                                                    <input type="text" name="game_code" class="form-control" value="<?= $game['game_code'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="banner" class="form-label">Banner</label>
                                                                    <div class="mb-2">
                                                                        <img src="<?= $game['banner'] ?>" alt="Preview do Banner" class="img-thumbnail" style="max-width: 150px;">
                                                                    </div>
                                                                    <input type="text" name="banner" class="form-control" value="<?= $game['banner'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="provider" class="form-label">Provedor</label>
                                                                    <input type="text" name="provider" class="form-control" value="<?= $game['provider'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="api" class="form-label">API Usada</label>
                                                                    <select name="api" class="form-control" required>
                                                                        <option value="Clone" <?= $game['api'] == 'Clone' ? 'selected' : '' ?>>Clone</option>
                                                                        <option value="PlayFiver" <?= $game['api'] == 'PlayFiver' ? 'selected' : '' ?>>PlayFiver</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <input type="hidden" name="type" value="slot">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <input type="hidden" name="game_type" value="3">
                                                                </div>
                                                                <input type="hidden" name="id" value="<?= $game['id'] ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                <button type="submit" class="btn btn-primary">Salvar alterações</button>
                                                                
                                                                <form method="POST" action="" style="display:inline;">
                                                                    <input type="hidden" name="id" value="<?= $game['id'] ?>">
                                                                    <button type="submit" class="btn btn-danger" name="delete_game">Excluir Jogo</button>
                                                                </form>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal de adição de jogo -->
                                            <div class="modal fade" id="addGameModal" tabindex="-1" aria-labelledby="addGameModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="addGameModalLabel">Adicionar Novo Jogo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label for="game_name" class="form-label">Nome do Jogo</label>
                                                                    <input type="text" name="game_name" class="form-control" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="game_code" class="form-label">Código do Jogo</label>
                                                                    <input type="text" name="game_code" class="form-control" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="banner" class="form-label">Banner</label>
                                                                    <input type="text" name="banner" class="form-control" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="provider" class="form-label">Provedor</label>
                                                                    <input type="text" name="provider" class="form-control" required>
                                                                </div>
                                                                <!-- Removido o campo "API Usada" -->
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                <button type="submit" class="btn btn-primary">Adicionar Jogo</button>
                                                            </div>
                                                            <!-- Campo oculto para definir o valor de "PlayFiver" em 'api' -->
                                                            <input type="hidden" name="api" value="PlayFiver">
                                                            <input type="hidden" name="type" value="slot">
                                                            <input type="hidden" name="game_type" value="3">
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                 <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Página anterior">
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
                                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                
                                        <?php if ($end < $total_pages): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                
                                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Próxima página">
                                                <span aria-hidden="true">&raquo;</span>
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
    </script>
    
    <script>
    function updateGameSetting(gameId, field, value) {
        const formData = new FormData();
        formData.append('action', 'update_game_setting');
        formData.append('id', gameId);
        formData.append('field', field);
        formData.append('value', value);

        fetch('ajax/att_jogos_all.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Exibir mensagem de sucesso usando o toast
                showToast('success', data.message || 'Configuração atualizada com sucesso!');
            } else {
                // Exibir mensagem de erro usando o toast
                showToast('error', data.message || 'Erro ao atualizar a configuração.');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            // Exibir mensagem de erro usando o toast
            showToast('error', 'Erro ao tentar atualizar a configuração.');
        });
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
