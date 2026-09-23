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
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();

checa_login_adm();

function get_playfiver_config()
{
    global $mysqli;
    $qry = "SELECT * FROM playfiver WHERE id = 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_playfiver_config($data)
{
    global $mysqli;
    $qry = $mysqli->prepare("UPDATE playfiver SET 
        url = ?, 
        agent_code = ?, 
        agent_token = ?, 
        ativo = ?
        WHERE id = 1");

    $qry->bind_param(
        "sssi",
        $data['url'],
        $data['agent_code'],
        $data['agent_token'],
        $data['ativo']
    );
    return $qry->execute();
}


$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_playfiver'])) {
        $data = [
            'url' => $_POST['url_playfiver'],
            'agent_code' => $_POST['agent_code_playfiver'],
            'agent_token' => $_POST['agent_token_playfiver'],
            'ativo' => intval($_POST['ativo_playfiver']),
        ];

        if (update_playfiver_config($data)) {
            $toastType = 'success';
            $toastMessage = 'Credenciais do Playfiver atualizadas com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar as credenciais do Playfiver. Tente novamente.';
        }
    }
}

# Buscar os dados atuais
$playfiver_config = get_playfiver_config();
?>

<head>
    <?php $title = "Configurações do PlayFiver"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">

                <!-- Configuração playfiver -->
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Gerenciamento de Credenciais (PlayFiver)</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <input type="hidden" name="update_playfiver">
                                    <!-- Campos de Configuração playfiver -->
                                    <div class="row">
                                        <!-- URL -->
                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">URL</h5>
                                                    <input type="text" name="url_playfiver" class="form-control" value="<?= $playfiver_config['url'] ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Token</h5>
                                                    <div class="input-group">
                                                    <input type="password" id="agent_code_playfiver" name="agent_code_playfiver" class="form-control" value="<?= $playfiver_config['agent_code'] ?>" required>
                                                        <span class="input-group-text"
                                                            onclick="togglePassword('agent_code_playfiver', this)">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title">Agent Secret</h5>
                                                    <div class="input-group">
                                                    <input type="password" id="agent_token_playfiver" name="agent_token_playfiver" class="form-control" value="<?= $playfiver_config['agent_token'] ?>" required>
                                                        <span class="input-group-text"
                                                            onclick="togglePassword('agent_token_playfiver', this)">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                            <!-- Status Ativo -->
                                            <div class="col-md-6">
                                            <div class="card mb-4">
                                                <div class="card-body">
                                                    <h5 class="card-title"><i class="iconoir-check-circle"></i> Ativo</h5>
                                                    <select name="ativo_playfiver" class="form-select" required>
                                                        <option value="1" <?= $playfiver_config['ativo'] == 1 ? 'selected' : '' ?>>Sim</option>
                                                        <option value="0" <?= $playfiver_config['ativo'] == 0 ? 'selected' : '' ?>>Não</option>
                                                    </select>

                                                </div>
                                            </div>
                                        </div>
                                        <!-- Outros campos... -->
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-success">Salvar Configurações</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                    <!-- 
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Gerenciamento de Credenciais (BeePlay)</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="update_beeplay">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title">URL</h5>
                                                        <input type="text" name="url_beeplay" class="form-control" value="<?= $beeplay_config['url'] ?>" required>
                                                    </div>
                                                </div>
                                            </div>
    
                                            <div class="col-md-6">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Agent Token</h5>
                                                        <div class="input-group">
                                                        <input type="password" id="agent_code_beeplay" name="agent_code_beeplay" class="form-control" value="<?= $beeplay_config['agent_code'] ?>" required>
                                                            <span class="input-group-text"
                                                                onclick="togglePassword('agent_code_beeplay', this)">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
    
                                            <div class="col-md-6">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Agent Secret</h5>
                                                        <div class="input-group">
                                                        <input type="password" id="agent_token_beeplay" name="agent_token_beeplay" class="form-control" value="<?= $beeplay_config['agent_token'] ?>" required>
                                                            <span class="input-group-text"
                                                                onclick="togglePassword('agent_token_beeplay', this)">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
    
                                                <div class="col-md-6">
                                                <div class="card mb-4">
                                                    <div class="card-body">
                                                        <h5 class="card-title"><i class="iconoir-check-circle"></i> Ativo</h5>
                                                        <select name="ativo_beeplay" class="form-select" required>
                                                            <option value="1" <?= $beeplay_config['ativo'] == 1 ? 'selected' : '' ?>>Sim</option>
                                                            <option value="0" <?= $beeplay_config['ativo'] == 0 ? 'selected' : '' ?>>Não</option>
                                                        </select>
    
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-success">Salvar Configurações</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div> -->

            </div>
            
    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
            
        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <!-- Javascript -->
    <?php include 'partials/vendorjs.php' ?>
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
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const iconElement = icon.querySelector('i');

            if (input.type === "password") {
                input.type = "text";
                iconElement.classList.remove('fa-eye');
                iconElement.classList.add('fa-eye-slash');
            } else {
                input.type = "password";
                iconElement.classList.remove('fa-eye-slash');
                iconElement.classList.add('fa-eye');
            }
        }
    </script>

    <!-- Exibir o Toast baseado nas ações do formulário -->
    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
