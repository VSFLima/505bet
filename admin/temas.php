<?php include 'partials/html.php' ?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
session_start();
include_once dirname(__DIR__) . "/config.php";
include_once "services/database.php";
//include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
include_once "validar_2fa.php";
$csrf = new CSRF_Protect();

checa_login_adm();

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// Definição dos Layouts e Temas disponíveis
$layouts_disponiveis = [
    'Layout2' => ["MidnightPurple", "MystLightBlue", "PhantomBlue"]
];

// Função para buscar o template ativo
function get_template_ativo() {
    global $mysqli;
    $query = "SELECT * FROM temas ORDER BY id DESC LIMIT 1";
    $result = $mysqli->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return [
            'id' => $row['id'],
            'nome_template' => 'Padrão',
            'layout' => $row['nome_cor'],
            'theme' => $row['valor_cor']
        ];
    }
    return [
        'id' => null,
        'nome_template' => 'Padrão',
        'layout' => 'Layout2',
        'theme' => 'MidnightPurple'
    ];
}

// Função para salvar o template
function salvar_template($layout, $theme, $nome_template) {
    global $mysqli;
    
    // Verificar se já existe um registro na tabela temas
    $check = $mysqli->query("SELECT id FROM temas LIMIT 1");
    if ($check && $row = $check->fetch_assoc()) {
        $id = $row['id'];
        $stmt = $mysqli->prepare("UPDATE temas SET nome_cor = ?, valor_cor = ? WHERE id = ?");
        $stmt->bind_param("ssi", $layout, $theme, $id);
        return $stmt->execute();
    } else {
        $stmt = $mysqli->prepare("INSERT INTO temas (nome_cor, valor_cor) VALUES (?, ?)");
        $stmt->bind_param("ss", $layout, $theme);
        return $stmt->execute();
    }
}

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['layout']) && isset($_POST['theme'])) {
        $layout = $_POST['layout'];
        $theme = $_POST['theme'];
        $nome_template = $_POST['nome_template'] ?? 'Template ' . date('d/m/Y H:i');
        
        if (salvar_template($layout, $theme, $nome_template)) {
            $toastType = 'success';
            $toastMessage = admin_t('toast_config_updated');
        } else {
            $toastType = 'error';
            $toastMessage = admin_t('toast_config_error');
        }
    }
    if (isset($_POST['userRankSwitch'])) {
        $userRankSwitch = (int)$_POST['userRankSwitch'];
        $stmtConfig = $mysqli->prepare("UPDATE config SET userRankSwitch = ? LIMIT 1");
        if ($stmtConfig) {
            $stmtConfig->bind_param("i", $userRankSwitch);
            $stmtConfig->execute();
            $stmtConfig->close();
        }
    }
}

$configRank = ['userRankSwitch' => 0];
$resultConfig = $mysqli->query("SELECT userRankSwitch FROM config LIMIT 1");
if ($resultConfig && $rowConfig = $resultConfig->fetch_assoc()) {
    $configRank['userRankSwitch'] = (int)($rowConfig['userRankSwitch'] ?? 0);
}

$template_ativo = get_template_ativo();
$rankingAtivo = !empty($configRank['userRankSwitch']);
$rankingTexto = $rankingAtivo ? admin_t('status_active') : admin_t('status_inactive');

?>
<head>
    <?php $title = admin_t('page_themes_title'); ?>
    <?php include 'partials/title-meta.php'; ?>
    <base href="/<?= defined('DASH') ? DASH : 'admin'; ?>/">
    <?php include 'partials/head-css.php'; ?>
</head>

