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
            AND distribution = 'BeePlay' 
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
            AND distribution = 'BeePlay'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

# Função para atualizar os dados do jogo
function update_game($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE games SET 
        game_name = ?, 
        banner = ?, 
        status = ?, 
        provider = ?, 
        popular = ?, 
        type = ?, 
        game_type = ? 
        WHERE id = ?");

    $qry->bind_param(
        "ssisssii",
        $data['game_name'],
        $data['banner'],
        $data['status'],
        $data['provider'],
        $data['popular'],
        $data['type'],
        $data['game_type'],
        $data['id']
    );
    return $qry->execute();
}

# Se o formulário for enviado, atualizar os dados do jogo
$toastType = null; 
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'id' => intval($_POST['id']),
        'game_name' => $_POST['game_name'],
        'banner' => $_POST['banner'],
        'status' => intval($_POST['status']),
        'provider' => $_POST['provider'],
        'popular' => intval($_POST['popular']),
        'type' => $_POST['type'],
        'game_type' => $_POST['game_type'],
    ];

    if (update_game($data)) {
        $toastType = 'success';
        $toastMessage = 'Jogo atualizado com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar o jogo. Tente novamente.';
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
    <?php $title = "Gerenciamento de Jogos BeePlay";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
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
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Jogos BeePlay</h4>
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
                                            <th>Banner</th>
                                            <th>Nome</th>
                                            <th>Início</th>
                                            <th>Popular</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= $game['banner'] ?>" alt="Banner" class="rounded-circle" style="width: 35px; height: 35px;">
                                                </td>
                                                <td><?= $game['game_name'] ?></td>
                                                <td><?= $game['status'] == 1 ? 'Ativo' : 'Inativo' ?></td>
                                                <td><?= $game['popular'] == 1 ? 'Sim' : 'Não' ?></td>
                                                <td>
                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editGameModal<?= $game['id'] ?>">Editar</button>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
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
                                                                    <label for="banner" class="form-label">Banner</label>
                                                                    <input type="text" name="banner" class="form-control" value="<?= $game['banner'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Início</label>
                                                                    <select name="status" class="form-select" required>
                                                                        <option value="1" <?= $game['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                                                        <option value="0" <?= $game['status'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="provider" class="form-label">Provider</label>
                                                                    <input type="text" name="provider" class="form-control" value="<?= $game['provider'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="popular" class="form-label">Popular</label>
                                                                    <select name="popular" class="form-select" required>
                                                                        <option value="1" <?= $game['popular'] == 1 ? 'selected' : '' ?>>Sim</option>
                                                                        <option value="0" <?= $game['popular'] == 0 ? 'selected' : '' ?>>Não</option>
                                                                    </select>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="type" class="form-label">Tipo</label>
                                                                    <input type="text" name="type" class="form-control" value="<?= $game['type'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="game_type" class="form-label">Game Type</label>
                                                                    <input type="text" name="game_type" class="form-control" value="<?= $game['game_type'] ?>" required>
                                                                </div>
                                                                <input type="hidden" name="id" value="<?= $game['id'] ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                                                <button type="submit" class="btn btn-primary">Salvar alterações</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <!-- Paginação -->
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>

                            </div>
                        </div>
                    </div>
                </div><!-- end row -->
            </div><!-- container -->
            
                            <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->

    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        function showToast(type, message){window.showToast(type,message);}
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
