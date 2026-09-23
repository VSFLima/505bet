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

function get_historico_vip($limit, $offset, $search_query = null)
{
    global $mysqli;
    // Se existir o filtro de pesquisa, adicione à query
    $search_condition = "";
    if (!empty($search_query)) {
        $search_condition = " WHERE id_user LIKE '%" . mysqli_real_escape_string($mysqli, $search_query) . "%'";
    }

    $qry = "
        SELECT 
            id_user, 
            bonus, 
            data 
        FROM 
            historico_vip 
        $search_condition
        ORDER BY 
            data DESC 
        LIMIT $limit OFFSET $offset";
    
    $result = mysqli_query($mysqli, $qry);
    $historico = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $historico[] = $row;
    }
    return $historico;
}

function count_historico_vip($search_query = null)
{
    global $mysqli;
    // Se existir o filtro de pesquisa, adicione à query
    $search_condition = "";
    if (!empty($search_query)) {
        $search_condition = " WHERE id_user LIKE '%" . mysqli_real_escape_string($mysqli, $search_query) . "%'";
    }

    $qry = "SELECT COUNT(*) as total FROM historico_vip $search_condition";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search_query = isset($_GET['search']) ? $_GET['search'] : '';  // Filtro de pesquisa

// Contagem total de registros
$total_historico = count_historico_vip($search_query);
$total_pages = ceil($total_historico / $limit);

// Pegar os registros com a busca e a paginação
$historico_vip = get_historico_vip($limit, $offset, $search_query);
?>

<head>
    <?php $title = "Histórico de Níveis Ganhos";
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
                                <h4 class="card-title">Histórico de Níveis</h4>
                            </div>
                            
                            <div class="card-body pt-0">
                                <form method="GET" action="">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <input type="text" name="search" class="form-control"
                                                placeholder="Buscar por ID do usuário"
                                                value="<?= htmlspecialchars($search_query) ?>">
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button type="submit" class="btn btn-success mt-2 mb-2">Filtrar</button>
                                        </div>
                                    </div>
                            </form>
                            
                            <div class="card-body">
                                <table class="table table-responsive">
                                    <thead>
                                        <tr>
                                            <th>ID Usuário</th>
                                            <th>Bônus</th>
                                            <th>Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($historico_vip as $registro): ?>
                                            <tr>
                                                <td><?= $registro['id_user'] ?></td>
                                                <td>R$ <?= number_format($registro['bonus'], 2, ',', '.') ?></td>
                                                <td><?= date('d/m/Y H:i:s', strtotime($registro['data'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            
<nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=1" aria-label="Primeira página">
                                                <span aria-hidden="true">&laquo;&laquo;</span>
                                            </a>
                                        </li>
                                
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
                                
                                        <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?page=<?= $total_pages ?>" aria-label="Última página">
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

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
