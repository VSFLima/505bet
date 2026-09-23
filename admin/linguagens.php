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

if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

function ensure_language_columns()
{
    global $mysqli;
    $fields = [
        'language' => "ALTER TABLE config ADD language VARCHAR(10) DEFAULT 'pt-BR'",
        'phoneCode' => "ALTER TABLE config ADD phoneCode VARCHAR(16) DEFAULT '+55'",
        'currency' => "ALTER TABLE config ADD currency VARCHAR(10) DEFAULT 'BRL'",
        'timezone' => "ALTER TABLE config ADD timezone VARCHAR(64) DEFAULT 'Etc/GMT+3'",
        'regionName' => "ALTER TABLE config ADD regionName VARCHAR(64) DEFAULT 'Brasil'",
        'regionId' => "ALTER TABLE config ADD regionId INT DEFAULT 1"
    ];
    foreach ($fields as $name => $sql) {
        $check = $mysqli->query("SHOW COLUMNS FROM config LIKE '" . $name . "'");
        if ($check && $check->num_rows === 0) {
            $mysqli->query($sql);
        }
    }
}

function get_config_linguagens()
{
    global $mysqli;
    $qry = "SELECT * FROM config ORDER BY id ASC LIMIT 1";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result);
}

function update_config_linguagens($data)
{
    global $mysqli;
    $colsRes = $mysqli->query("SHOW COLUMNS FROM config");
    $available = [];
    if ($colsRes) {
        while ($row = $colsRes->fetch_assoc()) {
            $available[$row['Field']] = true;
        }
    }
    $fields = [];
    $params = [];
    $types = "";
    if (isset($available['language'])) {
        $fields[] = "language = ?";
        $types .= "s";
        $params[] = $data['language'];
    }
    if (isset($available['phoneCode'])) {
        $fields[] = "phoneCode = ?";
        $types .= "s";
        $params[] = $data['phoneCode'];
    }
    if (isset($available['currency'])) {
        $fields[] = "currency = ?";
        $types .= "s";
        $params[] = $data['currency'];
    }
    if (isset($available['timezone'])) {
        $fields[] = "timezone = ?";
        $types .= "s";
        $params[] = $data['timezone'];
    }
    if (isset($available['regionName'])) {
        $fields[] = "regionName = ?";
        $types .= "s";
        $params[] = $data['regionName'];
    }
    if (isset($available['regionId'])) {
        $fields[] = "regionId = ?";
        $types .= "i";
        $params[] = $data['regionId'];
    }
    if (empty($fields)) {
        return false;
    }
    $sql = "UPDATE config SET " . implode(", ", $fields) . " ORDER BY id ASC LIMIT 1";
    $qry = $mysqli->prepare($sql);
    if (!$qry) {
        return false;
    }
    $bindParams = [];
    $bindParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$qry, 'bind_param'], $bindParams);

    return $qry->execute();
}

ensure_language_columns();

$toastType = null;
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $language = isset($_POST['language']) ? trim($_POST['language']) : '';
    $phoneCode = isset($_POST['phoneCode']) ? trim($_POST['phoneCode']) : '';
    $currency = isset($_POST['currency']) ? trim($_POST['currency']) : '';
    $timezone = isset($_POST['timezone']) ? trim($_POST['timezone']) : '';
    $regionName = isset($_POST['regionName']) ? trim($_POST['regionName']) : '';
    $regionId = isset($_POST['regionId']) ? (int)$_POST['regionId'] : 1;

    if ($language === '') {
        $language = 'pt-BR';
    }
    if ($phoneCode === '') {
        $phoneCode = '+55';
    }
    if ($currency === '') {
        $currency = 'BRL';
    }
    if ($timezone === '') {
        $timezone = 'Etc/GMT+3';
    }
    if ($regionName === '') {
        $regionName = 'Brasil';
    }
    if ($regionId <= 0) {
        $regionId = 1;
    }

    $data = [
        'language' => $language,
        'phoneCode' => $phoneCode,
        'currency' => $currency,
        'timezone' => $timezone,
        'regionName' => $regionName,
        'regionId' => $regionId
    ];

    if (update_config_linguagens($data)) {
        $toastType = 'success';
        $toastMessage = admin_t('toast_config_updated');
    } else {
        $toastType = 'error';
        $toastMessage = admin_t('toast_config_error');
    }
}

$config = get_config_linguagens();

$language = isset($config['language']) && $config['language'] !== '' ? $config['language'] : 'pt-BR';
$phoneCode = isset($config['phoneCode']) && $config['phoneCode'] !== '' ? $config['phoneCode'] : '+55';
$currency = isset($config['currency']) && $config['currency'] !== '' ? $config['currency'] : 'BRL';
$timezone = isset($config['timezone']) && $config['timezone'] !== '' ? $config['timezone'] : 'Etc/GMT+3';
$regionName = isset($config['regionName']) && $config['regionName'] !== '' ? $config['regionName'] : 'Brasil';
$regionId = isset($config['regionId']) && (int)$config['regionId'] > 0 ? (int)$config['regionId'] : 1;

