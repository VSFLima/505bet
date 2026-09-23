<?php
session_start();
include_once "services/checa_login_adm.php";
checa_login_adm();
include_once "validar_2fa.php";
?>
<?php include 'partials/html.php' ?>

<head>
    <?php $title = "Configurações do Fiverscan e PGClone"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>
    <?php
    $toastType = null;
    $toastMessage = null;

    function getStatusBadge($status)
    {
        switch ($status) {
            case 'pago':
                return "<span class='badge bg-success'>Pago</span>";
            case 'processamento':
                return "<span class='badge bg-warning'>Pendente</span>";
            case 'expirado':
                return "<span class='badge bg-danger'>Expirado</span>";
            default:
                return "<span class='badge bg-secondary'>Indefinido</span>";
        }
    }
    
    if (isset($_REQUEST['slug'])) {
        $id_user = decodeAll($_REQUEST['slug']);
        $qry = "SELECT * FROM usuarios WHERE id='" . intval($id_user) . "'";
        $res = mysqli_query($mysqli, $qry);
        $data = mysqli_fetch_assoc($res);
        $saldo_user = saldo_user($data['id']);
    }

    // Carregar CPA global e preparar métricas/efetivo (Individual vs Global)
    include_once "services/afiliacao.php";
    $afiliadosConfigGlobal = getAfiliadosConfig();

    $cpa_individual = [
        'cpaLvl1' => isset($data['cpaLvl1']) ? floatval($data['cpaLvl1']) : 0,
        'cpaLvl2' => isset($data['cpaLvl2']) ? floatval($data['cpaLvl2']) : 0,
        'cpaLvl3' => isset($data['cpaLvl3']) ? floatval($data['cpaLvl3']) : 0,
    ];
    $cpa_efetivo = [
        1 => ($cpa_individual['cpaLvl1'] > 0 ? $cpa_individual['cpaLvl1'] : floatval($afiliadosConfigGlobal['cpaLvl1'])),
        2 => ($cpa_individual['cpaLvl2'] > 0 ? $cpa_individual['cpaLvl2'] : floatval($afiliadosConfigGlobal['cpaLvl2'])),
        3 => ($cpa_individual['cpaLvl3'] > 0 ? $cpa_individual['cpaLvl3'] : floatval($afiliadosConfigGlobal['cpaLvl3'])),
    ];
    $cpa_origem = [
        1 => ($cpa_individual['cpaLvl1'] > 0 ? 'Individual' : 'Global'),
        2 => ($cpa_individual['cpaLvl2'] > 0 ? 'Individual' : 'Global'),
        3 => ($cpa_individual['cpaLvl3'] > 0 ? 'Individual' : 'Global'),
    ];

    function getCpaMetrics($user_id) {
        global $mysqli;
        $metrics = [];
        for ($nivel = 1; $nivel <= 3; $nivel++) {
            $tipo = "comissao_cpa_nivel_{$nivel}";
            $stmt = $mysqli->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(valor),0) AS soma FROM adicao_saldo WHERE id_user = ? AND tipo = ?");
            $stmt->bind_param("is", $user_id, $tipo);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $metrics[$nivel] = [
                'qtd' => intval($res['qtd'] ?? 0),
                'soma_reais' => floatval(($res['soma'] ?? 0) / 100.0),
            ];
        }
        return $metrics;
    }
    $metrics_cpa = getCpaMetrics($data['id']);

    // Salvar CPA individual (0 zera e volta para global)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cpa_individual'])) {
        $cpa1 = isset($_POST['cpaLvl1_ind']) ? floatval($_POST['cpaLvl1_ind']) : 0;
        $cpa2 = isset($_POST['cpaLvl2_ind']) ? floatval($_POST['cpaLvl2_ind']) : 0;
        $cpa3 = isset($_POST['cpaLvl3_ind']) ? floatval($_POST['cpaLvl3_ind']) : 0;

        $stmt = $mysqli->prepare("UPDATE usuarios SET cpaLvl1 = ?, cpaLvl2 = ?, cpaLvl3 = ? WHERE id = ?");
        $stmt->bind_param("dddi", $cpa1, $cpa2, $cpa3, $id_user);
        $ok = $stmt->execute();

        if ($ok) {
            $toastType = 'success';
            $toastMessage = 'CPA individual atualizado!';
            // Reload dados atuais
            $qry = "SELECT * FROM usuarios WHERE id='" . intval($id_user) . "'";
            $res = mysqli_query($mysqli, $qry);
            $data = mysqli_fetch_assoc($res);
            $cpa_individual['cpaLvl1'] = isset($data['cpaLvl1']) ? floatval($data['cpaLvl1']) : 0;
            $cpa_individual['cpaLvl2'] = isset($data['cpaLvl2']) ? floatval($data['cpaLvl2']) : 0;
            $cpa_individual['cpaLvl3'] = isset($data['cpaLvl3']) ? floatval($data['cpaLvl3']) : 0;
        } else {
            $toastType = 'danger';
            $toastMessage = 'Erro ao atualizar CPA individual.';
        }

        $cpa_efetivo[1] = ($cpa_individual['cpaLvl1'] > 0 ? $cpa_individual['cpaLvl1'] : floatval($afiliadosConfigGlobal['cpaLvl1']));
        $cpa_efetivo[2] = ($cpa_individual['cpaLvl2'] > 0 ? $cpa_individual['cpaLvl2'] : floatval($afiliadosConfigGlobal['cpaLvl2']));
        $cpa_efetivo[3] = ($cpa_individual['cpaLvl3'] > 0 ? $cpa_individual['cpaLvl3'] : floatval($afiliadosConfigGlobal['cpaLvl3']));
        $cpa_origem[1] = ($cpa_individual['cpaLvl1'] > 0 ? 'Individual' : 'Global');
        $cpa_origem[2] = ($cpa_individual['cpaLvl2'] > 0 ? 'Individual' : 'Global');
        $cpa_origem[3] = ($cpa_individual['cpaLvl3'] > 0 ? 'Individual' : 'Global');
        $metrics_cpa = getCpaMetrics($data['id']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
        $real_name = $_POST['real_name'];
        $token = $_POST['token'];
        $password = $_POST['password'];
        $statusaff = $_POST['statusaff'];
        $freeze = $_POST['freeze'];
        $lobby = $_POST['lobby'];
        $invite = $_POST['invite'];
        $senha_saque = $_POST['senhaparasacar'];

        $update_query = "UPDATE usuarios SET 
            mobile='$real_name', 
            token='$token', 
            password='$password', 
            statusaff='$statusaff', 
            invite_code='$invite', 
            senhaparasacar='$senha_saque', 
            freeze='$freeze', 
            lobby='$lobby', 
            relogar='1' 
            WHERE id='" . intval($id_user) . "'";

        $update_res = mysqli_query($mysqli, $update_query);

        if ($update_res) {
            $toastType = 'success';
            $toastMessage = 'Dados do usuário atualizados com sucesso!';
        } else {
            $toastType = 'danger';
            $toastMessage = 'Erro ao atualizar os dados do usuário.';
        }
    }

    if ($data['banido'] == 1) {
        $view_status = '<span class="badge bg-dark">Banido</span>';
    } elseif ($data['statusaff'] == 1) {
        $view_status = '<span class="badge bg-danger">Afiliado</span>';
    } else {
        $view_status = '<span class="badge bg-secondary">Usuário</span>';
    }

    function buscar_afiliador($invitation_code) {
        global $mysqli;
        
        if (empty($invitation_code)) {
            return "Sem afiliação";
        }
        
        $query = "SELECT mobile FROM usuarios WHERE invite_code = '$invitation_code' LIMIT 1";
        $result = mysqli_query($mysqli, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $afiliador = mysqli_fetch_assoc($result);
            return htmlspecialchars($afiliador['mobile']);
        }
        
        return "Sem afiliação";
    }
    ?>

    <div class="page-wrapper">

        <div class="page-content">
            <div class="container-xxl">

                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-4 align-self-center mb-3 mb-lg-0">
                                        <div class="d-flex align-items-center flex-row flex-wrap">
                                            <div class="w-100">
                                                <div class="border-dashed rounded border-theme-color p-2 me-2 flex-grow-1 flex-basis-0 text-center">
                                                    <img src="https://static.vecteezy.com/system/resources/previews/014/213/663/non_2x/penguin-nft-artwork-vector.jpg" alt="" height="120" class="rounded-circle">
                                                    <h5 class="fw-semibold fs-22 mb-1 mt-2"><?= htmlspecialchars($data['mobile']); ?></h5>
                                                    <p class="text-muted mb-1">ID: <?= $data['id']; ?></p>
                                                    <h5 class="fw-semibold fs-20 mb-1"><?= $view_status; ?></h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <style>
                                        @media screen and (max-width: 600px) {
                                            .dash {
                                                flex-wrap: wrap;
                                            }
                                        }
                                    </style>

                                    <div class="col-lg-5 ms-auto align-self-center">
                                        <div class="d-flex dash justify-content-center">
                                            <div
                                                class="border-dashed rounded border-theme-color p-2 me-2 flex-grow-1 flex-basis-0">
                                                <h5 class="fw-semibold fs-22 mb-1">
                                                    R$<?= Reais2(total_dep_pagos_id($data['id'])); ?>
                                                </h5>
                                                <p class="text-muted mb-0 fw-medium">Dep. total</p>
                                            </div>
                                            <div
                                                class="border-dashed rounded border-theme-color p-2 me-2 flex-grow-1 flex-basis-0">
                                                <h5 class="fw-semibold fs-22 mb-1">
                                                    R$<?= Reais2(total_saques_id($data['id'])); ?>
                                                </h5>
                                                <p class="text-muted mb-0 fw-medium">Saques total</p>
                                            </div>
                                            <div
                                                class="border-dashed rounded border-theme-color p-2 me-2 flex-grow-1 flex-basis-0">
                                                <h5 class="fw-semibold fs-22 mb-1">
                                                    R$<?= Reais2($saldo_user['saldo']); ?>
                                                </h5>
                                                <p class="text-muted mb-0 fw-medium">Saldo Atual</p>
                                            </div>
                                            <div
                                                class="border-dashed rounded border-theme-color p-2 me-2 flex-grow-1 flex-basis-0">
                                                <h5 class="fw-semibold fs-22 mb-1">
                                                    <?= buscar_afiliador($data['invitation_code']); ?>
                                                </h5>
                                                <p class="text-muted mb-0 fw-medium">Convidado por</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6 col-sm-12 align-self-center mt-3 mt-lg-0">
                                        <div
                                            class="d-grid gap-2 d-md-flex justify-content-md-end justify-content-sm-center">
                                            <?php if ($data['banido'] == 1): ?>
                                                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#desbanirUsuarioModal">
                                                    Desbanir Usuário
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#banirUsuarioModal">
                                                    Banir Usuário
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                                data-bs-target="#editarSaldoModal">Editar Saldo</button>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="banirUsuarioModal" tabindex="-1"
                                        aria-labelledby="banirUsuarioLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="banirUsuarioLabel">Confirmar Banimento
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Você tem certeza que deseja banir este usuário?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="button" class="btn btn-danger"
                                                        id="confirmarBanimento">Sim, tenho certeza</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="desbanirUsuarioModal" tabindex="-1"
                                        aria-labelledby="desbanirUsuarioLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="desbanirUsuarioLabel">Confirmar
                                                        Desbanimento</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Você tem certeza que deseja desbanir este usuário?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="button" class="btn btn-success"
                                                        id="confirmarDesbanimento">Sim, tenho certeza</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal fade" id="editarSaldoModal" tabindex="-1"
                                        aria-labelledby="editarSaldoLabel" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editarSaldoLabel">Editar Saldo</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="saldoAtual" class="form-label">Saldo Atual</label>
                                                        <input type="text" class="form-control" id="saldoAtual"
                                                            value="R$<?= Reais2($saldo_user['saldo']); ?>" disabled>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="adicionarSaldo" class="form-label">Adicionar
                                                            Saldo</label>
                                                        <input type="number" class="form-control" id="adicionarSaldo"
                                                            placeholder="Insira o valor para adicionar" step="0.01">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="removerSaldo" class="form-label">Remover
                                                            Saldo</label>
                                                        <input type="number" class="form-control" id="removerSaldo"
                                                            placeholder="Insira o valor para remover" step="0.01">
                                                    </div>

                                                    <div class="mt-3">
                                                        <h6>Saldo Final Estimado: <span
                                                                id="saldoFinal">R$<?= Reais2($saldo_user['saldo']); ?></span>
                                                        </h6>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary"
                                                        data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="button" class="btn btn-primary"
                                                        id="confirmarEdicaoSaldo">Salvar Alterações</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <ul class="nav nav-tabs mb-3" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link fw-medium active" data-bs-toggle="tab" href="#afiliado" role="tab"
                                    aria-selected="true">Informações <?= $data['statusaff'] == 1 ? 'de afiliado' : 'do usuário'; ?></a>
                            </li>
                            <?php if ($data['statusaff'] == 1): ?>
                            <li class="nav-item">
                                <a class="nav-link fw-medium" data-bs-toggle="tab" href="#usersafiliados" role="tab"
                                    aria-selected="false">Indicados</a>
                            </li>
                            <?php endif; ?>
                            <li class="nav-item">
                                <a class="nav-link fw-medium" data-bs-toggle="tab" href="#contasrecebimento" role="tab"
                                    aria-selected="false">Contas De Recebimento</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-medium" data-bs-toggle="tab" href="#depositos" role="tab"
                                    aria-selected="false">Registros de depósitos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-medium" data-bs-toggle="tab" href="#saques" role="tab"
                                    aria-selected="false">Registros de retiradas</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-medium" data-bs-toggle="tab" href="#editar" role="tab"
                                    aria-selected="false">Editar usuario</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane p-3" id="editar" role="tabpanel">
                                <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="real_name" class="form-label">Nome de usuario</label>
                                        <input type="text" class="form-control" name="real_name" id="real_name"
                                            value="<?= htmlspecialchars($data['mobile']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="token" class="form-label">Token</label>
                                        <input type="text" class="form-control" name="token" id="token"
                                                value="<?= htmlspecialchars($data['token']); ?>" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Senha</label>
                                        <input type="text" class="form-control" name="password" id="password"
                                                value="<?= htmlspecialchars($data['password']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="invite" class="form-label">Código De Convite</label>
                                        <input type="text" class="form-control" name="invite" id="invite"
                                                value="<?= htmlspecialchars($data['invite_code']); ?>" readonly>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="senhaparasacar" class="form-label">Senha De Saque</label>
                                        <input type="text" class="form-control" name="senhaparasacar"
                                                id="senhaparasacar" placeholder="Senha Para Sacar"
                                                value="<?= htmlspecialchars($data['senhaparasacar']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="statusaff" class="form-label">Status de Afiliado</label>
                                        <select id="statusaff" name="statusaff" class="form-select">
                                            <option value="1" <?= $data['statusaff'] == 1 ? 'selected' : '' ?>>Ativo</option>
                                            <option value="0" <?= $data['statusaff'] == 0 ? 'selected' : '' ?>>Inativo</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="freeze" class="form-label">Congelar saldo</label>
                                        <select id="freeze" name="freeze" class="form-select">
                                            <option value="0" <?= isset($data['freeze']) && $data['freeze'] == 0 ? 'selected' : '' ?>>Desativado</option>
                                            <option value="1" <?= isset($data['freeze']) && $data['freeze'] == 1 ? 'selected' : '' ?>>Ativado</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" name="update_user" class="btn btn-primary">Atualizar
                                                Usuário</button>
                                    </div>
                                </div>
                            </form>

                            <!-- Configurações de CPA (Individual) + Métricas -->
                            <hr class="my-4">
                                <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h5 class="card-title mb-0">
                                        <i class="iconoir-medal"></i> CPA do Afiliado (Individual)
                                    </h5>
                                    <span class="badge bg-info" style="font-size:10px;">
                                        Chance CPA: <?= htmlspecialchars($afiliadosConfigGlobal['chanceCpa']) ?>% • Dep. mín.: R$<?= htmlspecialchars($afiliadosConfigGlobal['minDepForCpa']) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="update_cpa_individual" value="1">

                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">CPA Nível 1 (%)</label>
                                                <input type="number" step="0.01" class="form-control" name="cpaLvl1_ind"
                                                    value="<?= htmlspecialchars($cpa_individual['cpaLvl1']) ?>">
                                                <small class="text-muted">
                                                    Efetivo: <?= $cpa_efetivo[1] ?>% (<?= $cpa_origem[1] ?>) • Global: <?= htmlspecialchars($afiliadosConfigGlobal['cpaLvl1']) ?>%
                                                </small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">CPA Nível 2 (%)</label>
                                                <input type="number" step="0.01" class="form-control" name="cpaLvl2_ind"
                                                    value="<?= htmlspecialchars($cpa_individual['cpaLvl2']) ?>">
                                                <small class="text-muted">
                                                    Efetivo: <?= $cpa_efetivo[2] ?>% (<?= $cpa_origem[2] ?>) • Global: <?= htmlspecialchars($afiliadosConfigGlobal['cpaLvl2']) ?>%
                                                </small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">CPA Nível 3 (%)</label>
                                                <input type="number" step="0.01" class="form-control" name="cpaLvl3_ind"
                                                    value="<?= htmlspecialchars($cpa_individual['cpaLvl3']) ?>">
                                                <small class="text-muted">
                                                    Efetivo: <?= $cpa_efetivo[3] ?>% (<?= $cpa_origem[3] ?>) • Global: <?= htmlspecialchars($afiliadosConfigGlobal['cpaLvl3']) ?>%
                                                </small>
                                            </div>
                                        </div>

                                        <div class="alert alert-secondary">
                                            Dica: defina 0 para usar o valor global. Valores > 0 ativam configuração Individual por nível.
                                        </div>

                                        <div class="mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                Salvar CPA Individual
                                            </button>
                                        </div>
                                    </form>

                                    <hr class="my-4">

                                    <h6 class="mb-3">Métricas de Comissões CPA</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="border-dashed rounded p-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-medium">Nível 1</span>
                                                    <span class="badge bg-<?= ($cpa_origem[1] === 'Individual') ? 'success' : 'secondary' ?>"><?= $cpa_origem[1] ?></span>
                                                </div>
                                                <div class="mt-2">
                                                    <div>Comissões: <strong><?= $metrics_cpa[1]['qtd'] ?></strong></div>
                                                    <div>Total: <strong>R$<?= Reais2($metrics_cpa[1]['soma_reais']) ?></strong></div>
                                                    <div>Percentual efetivo: <strong><?= $cpa_efetivo[1] ?>%</strong></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="border-dashed rounded p-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-medium">Nível 2</span>
                                                    <span class="badge bg-<?= ($cpa_origem[2] === 'Individual') ? 'success' : 'secondary' ?>"><?= $cpa_origem[2] ?></span>
                                                </div>
                                                <div class="mt-2">
                                                    <div>Comissões: <strong><?= $metrics_cpa[2]['qtd'] ?></strong></div>
                                                    <div>Total: <strong>R$<?= Reais2($metrics_cpa[2]['soma_reais']) ?></strong></div>
                                                    <div>Percentual efetivo: <strong><?= $cpa_efetivo[2] ?>%</strong></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="border-dashed rounded p-3">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-medium">Nível 3</span>
                                                    <span class="badge bg-<?= ($cpa_origem[3] === 'Individual') ? 'success' : 'secondary' ?>"><?= $cpa_origem[3] ?></span>
                                                </div>
                                                <div class="mt-2">
                                                    <div>Comissões: <strong><?= $metrics_cpa[3]['qtd'] ?></strong></div>
                                                    <div>Total: <strong>R$<?= Reais2($metrics_cpa[3]['soma_reais']) ?></strong></div>
                                                    <div>Percentual efetivo: <strong><?= $cpa_efetivo[3] ?>%</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div class="tab-content">
                            <div class="tab-pane active" id="afiliado" role="tabpanel">
                                <div class="row">
                                    <?php if ($data['statusaff'] == 1): ?>
                                    <div class="col-lg-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Referências Diretas</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            <?= count_refer_direto($data['invite_code']); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-eye fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Depositantes</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            <?= numero_total_dep($data['invite_code']); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-piggy-bank fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Saldo de Afiliado</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            R$<?= Reais2($saldo_user['saldo_afiliado']); ?></h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-dollar-circle fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <?php
                                    $config_query = "SELECT cpaLvl1, cpaLvl2, cpaLvl3 FROM afiliados_config WHERE id = 1";
                                    $config_result = mysqli_query($mysqli, $config_query);
                                    $config_cpa = mysqli_fetch_assoc($config_result);
                                    
                                    function getTotalComissaoNivel($user_id, $nivel) {
                                        global $mysqli;
                                        $tipo = "comissao_cpa_nivel_{$nivel}";
                                        $query = "SELECT SUM(valor) as total FROM adicao_saldo WHERE id_user = ? AND tipo = ?";
                                        $stmt = $mysqli->prepare($query);
                                        $stmt->bind_param("is", $user_id, $tipo);
                                        $stmt->execute();
                                        $result = $stmt->get_result();
                                        $data = $result->fetch_assoc();
                                        return ($data['total'] ?? 0) / 100;
                                    }
                                    
                                    $comissao_n1 = getTotalComissaoNivel($data['id'], 1);
                                    $comissao_n2 = getTotalComissaoNivel($data['id'], 2);
                                    $comissao_n3 = getTotalComissaoNivel($data['id'], 3);
                                    ?>
                        
                                    <div class="col-12">
                                        <hr class="my-3">
                                        <h5 class="text-center mb-3 fw-bold">Comissões por Nível (CPA)</h5>
                                    </div>
                        
                                    <div class="col-lg-4">
                                        <div class="card border-success">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-center align-items-center thumb-xl bg-success-subtle rounded-circle mx-auto">
                                                        <i class="iconoir-medal fs-30 align-self-center text-success"></i>
                                                    </div>
                                                </div>
                                                <h5 class="fw-semibold text-success">Comissão N1</h5>
                                                <p class="text-muted mb-2">Indicados Diretos</p>
                                                <div class="bg-light rounded p-3 mb-2">
                                                    <h6 class="text-muted mb-1">Percentual</h6>
                                                    <h4 class="fw-bold text-success mb-0"><?= number_format($config_cpa['cpaLvl1'], 1); ?>%</h4>
                                                </div>
                                                <div class="bg-success-subtle rounded p-3">
                                                    <h6 class="text-muted mb-1">Total Ganho</h6>
                                                    <h4 class="fw-bold text-success mb-0">R$ <?= number_format($comissao_n1, 2, ',', '.'); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-4">
                                        <div class="card border-primary">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-center align-items-center thumb-xl bg-primary-subtle rounded-circle mx-auto">
                                                        <i class="iconoir-medal fs-30 align-self-center text-primary"></i>
                                                    </div>
                                                </div>
                                                <h5 class="fw-semibold text-primary">Comissão N2</h5>
                                                <p class="text-muted mb-2">Indicados de N1</p>
                                                <div class="bg-light rounded p-3 mb-2">
                                                    <h6 class="text-muted mb-1">Percentual</h6>
                                                    <h4 class="fw-bold text-primary mb-0"><?= number_format($config_cpa['cpaLvl2'], 1); ?>%</h4>
                                                </div>
                                                <div class="bg-primary-subtle rounded p-3">
                                                    <h6 class="text-muted mb-1">Total Ganho</h6>
                                                    <h4 class="fw-bold text-primary mb-0">R$ <?= number_format($comissao_n2, 2, ',', '.'); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-4">
                                        <div class="card border-warning">
                                            <div class="card-body text-center">
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-center align-items-center thumb-xl bg-warning-subtle rounded-circle mx-auto">
                                                        <i class="iconoir-medal fs-30 align-self-center text-warning"></i>
                                                    </div>
                                                </div>
                                                <h5 class="fw-semibold text-warning">Comissão N3</h5>
                                                <p class="text-muted mb-2">Indicados de N2</p>
                                                <div class="bg-light rounded p-3 mb-2">
                                                    <h6 class="text-muted mb-1">Percentual</h6>
                                                    <h4 class="fw-bold text-warning mb-0"><?= number_format($config_cpa['cpaLvl3'], 1); ?>%</h4>
                                                </div>
                                                <div class="bg-warning-subtle rounded p-3">
                                                    <h6 class="text-muted mb-1">Total Ganho</h6>
                                                    <h4 class="fw-bold text-warning mb-0">R$ <?= number_format($comissao_n3, 2, ',', '.'); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-12">
                                        <hr class="my-3">
                                        <h5 class="text-center mb-3 fw-bold">Outras Estatísticas</h5>
                                    </div>
                        
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Depósitos dos Indicados</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            R$<?= Reais2(total_dep_afiliado($data['invite_code'])); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-send-dollars fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Convidado Por</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            <?= buscar_afiliador($data['invitation_code']); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-group fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <?php else: ?>
                                    <div class="col-lg-12">
                                        <div class="alert alert-info text-center" role="alert">
                                            <i class="iconoir-info-circle fs-20"></i>
                                            <strong>Este usuário não é um afiliado.</strong>
                                            <p class="mb-0 mt-2">Para visualizar estatísticas de afiliação, é necessário que o status de afiliado esteja ativo.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Saldo Atual</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            R$<?= Reais2($saldo_user['saldo']); ?></h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-wallet fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Total Depositado</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            R$<?= Reais2(total_dep_pagos_id($data['id'])); ?></h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-piggy-bank fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Total Sacado</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            R$<?= Reais2(total_saques_id($data['id'])); ?></h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-send-dollars fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                        
                                    <div class="col-lg-6">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row d-flex justify-content-center">
                                                    <div class="col">
                                                        <p class="text-dark mb-1 fw-semibold">Convidado Por</p>
                                                        <h3 class="my-2 fs-24 fw-bold">
                                                            <?= buscar_afiliador($data['invitation_code']); ?>
                                                        </h3>
                                                    </div>
                                                    <div class="col-auto align-self-center">
                                                        <div class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                            <i class="iconoir-group fs-30 align-self-center text-muted"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($data['statusaff'] == 1): ?>
                            <div class="tab-content">
                                <div class="tab-pane p-3" id="usersafiliados" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table mb-0 table-centered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Depositou</th>
                                                    <th>Data de Cadastro</th>
                                                    <th>Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $id_user = decodeAll($_REQUEST['slug']);

                                                $query_codigo_afiliado = "SELECT invite_code FROM usuarios WHERE id = '$id_user'";
                                                $result_codigo_afiliado = mysqli_query($mysqli, $query_codigo_afiliado);

                                                if ($result_codigo_afiliado && mysqli_num_rows($result_codigo_afiliado) > 0) {
                                                    $usuario_atual = mysqli_fetch_assoc($result_codigo_afiliado);
                                                    $affiliate_invite_code = $usuario_atual['invite_code'];

                                                    $query_usuarios_afiliado = "
                                                        SELECT u.*, 
                                                            (SELECT t.valor 
                                                            FROM transacoes t 
                                                            WHERE t.usuario = u.id AND t.status = 'pago' 
                                                            ORDER BY t.id ASC 
                                                            LIMIT 1) AS primeiro_deposito 
                                                        FROM usuarios u 
                                                        WHERE u.invitation_code = '$affiliate_invite_code' 
                                                        ORDER BY u.id DESC";

                                                    $result_usuarios_afiliado = mysqli_query($mysqli, $query_usuarios_afiliado);

                                                    if (!$result_usuarios_afiliado) {
                                                        echo "<tr><td colspan='5' class='text-center'>Erro na consulta: " . mysqli_error($mysqli) . "</td></tr>";
                                                    } else {
                                                        if (mysqli_num_rows($result_usuarios_afiliado) > 0) {
                                                            while ($usuario_afiliado = mysqli_fetch_assoc($result_usuarios_afiliado)) {
                                                ?>
                                                                <tr>
                                                                    <td><?= $usuario_afiliado['id']; ?></td>
                                                                    <td><?= htmlspecialchars($usuario_afiliado['mobile']); ?></td>
                                                                    <td><?= $usuario_afiliado['primeiro_deposito'] ? 'R$ ' . number_format($usuario_afiliado['primeiro_deposito'], 2, ',', '.') : 'Nenhum depósito'; ?></td>
                                                                    <td><?= $usuario_afiliado['data_registro']; ?></td>
                                                                    <td>
                                                                        <button class="btn btn-danger btn-sm" onclick="excluirIndicado(<?= $usuario_afiliado['id']; ?>)">Excluir</button>
                                                                    </td>
                                                                </tr>
                                                <?php
                                                            }
                                                        } else {
                                                            echo "<tr><td colspan='5' class='text-center'>Nenhum usuário afiliado encontrado!</td></tr>";
                                                        }
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5' class='text-center'>Erro: Não foi possível encontrar o código de convite do usuário.</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="tab-content">
                                <div class="tab-pane p-3" id="contasrecebimento" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table mb-0 table-centered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nome</th>
                                                    <th>Tipo</th>
                                                    <th>Chave</th>
                                                    <th>Data de Cadastro</th>
                                                    <th>Ação</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $id_user = decodeAll($_REQUEST['slug']);

                                                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                                    if (isset($_POST['edit_chave'])) {
                                                        $metodo_id = $_POST['id'];
                                                        $nova_chave = $_POST['chave'];
                                                        $update_query = "UPDATE metodos_pagamentos SET chave = ? WHERE id = ?";
                                                        $stmt = $mysqli->prepare($update_query);
                                                        $stmt->bind_param("si", $nova_chave, $metodo_id);
                                                        $stmt->execute();
                                                    } elseif (isset($_POST['delete_metodo'])) {
                                                        $metodo_id = $_POST['id'];

                                                        $delete_query = "DELETE FROM metodos_pagamentos WHERE id = ?";
                                                        $stmt = $mysqli->prepare($delete_query);
                                                        $stmt->bind_param("i", $metodo_id);
                                                        $stmt->execute();
                                                    }
                                                }

                                                $query_metodos_pagamento = "SELECT * FROM metodos_pagamentos WHERE user_id = '$id_user'";
                                                $result_metodos_pagamento = mysqli_query($mysqli, $query_metodos_pagamento);

                                                if (!$result_metodos_pagamento) {
                                                    echo "<tr><td colspan='6' class='text-center'>Erro na consulta: " . mysqli_error($mysqli) . "</td></tr>";
                                                } else {
                                                    if (mysqli_num_rows($result_metodos_pagamento) > 0) {
                                                        while ($metodo = mysqli_fetch_assoc($result_metodos_pagamento)) {
                                                ?>
                                                            <tr>
                                                                <td><?= $metodo['id']; ?></td>
                                                                <td><?= htmlspecialchars($metodo['realname']); ?></td>
                                                                <td><?= htmlspecialchars($metodo['tipo']); ?></td>
                                                                <td>
                                                                    <input type="text" id="chave_<?= $metodo['id']; ?>" value="<?= htmlspecialchars($metodo['chave']); ?>" class="form-control form-control-sm" />
                                                                </td>
                                                                <td><?= $metodo['created_at']; ?></td>
                                                                <td>
                                                                    <button class="btn btn-primary btn-sm" onclick="editarChave(<?= $metodo['id']; ?>)">Editar</button>

                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="id" value="<?= $metodo['id']; ?>" />
                                                                        <input type="hidden" name="delete_metodo" value="1" />
                                                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja excluir este método de pagamento?')">Excluir</button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                <?php
                                                        }
                                                    } else {
                                                        echo "<tr><td colspan='6' class='text-center'>Nenhum método de pagamento encontrado!</td></tr>";
                                                    }
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <script>
                                function editarChave(id) {
                                    var chave = document.getElementById('chave_' + id).value;

                                    if (chave) {
                                        var form = document.createElement('form');
                                        form.method = 'POST';
                                        form.style.display = 'none';
                                        var inputId = document.createElement('input');
                                        inputId.type = 'hidden';
                                        inputId.name = 'id';
                                        inputId.value = id;
                                        form.appendChild(inputId);

                                        var inputChave = document.createElement('input');
                                        inputChave.type = 'hidden';
                                        inputChave.name = 'chave';
                                        inputChave.value = chave;
                                        form.appendChild(inputChave);

                                        var inputEditChave = document.createElement('input');
                                        inputEditChave.type = 'hidden';
                                        inputEditChave.name = 'edit_chave';
                                        inputEditChave.value = '1';
                                        form.appendChild(inputEditChave);

                                        document.body.appendChild(form);
                                        form.submit();
                                    } else {
                                        alert("Digite uma chave válida.");
                                    }
                                }
                            </script>

                            <div class="tab-content">
                                <div class="tab-pane p-3" id="depositos" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table  mb-0 table-centered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Id</th>
                                                    <th>Transação ID</th>
                                                    <th>Valor</th>
                                                    <th>Data/Hora</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query_depositos = "SELECT * FROM transacoes WHERE usuario = '" . intval($id_user) . "' ORDER BY id DESC";
                                                $result_depositos = mysqli_query($mysqli, $query_depositos);

                                                if ($result_depositos && mysqli_num_rows($result_depositos) > 0) {
                                                    while ($deposito = mysqli_fetch_assoc($result_depositos)) {
                                                ?>
                                                        <tr>
                                                            <td><?= $deposito['id']; ?></td>
                                                            <td><?= htmlspecialchars($deposito['transacao_id']); ?></td>
                                                            <td>R$ <?= number_format($deposito['valor'], 2, ',', '.'); ?></td>
                                                            <td><?= $deposito['data_registro']; ?></td>
                                                            <td><?= getStatusBadge($deposito['status']); ?></td>
                                                        </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='5' class='text-center'>Sem depósitos disponíveis!</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="tab-pane p-3" id="saques" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table  mb-0 table-centered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Id</th>
                                                    <th>Valor</th>
                                                    <th>Data/Hora</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $query_saques = "SELECT * FROM solicitacao_saques WHERE id_user = '" . intval($id_user) . "' ORDER BY id DESC";
                                                $result_saques = mysqli_query($mysqli, $query_saques);

                                                if ($result_saques && mysqli_num_rows($result_saques) > 0) {
                                                    while ($saque = mysqli_fetch_assoc($result_saques)) {
                                                        $status_saque = $saque['status'] == 1 ? "Aprovado" : "Em Análise";
                                                ?>
                                                        <tr>
                                                            <td><?= $saque['id']; ?></td>
                                                            <td>R$ <?= number_format($saque['valor'], 2, ',', '.'); ?></td>
                                                            <td><?= $saque['data_registro']; ?></td>
                                                            <td><span
                                                                    class="badge bg-<?= $saque['status'] == 1 ? 'success' : 'warning' ?>"><?= $status_saque; ?></span>
                                                            </td>
                                                        </tr>
                                                <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4' class='text-center'>Sem registros de saques disponíveis!</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

        <?php include 'partials/vendorjs.php' ?>
        <script src="assets/js/app.js"></script>

        <script>
            function showToast(type, message) {
                if (typeof bootstrap === 'undefined') {
                    console.error('Bootstrap is not defined! Verifique se o bootstrap.bundle.min.js foi carregado corretamente.');
                    return;
                }
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

                setTimeout(function() {
                    bootstrapToast.hide();
                    setTimeout(() => toast.remove(), 500);
                }, 3000);

                setTimeout(function() {
                    window.location.href = window.location.href.split("?")[0] + "?reload=" +
                        encodeURIComponent(new URLSearchParams(window.location.search).get('reload'));
                }, 3000);
            }
        </script>

        <script>
            function excluirIndicado(usuarioId) {
                fetch('fetch/excluir_indicado.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            usuario_id: usuarioId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Usuário excluído com sucesso.');
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        } else {
                            showToast('danger', 'Erro ao excluir usuário: ' + data.message);
                        }
                    })
                    .catch((error) => {
                        showToast('danger', 'Erro ao excluir usuário: ' + error);
                    });
            }
        </script>

        <script>
            document.getElementById('confirmarBanimento').addEventListener('click', function() {
                modificarUsuario(<?= $id_user; ?>, 'banir');
            });

            document.getElementById('confirmarDesbanimento').addEventListener('click', function() {
                modificarUsuario(<?= $id_user; ?>, 'desbanir');
            });

            function modificarUsuario(userId, action) {
                fetch('fetch/banir_usuario.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            action: action
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', data.message);
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        } else {
                            showToast('danger', 'Erro: ' + data.message);
                        }
                    })
                    .catch(error => {
                        showToast('danger', 'Erro ao processar a solicitação.');
                    });
            }
        </script>

        <script>
            document.getElementById('adicionarSaldo').addEventListener('input', atualizarSaldoFinal);
            document.getElementById('removerSaldo').addEventListener('input', atualizarSaldoFinal);

            function atualizarSaldoFinal() {
                var saldoAtual = parseFloat(<?= $saldo_user['saldo']; ?>);
                var adicionarSaldo = parseFloat(document.getElementById('adicionarSaldo').value) || 0;
                var removerSaldo = parseFloat(document.getElementById('removerSaldo').value) || 0;

                var saldoFinal = saldoAtual + adicionarSaldo - removerSaldo;

                document.getElementById('saldoFinal').textContent = 'R$' + saldoFinal.toFixed(2).replace('.', ',');
            }

            document.getElementById('confirmarEdicaoSaldo').addEventListener('click', function() {
                var adicionarSaldo = parseFloat(document.getElementById('adicionarSaldo').value) || 0;
                var removerSaldo = parseFloat(document.getElementById('removerSaldo').value) || 0;
                
                editarSaldoUsuario(<?= $id_user; ?>, adicionarSaldo, removerSaldo); // ✅ Usando o ID correto
            });

            function editarSaldoUsuario(userId, adicionar, remover) {
                fetch('fetch/editar_saldo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: userId,
                            adicionar: adicionar,
                            remover: remover
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', 'Saldo atualizado com sucesso!');
                            setTimeout(() => {
                                location.reload();
                            }, 3000);
                        } else {
                            showToast('danger', 'Erro: ' + data.message);
                        }
                    })
                    .catch(error => {
                        showToast('danger', 'Erro ao atualizar o saldo.');
                    });
            }
        </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
            });
        </script>
    <?php endif; ?>
</body>
</html>
