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

function get_coupons($limit, $offset)
{
    global $mysqli;
    $qry = "SELECT * FROM cupom ORDER BY id DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($mysqli, $qry);
    $coupons = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $coupons[] = $row;
    }
    return $coupons;
}

function count_coupons()
{
    global $mysqli;
    $qry = "SELECT COUNT(*) as total FROM cupom";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

function add_coupon($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("INSERT INTO cupom (nome, valor, qtd_insert, range_valor, status) VALUES (?, 0, ?, ?, ?)");
    $qry->bind_param("sisi", $data['nome'], $data['qtd_insert'], $data['range_valor'], $data['status']);
    return $qry->execute();
}

function update_coupon($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE cupom SET 
        nome = ?, 
        qtd_insert = ?, 
        range_valor = ?, 
        status = ? 
        WHERE id = ?");

    $qry->bind_param(
        "sisii",
        $data['nome'],
        $data['qtd_insert'],
        $data['range_valor'],
        $data['status'],
        $data['id']
    );
    return $qry->execute();
}

function delete_coupon($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM cupom WHERE id = ?");
    $qry->bind_param("i", $id);
    return $qry->execute();
}

$toastType = null; 
$toastMessage = '';

// Adicionar novo cupom
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $data = [
        'nome' => $_POST['nome'],
        'qtd_insert' => intval($_POST['qtd_insert']),
        'range_valor' => $_POST['range_valor'],
        'status' => 0, // Sempre inativo ao criar
    ];

    if (add_coupon($data)) {
        $toastType = 'success';
        $toastMessage = 'Cupom adicionado com sucesso! Ative-o na tabela quando estiver pronto.';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao adicionar o cupom. Tente novamente.';
    }
}

// Atualizar cupom
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $data = [
        'id' => intval($_POST['id']),
        'nome' => $_POST['nome'],
        'qtd_insert' => intval($_POST['qtd_insert']),
        'range_valor' => $_POST['range_valor'],
        'status' => isset($_POST['status']) ? 1 : 0,
    ];

    if (update_coupon($data)) {
        $toastType = 'success';
        $toastMessage = 'Cupom atualizado com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao atualizar o cupom. Tente novamente.';
    }
}

// Deletar cupom
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = intval($_POST['id']);
    
    if (delete_coupon($id)) {
        $toastType = 'success';
        $toastMessage = 'Cupom excluído com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao excluir o cupom. Tente novamente.';
    }
}

// Toggle status do cupom
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
    $id = intval($_POST['id']);
    $status = intval($_POST['status']);
    
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE cupom SET status = ? WHERE id = ?");
    $qry->bind_param("ii", $status, $id);
    
    if ($qry->execute()) {
        $toastType = 'success';
        $toastMessage = $status == 1 ? 'Cupom ativado com sucesso!' : 'Cupom desativado com sucesso!';
    } else {
        $toastType = 'error';
        $toastMessage = 'Erro ao alterar status. Tente novamente.';
    }
}

$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_coupons = count_coupons();
$total_pages = ceil($total_coupons / $limit);

$coupons = get_coupons($limit, $offset);
?>