$languagesOptions = [
    'pt-BR' => 'Português (Brasil)',
    'en-US' => 'English (Estados Unidos)',
    'es-ES' => 'Español (España)',
    'hi-IN' => 'हिन्दी (Índia)',
    'id-ID' => 'Bahasa Indonesia',
    'vi-VN' => 'Tiếng Việt',
    'zh-CN' => '中文 (China)'
];
$languageData = [
    'pt-BR' => ['phoneCode' => '+55', 'currency' => 'BRL', 'timezone' => 'Etc/GMT+3', 'regionName' => 'Brasil'],
    'en-US' => ['phoneCode' => '+1', 'currency' => 'USD', 'timezone' => 'Etc/GMT+5', 'regionName' => 'United States'],
    'es-ES' => ['phoneCode' => '+34', 'currency' => 'EUR', 'timezone' => 'Etc/GMT-1', 'regionName' => 'España'],
    'hi-IN' => ['phoneCode' => '+91', 'currency' => 'INR', 'timezone' => 'Etc/GMT-5', 'regionName' => 'India'],
    'id-ID' => ['phoneCode' => '+62', 'currency' => 'IDR', 'timezone' => 'Etc/GMT-7', 'regionName' => 'Indonesia'],
    'vi-VN' => ['phoneCode' => '+84', 'currency' => 'VND', 'timezone' => 'Etc/GMT-7', 'regionName' => 'Việt Nam'],
    'zh-CN' => ['phoneCode' => '+86', 'currency' => 'CNY', 'timezone' => 'Etc/GMT-8', 'regionName' => '中国']
];
?>

<head>
    <?php $title = admin_t('page_languages_title');
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
                        <div class="card rounded-4 shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 pt-4 px-4">
                                <h4 class="card-title fw-bold mb-0"><?= admin_t('page_languages_title') ?></h4>
                                <p class="text-muted fs-13 mb-0"><?= admin_t('page_languages_subtitle') ?></p>
                            </div>

                            <div class="card-body p-4">
                                <form method="POST" action="">
                                    <div class="row g-4">
                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_default_language') ?></label>
                                                <select name="language" class="form-select" required>
                                                    <?php foreach ($languagesOptions as $key => $label): ?>
                                                        <option value="<?= $key ?>" <?= $language === $key ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_phone_code') ?></label>
                                                <select name="phoneCode" class="form-select" required>
                                                    <?php foreach ($languagesOptions as $key => $label): ?>
                                                        <?php $val = $languageData[$key]['phoneCode'] ?? ''; ?>
                                                        <option value="<?= htmlspecialchars($val) ?>" <?= $phoneCode === $val ? 'selected' : '' ?>><?= $label ?> — <?= htmlspecialchars($val) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_currency') ?></label>
                                                <select name="currency" class="form-select" required>
                                                    <?php foreach ($languagesOptions as $key => $label): ?>
                                                        <?php $val = $languageData[$key]['currency'] ?? ''; ?>
                                                        <option value="<?= htmlspecialchars($val) ?>" <?= $currency === $val ? 'selected' : '' ?>><?= $label ?> — <?= htmlspecialchars($val) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_timezone') ?></label>
                                                <select name="timezone" class="form-select" required>
                                                    <?php foreach ($languagesOptions as $key => $label): ?>
                                                        <?php $val = $languageData[$key]['timezone'] ?? ''; ?>
                                                        <option value="<?= htmlspecialchars($val) ?>" <?= $timezone === $val ? 'selected' : '' ?>><?= $label ?> — <?= htmlspecialchars($val) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_region_name') ?></label>
                                                <select name="regionName" class="form-select" required>
                                                    <?php foreach ($languagesOptions as $key => $label): ?>
                                                        <?php $val = $languageData[$key]['regionName'] ?? ''; ?>
                                                        <option value="<?= htmlspecialchars($val) ?>" <?= $regionName === $val ? 'selected' : '' ?>><?= $label ?> — <?= htmlspecialchars($val) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6 col-lg-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold"><?= admin_t('label_region_id') ?></label>
                                                <input type="number" name="regionId" class="form-control" value="<?= $regionId ?>" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-success"><?= admin_t('button_save_settings') ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include 'partials/endbar.php' ?>
                <?php include 'partials/footer.php' ?>
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
            toast.className = "toast align-items-center bg-light border-0 fade show";
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = '<div class="toast-header"><h5 class="me-auto my-0">Atualização</h5><small>Agora</small><button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button></div><div class="toast-body">' + message + '</div>';
            toastPlacement.appendChild(toast);

            var bootstrapToast = new bootstrap.Toast(toast);
            bootstrapToast.show();

            setTimeout(function () {
                bootstrapToast.hide();
                setTimeout(function () {
                    toast.remove();
                }, 500);
            }, 3000);
        }
    </script>
    <script>
        var langData = <?= json_encode($languageData) ?>;
        var langSelect = document.querySelector('select[name="language"]');
        var phoneSelect = document.querySelector('select[name="phoneCode"]');
        var currencySelect = document.querySelector('select[name="currency"]');
        var timezoneSelect = document.querySelector('select[name="timezone"]');
        var regionNameSelect = document.querySelector('select[name="regionName"]');
        function setFromLanguage(code) {
            var d = langData[code] || null;
            if (!d) return;
            for (var i = 0; i < phoneSelect.options.length; i++) if (phoneSelect.options[i].value === d.phoneCode) { phoneSelect.selectedIndex = i; break; }
            for (var i = 0; i < currencySelect.options.length; i++) if (currencySelect.options[i].value === d.currency) { currencySelect.selectedIndex = i; break; }
            for (var i = 0; i < timezoneSelect.options.length; i++) if (timezoneSelect.options[i].value === d.timezone) { timezoneSelect.selectedIndex = i; break; }
            for (var i = 0; i < regionNameSelect.options.length; i++) if (regionNameSelect.options[i].value === d.regionName) { regionNameSelect.selectedIndex = i; break; }
        }
        if (langSelect) {
            langSelect.addEventListener('change', function () { setFromLanguage(this.value); });
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
