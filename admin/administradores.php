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

// Função para buscar todos os administradores
function get_admins()
{
    global $mysqli;
    $qry = "SELECT * FROM admin_users";
    $result = mysqli_query($mysqli, $qry);
    $admins = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $admins[] = $row;
    }
    return $admins;
}

function bloquearadmin(){
    if ($_SESSION['data_adm']['email'] == 'vxciian@gmail.com') {
        // Redireciona para a página de login ou exibe uma mensagem de erro
        header("Location: dashboard");
        exit();
    }
}

bloquearadmin();

// Função para verificar se é o admin master
function is_master_admin($email) {
    return false;
}

// Função para verificar se o usuário logado pode editar um admin
function pode_editar_admin($admin_email) {
    return true;
}

// Função para validar 2FA do administrador logado
function validar_2fa_admin($codigo_2fa)
{
    global $mysqli;
    $admin_id = $_SESSION['data_adm']['id'];
    
    $qry = $mysqli->prepare("SELECT 2fa FROM admin_users WHERE id = ?");
    $qry->bind_param("i", $admin_id);
    $qry->execute();
    $result = $qry->get_result();
    $admin = $result->fetch_assoc();
    
    if ($admin && password_verify($codigo_2fa, $admin['2fa'])) {
        return true;
    }
    return false;
}

function update_admin($data)
{
    global $mysqli;

    $qry_check = $mysqli->prepare("SELECT email FROM admin_users WHERE id = ?");
    $qry_check->bind_param("i", $data['id']);
    $qry_check->execute();
    $result_check = $qry_check->get_result();
    $admin_check = $result_check->fetch_assoc();
    
    if (!pode_editar_admin($admin_check['email'])) {
        return false;
    }

    $atualizar_senha = !empty($data['senha']);
    $atualizar_2fa = !empty($data['2fa']);

    if ($atualizar_senha) {
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT, array("cost" => 10));
    }

    if ($atualizar_2fa) {
        $twofa_hash = password_hash($data['2fa'], PASSWORD_DEFAULT, array("cost" => 10));
    }

    if ($atualizar_senha && $atualizar_2fa) {
        $qry = $mysqli->prepare("UPDATE admin_users SET nome = ?, email = ?, nivel = ?, status = ?, senha = ?, 2fa = ? WHERE id = ?");
        $qry->bind_param(
            "ssisssi",
            $data['nome'],
            $data['email'],
            $data['nivel'],
            $data['status'],
            $senha_hash,
            $twofa_hash,
            $data['id']
        );
    } elseif ($atualizar_senha && !$atualizar_2fa) {
        $qry = $mysqli->prepare("UPDATE admin_users SET nome = ?, email = ?, nivel = ?, status = ?, senha = ? WHERE id = ?");
        $qry->bind_param(
            "ssissi",
            $data['nome'],
            $data['email'],
            $data['nivel'],
            $data['status'],
            $senha_hash,
            $data['id']
        );
    } elseif (!$atualizar_senha && $atualizar_2fa) {
        $qry = $mysqli->prepare("UPDATE admin_users SET nome = ?, email = ?, nivel = ?, status = ?, 2fa = ? WHERE id = ?");
        $qry->bind_param(
            "ssissi",
            $data['nome'],
            $data['email'],
            $data['nivel'],
            $data['status'],
            $twofa_hash,
            $data['id']
        );
    } else {
        $qry = $mysqli->prepare("UPDATE admin_users SET nome = ?, email = ?, nivel = ?, status = ? WHERE id = ?");
        $qry->bind_param(
            "ssisi",
            $data['nome'],
            $data['email'],
            $data['nivel'],
            $data['status'],
            $data['id']
        );
    }

    return $qry->execute();
}