<body>
    <?php include 'partials/topbar.php'; ?>
    <?php include 'partials/startbar.php'; ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title"><?= admin_t('page_themes_title') ?></h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="" id="temaForm">
                                    <input type="hidden" name="layout" id="layoutInput" value="<?= htmlspecialchars($template_ativo['layout']) ?>">
                                    <input type="hidden" name="theme" id="themeInput" value="<?= htmlspecialchars($template_ativo['theme']) ?>">
                                    <input type="hidden" name="nome_template" value="<?= htmlspecialchars($template_ativo['nome_template']) ?>">
                                    <input type="hidden" name="userRankSwitch" id="userRankSwitchInput" value="<?= (int)($configRank['userRankSwitch'] ?? 0) ?>">
                                </form>

                                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                    <i class="fas fa-check-circle me-2" style="font-size:20px;"></i>
                                    <div>
                                        Tema ativo: <strong><?= htmlspecialchars($template_ativo['layout']) ?> — <?= htmlspecialchars($template_ativo['theme']) ?></strong><br>
                                        Ranking de Apostas: 
                                        <?php if ($rankingAtivo): ?>
                                            <span class="badge bg-success"><?= $rankingTexto ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= $rankingTexto ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="userRankSwitchToggle" <?= !empty($configRank['userRankSwitch']) ? 'checked' : '' ?> onchange="toggleUserRankSwitch(this)">
                                        <label class="form-check-label" for="userRankSwitchToggle"><?= admin_t('themes_rank_toggle_label') ?></label>
                                    </div>
                                </div>

                                <ul class="nav nav-tabs mb-4" id="layoutTabs" role="tablist">
                                    <?php foreach ($layouts_disponiveis as $layoutName => $themes): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?= $template_ativo['layout'] == $layoutName ? 'active' : '' ?>" 
                                                id="tab-<?= $layoutName ?>" data-bs-toggle="tab" 
                                                data-bs-target="#content-<?= $layoutName ?>" type="button" role="tab">
                                                <?= $layoutName ?> <span class="badge bg-secondary"><?= count($themes) ?></span>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <div class="tab-content" id="layoutTabsContent">
                                    <?php foreach ($layouts_disponiveis as $layoutName => $themes): ?>
                                        <div class="tab-pane fade <?= $template_ativo['layout'] == $layoutName ? 'show active' : '' ?>" 
                                             id="content-<?= $layoutName ?>" role="tabpanel">
                                            <div class="row g-3">
                                                <?php foreach ($themes as $theme): 
                                                    $isAtivo = ($template_ativo['layout'] == $layoutName && $template_ativo['theme'] == $theme);
                                                    $imgFile = "/uploads/temas/{$layoutName}_{$theme}.png";
                                                    $imgExists = file_exists(dirname(__DIR__) . $imgFile);
                                                ?>
                                                    <div class="col-6 col-md-4 col-lg-3">
                                                        <div class="tema-card <?= $isAtivo ? 'ativo' : '' ?>" 
                                                             onclick="selecionarTema('<?= $layoutName ?>', '<?= $theme ?>')"
                                                             title="<?= $layoutName ?>: <?= $theme ?>">
                                                            
                                                            <?php if ($isAtivo): ?>
                                                                <div class="tema-badge-ativo">
                                                                    <i class="fas fa-check"></i> <?= strtoupper(admin_t('status_active')) ?>
                                                                </div>
                                                            <?php endif; ?>

                                                            <div class="tema-img-wrapper">
                                                                <?php if ($imgExists): ?>
                                                                    <img src="<?= $imgFile ?>" alt="<?= $theme ?>" class="tema-img" loading="lazy">
                                                                <?php else: ?>
                                                                    <div class="tema-img-placeholder">
                                                                        <i class="fas fa-image" style="font-size:40px; opacity:0.3;"></i>
                                                                        <small><?= admin_t('themes_no_preview') ?></small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="tema-nome"><?= $theme ?></div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include 'partials/endbar.php'; ?>
            <?php include 'partials/footer.php'; ?>
        </div>
    </div>

    <div id="toastPlacement" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <?php include 'partials/vendorjs.php'; ?>
    <script src="assets/js/app.js"></script>

    <script>
        function selecionarTema(layout, theme) {
            document.getElementById('layoutInput').value = layout;
            document.getElementById('themeInput').value = theme;
            document.getElementById('temaForm').submit();
        }

        function toggleUserRankSwitch(element) {
            var input = document.getElementById('userRankSwitchInput');
            if (!input) {
                return;
            }
            input.value = element.checked ? 1 : 0;
            document.getElementById('temaForm').submit();
        }

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

            setTimeout(function() {
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

    <style>
        .tema-card { border: 2px solid #dee2e6; border-radius: 12px; overflow: hidden; cursor: pointer; transition: all 0.25s ease; background: #fff; position: relative; }
        .tema-card:hover { border-color: #0d6efd; transform: translateY(-4px); box-shadow: 0 8px 25px rgba(13, 110, 253, 0.25); }
        .tema-card.ativo { border-color: #198754; box-shadow: 0 4px 15px rgba(25, 135, 84, 0.35); }
        .tema-badge-ativo { position: absolute; top: 8px; right: 8px; background: #198754; color: #fff; font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 20px; z-index: 2; letter-spacing: 0.5px; }
        .tema-img-wrapper { width: 100%; aspect-ratio: 430 / 932; overflow: hidden; background: #f0f0f0; }
        .tema-img { width: 100%; height: 100%; object-fit: cover; object-position: top; display: block; }
        .tema-img-placeholder { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #aaa; gap: 5px; }
        .tema-nome { text-align: center; padding: 8px 5px; font-weight: 600; font-size: 13px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tema-card.ativo .tema-nome { color: #198754; }
    </style>

</body>
</html>