<head>
    <?php $title = "Gerenciamento de Cupons";
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
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Gerenciamento de Cupons</h4>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCouponModal">
                                    <i class="fas fa-plus"></i> Adicionar Novo Cupom
                                </button>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nome do Cupom</th>
                                                <th>Qtd. Disponível</th>
                                                <th>Valor do Bônus (Range)</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($coupons)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">Nenhum cupom cadastrado</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($coupons as $coupon): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($coupon['nome']) ?></strong></td>
                                                        <td><span class="badge bg-info"><?= $coupon['qtd_insert'] ?> Unidades</span></td>
                                                        <td><strong><?= htmlspecialchars($coupon['range_valor']) ?></strong></td>
                                                        <td>
                                                            <form method="POST" action="" class="d-inline" id="form-status-<?= $coupon['id'] ?>">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                           id="status-toggle-<?= $coupon['id'] ?>" 
                                                                           <?= $coupon['status'] == 1 ? 'checked' : '' ?>
                                                                           onchange="toggleStatus(<?= $coupon['id'] ?>, this.checked)">
                                                                </div>
                                                                <input type="hidden" name="action" value="toggle_status">
                                                                <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                                                <input type="hidden" name="status" id="status-value-<?= $coupon['id'] ?>" value="<?= $coupon['status'] == 1 ? 0 : 1 ?>">
                                                            </form>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group gap-1" role="group">
                                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCouponModal<?= $coupon['id'] ?>">
                                                                    <i class="fas fa-edit"></i> Editar
                                                                </button>
                                                                <button class="btn btn-sm btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#deleteCouponModal<?= $coupon['id'] ?>">
                                                                    <i class="fas fa-trash"></i> Excluir
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>

                                                    <!-- Modal de Edição -->
                                                    <div class="modal fade" id="editCouponModal<?= $coupon['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Editar Cupom</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST" action="">
                                                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome do Cupom</label>
                                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($coupon['nome']) ?>" placeholder="Ex: DEPÓSITO DE 20" required>
                                            <small class="text-muted">Exemplo: DEPÓSITO DE 20, BÔNUS VIP, etc.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label for="qtd_insert" class="form-label">Quantidade Disponível</label>
                                            <input type="number" name="qtd_insert" class="form-control" value="<?= $coupon['qtd_insert'] ?>" min="0" required>
                                            <small class="text-muted">Quantidade total de cupons que podem ser utilizados</small>
                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="range_valor" class="form-label">Valor do Bônus (Range)</label>
                                                                            <input type="text" name="range_valor" class="form-control" value="<?= htmlspecialchars($coupon['range_valor']) ?>" placeholder="Ex: 3,8" required>
                                                                            <small class="text-muted">Defina o intervalo do bônus (mínimo,máximo). Ex: 3,8 para um valor aleatório entre 3 e 8.</small>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <div class="form-check form-switch">
                                                                                <input class="form-check-input" type="checkbox" name="status" id="status-edit-<?= $coupon['id'] ?>" <?= $coupon['status'] == 1 ? 'checked' : '' ?>>
                                                                                <label class="form-check-label" for="status-edit-<?= $coupon['id'] ?>">
                                                                                    Cupom Ativo
                                                                                </label>
                                                                            </div>
                                                                        </div>
                                                                        <input type="hidden" name="action" value="edit">
                                                                        <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Modal de Exclusão -->
                                                    <div class="modal fade" id="deleteCouponModal<?= $coupon['id'] ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header bg-danger text-white">
                                                                    <h5 class="modal-title">Confirmar Exclusão</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form method="POST" action="">
                                                                    <div class="modal-body">
                                                                        <p>Tem certeza que deseja excluir o bônus <strong><?= htmlspecialchars($coupon['nome']) ?></strong>?</p>
                                                                        <p class="text-danger"><strong>Esta ação não pode ser desfeita!</strong></p>
                                                                        <input type="hidden" name="action" value="delete">
                                                                        <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                        <button type="submit" class="btn btn-danger">Sim, Excluir</button>
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
            </div>
            
    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
        </div>
    </div>

    <!-- Modal de Adicionar -->
    <div class="modal fade" id="addCouponModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Cupom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Cupom</label>
                            <input type="text" name="nome" class="form-control" placeholder="Ex: DEPÓSITO DE 20" required>
                            <small class="text-muted">Exemplo: DEPÓSITO DE 20, BÔNUS VIP, etc.</small>
                        </div>
                        <div class="mb-3">
                            <label for="qtd_insert" class="form-label">Quantidade Disponível</label>
                            <input type="number" name="qtd_insert" id="add-bonus" class="form-control" placeholder="100" min="0" required>
                            <small class="text-muted">Quantidade total de cupons que podem ser utilizados</small>
                        </div>
                        <div class="mb-3">
                            <label for="range_valor" class="form-label">Valor do Bônus (Range)</label>
                            <input type="text" name="range_valor" class="form-control" placeholder="Ex: 3,8" required>
                            <small class="text-muted">Defina o intervalo do bônus (mínimo,máximo). Ex: 3,8 para um valor aleatório entre 3 e 8.</small>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> O cupom será criado como <strong>inativo</strong>. Você poderá ativá-lo depois na tabela.
                        </div>
                        <input type="hidden" name="action" value="add">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Adicionar Cupom</button>
                    </div>
                </form>
            </div>
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
                    <h5 class="me-auto my-0">${type === 'success' ? 'Sucesso' : 'Erro'}</h5>
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

        function toggleStatus(id, checked) {
            const form = document.getElementById('form-status-' + id);
            const statusValue = document.getElementById('status-value-' + id);
            
            // Atualiza o valor do input hidden
            statusValue.value = checked ? 1 : 0;
            
            // Submete o formulário
            form.submit();
        }

    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>