function add_admin($data)
{
    global $mysqli;
    $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT, array("cost" => 10));
    $twofa_hash = password_hash($data['2fa'], PASSWORD_DEFAULT, array("cost" => 10));
    
    $qry = $mysqli->prepare("INSERT INTO admin_users (nome, email, contato, senha, nivel, status, avatar, 2fa) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $qry->bind_param(
        "ssssisss",
        $data['nome'],
        $data['email'],
        $data['contato'],
        $senha_hash,
        $data['nivel'],
        $data['status'],
        $data['avatar'],
        $twofa_hash
    );
    return $qry->execute();
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_admin'])) {
    // Validar 2FA antes de editar
    if (!isset($_POST['codigo_2fa_validacao']) || empty($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA é obrigatório para editar administrador.';
    } elseif (!validar_2fa_admin($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA inválido. Acesso negado.';
    } else {
        $data = [
            'id' => intval($_POST['id']),
            'nome' => $_POST['nome'],
            'email' => $_POST['email'],
            'contato' => $_POST['contato'],
            'senha' => $_POST['senha'],
            '2fa' => $_POST['2fa'],
            'nivel' => intval($_POST['nivel']),
            'status' => intval($_POST['status']),
            'avatar' => $_POST['avatar']
        ];

        $update_result = update_admin($data);
        
        if ($update_result === false) {
            $toastType = 'error';
            $toastMessage = 'Você não tem permissão para editar este administrador.';
        } elseif ($update_result) {
            $toastType = 'success';
            $toastMessage = 'Administrador atualizado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao atualizar o administrador.';
        }
    }
}


function clear_history_complete()
{
    global $mysqli;
    
    // Tabelas para limpar
    $tables = [
        'historico_play', 
        'transacoes', 
        'usuarios', 
        'visita_site', 
        'bau', 
        'solicitacao_saques', 
        'logs', 
        'metodos_pagamentos', 
        'cupom_usados', 
        'historico_vip', 
        'adicao_saldo', 
        'resgate_comissoes', 
        'lobby_pgsoft',
        'customer_feedback',
        'manipulacao_indicacoes'
    ];
    
    // Limpar tabelas do banco
    foreach ($tables as $table) {
        $mysqli->query("DELETE FROM $table");
    }
    
    // Limpar sessões ativas (se houver tabela de sessões)
    $mysqli->query("DELETE FROM sessions WHERE 1=1");
    
    // Destruir sessão atual
    session_destroy();
    
    // Limpar todos os cookies do domínio
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time()-3600, '/');
            setcookie($name, '', time()-3600, '/', $_SERVER['HTTP_HOST']);
            setcookie($name, '', time()-3600, '/', '.' . $_SERVER['HTTP_HOST']);
        }
    }
    
    return true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['limpar_historico'])) {
    // Validar 2FA antes de limpar histórico
    if (!isset($_POST['codigo_2fa_validacao']) || empty($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA é obrigatório para limpar histórico.';
    } elseif (!validar_2fa_admin($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA inválido. Acesso negado.';
    } else {
        $tables = [
            'historico_play', 'transacoes', 'usuarios', 'visita_site', 'bau',
            'solicitacao_saques', 'logs', 'metodos_pagamentos', 'cupom_usados',
            'historico_vip', 'adicao_saldo', 'resgate_comissoes', 'lobby_pgsoft',
            'customer_feedback', 'manipulacao_indicacoes'
        ];
        $errors = [];

        // Limpar tabelas
        foreach ($tables as $table) {
            $query = "DELETE FROM $table";
            if (!$mysqli->query($query)) {
                $errors[] = "Erro ao limpar a tabela $table: " . $mysqli->error;
            }
        }

        // Limpar sessões ativas
        $mysqli->query("DELETE FROM sessions WHERE 1=1");

        // Limpar todos os cookies do painel atual
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                $name = trim($parts[0]);
                setcookie($name, '', time() - 3600, '/');
                setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
                setcookie($name, '', time() - 3600, '/', '.' . $_SERVER['HTTP_HOST']);
            }
        }

        if (empty($errors)) {
            $toastType = 'success';
            $toastMessage = 'Histórico e sessões limpos com sucesso! Redirecionando...';

            echo '<script>
                // 🔹 Limpar dados locais do painel admin
                if (typeof(Storage) !== "undefined") {
                    localStorage.clear();
                    sessionStorage.clear();
                }

                // 🔹 Apagar cookies do painel admin
                document.cookie.split(";").forEach(function(c) {
                    document.cookie = c.replace(/^ +/, "")
                        .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                });

                // 🔹 Forçar limpeza também no domínio principal (usuários)
                var iframe = document.createElement("iframe");
                iframe.style.display = "none";
                iframe.src = "data:text/html;base64," + btoa(`
                    <script>
                        try {
                            // Lista de cookies comuns do site principal
                            var cookiesToDelete = [
                                "_ga",
                                "_ga_63M4EQ6DW5",
                                "gt_local_id",
                                "PHPSESSID",
                                "token_user",
                                "web__lobby__persisted__token",
                                "web__lobby__persisted__user"
                            ];

                            cookiesToDelete.forEach(function(name) {
                                // Clear on current domain
                                document.cookie = name + "=;expires=" + new Date(0).toUTCString() + ";path=/";
                                // Clear on root domain if possible (generic attempt)
                                var domainParts = window.location.hostname.split('.');
                                if (domainParts.length > 2) {
                                    var rootDomain = domainParts.slice(-2).join('.');
                                    document.cookie = name + "=;expires=" + new Date(0).toUTCString() + ";path=/;domain=." + rootDomain;
                                }
                            });

                            // Limpa localStorage e sessionStorage no domínio principal
                            if (typeof(Storage) !== "undefined") {
                                localStorage.clear();
                                sessionStorage.clear();
                            }

                            // Limpa qualquer outro cookie residual
                            document.cookie.split(";").forEach(function(c) {
                                document.cookie = c.replace(/^ +/, "")
                                    .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/");
                            });
                        } catch (e) {}
                    <\/script>
                `);
                document.body.appendChild(iframe);

                // 🔹 Redirecionar após limpeza
                setTimeout(function() {
                    window.location.href = "auth-login.php";
                }, 2500);
            </script>';

        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao limpar o histórico: ' . implode(', ', $errors);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_admin'])) {
    // Validar 2FA antes de adicionar
    if (!isset($_POST['codigo_2fa_validacao']) || empty($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA é obrigatório para adicionar administrador.';
    } elseif (!validar_2fa_admin($_POST['codigo_2fa_validacao'])) {
        $toastType = 'error';
        $toastMessage = 'Código 2FA inválido. Acesso negado.';
    } else {
        $data = [
            'nome' => $_POST['nome'],
            'email' => $_POST['email'],
            'contato' => '',
            'senha' => $_POST['senha'],
            '2fa' => $_POST['2fa'],
            'nivel' => 0,
            'status' => 1,
            'avatar' => ''
        ];

        if (add_admin($data)) {
            $toastType = 'success';
            $toastMessage = 'Novo administrador adicionado com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao adicionar o administrador.';
        }
    }
}

$admins = get_admins();
$email_logado = $_SESSION['data_adm']['email'];
?>


<head>
<?php $title = "Gerenciamento de Administradores";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <?php include 'partials/head-css.php' ?>
</head>
<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>
    
    <?php if (!isset($_SESSION['2fa_validado']) || $_SESSION['2fa_validado'] !== true): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('modal2FA'));
            modal.show();
        });
    </script>
    <?php endif; ?>


    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="card-title mb-0">
                                        <i class="ti ti-shield-lock me-2"></i><?= admin_t('page_admins_title') ?>
                                    </h4>
                                    <p class="text-muted mb-0"><?= admin_t('page_admins_subtitle') ?></p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-outline-danger btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#limparHistoricoModal">
                                        <i class="ti ti-trash-x me-1"></i><?= admin_t('button_clear_history') ?>
                                    </button>
                                    <button class="btn btn-primary btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                        <i class="ti ti-user-plus me-1"></i><?= admin_t('button_new_admin') ?>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle text-nowrap mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nome</th>
                                                <th>Email</th>
                                                <th>2FA</th>
                                                <th><?= admin_t('status') ?></th>
                                                <th class="text-end">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($admins as $admin): ?>
                                            <tr>
                                                <td>
                                                    <?= $admin['nome'] ?>
                                                    <?php if (is_master_admin($admin['email'])): ?>
                                                        <span class="badge bg-secondary ms-2" title="Administrador Master">
                                                            <i class="ti ti-crown me-1"></i>Desenvolvedor
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $admin['email'] ?></td>
                                                <td><span class="badge bg-success-subtle text-success">2FA ativo</span></td>
                                                <td>
                                                    <?php if ($admin['status'] == 1) { ?>
                                                        <span class="badge bg-success-subtle text-success"><?= admin_t('status_active') ?></span>
                                                    <?php } else { ?>
                                                        <span class="badge bg-danger-subtle text-danger"><?= admin_t('status_inactive') ?></span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (pode_editar_admin($admin['email'])): ?>
                                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editAdminModal<?= $admin['id'] ?>">
                                                            <i class="ti ti-pencil me-1"></i><?= admin_t('button_edit') ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm d-inline-flex align-items-center" disabled title="Apenas o administrador master pode editar esta conta">
                                                            <i class="ti ti-lock me-1"></i><?= admin_t('button_blocked') ?>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- Modal de Edição -->
                                            <?php if (pode_editar_admin($admin['email'])): ?>
                                            <div class="modal fade" id="editAdminModal<?= $admin['id'] ?>" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editAdminModalLabel">
                                                                <?= admin_t('modal_edit_admin') ?>
                                                                <?php if (is_master_admin($admin['email'])): ?>
                                                                    <span class="badge bg-danger ms-2 d-inline-flex align-items-center">
                                                                        <i class="ti ti-crown me-1"></i>MASTER
                                                                    </span>
                                                                <?php endif; ?>
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= admin_t('modal_close') ?>"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                                            <input type="hidden" name="contato" value="<?= $admin['contato'] ?>">
                                                            <input type="hidden" name="avatar" value="<?= $admin['avatar'] ?>">
                                                            <input type="hidden" name="nivel" value="<?= $admin['nivel'] ?>">
                                                            
                                                            <div class="modal-body">
                                                                <?php if (is_master_admin($admin['email'])): ?>
                                                                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                                                                        <i class="ti ti-crown me-2"></i>
                                                                        <span><strong>Conta master:</strong> esta é a conta principal do sistema. Tome cuidado ao fazer alterações.</span>
                                                                    </div>
                                                                <?php endif; ?>
                                                                
                                                                <div class="alert alert-warning d-flex align-items-center" role="alert">
                                                                    <i class="ti ti-shield-lock me-2"></i>
                                                                    <span><strong>Segurança:</strong> digite seu código 2FA atual para confirmar esta ação.</span>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="codigo_2fa_validacao" class="form-label">Seu código 2FA atual *</label>
                                                                    <input type="text" name="codigo_2fa_validacao" class="form-control" placeholder="Digite seu código 2FA" required>
                                                                    <small class="text-muted">Obrigatório para validar esta operação.</small>
                                                                </div>
                                                                
                                                                <hr>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="nome" class="form-label">Nome</label>
                                                                    <input type="text" name="nome" class="form-control" value="<?= $admin['nome'] ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="email" class="form-label">Email</label>
                                                                    <input type="email" name="email" class="form-control" value="<?= $admin['email'] ?>" required <?= is_master_admin($admin['email']) ? 'readonly' : '' ?>>
                                                                    <?php if (is_master_admin($admin['email'])): ?>
                                                                        <small class="text-danger">O email do administrador master não pode ser alterado.</small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="senha" class="form-label">Nova senha</label>
                                                                    <input type="password" name="senha" class="form-control" placeholder="Deixe em branco para manter a senha atual">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="2fa" class="form-label">Novo código 2FA</label>
                                                                    <input type="text" name="2fa" class="form-control" placeholder="Digite o novo código 2FA" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="status" class="form-label">Status</label>
                                                                    <select name="status" class="form-select">
                                                                        <option value="1" <?= $admin['status'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                                                        <option value="0" <?= $admin['status'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="edit_admin" class="btn btn-primary"><?= admin_t('button_save_settings') ?></button>
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= admin_t('modal_close') ?></button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="modal fade" id="limparHistoricoModal" tabindex="-1" aria-labelledby="limparHistoricoModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title d-flex align-items-center" id="limparHistoricoModalLabel">
                                            <i class="ti ti-trash-x me-2"></i><?= admin_t('button_clear_history') ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                                <i class="ti ti-alert-triangle me-2"></i>
                                                <span><strong>Atenção:</strong> esta ação é irreversível e apagará todos os dados do sistema.</span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="codigo_2fa_validacao" class="form-label">Seu código 2FA *</label>
                                                <input type="text" name="codigo_2fa_validacao" class="form-control" placeholder="Digite seu código 2FA para confirmar" required>
                                                <small class="text-muted">Obrigatório para validar esta operação crítica.</small>
                                            </div>
                                            
                                            <p class="text-danger mb-0"><strong>Tem certeza de que deseja limpar todo o histórico?</strong></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" name="limpar_historico" class="btn btn-danger d-flex align-items-center">
                                                <i class="ti ti-trash-x me-1"></i>Confirmar limpeza
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title d-flex align-items-center" id="addAdminModalLabel">
                                            <i class="ti ti-user-plus me-2"></i><?= admin_t('button_add_admin') ?>
                                        </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= admin_t('modal_close') ?>"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <div class="alert alert-info d-flex align-items-center" role="alert">
                                                <i class="ti ti-shield-lock me-2"></i>
                                                <span><strong>Segurança:</strong> digite seu código 2FA atual para confirmar esta ação.</span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="codigo_2fa_validacao" class="form-label">Seu código 2FA atual *</label>
                                                <input type="text" name="codigo_2fa_validacao" class="form-control" placeholder="Digite seu código 2FA" required>
                                                <small class="text-muted">Obrigatório para validar esta operação.</small>
                                            </div>
                                            
                                            <hr>
                                            
                                            <div class="mb-3">
                                                <label for="nome" class="form-label">Nome</label>
                                                <input type="text" name="nome" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="senha" class="form-label">Senha</label>
                                                <input type="password" name="senha" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="2fa" class="form-label">Código 2FA do novo admin</label>
                                                <input type="text" name="2fa" class="form-control" placeholder="Defina o código 2FA" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
                                            <button type="submit" name="add_admin" class="btn btn-success d-flex align-items-center">
                                                <i class="ti ti-check me-1"></i><?= admin_t('button_add_admin') ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
    <?php include 'partials/endbar.php' ?>
    <?php include 'partials/footer.php' ?>
            
        </div>
    </div>
    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
            
            <?php if ($toastType === 'success' && strpos($toastMessage, 'Histórico e sessões limpos') !== false): ?>
                function clearAllData() {
                    if (typeof(Storage) !== "undefined") {
                        localStorage.clear();
                        sessionStorage.clear();
                    }
                    
                    document.cookie.split(";").forEach(function(c) { 
                        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
                        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/;domain=" + window.location.hostname); 
                        document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/;domain=." + window.location.hostname); 
                    });
                    
                    if ('caches' in window) {
                        caches.keys().then(function(names) {
                            names.forEach(function(name) {
                                caches.delete(name);
                            });
                        });
                    }
                    
                    setTimeout(function() {
                        window.location.replace("auth-login.php");
                    }, 2000);
                }
                
                clearAllData();
            <?php endif; ?>
        </script>
    <?php endif; ?>
    
</body>
</html>
