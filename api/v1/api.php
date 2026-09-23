<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
$data = json_decode(file_get_contents("php://input"), true);
function sendApiError($code, $message)
{
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}
$rotaEncontrada = false;
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestURI = $_SERVER['REQUEST_URI'];
$path = parse_url($requestURI, PHP_URL_PATH);
// ── Cache de logos: serve o arquivo salvo SEM abrir conexao com o banco ──
if (strpos($path, '/api/frontend/game-logo/') === 0) {
    $relLogo = urldecode(preg_replace('#^/api/frontend/#', '', $path));
    if ($relLogo !== '' && strpos($relLogo, '..') === false) {
        $cacheLogo = __DIR__ . '/../frontend/' . $relLogo;
        if (is_file($cacheLogo)) {
            if (ob_get_length()) ob_clean();
            $ctLogo = 'image/jpeg';
            if (function_exists('finfo_open')) {
                $fiLogo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($fiLogo) { $mLogo = @finfo_file($fiLogo, $cacheLogo); @finfo_close($fiLogo); if ($mLogo) $ctLogo = $mLogo; }
            }
            header('Content-Type: ' . $ctLogo);
            header('Cache-Control: public, max-age=604800');
            readfile($cacheLogo);
            exit;
        }
    }
}
include_once "./../../config.php";
include_once "./../../" . DASH . "/services-prod/prod.php";
include_once "./../../" . DASH . "/services/database.php";
include_once "./../../" . DASH . "/services/funcao.php";

// ── Função helper: lê mina_config do banco ──────────────────────────────────
function getMinaConfig($mysqli) {
    $defaults = ['active'=>1,'daily_max_count'=>6,'reward_min'=>10000,'reward_max'=>20000,
                 'reward_recharge_min'=>0,'reward_recharge_max'=>200,'hours_json'=>null];
    $res = @$mysqli->query("SELECT * FROM mina_config WHERE id=1");
    if ($res && $row = $res->fetch_assoc()) return $row;
    return $defaults;
}
// ── Função helper: lê cadastro_bonus_config do banco ────────────────────────
function getBonusConfig($mysqli) {
    $defaults = ['active' => 0, 'valor_min' => 100, 'valor_max' => 9880];
    // Garante que a tabela exista
    @$mysqli->query("CREATE TABLE IF NOT EXISTS cadastro_bonus_config (
        id INT PRIMARY KEY DEFAULT 1,
        active TINYINT(1) NOT NULL DEFAULT 1,
        valor_min INT NOT NULL DEFAULT 100,
        valor_max INT NOT NULL DEFAULT 9880,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    @$mysqli->query("INSERT IGNORE INTO cadastro_bonus_config (id, active, valor_min, valor_max) VALUES (1, 0, 100, 9880)");
    $res = @$mysqli->query("SELECT active, valor_min, valor_max FROM cadastro_bonus_config WHERE id=1");
    if ($res && $row = $res->fetch_assoc()) return $row;
    return $defaults;
}
// Formata valor em centavos para string sem zeros desnecessários (ex: 9880 -> "98.8", 100 -> "1")
function formatBonusAmount($cents) {
    $val = $cents / 100;
    // Remove zeros à direita: 98.80 -> 98.8, 1.00 -> 1
    return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
}
// ────────────────────────────────────────────────────────────────────────────

function buildMinaTimeConfig($minaRow) {
    $defaultHours = [2,3,6,7,8,9,10,11,12,13,14,17,19,20,21,22,23,0];
    $uuids = [2=>"2617ff3a",3=>"c2cffcc3",6=>"3f435034",7=>"98df1968",8=>"e6a65d40",
              9=>"b069e48c",10=>"66790ca3",11=>"3e77b676",12=>"32000089",13=>"9c8677e6",
              14=>"5c88163c",17=>"aa3fb50f",19=>"8934bde7",20=>"82c8b009",21=>"2ae93c3b",
              22=>"caaeb3b1",23=>"80b61caf",0=>"fa6aad0d"];
    if (!empty($minaRow['hours_json'])) {
        $h = json_decode($minaRow['hours_json'], true);
        if ($h) return $h;
    }
    $dur = 60;
    return array_map(fn($h) => ["uuid"=>$uuids[$h]??md5($h),"hour"=>$h,"durationIn"=>$dur], $defaultHours);
}
// ────────────────────────────────────────────────────────────────────────────

include_once "./../../" . DASH . "/services/crud.php";
ini_set('error_log', __DIR__ . '/error.log');
$dominios_lista = [
    $url_base
];
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $dominios_lista)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
$WG_BUCKET_SITE = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") .
    "://" . $_SERVER['HTTP_HOST'];
if (strpos($path, '/api/frontend/game-logo/') === 0) {
    if (ob_get_length()) ob_clean();
    $parts = explode('/', trim($path, '/'));
    $file = end($parts);
    if (!empty($_GET['name'])) {
        $file = $_GET['name'];
    } elseif (!empty($_GET['file'])) {
        $file = $_GET['file'];
    }
    $file = urldecode($file);
    $name = preg_replace('/\\.(jpg|jpeg|png|webp)$/i', '', $file);
    $prov = '';
    $gameKey = '';
    $pos = strpos($name, '_');
    if ($pos !== false) {
        $prov = substr($name, 0, $pos);
        $gameKey = substr($name, $pos + 1);
    } else {
        $gameKey = $name;
    }
    $normalizeKey = function($value) {
        $value = str_replace(["\r", "\n"], '', (string)$value);
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }
        $value = strtolower($value);
        $value = str_replace(['™', '®', '©'], '', $value); // NAO remover 'tm'/'tmr' literais: apagavam letras de nomes colados (HotMuchacho->houchacho)
        $value = str_replace(['™','&','+','?','.',':','/','-','_',"'",'’'], '', $value);
        return preg_replace('/[^a-z0-9]/', '', $value);
    };
    $prov = strtoupper($prov);
    $gameKey = $normalizeKey($gameKey);
    $aliases = [];
    if ($prov === 'PG' || $prov === 'PGSOFT' || $prov === 'KKGAME') $aliases = ['PG', 'PGSOFT', 'pg', 'KKGAME'];
    if ($prov === 'PP' || $prov === 'PRAGMATIC' || $prov === 'ONE_API_PP') $aliases = ['PP', 'pp', 'PRAGMATIC', 'ONE_API_PP'];
    if ($prov === 'JDB' || $prov === 'ONE_API_JDB') $aliases = ['JDB', 'jdb', 'ONE_API_JDB', 'slot-jdb'];
    if ($prov === 'TADA' || $prov === 'ONE_API_TADA') $aliases = ['Tada', 'tada', 'ONE_API_Tada'];
    if ($prov === 'FACHAI' || $prov === 'ONE_API_FACHAI') $aliases = ['FaChai', 'fachai', 'ONE_API_FaChai', 'SLOT-FACHAI', 'SLOT_FACHAI'];
    if ($prov === 'CQ9' || $prov === 'ONE_API_CQ9') $aliases = ['CQ9', 'cq9', 'ONE_API_CQ9'];
    if ($prov === 'SPRIBE' || $prov === 'ONE_API_SPRIBE') $aliases = ['SPRIBE', 'spribe', 'ONE_API_Spribe'];
    if ($prov === 'POPOK') $aliases = ['POPOK', 'SLOT-POPOK', 'SLOT_POPOK'];
    if ($prov === 'RUBYPLAY') $aliases = ['RUBYPLAY', 'SLOT-RUBYPLAY', 'SLOT_RUBYPLAY'];
    if (empty($aliases)) $aliases = [$prov];
    $aliases = array_values(array_unique(array_map('strtoupper', $aliases)));
    $bannerUrl = null;
    if (isset($mysqli)) {
        $ph = implode(',', array_fill(0, count($aliases), '?'));
        $types = str_repeat('s', count($aliases));
        $pickBanner = function($banner) use ($WG_BUCKET_SITE) {
            if ($banner === null || trim($banner) === '') return null;
            if (strpos($banner, 'http') === 0) return $banner;
            if (strpos($banner, '/') !== 0) $banner = '/uploads/' . $banner;
            return $WG_BUCKET_SITE . $banner;
        };
        $findIn = function($rows) use ($normalizeKey, $gameKey, $pickBanner) {
            foreach ($rows as $row) {
                if ($normalizeKey($row['game_name']) === $gameKey || $normalizeKey($row['game_code']) === $gameKey) {
                    $u = $pickBanner($row['banner']);
                    if ($u !== null) return $u;
                }
            }
            return null;
        };
        // 1) procura dentro do provider (IGNORA status; casa por nome OU codigo)
        $rows = [];
        $stmt = $mysqli->prepare("SELECT game_name, game_code, banner FROM games WHERE UPPER(provider) IN ($ph)");
        $stmt->bind_param($types, ...$aliases);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $rows[] = $row; }
        $stmt->close();
        $bannerUrl = $findIn($rows);
        // 2) fallback global (ignora provider e status)
        if (!$bannerUrl) {
            $rowsG = [];
            $stmtG = $mysqli->prepare("SELECT game_name, game_code, banner FROM games");
            $stmtG->execute();
            $resG = $stmtG->get_result();
            while ($rg = $resG->fetch_assoc()) { $rowsG[] = $rg; }
            $stmtG->close();
            $bannerUrl = $findIn($rowsG);
        }
    }
    if ($bannerUrl) {
        // salva no cache local: proximas requests dessa imagem nao tocam no banco
        if (strpos($bannerUrl, 'http') === 0) {
            $relSave = urldecode(preg_replace('#^/api/frontend/#', '', $path));
            if ($relSave !== '' && strpos($relSave, '..') === false) {
                $savePath = __DIR__ . '/../frontend/' . $relSave;
                $saveDir = dirname($savePath);
                if (!is_dir($saveDir)) @mkdir($saveDir, 0755, true);
                if (is_dir($saveDir) && !is_file($savePath)) {
                    $ctxSave = stream_context_create(['http'=>['timeout'=>5],'https'=>['timeout'=>5]]);
                    $imgData = @file_get_contents($bannerUrl, false, $ctxSave);
                    if ($imgData !== false && strlen($imgData) > 100) {
                        @file_put_contents($savePath, $imgData);
                    }
                }
            }
        }
        header('Location: ' . $bannerUrl, true, 302);
        exit;
    }
    // fallback final: nunca deixa o card sem imagem
    header('Location: /images/skin-game-platform.png', true, 302);
    exit;
}
function resolveLogoUrl($logo, $baseUrl) {
    if (empty($logo)) return '';
    if (strpos($logo, 'http') === 0) {
        // Extrai o path da URL externa e reaplica no domínio atual
        $parsed = parse_url($logo);
        $path = $parsed['path'] ?? '';
        if (!empty($path)) {
            return rtrim($baseUrl, '/') . $path;
        }
        return '';
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($logo, '/'); // relativo → absoluto
}

function getTrpcInput() {
    if (isset($_GET['input'])) {
        return json_decode($_GET['input'], true);
    }
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $decoded = json_decode($rawInput, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    return [];
}
function sendTrpcResponse($data, $meta = null) {
    $response = [
        "result" => [
            "data" => [
                "json" => $data
            ]
        ]
    ];
    if ($meta) {
        $response["result"]["data"]["meta"] = $meta;
    }
    if (ob_get_length()) ob_clean();
    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function normalizeProviderKey($value) {
    $value = trim((string)$value);
    if ($value === '') return '';
    return strtoupper($value);
}
function getProviderAliasGroups() {
    static $groups = null;
    if ($groups === null) {
        $groups = [
            'PG' => ['PG', 'PGSOFT', 'KKGAME', 'KK'],
            'PP' => ['PP', 'PRAGMATIC', 'ONE_API_PP'],
            'JDB' => ['JDB', 'ONE_API_JDB', 'SLOT-JDB', 'SLOT_JDB'],
            'TADA' => ['TADA', 'ONE_API_TADA', 'SLOT-TADA', 'SLOT_TADA'],
            'FACHAI' => ['FACHAI', 'ONE_API_FACHAI', 'SLOT-FACHAI', 'SLOT_FACHAI'],
            'CQ9' => ['CQ9', 'ONE_API_CQ9'],
            'SPRIBE' => ['SPRIBE', 'ONE_API_SPRIBE'],
            'EVOLUTION' => ['EVOLUTION', 'WHITECLIFF_EVOLUTION', 'EVOLIVE'],
            'RUBYPLAY' => ['RUBYPLAY', 'SLOT-RUBYPLAY', 'SLOT_RUBYPLAY'],
            'POPOK' => ['POPOK', 'SLOT-POPOK', 'SLOT_POPOK'],
            'PLAYSON' => ['PLAYSON', 'SLOT-PLAYSON', 'SLOT_PLAYSON'],
            'FASTSPIN' => ['FASTSPIN'],
            'INOUT' => ['INOUT'],
            'G759' => ['G759'],
            'CP' => ['CP'],
            'PANDA' => ['PANDA']
        ];
    }
    return $groups;
}
function getProviderAliasesFromConfig($providerConfig) {
    $aliases = [];
    $add = function($value) use (&$aliases) {
        $key = normalizeProviderKey($value);
        if ($key !== '') $aliases[$key] = true;
    };
    $add($providerConfig['code'] ?? '');
    $add($providerConfig['name'] ?? '');
    $groups = getProviderAliasGroups();
    $seed = array_keys($aliases);
    foreach ($seed as $key) {
        if (isset($groups[$key])) {
            foreach ($groups[$key] as $alias) $add($alias);
        } else {
            foreach ($groups as $canonical => $list) {
                if (in_array($key, $list, true)) {
                    $add($canonical);
                    foreach ($list as $alias) $add($alias);
                }
            }
        }
    }
    return array_values(array_keys($aliases));
}
function getProviderConfig($provider) {
    global $mysqli;
    static $providersMap = null;
    static $providersNameMap = null;
    if ($providersMap === null) {
        $providersMap = [];
        $providersNameMap = [];
        if (isset($mysqli)) {
            $res = $mysqli->query("SELECT * FROM provedores");
            if ($res) {
                while ($prow = $res->fetch_assoc()) {
                    $config = [
                        'name' => $prow['name'],
                        'code' => $prow['code'],
                        'id' => (int)$prow['id'],
                        'status' => (int)$prow['status']
                    ];
                    $codeKey = normalizeProviderKey($prow['code']);
                    $nameKey = normalizeProviderKey($prow['name']);
                    if ($codeKey !== '') {
                        $providersMap[$codeKey] = $config;
                    }
                    if ($nameKey !== '') {
                        $providersNameMap[$nameKey] = $config;
                    }
                    if ($prow['code'] !== '') {
                        $providersMap[$prow['code']] = $config;
                    }
                    if ($prow['name'] !== '') {
                        $providersNameMap[$prow['name']] = $config;
                    }
                }
            }
        }
    }
    $providerKey = normalizeProviderKey($provider);
    if ($providerKey !== '') {
        if (isset($providersMap[$providerKey])) return $providersMap[$providerKey];
        if (isset($providersNameMap[$providerKey])) return $providersNameMap[$providerKey];
        $groups = getProviderAliasGroups();
        foreach ($groups as $canonical => $list) {
            if ($providerKey === $canonical || in_array($providerKey, $list, true)) {
                if (isset($providersNameMap[$canonical])) return $providersNameMap[$canonical];
                if (isset($providersMap[$canonical])) return $providersMap[$canonical];
                foreach ($list as $alias) {
                    if (isset($providersMap[$alias])) return $providersMap[$alias];
                    if (isset($providersNameMap[$alias])) return $providersNameMap[$alias];
                }
            }
        }
    }
    return ['name' => $provider, 'code' => $provider, 'id' => 0, 'status' => 1];
}
function buildLogoFlag($providerName, $gameName) {
    $value = str_replace(["\r", "\n"], '', (string)$gameName);
    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($ascii !== false) {
        $value = $ascii;
    }
    $value = preg_replace('/[^a-zA-Z0-9]/', '', $value);
    return $providerName . "_" . $value;
}
function formatGameData($row, $baseUrl) {
    $providerConfig = getProviderConfig($row['provider']);
    $cleanName = trim(str_replace(["\r", "\n"], '', $row['game_name']));
    $banner = $row['banner'];
    if (strpos($banner, 'game_pictures/') !== false) {
        $parts = explode('game_pictures/', $banner, 2);
        if (count($parts) > 1) {
             $banner = '/game_pictures/' . $parts[1];
        }
    }
    if (strpos($banner, 'http') !== 0) {
        if (strpos($banner, '/') !== 0) {
            if (strpos($banner, 'PlayFiver/') === 0) {
                $banner = '/' . $banner;
            } else {
                $banner = '/uploads/' . $banner;
            }
        }
        $banner = $baseUrl . $banner;
    }
    $platformCode = $providerConfig['code'];
    return [
        "status" => ($row['status'] == 1) ? "ON" : "OFF",
        "hot" => (bool)$row['popular'],
        "sort" => (int)$row['id'],
        "top" => false,
        "topSort" => 0,
        "logo" => $banner,
        "id" => (int)$row['id'],
        "code" => $row['game_code'], 
        "name" => $cleanName,
        "gameNameMultiLanguage" => [
            "br" => "",
            "en" => $cleanName,
            "hi" => $cleanName,
            "id" => $cleanName,
            "vi" => $cleanName,
            "zh" => $cleanName
        ],
        "externalGameId" => rand(10000000, 99999999),
        "horizontalScreen" => false,
        "gameType" => "ELECTRONIC",
        "gameTypeStatus" => "ON",
        "platformId" => (int)$providerConfig['id'],
        "platformName" => $providerConfig['name'],
        "platformStatus" => ($providerConfig['status'] == 1) ? "ON" : "OFF",
        "regionCode" => "BR",
        "platformCode" => $platformCode,
        "logoFlag" => buildLogoFlag($providerConfig['name'], $cleanName)
    ];
}
function formatHotGameData($row, $baseUrl) {
    $providerConfig = getProviderConfig($row['provider']);
    $cleanName = trim(str_replace(["\r", "\n"], '', $row['game_name']));
    $banner = $row['banner'];
    if (strpos($banner, 'game_pictures/') !== false) {
        $parts = explode('game_pictures/', $banner, 2);
        if (count($parts) > 1) {
             $banner = '/game_pictures/' . $parts[1];
        }
    }
    if (strpos($banner, 'http') !== 0) {
        if (strpos($banner, '/') !== 0) {
            if (strpos($banner, 'PlayFiver/') === 0) {
                $banner = '/' . $banner;
            } else {
                $banner = '/uploads/' . $banner;
            }
        }
        $banner = $baseUrl . $banner;
    }
    $platformCode = $providerConfig['code'];
    return [
        "background" => "",
        "gameCode" => $row['game_code'],
        "gameId" => (int)$row['id'],
        "gameName" => $cleanName,
        "gameStatus" => ($row['status'] == 1) ? "ON" : "OFF",
        "gameType" => "ELECTRONIC",
        "gameTypeStatus" => "ON",
        "horizontalScreen" => "0",
        "hot" => (int)$row['popular'],
        "hotTop" => 0,
        "hotTopSort" => 99,
        "id" => (int)$row['id'],
        "logo" => $banner,
        "logoFlag" => buildLogoFlag($providerConfig['name'], $cleanName),
        "openType" => false,
        "platformCode" => $platformCode,
        "platformId" => (int)$providerConfig['id'],
        "platformName" => $providerConfig['name'],
        "platformStatus" => ($providerConfig['status'] == 1) ? "ON" : "OFF",
        "regionCode" => "BR",
        "secondaryBackground" => "",
        "sort" => -5,
        "status" => ($row['status'] == 1) ? "ON" : "OFF",
        "target" => "gameList",
        "type" => "game"
    ];
}
$global_config = [];
try {
    if (isset($mysqli)) {
        $conf_query = $mysqli->query("SELECT * FROM config LIMIT 1");
        if ($conf_query && $row = $conf_query->fetch_assoc()) {
            $global_config = $row;
        }
    }
} catch (Exception $e) {
    error_log("Erro ao carregar configs em api.php: " . $e->getMessage());
}
function getConf($key, $default = '') {
    global $global_config;
    return $global_config[$key] ?? $default;
}
function getConfUrl($key) {
    global $global_config, $WG_BUCKET_SITE;
    $val = $global_config[$key] ?? '';
    if (!empty($val) && strpos($val, 'http') !== 0) {
         if (strpos($val, '/') !== 0) {
             $val = '/uploads/' . $val;
         }
         $val = $WG_BUCKET_SITE . $val;
    }
    return $val;
}
function getCurrentUser($mysqli) {
    $token = '';
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }
    }
    if (empty($token) && isset($_COOKIE['token_user'])) {
        $token = $_COOKIE['token_user'];
    }
    if (empty($token)) {
        return null;
    }
    $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return null;
}
function deve_contabilizar_indicacao($mysqli, $invite_code_afiliado) {
    $config_qry = "SELECT * FROM manipulacao_indicacoes WHERE id = 1 LIMIT 1";
    $config_result = $mysqli->query($config_qry);
    if (!$config_result || $config_result->num_rows == 0) {
        return true;
    }
    $config = $config_result->fetch_assoc();
    if ($config['ativo'] != 1) {
        return true;
    }
    $count_qry = "SELECT pessoas_convidadas, indicacoes_roubadas FROM usuarios WHERE invite_code = ? LIMIT 1";
    $stmt = $mysqli->prepare($count_qry);
    $stmt->bind_param("s", $invite_code_afiliado);
    $stmt->execute();
    $count_result = $stmt->get_result();
    if ($count_result->num_rows == 0) {
        $stmt->close();
        return true;
    }
    $afiliado_data = $count_result->fetch_assoc();
    $pessoas_convidadas = intval($afiliado_data['pessoas_convidadas']);
    $indicacoes_roubadas = intval($afiliado_data['indicacoes_roubadas']);
    $stmt->close();
    $total_cadastros = $pessoas_convidadas + $indicacoes_roubadas;
    $dar = intval($config['dar_indicacoes']);
    $roubar = intval($config['roubar_indicacoes']);
    $ciclo_total = $dar + $roubar;
    $posicao_no_ciclo = ($total_cadastros % $ciclo_total) + 1;
    if ($posicao_no_ciclo <= $dar) {
        return true;
    } else {
        return false;
    }
}
function getBoxList($mysqli, $token) {
    $config_afiliados_qry = "SELECT minDepForCpa, minResgate, pagar_baus FROM afiliados_config WHERE id = 1";
    $config_afiliados_resp = mysqli_query($mysqli, $config_afiliados_qry);
    $min_deposito = 20; 
    $min_apostado = 30; 
    $pagar_baus = 1;
    if ($config_afiliados_resp && $row = mysqli_fetch_assoc($config_afiliados_resp)) {
        $min_deposito = $row['minDepForCpa'];
        $min_apostado = $row['minResgate'];
        $pagar_baus = $row['pagar_baus'] ?? 1;
    }
    $qry = "SELECT * FROM usuarios WHERE token = '$token'";
    $resp = mysqli_query($mysqli, $qry);
    if (mysqli_num_rows($resp) > 0) {
        $user = mysqli_fetch_assoc($resp);
        $codigoConvite = $user['invite_code'];
        $qryConvidados = "SELECT id FROM usuarios WHERE invitation_code = '$codigoConvite'";
        $resConvidados = mysqli_query($mysqli, $qryConvidados);
        $idsConvidados = [];
        while ($row = mysqli_fetch_assoc($resConvidados)) {
            $idsConvidados[] = $row['id'];
        }
        $idsValidos = [];
        foreach ($idsConvidados as $idConvidado) {
            $qryDeposito = "SELECT SUM(valor) as total_depositado FROM transacoes WHERE usuario = $idConvidado AND status = 'pago'";
            $resDeposito = mysqli_query($mysqli, $qryDeposito);
            $total_depositado = mysqli_fetch_assoc($resDeposito)['total_depositado'] ?? 0;
            $qryAposta = "SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = $idConvidado AND status_play = '1'";
            $resAposta = mysqli_query($mysqli, $qryAposta);
            $total_apostado = mysqli_fetch_assoc($resAposta)['total_apostado'] ?? 0;
            if ($total_depositado >= $min_deposito && $total_apostado >= $min_apostado) {
                $idsValidos[] = $idConvidado;
            }
        }
        $total_mem_count = count($idsValidos);
        $numsArray = [];
        $qryBau = "SELECT num, status, is_get FROM bau WHERE id_user = '{$user['id']}'";
        $respBau = mysqli_query($mysqli, $qryBau);
        while ($rowBau = mysqli_fetch_assoc($respBau)) {
            if ($rowBau['status'] === 'claimed' || $rowBau['is_get'] == 1) {
                $numsArray[] = trim($rowBau['num']);
            }
        }
        $config_qry = "SELECT niveisbau, qntsbaus, nvlbau, pessoasbau FROM config";
        $config_resp = mysqli_query($mysqli, $config_qry);
        $config = mysqli_fetch_assoc($config_resp);
        $niveis_bau = explode(',', $config['niveisbau']);
        $quantidade_baus = $config['qntsbaus'];
        $pessoas_bau = $config['pessoasbau'];
        $baus_por_nivel = ceil($quantidade_baus / count($niveis_bau));
        $baus = [];
        for ($i = 1; $i <= $quantidade_baus; $i++) {
            $nivel_index = floor(($i - 1) / $baus_por_nivel);
            $money = isset($niveis_bau[$nivel_index]) ? (float) $niveis_bau[$nivel_index] : (float) end($niveis_bau);
            $condition = $i * $pessoas_bau;
            $is_get = 0;
            if ($pagar_baus == 0) {
                $is_get = 0;
            } elseif (in_array((string)$i, $numsArray)) {
                $is_get = 2; 
            } elseif ($total_mem_count >= $condition) {
                $is_get = 1; 
            }
            $baus[] = [
                "id" => $i,
                "promote_num" => $condition,
                "reward" => number_format($money, 2, '.', ''),
                "expectedReward" => $money,
                "displayReward" => "",
                "promoteStatus" => $is_get,
                "logCategory" => 0,
                "promoteAmount" => $money,
                "receiveId" => 0
            ];
        }
        return $baus;
    } else {
        $config_qry = "SELECT niveisbau, qntsbaus, nvlbau, pessoasbau FROM config";
        $config_resp = mysqli_query($mysqli, $config_qry);
        $config = mysqli_fetch_assoc($config_resp);
        $niveis_bau = explode(',', $config['niveisbau']);
        $quantidade_baus = $config['qntsbaus'];
        $pessoas_bau = $config['pessoasbau'];
        $baus_por_nivel = ceil($quantidade_baus / count($niveis_bau));
        $baus = [];
        for ($i = 1; $i <= $quantidade_baus; $i++) {
            $nivel_index = floor(($i - 1) / $baus_por_nivel);
            $money = isset($niveis_bau[$nivel_index]) ? (float) $niveis_bau[$nivel_index] : (float) end($niveis_bau);
            $condition = $i * $pessoas_bau;
            $baus[] = [
                "id" => $i,
                "promote_num" => $condition,
                "reward" => number_format($money, 2, '.', ''),
                "expectedReward" => $money,
                "displayReward" => "",
                "promoteStatus" => 0,
                "logCategory" => 0,
                "promoteAmount" => $money,
                "receiveId" => 0
            ];
        }
        return $baus;
    }
}
if ($path === '/api/frontend/trpc/auth.tenants') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/auth.registe') {
    $rotaEncontrada = true;
    $input = $data['json'] ?? $data;
    if (empty($input)) {
        $input = getTrpcInput();
        $input = $input['json'] ?? $input;
    }
    $mobile = $input['phone'] ?? $input['mobile'] ?? $input['username'] ?? '';
    $password = $input['password'] ?? $input['pass'] ?? '';
    $cpfInput = preg_replace('/\D/', '', $input['cpf'] ?? '');
    $inviteCode = $input['inviteCode'] ?? $input['code'] ?? $_GET['pid'] ?? '';
    if (empty($inviteCode) && !empty($_SERVER['HTTP_REFERER'])) {
        $query_str = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        if ($query_str) {
            parse_str($query_str, $query_params);
            if (!empty($query_params['pid'])) {
                $inviteCode = $query_params['pid'];
            }
        }
    }
    if (empty($mobile) || empty($password)) {
        sendApiError(400, "Mobile and password are required");
    }
    // Validação matemática de CPF (se enviado)
    if (!empty($cpfInput)) {
        $cpfInvalido = false;
        if (strlen($cpfInput) !== 11 || preg_match('/^(\d)\1{10}$/', $cpfInput)) {
            $cpfInvalido = true;
        } else {
            $s = 0;
            for ($i = 0; $i < 9; $i++) $s += intval($cpfInput[$i]) * (10 - $i);
            $r = ($s * 10) % 11;
            if ($r === 10 || $r === 11) $r = 0;
            if ($r !== intval($cpfInput[9])) $cpfInvalido = true;
            if (!$cpfInvalido) {
                $s = 0;
                for ($i = 0; $i < 10; $i++) $s += intval($cpfInput[$i]) * (11 - $i);
                $r = ($s * 10) % 11;
                if ($r === 10 || $r === 11) $r = 0;
                if ($r !== intval($cpfInput[10])) $cpfInvalido = true;
            }
        }
        if ($cpfInvalido) {
            if (ob_get_length()) ob_clean();
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "CPF inválido, por favor insira um CPF válido",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "auth.registe"
                        ]
                    ]
                ]
            ]);
            exit;
        }
    }
    if (!isset($mysqli)) {
        error_log("MySQLi connection not found in auth.registe");
        sendApiError(500, "Database connection error");
    }
    try {
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE mobile = ?");
        if (!$stmt) {
             throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "Usuário já existe",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "auth.registe"
                        ]
                    ]
                ]
            ]);
            exit;
        }
        // Verificar CPF duplicado
        if (!empty($cpfInput)) {
            $stmtCpf = $mysqli->prepare("SELECT id FROM usuarios WHERE cpf = ? LIMIT 1");
            if ($stmtCpf) {
                $stmtCpf->bind_param("s", $cpfInput);
                $stmtCpf->execute();
                if ($stmtCpf->get_result()->num_rows > 0) {
                    $stmtCpf->close();
                    http_response_code(400);
                    echo json_encode([
                        "error" => [
                            "json" => [
                                "message" => "CPF já cadastrado",
                                "code" => -32600,
                                "data" => [
                                    "code" => "BAD_REQUEST",
                                    "httpStatus" => 400,
                                    "path" => "auth.registe"
                                ]
                            ]
                        ]
                    ]);
                    exit;
                }
                $stmtCpf->close();
            }
        }
    } catch (Throwable $e) {
        error_log("Error in auth.registe check: " . $e->getMessage());
        sendApiError(500, "Internal Error: " . $e->getMessage());
    }
    $userId = mt_rand(100000000, 999999999);
    try {
        while(true) {
            $check = $mysqli->query("SELECT id FROM usuarios WHERE id = $userId");
            if ($check && $check->num_rows > 0) {
                $userId = mt_rand(100000000, 999999999);
            } else {
                break;
            }
        }
    } catch (Throwable $e) {
        error_log("Error generating ID: " . $e->getMessage());
        sendApiError(500, "ID Generation Error");
    }
    $token = bin2hex(random_bytes(20));
    $tenantId = 5657408;
    $data_registro = date('Y-m-d H:i:s');
    $invite_code = $userId;
    $url = $WG_BUCKET_SITE;
    $invite_code_bspay_1 = '';
    $invite_code_bspay_2 = '';
    $sql_bspay1 = "SELECT invite_code FROM bspay WHERE id = 1 LIMIT 1";
    $res_bspay1 = $mysqli->query($sql_bspay1);
    if ($res_bspay1 && $row_bspay1 = $res_bspay1->fetch_assoc()) {
        $invite_code_bspay_1 = $row_bspay1['invite_code'];
    }
    $sql_bspay2 = "SELECT invite_code FROM bspay WHERE id = 2 LIMIT 1";
    $res_bspay2 = $mysqli->query($sql_bspay2);
    if ($res_bspay2 && $row_bspay2 = $res_bspay2->fetch_assoc()) {
        $invite_code_bspay_2 = $row_bspay2['invite_code'];
    }
    $statusaff = 0;
    if (!empty($inviteCode)) {
        if ((!empty($invite_code_bspay_1) && $inviteCode === $invite_code_bspay_1) || 
            (!empty($invite_code_bspay_2) && $inviteCode === $invite_code_bspay_2)) {
            $statusaff = 1;
        }
    }
    $invitation_code_final = $inviteCode;
    $indicacao_foi_roubada = false;
    if (!empty($inviteCode)) {
        $deve_contabilizar = deve_contabilizar_indicacao($mysqli, $inviteCode);
        if (!$deve_contabilizar) {
            $invitation_code_final = null;
            $indicacao_foi_roubada = true;
        }
    }
    $tipo_pagamento = 1;
    $stmt = $mysqli->prepare("INSERT INTO usuarios (id, mobile, celular, password, senhaparasacar, url, token, data_registro, invite_code, invitation_code, tipo_pagamento, statusaff, cpf) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        sendApiError(500, "DB Error: " . $mysqli->error);
    }
    $senhaparasacar_empty = "";
    $cpfParaSalvar = !empty($cpfInput) ? $cpfInput : null;
    $stmt->bind_param("isssssssssiis", $userId, $mobile, $mobile, $password, $senhaparasacar_empty, $url, $token, $data_registro, $invite_code, $invitation_code_final, $tipo_pagamento, $statusaff, $cpfParaSalvar);
    if ($stmt->execute()) {
        if (!empty($inviteCode)) {
            if ($indicacao_foi_roubada) {
                $stmt_aff = $mysqli->prepare("UPDATE usuarios SET indicacoes_roubadas = indicacoes_roubadas + 1 WHERE invite_code = ?");
                $stmt_aff->bind_param("s", $inviteCode);
                $stmt_aff->execute();
                $stmt_aff->close();
            } else {
                $stmt_aff = $mysqli->prepare("UPDATE usuarios SET pessoas_convidadas = pessoas_convidadas + 1, LuckyWheel = LuckyWheel + 1 WHERE invite_code = ?");
                $stmt_aff->bind_param("s", $inviteCode);
                $stmt_aff->execute();
                $stmt_aff->close();
            }
        }
        $config_qry = "SELECT qntsbaus FROM config WHERE id=1";
        $config_res = $mysqli->query($config_qry);
        $config_bau = $config_res ? $config_res->fetch_assoc() : null;
        $qntsbaus = (int)($config_bau['qntsbaus'] ?? 1);
        for ($i = 1; $i <= $qntsbaus; $i++) {
            $uuid = md5($userId . $i . time() . 'salt_bau');
            $stmt_bau = $mysqli->prepare("INSERT INTO bau (id_user, num, status, token) VALUES (?, ?, 'user novo', ?)");
            $stmt_bau->bind_param("iis", $userId, $i, $uuid);
            $stmt_bau->execute();
            $stmt_bau->close();
        }
        if (function_exists('criar_financeiro')) {
             criar_financeiro($userId);
        }
        if (function_exists('criar_tokenrefer')) {
             criar_tokenrefer($userId);
        }

        setcookie('token_user', $token, time() + (10 * 365 * 24 * 60 * 60), '/');

        // ── Bônus de Cadastro automático (só se a roleta estiver INATIVA) ───────
        $bonusCreditado = 0;
        // Checa se a roleta de boas-vindas está ativa
        $roletaAtiva = false;
        $resRoleta = @$mysqli->query("SELECT active FROM welcome_roulette_settings WHERE id=1 LIMIT 1");
        if ($resRoleta && $rowRoleta = $resRoleta->fetch_assoc()) {
            $roletaAtiva = (bool)(int)$rowRoleta['active'];
        }

        if (!$roletaAtiva) {
            // Roleta inativa → credita bônus automático e trava a flag
            $bc = getBonusConfig($mysqli);
            if (!empty($bc['active']) && (int)$bc['valor_max'] >= (int)$bc['valor_min'] && (int)$bc['valor_min'] > 0) {
                $bonusCents  = mt_rand((int)$bc['valor_min'], (int)$bc['valor_max']);
                $bonusAmount = round($bonusCents / 100, 2);
                $stmtBonus = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ?, canApplyRegisterReward = 0 WHERE id = ?");
                $stmtBonus->bind_param("di", $bonusAmount, $userId);
                $stmtBonus->execute();
                $stmtBonus->close();
                $stmtBonusLog = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, 'register_reward', NOW())");
                $stmtBonusLog->bind_param("id", $userId, $bonusAmount);
                $stmtBonusLog->execute();
                $bonusCreditado = $bonusCents;
                $stmtBonusLog->close();
            }
        }
        // Se roleta ativa: canApplyRegisterReward permanece 1 (padrão do INSERT)
        // → JS detecta e redireciona para /subscribeReward automaticamente
        // ─────────────────────────────────────────────────────────────────────

        $responseData = [
        "data" => [
            "userId" => (string)$userId,
            "tenantId" => $tenantId,
            "token" => $token,
            "giftTrialPlayAmount" => $bonusCreditado,
            "trialPlayAmountType" => $bonusCreditado > 0 ? "REGISTER_BONUS" : ""
        ]
    ];
    sendTrpcResponse($responseData);
} else {
        sendApiError(500, "Registration failed");
    }
}
if ($path === '/api/frontend/trpc/mail.noRead') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $count = 0;
    if ($user) {
        try {
            $userId = $user['id'];
            $stmt = $mysqli->prepare("SELECT count(*) as c FROM notificacoes WHERE status = 1 AND (destinatario = 'todos' OR destinatario = ?) AND id NOT IN (SELECT notification_id FROM notificacoes_lidas WHERE admin_id = ? AND notification_type = 'notificacao')");
            if ($stmt) {
                $stmt->bind_param("si", $userId, $userId);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $count = (int)$row['c'];
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error in mail.noRead: " . $e->getMessage());
        }
    }
    sendTrpcResponse($count);
}
if ($path === '/api/frontend/trpc/mail.list') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
    $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 10;
    if ($page < 1) {
        $page = 1;
    }
    if ($pageSize < 1) {
        $pageSize = 10;
    }
    $offset = ($page - 1) * $pageSize;
    $mailList = [];
    $total = 0;
    try {
        $userId = $user ? $user['id'] : '';
        if ($userId) {
            $stmtTotal = $mysqli->prepare("SELECT COUNT(*) as c FROM notificacoes WHERE status = 1 AND (destinatario = 'todos' OR destinatario = ?)");
            if ($stmtTotal) {
                $stmtTotal->bind_param("s", $userId);
                $stmtTotal->execute();
                $resTotal = $stmtTotal->get_result();
                if ($rowTotal = $resTotal->fetch_assoc()) $total = intval($rowTotal['c']);
                $stmtTotal->close();
            }
            $stmt = $mysqli->prepare("SELECT id, titulo, conteudo as content, imagem, criado_em FROM notificacoes WHERE status = 1 AND (destinatario = 'todos' OR destinatario = ?) ORDER BY criado_em DESC LIMIT ? OFFSET ?");
        } else {
            $total_query = $mysqli->query("SELECT COUNT(*) as c FROM notificacoes WHERE status = 1 AND destinatario = 'todos'");
            if ($total_query) {
                $rowTotal = $total_query->fetch_assoc();
                $total = isset($rowTotal['c']) ? intval($rowTotal['c']) : 0;
            }
            $stmt = $mysqli->prepare("SELECT id, titulo, conteudo as content, imagem, criado_em FROM notificacoes WHERE status = 1 AND destinatario = 'todos' ORDER BY criado_em DESC LIMIT ? OFFSET ?");
        }
        if ($stmt) {
            if ($userId) {
                $stmt->bind_param("sii", $userId, $pageSize, $offset);
            } else {
                $stmt->bind_param("ii", $pageSize, $offset);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $isRead = false;
                if ($user) {
                    $stmtRead = $mysqli->prepare("SELECT id FROM notificacoes_lidas WHERE admin_id = ? AND notification_type = 'notificacao' AND notification_id = ? LIMIT 1");
                    if ($stmtRead) {
                        $stmtRead->bind_param("ii", $user['id'], $row['id']);
                        $stmtRead->execute();
                        $resRead = $stmtRead->get_result();
                        if ($resRead && $resRead->num_rows > 0) {
                            $isRead = true;
                        }
                        $stmtRead->close();
                    }
                }
                $createTime = $row['criado_em'] ?? null;
                if ($createTime) {
                    $createTime = date('c', strtotime($createTime));
                }
                $content = $row['content'];
                $mailList[] = [
                    "id" => (int)$row['id'],
                    "title" => $row['titulo'],
                    "isRead" => $isRead,
                    "createTime" => $createTime,
                    "content" => $content,
                    "mailLogId" => null,
                    "signature" => "",
                    "activityConfig" => null,
                    "titleType" => null
                ];
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error in mail.list: " . $e->getMessage());
    }
    $meta = [
        "values" => [
            "mailList.0.createTime" => ["Date"]
        ]
    ];
    sendTrpcResponse([
        "mailList" => $mailList,
        "total" => $total
    ], $meta);
}
if ($path === '/api/frontend/trpc/mail.read') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $mailId = isset($dataInput['mailId']) ? intval($dataInput['mailId']) : 0;
    if (!$mailId && isset($dataInput['id'])) {
        $mailId = intval($dataInput['id']);
    }
    $data = null;
    if ($mailId > 0) {
        try {
            $userId = $user ? $user['id'] : '';
            if ($userId) {
                $stmt = $mysqli->prepare("SELECT id, titulo, conteudo as content, imagem, criado_em FROM notificacoes WHERE id = ? AND status = 1 AND (destinatario = 'todos' OR destinatario = ?) LIMIT 1");
                $stmt->bind_param("is", $mailId, $userId);
            } else {
                $stmt = $mysqli->prepare("SELECT id, titulo, conteudo as content, imagem, criado_em FROM notificacoes WHERE id = ? AND status = 1 AND destinatario = 'todos' LIMIT 1");
                $stmt->bind_param("i", $mailId);
            }
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $isRead = false;
                    if ($user) {
                        $stmtCheck = $mysqli->prepare("SELECT id FROM notificacoes_lidas WHERE admin_id = ? AND notification_type = 'notificacao' AND notification_id = ? LIMIT 1");
                        if ($stmtCheck) {
                            $stmtCheck->bind_param("ii", $user['id'], $row['id']);
                            $stmtCheck->execute();
                            $resCheck = $stmtCheck->get_result();
                            if ($resCheck && $resCheck->num_rows > 0) {
                                $isRead = true;
                            }
                            $stmtCheck->close();
                        }
                        if (!$isRead) {
                            $stmtInsert = $mysqli->prepare("INSERT INTO notificacoes_lidas (admin_id, notification_type, notification_id, data_leitura) VALUES (?, 'notificacao', ?, NOW())");
                            if ($stmtInsert) {
                                $stmtInsert->bind_param("ii", $user['id'], $row['id']);
                                $stmtInsert->execute();
                                $stmtInsert->close();
                            }
                            $isRead = true;
                        }
                    }
                    $createTime = $row['criado_em'] ?? null;
                    if ($createTime) {
                        $createTime = date('c', strtotime($createTime));
                    }
                    $content = $row['content'];
                    $img = $row['imagem'] ?? '';
                    if ($img) {
                        $imgUrl = $img;
                        if (strpos($imgUrl, 'http') !== 0) {
                            if (strpos($imgUrl, '/') !== 0) {
                                $imgUrl = '/uploads/' . $imgUrl;
                            }
                            $imgUrl = $WG_BUCKET_SITE . $imgUrl;
                        }
                        $content .= '<img src="' . $imgUrl . '" width="320" height="130" />';
                    }
                    $hostSig = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'site';
                    $data = [
                        "content" => $content,
                        "createTime" => $createTime,
                        "external_id" => (int)$row['id'],
                        "isRead" => $isRead,
                        "mailLogId" => 'ML' . time() . (int)$row['id'],
                        "signature" => $hostSig,
                        "title" => $row['titulo']
                    ];
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Error in mail.read: " . $e->getMessage());
        }
    }
    $meta = [
        "values" => [
            "createTime" => ["Date"]
        ]
    ];
    sendTrpcResponse($data, $meta);
}
if ($path === '/api/frontend/trpc/mail.update') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $userId = $user['id'];
        $sql = "SELECT id FROM notificacoes WHERE status = 1 AND (destinatario = 'todos' OR destinatario = ?) AND id NOT IN (SELECT notification_id FROM notificacoes_lidas WHERE admin_id = ? AND notification_type = 'notificacao')";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("si", $userId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $idsToInsert = [];
            while ($row = $result->fetch_assoc()) {
                $idsToInsert[] = $row['id'];
            }
            $stmt->close();
            if (!empty($idsToInsert)) {
                $stmtInsert = $mysqli->prepare("INSERT INTO notificacoes_lidas (admin_id, notification_type, notification_id, data_leitura) VALUES (?, 'notificacao', ?, NOW())");
                if ($stmtInsert) {
                    foreach ($idsToInsert as $notifId) {
                         $stmtInsert->bind_param("ii", $userId, $notifId);
                         $stmtInsert->execute();
                    }
                    $stmtInsert->close();
                }
            }
        }
    }
    sendTrpcResponse(new stdClass());
}
if ($path === '/api/frontend/trpc/task.list') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/reward.double') {
    $rotaEncontrada = true;
    sendTrpcResponse(["success" => true]);
}
if ($path === '/api/frontend/trpc/reward.getBePaidPayOrder') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/registerReward.doubleResult') {
    $rotaEncontrada = true;
    sendTrpcResponse(new stdClass());
}

if ($path === '/api/frontend/trpc/registerReward.info') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    
    $registerRewardId = null;
    $registerRewardAmount = null;
    $registerRewardReceiveTime = null;

    if ($user) {
        $stmt = $mysqli->prepare("SELECT id, valor, data_registro FROM adicao_saldo WHERE id_user = ? AND tipo = 'register_reward' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $stmt->bind_result($rId, $rVal, $rDate);
            if ($stmt->fetch()) {
                $registerRewardId = (int)$rId;
                $registerRewardAmount = (float)$rVal;
                $registerRewardReceiveTime = $rDate;
            }
            $stmt->close();
        }
    }
    
    // Carregar range da Roleta Boas-Vindas
    $minRoulette = 30;
    $maxRoulette = 30;
    $resWR = $mysqli->query("SELECT min_cents, max_cents FROM welcome_roulette_settings WHERE id=1");
    if ($resWR && $rowWR = $resWR->fetch_assoc()) {
        $minRoulette = (int)$rowWR['min_cents'];
        $maxRoulette = (int)$rowWR['max_cents'];
        if ($maxRoulette < $minRoulette) { $t=$minRoulette; $minRoulette=$maxRoulette; $maxRoulette=$t; }
    }
    $auditMultipleCfg = 1.00;
    $resWRAM = $mysqli->query("SELECT audit_multiple FROM welcome_roulette_settings WHERE id=1");
    if ($resWRAM && $rowWRAM = $resWRAM->fetch_assoc()) {
        $auditMultipleCfg = (float)$rowWRAM['audit_multiple'];
        if ($auditMultipleCfg <= 0) $auditMultipleCfg = 1.00;
    }
    $response = [
        "frontConfig" => [
            "android" => [
                "downloadBtn" => true,
                "guideInstall" => true,
                "popupType" => "NORMAL",
                "showGiftAmountType" => 0,
                "showGiftAmount" => 0,
                "showGiftMaxAmount" => 0,
                "popupTime" => "HOME",
                "popupInterval" => "0",
                "installType" => "PWA+APK",
                "installUrl" => "https://upload-us.f-1-q-h.com/s4/1248680527158/a89s.com_400015.apk" 
            ],
            "ios" => [
                "downloadBtn" => false,
                "guideInstall" => true,
                "popupType" => "NORMAL",
                "showGiftAmountType" => 0,
                "showGiftAmount" => 0,
                "showGiftMaxAmount" => 0,
                "popupTime" => "HOME",
                "popupInterval" => "0",
                "installType" => "APPSTORE",
                "installUrl" => "https://apps.apple.com/",
                "iosPackageId" => 0,
                "iosAddressType" => "normal"
            ]
        ],
        "wheelReward" => (function() use ($mysqli, $minRoulette, $maxRoulette) {
            // Tenta carregar prêmios do banco (roulette_prizes)
            $prizes = [];
            $resP = $mysqli->query("SELECT type, amount, weight FROM roulette_prizes WHERE active=1 ORDER BY sort_order ASC, id ASC");
            if ($resP && $resP->num_rows > 0) {
                while ($rp = $resP->fetch_assoc()) {
                    $type = $rp['type'];
                    $amount = (float)$rp['amount'];
                    // converte para centavos inteiros para rewardValue
                    if ($type === 'rangeAmount') {
                        $prizes[] = ["rewardType" => "RANDOM", "rewardValue" => $maxRoulette > 0 ? $maxRoulette : 30];
                    } elseif ($type === 'nothing') {
                        $prizes[] = ["rewardType" => "THANKS", "rewardValue" => 0];
                    } elseif ($type === 'bonus') {
                        $prizes[] = ["rewardType" => "RANDOM", "rewardValue" => $maxRoulette > 0 ? $maxRoulette : 30];
                    } else {
                        // fixedAmount: converte R$ para centavos
                        $prizes[] = ["rewardType" => "FIXED", "rewardValue" => (int)round($amount * 100)];
                    }
                }
                return $prizes;
            }
            // Fallback: prêmios padrão
            return [
                ["rewardType" => "RANDOM", "rewardValue" => $maxRoulette > 0 ? $maxRoulette : 30],
                ["rewardType" => "FIXED",  "rewardValue" => 300],
                ["rewardType" => "THANKS", "rewardValue" => 0],
                ["rewardType" => "FIXED",  "rewardValue" => 880],
                ["rewardType" => "RANDOM", "rewardValue" => $maxRoulette > 0 ? $maxRoulette : 30],
                ["rewardType" => "FIXED",  "rewardValue" => 1600],
                ["rewardType" => "FIXED",  "rewardValue" => 3300],
                ["rewardType" => "FIXED",  "rewardValue" => 8800]
            ];
        })(),
        "rewardSwitch" => (function() use ($mysqli) {
            $bc = getBonusConfig($mysqli);
            return (bool)$bc['active'];
        })(),
        "buttonShowAmount" => (function() use ($mysqli) {
            $bc = getBonusConfig($mysqli);
            if (!$bc['active']) return null;
            return formatBonusAmount((int)$bc['valor_min']) . '-' . formatBonusAmount((int)$bc['valor_max']);
        })(),
        "applyAppType" => "iOSH5,DesktopOS,AndroidH5,PWA,APK,iOSApp,iOSBookmark,APKRelease",
        "auditMultiple" => $auditMultipleCfg,
        "registerRewardId" => $registerRewardId,
        "registerRewardAmount" => $registerRewardAmount,
        "registerRewardReceiveTime" => $registerRewardReceiveTime
    ];
    sendTrpcResponse($response);
}

if ($path === '/api/frontend/trpc/registerReward.receive') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse(["status" => false, "message" => "Login required"]); 
    }
    
    $stmt = $mysqli->prepare("SELECT id FROM adicao_saldo WHERE id_user = ? AND tipo = 'register_reward'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    if ($stmt->fetch()) {
        $stmt->close();
        sendTrpcResponse(["status" => false, "message" => "Already received"]);
    }
    $stmt->close();

    // Valor aleatório definido pelo admin em config-bonus-cadastro.php
    $bc = getBonusConfig($mysqli);
    $amountCents = ($bc['active'] && $bc['valor_max'] >= $bc['valor_min'])
        ? mt_rand((int)$bc['valor_min'], (int)$bc['valor_max'])
        : 100; // fallback: R$ 1,00
    $amount = round($amountCents / 100, 2);
    $type = 'register_reward';
    $date = date('Y-m-d H:i:s');
    
    $stmt = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("idss", $user['id'], $amount, $type, $date);
    $stmt->execute();
    $insertedId = $stmt->insert_id;
    $stmt->close();
    
    $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $user['id']);
    $stmt->execute();
    $stmt->close();
    
    sendTrpcResponse([
        "status" => true, 
        "awardAmount" => $amount,
        "id" => $insertedId
    ]);
}

if ($path === '/api/frontend/trpc/registerReward.apply') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    
    if ($user) {
        $userId = $user['id'];
        
        // Check if already applied
        if (isset($user['canApplyRegisterReward']) && ($user['canApplyRegisterReward'] == 0 || $user['canApplyRegisterReward'] === false)) {
            sendTrpcResponse(["error" => "Already received"]);
            exit;
        }

        // ── Sorteia valor do cadastro_bonus_config ───────────────────────────
        $amountCents = 0;
        $bcRes = @$mysqli->query("SELECT active, valor_min, valor_max FROM cadastro_bonus_config WHERE id=1");
        if ($bcRes && $bcRow = $bcRes->fetch_assoc()) {
            if ((int)$bcRow['active'] === 1 && (int)$bcRow['valor_max'] >= (int)$bcRow['valor_min'] && (int)$bcRow['valor_min'] > 0) {
                $amountCents = mt_rand((int)$bcRow['valor_min'], (int)$bcRow['valor_max']);
            }
        }
        // Fallback seguro
        if ($amountCents <= 0) $amountCents = 100; // R$ 1,00
        // ────────────────────────────────────────────────────────────────────

        $amount = round($amountCents / 100.0, 2);
        
        // Creditar saldo e marcar como recebido
        $oldBalance = $user['saldo'];
        $newBalance = $oldBalance + $amount;
        $stmtUpdate = $mysqli->prepare("UPDATE usuarios SET saldo = ?, canApplyRegisterReward = 0 WHERE id = ?");
        $stmtUpdate->bind_param("di", $newBalance, $userId);
        
        if ($stmtUpdate->execute()) {
            $stmtUpdate->close();

            // Audit flow (rollover)
            $rolloverMultiple = 1.00;
            $resWRAM = $mysqli->query("SELECT audit_multiple FROM welcome_roulette_settings WHERE id=1");
            if ($resWRAM && $rowWRAM = $resWRAM->fetch_assoc()) {
                $rolloverMultiple = (float)$rowWRAM['audit_multiple'];
                if ($rolloverMultiple <= 0) $rolloverMultiple = 1.00;
            }
            $needFlow = $amount * $rolloverMultiple;
            $stmtAudit = $mysqli->prepare("INSERT INTO audit_flows (user_id, amount, flow_multiple, need_flow, current_flow, status, flow_type, activity_name) VALUES (?, ?, ?, ?, 0, 'notStarted', 'ACTIVITY', 'RegisterReward')");
            $stmtAudit->bind_param("iddd", $userId, $amount, $rolloverMultiple, $needFlow);
            $stmtAudit->execute();
            $stmtAudit->close();
            
            // Log
            $stmtLog = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, 'register_reward', NOW())");
            $stmtLog->bind_param("id", $userId, $amount);
            $stmtLog->execute();
            $stmtLog->close();
            
            sendTrpcResponse([
                "awardAmount"      => $amountCents,
                "doubleMultiplier" => 0,
                "isOpenDouble"     => false,
                "registerRewardId" => 544583489
            ]);
        } else {
            sendTrpcResponse(["error" => "Failed to update balance"]);
        }
    } else {
        sendTrpcResponse(["error" => "Login required"]);
    }
}
if ($path === '/api/frontend/trpc/auth.info') {
    $rotaEncontrada = true;
    $response = [
        "accountRegisterSwitch" => false,
        "accountRegisterShowPhone" => false,
        "accountRegisterPhoneRequired" => false,
        "accountRegisterPhoneValidate" => false,
        "phoneRegisterSwitch" => true,
        "phoneRegisterPhoneValidate" => false,
        "googleRegisterSwitch" => false,
        "captchaSwitch" => "OFF",
        "imageCaptchaSwitch" => "OFF", 
        "imageCaptchaType" => "IMAGE_RESTORE",
        "loginCaptcha" => "OFF",
        "loginImageCaptchaType" => "PUZZLE",
        "loginType" => ["Phone"],
        "needCpf" => true, 
        "needRealName" => false,
        "needBirthday" => false,
        "needRealNameInput" => false,
        "fingerprintVerifySwitch" => "OFF",
        "fingerprintPublicKey" => "",
        "thirdPartyLogin" => "",
        "thirdPartyAuthInfo" => [],
        "regionConfig" => [
             "smsSwitch" => true,
             "registerType" => ["Phone"],
             "currencySymbol" => "fiat"
        ],
        "imageCaptchaSceneId" => "xkatsn8zh",
        "loginImageCaptchaSceneId" => "1p315nygr"
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/tenant.jumpGoogleList') {
    $rotaEncontrada = true;
    $response = [
        "isOpen" => false,
        "isOpenDownloadPageJumpForIos" => false,
        "list" => []
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/auth.login') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $username = $json['username'] ?? '';
    $password = $json['password'] ?? '';
    if (empty($username) || empty($password)) {
        sendApiError(400, "Username and password required");
    }
    $stmt = $mysqli->prepare("SELECT id, password, token, banido FROM usuarios WHERE mobile = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['banido'] == 1) {
             sendApiError(403, "Account banned");
        }
        if ($password === $row['password']) {
            $token = $row['token'];
            $userId = $row['id'];
            setcookie('token_user', $token, time() + (10 * 365 * 24 * 60 * 60), '/');
            $tenantId = 5657408; 
            $responseData = [
                "data" => [
                    "userId" => (string)$userId,
                    "tenantId" => $tenantId,
                    "token" => $token,
                    "giftTrialPlayAmount" => 0,
                    "trialPlayAmountType" => ""
                ]
            ];
            sendTrpcResponse($responseData);
        } else {
            sendApiError(401, "Invalid password");
        }
    } else {
        sendApiError(404, "User not found");
    }
}
if ($path === '/api/frontend/trpc/auth.logout') {
    $rotaEncontrada = true;
    if (isset($_COOKIE['token'])) {
        unset($_COOKIE['token']);
        setcookie('token', '', time() - 3600, '/');
    }
    if (isset($_COOKIE['token_user'])) {
        unset($_COOKIE['token_user']);
        setcookie('token_user', '', time() - 3600, '/');
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    sendTrpcResponse(new stdClass());
}
if ($path === '/api/frontend/trpc/tenant.domainInfo') {
    $rotaEncontrada = true;
    $response = [
        "info" => [
            "tenantId" => 1247858,
            "type" => "app",
            "tenantName" => getConf('nome'),
            "regionId" => 1,
            "regionName" => "Brasil",
            "timezone" => "Etc/GMT+3",
            "currency" => "BRL",
            "language" => "pt-BR",
            "rechargeRatio" => 10000,
            "phoneCode" => "+55",
            "code" => "BR",
            "domian" => getConf('nome_site'),
            "landing" => null,
            "seo" => [
                "title" => getConf('nome_site'),
                "description" => getConf('descricao'),
                "image" => getConf('img_seo')
            ],
            "merchantCy" => "R$"
        ],
        "configList" => [
            "siteName" => getConf('nome_site'),
            "appIcon" => getConfUrl('favicon'),
            "siteLogo" => getConfUrl('logo'),
            "paymentPartnerPic" => "",
            "appLanguage" => ["pt-BR"]
        ],
        "loginConfig" => [
             "allowUserChangePassword" => true,
             "allowChangeAssetPassword" => true,
             "allowChangePhone" => true,
             "allowChangeEmail" => true,
             "allowEmailPhoneLogin" => true
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/tenant.info') {
    $rotaEncontrada = true;
    $response = [
        "id" => 1247858,
        "name" => getConf('nome', ''),
        "enabled" => true,
        "siteName" => getConf('nome_site', ''),
        "appIcon" => getConf('favicon', ''),
        "siteLogo" => getConf('logo', ''),
        "merchantCy" => "R$",
        "region" => [
             "id" => 1,
             "name" => "Brasil",
             "currency" => "BRL",
             "language" => "pt-BR",
             "phoneCode" => "+55"
        ],
        "gamePartnerPic" => "",
        "rewardSwitch" => (function() use ($mysqli) {
            $bc = getBonusConfig($mysqli);
            return (bool)$bc['active'];
        })(),
        "buttonShowAmount" => (function() use ($mysqli) {
            $bc = getBonusConfig($mysqli);
            if (!$bc['active']) return null;
            return formatBonusAmount((int)$bc['valor_min']) . '-' . formatBonusAmount((int)$bc['valor_max']);
        })()
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/agency.config') {
    $rotaEncontrada = true;
    $response = [
        "configList" => [
             "siteName" => getConf('nome_site'),
             "siteUrl" => $WG_BUCKET_SITE,
             "logo" => getConfUrl('logo'),
             "intro" => getConf('intro', ""),
             "advertising_local" => getConf('advertising_local', "")
        ],
        "ossUrl" => "https://upload-oss-4s.f-1-q-h.com"
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/channel.info') {
    $rotaEncontrada = true;
    $response = [
        "config" => [
             "pointType" => "Facebook",
             "domainId" => 0,
             "frontConfig" => json_encode([
                 "android" => ["downloadBtn" => true, "installUrl" => getConf('link_app_android', '')],
                 "ios" => ["downloadBtn" => true, "installUrl" => getConf('link_app_ios', '')]
             ])
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/pusher/channel-auth') {
    $rotaEncontrada = true;
    $socket_id = $_POST['socket_id'] ?? '';
    $channel_name = $_POST['channel_name'] ?? '';
    $user_id = 0;
    if (!empty($_COOKIE['token_user'])) {
        $token = $_COOKIE['token_user'];
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $user_id = $res->fetch_assoc()['id'];
        }
        $stmt->close();
    }
    $channel_data = json_encode([
        "user_id" => (string)$user_id,
        "user_info" => ["tenantId" => 5657408] 
    ]);
    $auth_key = "6efb2c585e6c00c9b895"; 
    $auth_secret = "d0330d476806bb9f6e91bf930711a4426eb94f9c89593947f6fee2a8cc89bf7c"; 
    $signature = hash_hmac('sha256', $socket_id . ':' . $channel_name . ':' . $channel_data, $auth_secret);
    echo json_encode([
        "channel_data" => $channel_data,
        "auth" => $auth_key . ":" . $signature
    ]);
    exit;
}
if ($path === '/api/frontend/pusher/user-auth') {
    $rotaEncontrada = true;
    $socket_id = $_POST['socket_id'] ?? '';
    $user_id = 0;
    if (!empty($_COOKIE['token_user'])) {
        $token = $_COOKIE['token_user'];
        $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $user_id = $res->fetch_assoc()['id'];
        }
        $stmt->close();
    }
    $user_data = json_encode([
        "id" => (string)$user_id,
        "user_info" => ["tenantId" => 5657408]
    ]);
    $auth_key = "6efb2c585e6c00c9b895";
    $auth_secret = "d0330d476806bb9f6e91bf930711a4426eb94f9c89593947f6fee2a8cc89bf7c";
    $signature = hash_hmac('sha256', $socket_id . '::user::' . $user_data, $auth_secret);
    echo json_encode([
        "user_data" => $user_data,
        "auth" => $auth_key . ":" . $signature
    ]);
    exit;
}
if ($path === '/api/frontend/trpc/activity.config') {
    $rotaEncontrada = true;
    $response = [
        "configList" => [
            "tabSort" => [
                "{\"title\":\"all\",\"sort\":1,\"isOpen\":true}",
                "{\"title\":\"ELECTRONIC\",\"sort\":2,\"isOpen\":true}",
                "{\"title\":\"CHESS\",\"sort\":3,\"isOpen\":false}",
                "{\"title\":\"FISHING\",\"sort\":4,\"isOpen\":false}",
                "{\"title\":\"VIDEO\",\"sort\":5,\"isOpen\":false}",
                "{\"title\":\"SPORTS\",\"sort\":6,\"isOpen\":false}",
                "{\"title\":\"LOTTERY\",\"sort\":7,\"isOpen\":false}"
            ]
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/banner.quickEntryList') {
    $rotaEncontrada = true;
    $quickEntryList = [];
    if (isset($mysqli)) {
        $query = "SELECT * FROM floats WHERE status = 1 AND tipo != 3 ORDER BY id DESC";
        $result = $mysqli->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $imageUrl = $row['img'];
                if (!empty($imageUrl) && strpos($imageUrl, 'http') !== 0) {
                     if (strpos($imageUrl, '/') !== 0) {
                         $imageUrl = '/uploads/' . $imageUrl;
                     }
                     $imageUrl = $WG_BUCKET_SITE . $imageUrl;
                }
                $quickEntryList[] = [
                    "activityDetailSelect" => null,
                    "activityId" => 0,
                    "activityType" => "Agency",
                    "id" => (int)$row['id'],
                    "imageUrl" => $imageUrl,
                    "isClose" => true,
                    "sort" => 0,
                    "targetType" => "link",
                    "targetValue" => $row['redirect']
                ];
            }
        }
    }
    sendTrpcResponse($quickEntryList);
}

if ($path === '/api/frontend/trpc/service.listPublic' || $path === '/api/frontend/trpc/service.list') {
    $rotaEncontrada = true;
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    $stmt = $mysqli->prepare("SELECT telegram, whatsapp, atendimento, suporte, support_icon_active FROM config LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    
    $telegram = $config['telegram'] ?? "";
    $whatsapp = $config['whatsapp'] ?? "";
    $atendimento = $config['atendimento'] ?? ($config['suporte'] ?? "");
    $supportIconActive = (int)($config['support_icon_active'] ?? 1);

    $onlineServices = $supportIconActive ? [
        [
            "link" => [
                $atendimento
            ]
        ]
    ] : [];

    $response = [
        "onlineServices" => $onlineServices,
        "services" => [
            [
                "account" => "Siga e receba recompensas",
                "androidUrl" => $telegram,
                "iosUrl" => $telegram,
                "logo" => "https://upload-us.f-1-g-h.com/s4/1753299860772/telegram.png",
                "nickname" => "Canal Oficial do Telegram",
                "pcUrl" => $telegram,
                "sort" => 1,
                "type" => "Telegram",
                "typeId" => 34
            ],
            [
                "account" => "Apoio on-line 7x24 horas",
                "androidUrl" => $telegram,
                "iosUrl" => $telegram,
                "logo" => "https://upload-us.f-1-g-h.com/s4/1753299953232/telegram.png",
                "nickname" => "Serviço Telegram",
                "pcUrl" => $telegram,
                "sort" => 3,
                "type" => "Telegram",
                "typeId" => 34
            ],
            [
                "account" => "Siga e receba recompensas",
                "androidUrl" => $whatsapp,
                "iosUrl" => $whatsapp,
                "logo" => "https://upload-us.f-1-g-h.com/s4/1753300006265/whatsapp.png",
                "nickname" => "Canal oficial do WhatsApp",
                "pcUrl" => $whatsapp,
                "sort" => 2,
                "type" => "Telegram", 
                "typeId" => 34
            ]
        ],
        "types" => [
            [
                "id" => 34,
                "logo" => "https://upload-us.f-1-g-h.com/s4/1753293332058/telegram.png",
                "type" => "Telegram"
            ],
            [
                "id" => 35,
                "logo" => "https://upload-us.f-1-g-h.com/s4/1753293345086/whatsapp.png",
                "type" => "Whatsapp"
            ]
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/announcementMessage.announcementMessage') {
    $rotaEncontrada = true;
    $messages = [];
    if (isset($mysqli)) {
        $query = "SELECT id, titulo as title, content, banner, criado_em as created_at FROM mensagens WHERE status = 1 ORDER BY criado_em DESC";
        $result = $mysqli->query($query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['banner']) && strpos($row['banner'], 'http') !== 0) {
                     if (strpos($row['banner'], '/') !== 0) {
                         $row['banner'] = '/uploads/' . $row['banner'];
                     }
                     $row['banner'] = $WG_BUCKET_SITE . $row['banner'];
                }
                $messages[] = $row;
            }
        }
    }
    sendTrpcResponse($messages);
}
if ($path === '/api/frontend/trpc/activity.redPoint') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/activity.batchAward') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $vipAmount = 0;
    if ($user) {
        $userId = $user['id'];
        $vipLevel = $user['vip'];
        // Coleta todos os bônus VIP pendentes (UPGRADE não coletados)
        $stmtL = $mysqli->prepare("SELECT id_vip, bonus FROM vip_levels WHERE id_vip <= ?");
        $stmtL->bind_param("i", $vipLevel);
        $stmtL->execute();
        $resL = $stmtL->get_result();
        $vipLevels = [];
        while ($r = $resL->fetch_assoc()) $vipLevels[$r['id_vip']] = $r['bonus'];
        $stmtL->close();
        $stmtC = $mysqli->prepare("SELECT vip_level FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'UPGRADE'");
        $stmtC->bind_param("i", $userId);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        $claimed = [];
        while ($r = $resC->fetch_assoc()) $claimed[$r['vip_level']] = true;
        $stmtC->close();
        $mysqli->begin_transaction();
        try {
            foreach ($vipLevels as $lvl => $bonus) {
                if (!isset($claimed[$lvl]) && $lvl > 0 && $bonus > 0) {
                    $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                    $stmtAdd->bind_param("di", $bonus, $userId);
                    $stmtAdd->execute();
                    $stmtAdd->close();
                    $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'UPGRADE', NOW())");
                    $amountCents = $bonus * 100;
                    $stmtLog->bind_param("iid", $userId, $lvl, $amountCents);
                    $stmtLog->execute();
                    $stmtLog->close();
                    $vipAmount += $amountCents;
                }
            }
            $mysqli->commit();
        } catch (Exception $e) {
            $mysqli->rollback();
        }
    }
    sendTrpcResponse([
        'awardInfo' => [],
        'vipInfo'   => ['amount' => $vipAmount],
        'taskInfo'  => ['amount' => 0],
    ]);
}
if ($path === '/api/frontend/trpc/flow.list') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $page = 1;
    $pageSize = 20;
    $flowType = null;
    if (isset($_GET['input'])) {
        $input = json_decode($_GET['input'], true);
        if (isset($input['json'])) {
            $json = $input['json'];
            $page = isset($json['page']) ? (int)$json['page'] : 1;
            $pageSize = isset($json['pageSize']) ? (int)$json['pageSize'] : 20;
            $flowType = isset($json['flowType']) ? $json['flowType'] : null;
        }
    }
    $offset = ($page - 1) * $pageSize;
    $queryWhiteList = [];
    $total = 0;
    $sumNeedFlow = 0;
    $sumCurrentFlow = 0;
    $metaValues = [];
    if ($user) {
        $userId = $user['id'];
        $stmtFlows = $mysqli->prepare("SELECT id, need_flow, current_flow, status, created_at FROM audit_flows WHERE user_id = ? AND status != 'completed' ORDER BY id ASC");
        if ($stmtFlows) {
            $stmtFlows->bind_param("i", $userId);
            $stmtFlows->execute();
            $resFlows = $stmtFlows->get_result();
            while ($flow = $resFlows->fetch_assoc()) {
                $flowId = $flow['id'];
                $needFlow = (float)$flow['need_flow'];
                $currentFlow = (float)$flow['current_flow'];
                $status = $flow['status'];
                $createdAt = $flow['created_at'];
                $stmtBets = $mysqli->prepare("SELECT COALESCE(SUM(bet_money), 0) FROM historico_play WHERE id_user = ? AND status_play = 1 AND created_at >= ?");
                $validBets = 0;
                if ($stmtBets) {
                    $stmtBets->bind_param("is", $userId, $createdAt);
                    $stmtBets->execute();
                    $resBets = $stmtBets->get_result();
                    $validBets = (float)($resBets->fetch_row()[0] ?? 0);
                    $stmtBets->close();
                }
                $newCurrent = 0;
                $newStatus = 'notStarted';
                if ($validBets >= $needFlow) {
                    $newCurrent = $needFlow;
                    $newStatus = 'completed';
                } else {
                    $newCurrent = $validBets;
                    $newStatus = ($newCurrent > 0) ? 'ongoing' : 'notStarted';
                }
                if (abs($newCurrent - $currentFlow) > 0.001 || $status !== $newStatus) {
                    $stmtUpdate = $mysqli->prepare("UPDATE audit_flows SET current_flow = ?, status = ?, updated_at = NOW() WHERE id = ?");
                    if ($stmtUpdate) {
                        $stmtUpdate->bind_param("dsi", $newCurrent, $newStatus, $flowId);
                        $stmtUpdate->execute();
                        $stmtUpdate->close();
                    }
                }
            }
            $stmtFlows->close();
        }
        $whereSql = "WHERE user_id = ?";
        $types = "i";
        $params = [$userId];
        if (!empty($flowType)) {
            $whereSql .= " AND flow_type = ?";
            $types .= "s";
            $params[] = $flowType;
        }
        $countSql = "SELECT COUNT(*) as total, SUM(need_flow) as sumNeed, SUM(current_flow) as sumCurrent FROM audit_flows $whereSql";
        $stmtCount = $mysqli->prepare($countSql);
        if ($stmtCount) {
            $stmtCount->bind_param($types, ...$params);
            $stmtCount->execute();
            $resCount = $stmtCount->get_result();
            if ($row = $resCount->fetch_assoc()) {
                $total = (int)$row['total'];
                $sumNeedFlow = (float)($row['sumNeed'] ?? 0) * 100;
                $sumCurrentFlow = (float)($row['sumCurrent'] ?? 0) * 100;
            }
            $stmtCount->close();
        }
        $dataSql = "SELECT * FROM audit_flows $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmtData = $mysqli->prepare($dataSql);
        if ($stmtData) {
            $typesData = $types . "ii";
            $paramsData = array_merge($params, [$pageSize, $offset]);
            $stmtData->bind_param($typesData, ...$paramsData);
            $stmtData->execute();
            $resData = $stmtData->get_result();
            $index = 0;
            while ($row = $resData->fetch_assoc()) {
                    $activityName = isset($row['activity_name']) && (string)$row['activity_name'] !== '' ? $row['activity_name'] : null;
                    if (!$activityName) {
                        $fType = isset($row['flow_type']) ? strtolower(trim($row['flow_type'])) : '';
                        $activityName = ($fType === 'deposit' || $fType === 'recharge') ? 'Depósito' : 'Bônus';
                    }
                    $queryWhiteList[] = [
                        "flowListId" => (int)$row['id'],
                        "userId" => (int)$row['user_id'],
                        "amount" => (float)$row['amount'] * 100,
                        "flowMultiple" => (float)$row['flow_multiple'],
                        "needFlow" => (float)$row['need_flow'] * 100,
                        "status" => $row['status'],
                        "flowType" => $row['flow_type'],
                        "createTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['created_at'])),
                        "updateTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['updated_at'])),
                        "currentFlow" => (float)$row['current_flow'] * 100,
                        "releaseSetting" => $row['release_setting'],
                        "activityName" => $activityName,
                        "gameLimit" => '{"status":"OFF","limitData":[]}'
                    ];
                $metaValues["queryWhiteList.$index.createTime"] = ["Date"];
                $metaValues["queryWhiteList.$index.updateTime"] = ["Date"];
                $index++;
            }
            $stmtData->close();
        }
    }
    $response = [
        "queryWhiteList" => $queryWhiteList,
        "count" => $total,
        "sumFlow" => [
            "sumNeedFlow" => $sumNeedFlow,
            "sumCurrentFlow" => $sumCurrentFlow
        ]
    ];
    $meta = ["values" => $metaValues];
    sendTrpcResponse($response, $meta);
}
if ($path === '/api/frontend/trpc/flow.details') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $input = getTrpcInput();
        $data = $input['json'] ?? $input;
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        
        $stmt = $mysqli->prepare("SELECT * FROM audit_flows WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
             $activityName = isset($row['activity_name']) && (string)$row['activity_name'] !== '' ? $row['activity_name'] : null;
             if (!$activityName) {
                 $fType = isset($row['flow_type']) ? strtolower(trim($row['flow_type'])) : '';
                 $activityName = ($fType === 'deposit' || $fType === 'recharge') ? 'Depósito' : 'Bônus';
             }
             
             $response = [
                "flowListId" => (int)$row['id'],
                "userId" => (int)$row['user_id'],
                "amount" => (float)$row['amount'] * 100,
                "flowMultiple" => (float)$row['flow_multiple'],
                "needFlow" => (float)$row['need_flow'] * 100,
                "status" => $row['status'],
                "flowType" => $row['flow_type'],
                "createTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['created_at'])),
                "updateTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['updated_at'] ?? $row['created_at'])),
                "currentFlow" => (float)$row['current_flow'] * 100,
                "releaseSetting" => $row['release_setting'] ?? null,
                "activityName" => $activityName,
                "gameLimit" => '{"status":"OFF","limitData":[]}'
             ];
             
             $meta = [
                 "values" => [
                     "createTime" => ["Date"],
                     "updateTime" => ["Date"]
                 ]
             ];
             
             sendTrpcResponse($response, $meta);
        } else {
             sendTrpcResponse(null);
        }
        $stmt->close();
    } else {
        sendTrpcResponse(["error" => "Login required"]);
    }
}
if ($path === '/api/frontend/trpc/vip.list') {
    $rotaEncontrada = true;
    $vipLevelDatas = [];
    $stmt = $mysqli->prepare("SELECT id, id_vip, meta, bonus FROM vip_levels ORDER BY id_vip ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $vipLevelDatas[] = [
                "id" => $row['id'],
                "vipId" => 159,
                "level" => $row['id_vip'],
                "promotionBet" => (float)$row['meta'] * 100,
                "promotionRecharge" => 0,
                "promotionReward" => (float)$row['bonus'] * 100,
                "dailyBet" => (float)$row['meta'] * 0.60 * 100,
                "dailyReward" => (float)$row['bonus'] * 0.55 * 100,
                "weeklyBet" => (float)$row['meta'] * 0.90 * 100,
                "weeklyReward" => (float)$row['bonus'] * 0.05 * 100,
                "monthlyBet" => 0,
                "monthlyReward" => 0,
                "retentionBet" => 0,
                "retentionRecharge" => 0,
                "createTime" => date("Y-m-d\TH:i:s.000\Z"),
                "updateTime" => date("Y-m-d\TH:i:s.000\Z"),
                "lastOperator" => null
            ];
        }
        $stmt->close();
    }
    $totalValidBetAmount = 0;
    $totalRechargeAmount = 0;
    $vipUserReceiveList = [];
    $totalValidBetAmountByDaily = 0;
    $totalValidBetAmountByWeekLy = 0;
    if (isset($_COOKIE['token_user'])) {
        $token = $_COOKIE['token_user'];
        $stmt = $mysqli->prepare("SELECT id, vip FROM usuarios WHERE token = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $userResult = $stmt->get_result();
            if ($userRow = $userResult->fetch_assoc()) {
                $userId = $userRow['id'];
                $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = ?");
                if ($stmtBet) {
                        $stmtBet->bind_param("i", $userId);
                        $stmtBet->execute();
                        $betResult = $stmtBet->get_result();
                        if ($betRow = $betResult->fetch_assoc()) {
                            $totalValidBetAmount = (float)($betRow['total_apostado'] ?? 0) * 100;
                        }
                        $stmtBet->close();
                        $totalValidBetAmountByDaily = 0;
                $stmtBetDaily = $mysqli->prepare("SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = ? AND DATE(created_at) = CURDATE()");
                if ($stmtBetDaily) {
                    $stmtBetDaily->bind_param("i", $userId);
                    $stmtBetDaily->execute();
                    $betResult = $stmtBetDaily->get_result();
                    if ($betRow = $betResult->fetch_assoc()) {
                        $totalValidBetAmountByDaily = (float)($betRow['total_apostado'] ?? 0) * 100;
                    }
                    $stmtBetDaily->close();
                }
                $totalValidBetAmountByWeekLy = 0;
                $stmtBetWeekly = $mysqli->prepare("SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
                if ($stmtBetWeekly) {
                    $stmtBetWeekly->bind_param("i", $userId);
                    $stmtBetWeekly->execute();
                    $betResult = $stmtBetWeekly->get_result();
                    if ($betRow = $betResult->fetch_assoc()) {
                        $totalValidBetAmountByWeekLy = (float)($betRow['total_apostado'] ?? 0) * 100;
                    }
                    $stmtBetWeekly->close();
                }
                        $currentVipLevel = $userRow['vip'];
                        $newVipLevel = $currentVipLevel;
                        foreach ($vipLevelDatas as $vData) {
                            if ($totalValidBetAmount >= $vData['promotionBet'] && $vData['level'] > $newVipLevel) {
                                $newVipLevel = $vData['level'];
                            }
                        }
                        if ($newVipLevel > $currentVipLevel) {
                            $stmtUpdate = $mysqli->prepare("UPDATE usuarios SET vip = ? WHERE id = ?");
                            $stmtUpdate->bind_param("ii", $newVipLevel, $userId);
                            $stmtUpdate->execute();
                            $stmtUpdate->close();
                            $currentVipLevel = $newVipLevel;
                        }
                        $claimedRewards = [];
                        $stmtClaimed = $mysqli->prepare("SELECT vip_level FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'UPGRADE'");
                        if ($stmtClaimed) {
                            $stmtClaimed->bind_param("i", $userId);
                            $stmtClaimed->execute();
                            $resClaimed = $stmtClaimed->get_result();
                            while ($rowC = $resClaimed->fetch_assoc()) {
                                $claimedRewards[$rowC['vip_level']] = true;
                            }
                            $stmtClaimed->close();
                        }
                        $hasUpgradeAvailable = false;
                        foreach ($vipLevelDatas as $vData) {
                            $lvl = $vData['level'];
                            if ($lvl <= 0) continue;
                            if ($currentVipLevel >= $lvl) {
                                if ($vData['promotionReward'] > 0 && !isset($claimedRewards[$lvl])) {
                                    $hasUpgradeAvailable = true;
                                    break;
                                }
                            }
                        }
                        if ($hasUpgradeAvailable) {
                            $vipUserReceiveList[] = "PROMOTION";
                        }
                        if ($currentVipLevel > 0) {
                            $today = date('Y-m-d');
                            $stmtDailyClaimed = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'DAILY' AND DATE(created_at) = CURDATE()");
                            $stmtDailyClaimed->bind_param("i", $userId);
                            $stmtDailyClaimed->execute();
                            $dailyAlreadyClaimed = $stmtDailyClaimed->get_result()->num_rows > 0;
                            $stmtDailyClaimed->close();
                            if (!$dailyAlreadyClaimed) {
                                $currentVipData = null;
                                foreach ($vipLevelDatas as $vData) {
                                    if ($vData['level'] == $currentVipLevel) {
                                        $currentVipData = $vData;
                                        break;
                                    }
                                }
                                if ($currentVipData && $currentVipData['dailyReward'] > 0) {
                                    $dailyBetTarget = $currentVipData['dailyBet'];
                                    if ($totalValidBetAmountByDaily >= $dailyBetTarget) {
                                        $vipUserReceiveList[] = "DAILY";
                                    }
                                }
                            }
                        }
                        if ($currentVipLevel > 0) {
                            $stmtWeeklyClaimed = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'WEEKLY' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
                            $stmtWeeklyClaimed->bind_param("i", $userId);
                            $stmtWeeklyClaimed->execute();
                            $weeklyAlreadyClaimed = $stmtWeeklyClaimed->get_result()->num_rows > 0;
                            $stmtWeeklyClaimed->close();
                            if (!$weeklyAlreadyClaimed) {
                                $currentVipData = null;
                                foreach ($vipLevelDatas as $vData) {
                                    if ($vData['level'] == $currentVipLevel) {
                                        $currentVipData = $vData;
                                        break;
                                    }
                                }
                                if ($currentVipData && $currentVipData['weeklyReward'] > 0) {
                                    $weeklyBetTarget = $currentVipData['weeklyBet'];
                                    if ($totalValidBetAmountByWeekLy >= $weeklyBetTarget) {
                                        $vipUserReceiveList[] = "WEEKLY";
                                    }
                                }
                            }
                        }
            }
            }
            $stmt->close();
        }
    }
    $data = [
        "receiveRule" => "RESERVE",
        "auditMultiple" => 3,
        "auditLimitGame" => '{"status":"ON","limitData":[{"gameType":"CHESS","platformData":[{"platformId":25,"gameData":[{"gameId":2885}]}]}]}',
        "promotionStatus" => true,
        "retentionStatus" => true,
        "dailyStatus" => true,
        "weeklyStatus" => true,
        "monthlyStatus" => false,
        "vipLevelDatas" => $vipLevelDatas,
        "vipUserReceiveList" => $vipUserReceiveList,
        "totalValidBetAmount" => $totalValidBetAmount,
        "totalRechargeAmount" => $totalRechargeAmount,
        "totalValidBetAmountByDaily" => $totalValidBetAmountByDaily,
        "totalValidBetAmountByWeekLy" => $totalValidBetAmountByWeekLy,
        "totalValidBetAmountByMonthly" => null,
        "rechargeDaysByUserLimit" => 5
    ];
    $logFile = __DIR__ . '/vip_debug.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " vip.list | user=" . ($userId ?? 'none') . " | vip=$currentVipLevel | dailyBet=$totalValidBetAmountByDaily | receiveList=" . implode(',', $vipUserReceiveList) . "\n", FILE_APPEND);
    sendTrpcResponse($data, [
        "values" => [
            "vipLevelDatas.0.createTime" => ["Date"],
            "vipLevelDatas.0.updateTime" => ["Date"]
        ]
    ]);
}
if ($path === '/api/frontend/trpc/vip.receive') {
    $rotaEncontrada = true;
    // Garante que a coluna created_at existe com DEFAULT correto
    @$mysqli->query("ALTER TABLE vip_rewards_log ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $level = isset($json['level']) ? intval($json['level']) : 0;
    $receiveType = isset($json['receiveType']) ? $json['receiveType'] : '';
    $user = getCurrentUser($mysqli);
    $logFile = __DIR__ . '/vip_debug.log';
    $rawBody = file_get_contents('php://input');
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " vip.receive | user=" . ($user['id'] ?? 'none') . " | vip=" . ($user['vip'] ?? '?') . " | receiveType=" . $receiveType . " | level=" . $level . " | rawBody=" . $rawBody . "\n", FILE_APPEND);
    if ($user) {
        if ($receiveType === 'DAILY') {
             $today = date('Y-m-d');
             $stmtC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'DAILY' AND DATE(created_at) = CURDATE()");
             $stmtC->bind_param("i", $user['id']);
             $stmtC->execute();
             if ($stmtC->get_result()->num_rows > 0) {
                 sendApiError(400, "Already claimed today");
             }
             $stmtC->close();
             $vipLevel = $user['vip'];
             if ($vipLevel <= 0) {
                  sendApiError(400, "VIP Level 0 not eligible");
             }
             $stmtL = $mysqli->prepare("SELECT meta, bonus FROM vip_levels WHERE id_vip = ?");
             $stmtL->bind_param("i", $vipLevel);
             $stmtL->execute();
             $resL = $stmtL->get_result();
             if ($rowL = $resL->fetch_assoc()) {
                  $dailyBetTarget = (float)$rowL['meta'] * 0.60;
                  $dailyReward = (float)$rowL['bonus'] * 0.55;
                  // Se meta=0, exige pelo menos R$1 de aposta para evitar coleta infinita
                  if ($dailyBetTarget <= 0) {
                      sendApiError(400, "Daily reward not configured for this VIP level");
                  }
                  $currentDailyBet = 0;
                  $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND DATE(created_at) = CURDATE()");
                  $stmtBet->bind_param("i", $user['id']);
                  $stmtBet->execute();
                  if ($rowB = $stmtBet->get_result()->fetch_assoc()) {
                      $currentDailyBet = (float)$rowB['total'];
                  }
                  $stmtBet->close();
                  if ($currentDailyBet < $dailyBetTarget) {
                      sendApiError(400, "Daily bet target not met");
                  }
                  $amountCents = $dailyReward * 100;
                  $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                  $stmtAdd->bind_param("di", $dailyReward, $user['id']);
                  $stmtAdd->execute();
                  $stmtAdd->close();
                  $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'DAILY', NOW())");
                  $stmtLog->bind_param("iid", $user['id'], $vipLevel, $amountCents);
                  $stmtLog->execute();
                  $stmtLog->close();
                  sendTrpcResponse([
                        "status" => true,
                        "amount" => $amountCents,
                        "type" => "BALANCE"
                    ]);
             } else {
                 sendApiError(400, "VIP data not found");
             }
             $stmtL->close();
        } elseif ($receiveType === 'WEEKLY') {
             $yearWeek = date('oW'); 
             $stmtC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'WEEKLY' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
             $stmtC->bind_param("i", $user['id']);
             $stmtC->execute();
             if ($stmtC->get_result()->num_rows > 0) {
                 sendApiError(400, "Already claimed this week");
             }
             $stmtC->close();
             $vipLevel = $user['vip'];
             if ($vipLevel <= 0) {
                  sendApiError(400, "VIP Level 0 not eligible");
             }
             $stmtL = $mysqli->prepare("SELECT meta, bonus FROM vip_levels WHERE id_vip = ?");
             $stmtL->bind_param("i", $vipLevel);
             $stmtL->execute();
             $resL = $stmtL->get_result();
             if ($rowL = $resL->fetch_assoc()) {
                  $weeklyBetTarget = (float)$rowL['meta'] * 0.90;
                  $weeklyReward = (float)$rowL['bonus'] * 0.05;
                  $currentWeeklyBet = 0;
                  $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
                  $stmtBet->bind_param("i", $user['id']);
                  $stmtBet->execute();
                  if ($rowB = $stmtBet->get_result()->fetch_assoc()) {
                      $currentWeeklyBet = (float)$rowB['total'];
                  }
                  $stmtBet->close();
                  if ($currentWeeklyBet < $weeklyBetTarget) {
                      sendApiError(400, "Weekly bet target not met");
                  }
                  $amountCents = $weeklyReward * 100;
                  $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                  $stmtAdd->bind_param("di", $weeklyReward, $user['id']);
                  $stmtAdd->execute();
                  $stmtAdd->close();
                  $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'WEEKLY', NOW())");
                  $stmtLog->bind_param("iid", $user['id'], $vipLevel, $amountCents);
                  $stmtLog->execute();
                  $stmtLog->close();
                  sendTrpcResponse([
                        "status" => true,
                        "amount" => $amountCents,
                        "type" => "BALANCE"
                    ]);
             } else {
                 sendApiError(400, "VIP data not found");
             }
             $stmtL->close();
        } elseif ($level > 0) {
        if ($user['vip'] >= $level) {
            $stmtC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND vip_level = ? AND reward_type = 'UPGRADE'");
            $stmtC->bind_param("ii", $user['id'], $level);
            $stmtC->execute();
            $resC = $stmtC->get_result();
            if ($resC->num_rows == 0) {
                 $stmtL = $mysqli->prepare("SELECT bonus FROM vip_levels WHERE id_vip = ?");
                 $stmtL->bind_param("i", $level);
                 $stmtL->execute();
                 $resL = $stmtL->get_result();
                 if ($rowL = $resL->fetch_assoc()) {
                     $amountCents = (float)$rowL['bonus'] * 100;
                     $amountReais = (float)$rowL['bonus'];
                     $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                     $stmtAdd->bind_param("di", $amountReais, $user['id']);
                     $stmtAdd->execute();
                     $stmtAdd->close();
                     $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'UPGRADE', NOW())");
                     $stmtLog->bind_param("iid", $user['id'], $level, $amountCents);
                     $stmtLog->execute();
                    $stmtLog->close();
                    sendTrpcResponse([
                        "status" => true,
                        "amount" => $amountCents,
                        "type" => "BALANCE"
                    ]);
                } else {
                     sendApiError(400, "Level not found");
                 }
                 $stmtL->close();
            } else {
                 sendApiError(400, "Already claimed");
            }
            $stmtC->close();
        } else {
            sendApiError(400, "Not eligible");
        }
    }
    } else {
        sendApiError(401, "Unauthorized");
    }
}

if ($path === '/api/frontend/trpc/registerReward.get') {
    $rotaEncontrada = true;
    $response = [
        "awardAmount" => 2000,
        "isOpenDouble" => false,
        "registerRewardId" => 1
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/registerReward.receive') {
    $rotaEncontrada = true;
    sendTrpcResponse(['status' => 'SUCCESS']);
}
if ($path === '/api/frontend/trpc/activity.list' || $path === '/api/frontend/trpc/activity.listPublic') {
    $rotaEncontrada = true;
    $dbPromos = [];
    $__colChk = $mysqli->query("SHOW COLUMNS FROM promocoes LIKE 'activity_type'");
    if ($__colChk && $__colChk->num_rows === 0) {
        $mysqli->query("ALTER TABLE promocoes ADD COLUMN activity_type VARCHAR(50) DEFAULT NULL");
    }
    $stmt = $mysqli->prepare("SELECT id, titulo, img, status, activity_type FROM promocoes");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dbPromos[$row['id']] = $row;
        }
        $stmt->close();
    }
// ── Ler configurações da Mina Misteriosa do banco ──────────────────────────
$minaActive = 1;
$minaHours = [];
$minaRewardConfig = [["amountType"=>"RECHARGE","amount"=>10000,"min"=>0,"max"=>100],["amountType"=>"RECHARGE","amount"=>20000,"min"=>0,"max"=>200]];
$minaDailyMaxCount = 6;
$mysqli->query("CREATE TABLE IF NOT EXISTS mina_config (
    id INT PRIMARY KEY DEFAULT 1,
    active TINYINT(1) NOT NULL DEFAULT 1,
    daily_max_count INT NOT NULL DEFAULT 6,
    reward_min INT NOT NULL DEFAULT 10000,
    reward_max INT NOT NULL DEFAULT 20000,
    reward_recharge_min INT NOT NULL DEFAULT 0,
    reward_recharge_max INT NOT NULL DEFAULT 200,
    hours_json TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$_mcRes = $mysqli->query("SELECT * FROM mina_config WHERE id=1");
if ($_mcRes && $_mcRow = $_mcRes->fetch_assoc()) {
    $minaActive = (int)$_mcRow['active'];
    $minaDailyMaxCount = (int)$_mcRow['daily_max_count'];
    $minaRewardConfig = [
        ["amountType"=>"RECHARGE","amount"=>(int)$_mcRow['reward_min'],"min"=>(int)$_mcRow['reward_recharge_min'],"max"=>(int)$_mcRow['reward_recharge_max']],
        ["amountType"=>"RECHARGE","amount"=>(int)$_mcRow['reward_max'],"min"=>(int)$_mcRow['reward_recharge_min'],"max"=>(int)$_mcRow['reward_recharge_max']]
    ];
    if (!empty($_mcRow['hours_json'])) {
        $minaHours = json_decode($_mcRow['hours_json'], true) ?: [];
    }
} else {
    $mysqli->query("INSERT IGNORE INTO mina_config (id,active,daily_max_count,reward_min,reward_max,reward_recharge_min,reward_recharge_max,hours_json) VALUES (1,1,6,10000,20000,0,200,NULL)");
}
if (!$minaHours) {
    $minaHours = [
        ["uuid"=>"2617ff3ae88640e2a519a69b76ea8238","hour"=>2,"durationIn"=>59],
        ["uuid"=>"c2cffcc3a0704a28ad8190f362e44313","hour"=>3,"durationIn"=>59],
        ["uuid"=>"3f435034077b428cb3ea3509850f2d45","hour"=>6,"durationIn"=>59],
        ["uuid"=>"98df1968ea874bcdba7f041d1c087b95","hour"=>7,"durationIn"=>59],
        ["uuid"=>"e6a65d40cd1549478bda72a2e06f0e9b","hour"=>8,"durationIn"=>59],
        ["uuid"=>"b069e48c87a94a05b5f477a3928ff35a","hour"=>9,"durationIn"=>60],
        ["uuid"=>"66790ca35d7949aa919204f43f08b78b","hour"=>10,"durationIn"=>60],
        ["uuid"=>"3e77b6768b2c4408b0904d5dab4492c7","hour"=>11,"durationIn"=>60],
        ["uuid"=>"320000893e1b4da8aa1505f3fe9304b8","hour"=>12,"durationIn"=>60],
        ["uuid"=>"9c8677e62bb7432e9a548ef042c27b11","hour"=>13,"durationIn"=>60],
        ["uuid"=>"5c88163c9bb143f8a7207b7c7f5f1edc","hour"=>14,"durationIn"=>60],
        ["uuid"=>"aa3fb50f84a449d995b499256219659e","hour"=>17,"durationIn"=>60],
        ["uuid"=>"8934bde757e54f058e1fe995ec81913b","hour"=>19,"durationIn"=>60],
        ["uuid"=>"82c8b00981c940f1bafe9013def81cef","hour"=>20,"durationIn"=>60],
        ["uuid"=>"2ae93c3b93424ce1b15ec274676d90bd","hour"=>21,"durationIn"=>60],
        ["uuid"=>"caaeb3b1b44245279069177d1764ca7b","hour"=>22,"durationIn"=>60],
        ["uuid"=>"80b61caf0cf74526b549e589ebe106e6","hour"=>23,"durationIn"=>60],
        ["uuid"=>"fa6aad0db9834df7beb48ed572e92840","hour"=>0,"durationIn"=>60]
    ];
}
$minaCondition = json_encode([
    "uuid" => "2c2256d7ca52458f98f419811a71670b",
    "timeConfig" => $minaHours,
    "setting" => [
        "roundMaxAmount" => 100000,
        "roundMaxAmountShow" => 52000,
        "maxAmount" => 10000,
        "maxAmountShow" => 131400,
        "type" => "RECHARGE",
        "rechargeAmount" => 0,
        "betAmount" => 0,
        "awardType" => "FIXED_AMOUNT",
        "amountType" => "RECHARGE",
        "timeRangeType" => "TODAY",
        "rewardConfig" => $minaRewardConfig,
        "dailyMaxCount" => $minaDailyMaxCount
    ],
    "appIconUrl" => "https://upload-sys-pics.f-1-g-h.com/activityStar/redPacket/redPacket_5.png",
    "mainMediaShare" => "OFF"
]);
// ────────────────────────────────────────────────────────────────────────────
    $activityList = array (
  0 => 
  array (
    'id' => 3288,
    'name' => '测试外部链接跳转',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"uuid":"3b5a8183dfc8431487bc484a8ca83c46","content":"","isShowApply":false,"jumpType":"LINK","target":{"type":"internal","targetValue":{"type":"promotion","info":"string"}}}',
    'category' => 'all',
    'type' => 'Custom',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_3.jpg',
    'sort' => 99999,
    'showStartTime' => '2025-08-25T17:36:45.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-08-25T17:32:32.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 1,
  ),
  2 => 
  array (
    'id' => 495,
    'name' => '代理宝箱',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"uuid":"2b129f6c1fd54aab97b5be57ca371ee0","validUsers":{"firstRechargeAmount":{"amount":0,"status":"OFF"},"recharge":{"amount":3000,"status":"ON"},"bet":{"amount":3000,"status":"ON"},"rechargeDays":{"days":0,"status":"OFF"},"rechargeCount":{"count":0,"status":"OFF"},"type":"ALL","userLimit":"NEW_REGISTER"},"limitStats":{"limitIP":10000,"limitDevice":10000},"rewardType":"FIXED","displayMode":"BOX","isShow":true,"rewardConfig":[{"uuid":"34e60925c45244c4b94b0f366f1a944b","userCount":1,"min":0,"max":2500},{"uuid":"138dd5c0a1ee44cfbfa33d7c9932d875","userCount":2,"min":0,"max":5000},{"uuid":"5f87a9fac3d64a03b4a4653f6ecca424","userCount":3,"min":0,"max":6000},{"uuid":"d1709713d9164dfc8bb8d98c11b863c5","userCount":4,"min":0,"max":1500},{"uuid":"b937f927672e4667a6842742b16ee184","userCount":5,"min":0,"max":8800},{"uuid":"865767e0ac7841ab85c9d60e7181be69","userCount":6,"min":0,"max":8800},{"uuid":"4af4150a5ceb4090b89e445ea185ec69","userCount":7,"min":0,"max":8800},{"uuid":"8c7ddd1e91c14f439c33a4288e2b3de3","userCount":8,"min":0,"max":8800}],"gameLimitType":"SELECT","gameLimit":[{"gameType":"ELECTRONIC","platformData":[{"platformId":23,"gameData":[]},{"platformId":25,"gameData":[]},{"platformId":26,"gameData":[]},{"platformId":29,"gameData":[]},{"platformId":33,"gameData":[]},{"platformId":35,"gameData":[]},{"platformId":36,"gameData":[]},{"platformId":37,"gameData":[]},{"platformId":38,"gameData":[]},{"platformId":39,"gameData":[]},{"platformId":40,"gameData":[]},{"platformId":41,"gameData":[]},{"platformId":44,"gameData":[]}]},{"gameType":"CHESS","platformData":[{"platformId":25,"gameData":[]}]}]}',
    'category' => 'all',
    'type' => 'Agency',
    'bannerBackground' => 'https://upload-us.f-1-g-h.com/s1/1760943813456/sm.png',
    'sort' => 999,
    'showStartTime' => '2025-01-21T03:00:00.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{"highestReward":88,"multiplier":1,"limitType":"ON","limitData":"Card Game: All games; Slot: Tada, CQ9, RUBYPLAY, inout, PLAYSON, POPOK, FaChai, G759, CP; Fishing events: All games; Live: All games","validBetStatus":"ON","limitAgencyType":"SELECT","limitAgencyData":"Card Game: All games; Slot: PG, Tada, CQ9, JDB, Spribe, PP, CP, G759, FaChai, POPOK, PLAYSON, RUBYPLAY, FASTSPIN","awardExpiredDays":0}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2026-01-11T09:07:39.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 1,
  ),
  6 => 
  array (
    'id' => 3036,
    'name' => '充值大酬宾',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"activityDay":4,"rewardAuditType":"Gift","homeEntry":false,"activityConfig":[{"uuid":"2658e9917efe4fb488a80f4dd4ba60be","rechargeAmount":20000,"rewardAmount":1000,"auditMultiple":2,"virtualRewardRatio":0},{"uuid":"12db948322f541768a55104d35cc64b2","rechargeAmount":30000,"rewardAmount":2000,"auditMultiple":3,"virtualRewardRatio":0}],"appIconUrl":"https://upload-sys-pics.f-1-g-h.com/activityStar/rechargePromotion/rechargePromotion_1.png"}',
    'category' => 'all',
    'type' => 'RechargePromotion',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_3.jpg',
    'sort' => 1,
    'showStartTime' => '2025-09-06T03:00:00.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{"highestReward":20,"multiplier":2,"utcValue":"Etc/GMT+3","ruleTimeShow":"2025-10-12 12:56:32 - 2025-12-07 23:59:59","timeType":"PERMANENT","limitType":"ON","limitData":"Slot: PG, Tada, CQ9, , JDB, Spribe, PP, CP, FaChai, POPOK","expiredAwardType":"AUTO","activityDay":4,"awardType":"BALANCE","awardExpiredDays":0,"rewardAuditType":"Gift"}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-10-12T15:56:32.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 1,
  ),
  9 => 
  array (
    'id' => 637,
    'name' => '助力领现金132456',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"cycle":5,"awardList":[{"uuid":"b59e1b54342441a1be5ae1ca67c4b2b7","type":"rangeAmount","amount":0,"weight":100},{"uuid":"1ffeff4741f242ccb07dca8771401d8d","type":"fixedAmount","amount":5000,"weight":0},{"uuid":"d2a1098bc3e94cd2a5e19eef068e975d","type":"fixedAmount","amount":18800,"weight":0},{"uuid":"c6134b2d7cd743dfa3bce96c51b22e19","type":"fixedAmount","amount":10000,"weight":0},{"uuid":"6b21e455908a4e68a0091f947bf1de69","type":"fixedAmount","amount":100000,"weight":0},{"uuid":"28d804b7cbbf47008ab7f04c7c28196a","type":"bonus","amount":10800,"weight":0}],"freeDrawCount":2,"ipLimit":50,"condition":[{"uuid":"572c6b475971427293099bb65a8a817e","round":1,"amount":10000,"directCount":1,"directBet":1000,"directRecharge":2000,"firstDrawMinAmount":9810,"firstDrawMaxAmount":9900}],"shareDomain":"www.55zaq.cyou","domainImg":"","invitePhone":"https://upload-oss.f-1-g-h.com/78e5e4a8-1416-4e27-a392-665c100e801e.csv"}',
    'category' => 'all',
    'type' => 'AssistanceCash',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_7.jpg',
    'sort' => 9999,
    'showStartTime' => '2025-05-02T03:00:00.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'CUSTOM',
    'rule' => '直属累计充值50，投注50
4. Os bônus emitidos neste evento podem ser sacados sem apostas;
5. Esta atividade é limitada às operações humanas normais do proprietário da conta. É proibido alugar, usar plug-ins, robôs, jogar com contas diferentes, escovação mútua, arbitragem, interfaces, protocolos, exploração de brechas, controle de grupo ou outras técnicas. significa participar, caso contrário será cancelado ou terá recompensas deduzidas, congeladas ou mesmo colocadas na lista negra;
6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final deste evento.',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-06-19T18:21:36.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => 'style_0',
    'statusIndex' => 1,
    'top' => 2,
  ),
  10 => 
  array (
    'id' => 494,
    'name' => '实时返水',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"gameTypes":["ELECTRONIC"],"rechargeLimit":0,"configType":"validBet","validBetRebate":[{"validBet":100,"platformByGameTypeGroupList":[{"gameType":"CHESS","platformRebateList":[{"platformId":25,"platformName":"Tada","rebateRatio":10},{"platformId":0,"platformName":"新平台默认","rebateRatio":10}]},{"gameType":"ELECTRONIC","platformRebateList":[{"platformId":23,"platformName":"PG","rebateRatio":10},{"platformId":25,"platformName":"Tada","rebateRatio":10},{"platformId":26,"platformName":"CQ9","rebateRatio":10},{"platformId":29,"platformName":"JDB","rebateRatio":10},{"platformId":0,"platformName":"新平台默认","rebateRatio":10},{"platformId":33,"platformName":"Spribe","rebateRatio":10},{"platformId":35,"platformName":"PP","rebateRatio":10},{"platformId":36,"platformName":"CP","rebateRatio":10},{"platformId":37,"platformName":"G759","rebateRatio":10},{"platformId":38,"platformName":"FaChai","rebateRatio":10},{"platformId":39,"platformName":"POPOK","rebateRatio":10},{"platformId":40,"platformName":"PLAYSON","rebateRatio":10},{"platformId":41,"platformName":"RUBYPLAY","rebateRatio":10},{"platformId":44,"platformName":"FASTSPIN","rebateRatio":10},{"platformId":43,"platformName":"inout","rebateRatio":10}]},{"gameType":"FISHING","platformRebateList":[{"platformId":25,"platformName":"Tada","rebateRatio":10},{"platformId":0,"platformName":"新平台默认","rebateRatio":10}]},{"gameType":"VIDEO","platformRebateList":[{"platformId":42,"platformName":"Evolution","rebateRatio":10},{"platformId":0,"platformName":"新平台默认","rebateRatio":10}]}]},{"validBet":200,"platformByGameTypeGroupList":[{"gameType":"CHESS","platformRebateList":[{"platformId":25,"platformName":"Tada","rebateRatio":100},{"platformId":0,"platformName":"新平台默认","rebateRatio":100}]},{"gameType":"ELECTRONIC","platformRebateList":[{"platformId":23,"platformName":"PG","rebateRatio":100},{"platformId":25,"platformName":"Tada","rebateRatio":100},{"platformId":26,"platformName":"CQ9","rebateRatio":100},{"platformId":29,"platformName":"JDB","rebateRatio":100},{"platformId":33,"platformName":"Spribe","rebateRatio":100},{"platformId":35,"platformName":"PP","rebateRatio":100},{"platformId":36,"platformName":"CP","rebateRatio":100},{"platformId":0,"platformName":"新平台默认","rebateRatio":100},{"platformId":37,"platformName":"G759","rebateRatio":100},{"platformId":38,"platformName":"FaChai","rebateRatio":100},{"platformId":39,"platformName":"POPOK","rebateRatio":100},{"platformId":40,"platformName":"PLAYSON","rebateRatio":100},{"platformId":41,"platformName":"RUBYPLAY","rebateRatio":100},{"platformId":44,"platformName":"FASTSPIN","rebateRatio":100},{"platformId":43,"platformName":"inout","rebateRatio":100}]},{"gameType":"FISHING","platformRebateList":[{"platformId":25,"platformName":"Tada","rebateRatio":100},{"platformId":0,"platformName":"新平台默认","rebateRatio":100}]},{"gameType":"VIDEO","platformRebateList":[{"platformId":42,"platformName":"Evolution","rebateRatio":100},{"platformId":0,"platformName":"新平台默认","rebateRatio":100}]}]}],"vipLevelRebate":[],"excludeGame":[{"gameType":"ELECTRONIC","platformId":23,"platformName":"PG","gameId":2196,"gameName":"虎虎生财 (Fortune Tiger)"},{"gameType":"ELECTRONIC","platformId":26,"platformName":"CQ9","gameId":3024,"gameName":"直式跳更高 (Jump Higher mobile)"}]}',
    'category' => 'all',
    'type' => 'Rebate',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_3.jpg',
    'sort' => 89,
    'showStartTime' => '2024-06-26T12:04:15.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{"lowestRebateBet":1,"rechargeLimit":0,"highestReward":"1%","multiplier":0,"awardType":"ACTIVITY","rebateType":"NORECHARGE","utcValue":"Etc/GMT+3","ruleTimeShow":"2025-07-19 04:49:10 - 2025-12-23 23:59:59","timeType":"PERMANENT","limitType":"ON","limitData":"Slot: PG, Tada, JDB, Spribe, PP, CP, G759, FaChai, POPOK","awardExpiredDays":8}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-07-19T07:49:10.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 2,
   ),
  14 => 
  array (
    'id' => 3119,
    'name' => '首提返利',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"joinType":"NEW_REGISTER","appShowType":"POP-UP","popUpRemark":"测试一下","giveRatio":500,"isOpenDouble":true,"doubleMultiplier":500,"doubleAuditMd5":"","doubleAuditMultiple":1,"doubleType":"FIXED","doubleRechargeConfig":[]}',
    'category' => 'all',
    'type' => 'FirstWithdrawRebate',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_2.jpg',
    'sort' => 1,
    'showStartTime' => '2025-08-08T02:12:19.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-08-08T02:07:20.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 2,
  ),
  16 => 
  array (
    'id' => 2098,
    'name' => '新窗口助力领现金链接',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"uuid":"73f555f894134ee08b89010300b427d5","content":"https://upload-us.f-1-g-h.com/s1/1753309146360/curstomActivity.txt","isShowApply":false,"jumpType":"DETAIL","target":{"type":"external","targetValue":""}}',
    'category' => 'all',
    'type' => 'Custom',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_3.jpg',
    'sort' => 1,
    'showStartTime' => '2024-12-13T16:18:26.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2024-12-13T16:14:29.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 2,
  ),
  19 => 
  array (
    'id' => 1887,
    'name' => 'CHECK - IN',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"joinType":"ALL","signInType":"CONTINUOUS","cycleType":"CYCLE","signInCycleDay":7,"rechargeSuccessPopup":"ON","rewardAudit":"ON","rewardAuditMultiple":0,"appShowRewardAmount":"ON","appShowExtraRewardAmount":"ON","ipLimit":500,"rewardConfig":{"rewardType":"VIP_LEVEL_REWARDS","configList":[{"vipLevel":[667,5215,5216,5217,5218],"config":[{"day":1,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":0,"validBet":100,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":2,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":3,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":4,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":5,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":6,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""},{"day":7,"amountType":"FIXED","amountMax":10000,"amountMin":1000,"rechargeAmount":5000,"validBet":2000,"extraReward":500,"iconType":"DEFAULT","icon":""}]}]}}',
    'category' => 'all',
    'type' => 'SignIn',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_2.jpg',
    'sort' => 1,
    'showStartTime' => '2024-11-26T07:10:20.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{"highestReward":100,"multiplier":1,"utcValue":"Etc/GMT+3","ruleTimeShow":"2025-08-27 10:00:36 - 2025-11-12 23:59:59","timeType":"PERMANENT","limitType":"OFF","limitData":"","platforms":"iOSH5,AndroidH5,PWA,APK,iOSApp,iOSBookmark,APKRelease,DesktopOS","signInType":"CONTINUOUS","awardExpiredDays":0,"rewardAuditType":"Gift"}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2026-01-07T08:37:07.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => 'style_2',
    'statusIndex' => 1,
    'top' => 2,
  ),
  20 => 
  array (
    'id' => 3184,
    'name' => '首充亏损',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"joinType":"NEW_REGISTER","appShowType":"POP-UP","popUpRemark":"首充亏损返利","lossRatio":5000,"giveRatio":2000,"isOpenDouble":true,"doubleMultiplier":0,"doubleAuditMd5":"","doubleAuditMultiple":1,"doubleType":"RECHARGE","doubleRechargeConfig":[{"rechargeAmount":1000,"rechargeDoubleMultiplier":200,"rechargeDoubleVirtualRewardRate":0},{"rechargeAmount":2000,"rechargeDoubleMultiplier":500,"rechargeDoubleVirtualRewardRate":0},{"rechargeAmount":3000,"rechargeDoubleMultiplier":600,"rechargeDoubleVirtualRewardRate":0},{"rechargeAmount":4000,"rechargeDoubleMultiplier":700,"rechargeDoubleVirtualRewardRate":0},{"rechargeAmount":8000,"rechargeDoubleMultiplier":800,"rechargeDoubleVirtualRewardRate":0},{"rechargeAmount":10000,"rechargeDoubleMultiplier":600,"rechargeDoubleVirtualRewardRate":0}]}',
    'category' => 'all',
    'type' => 'FirstRechargeRebate',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_2.jpg',
    'sort' => 1,
    'showStartTime' => '2025-08-10T15:51:15.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-08-10T15:46:16.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 2,
  ),
  23 => 
  array (
    'id' => 3981,
    'name' => '圣诞节',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => '{"uuid":"108e212f9ee846868c55ae2f7ca2d035","content":"https://upload-us.f-1-g-h.com/s1/1766121737796/curstomActivity.txt","isShowApply":true,"jumpType":"DETAIL","target":{"type":"external","targetValue":""}}',
    'category' => 'all',
    'type' => 'Custom',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_2.jpg',
    'sort' => 1,
    'showStartTime' => '2025-12-19T05:27:42.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-12-19T05:23:06.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => NULL,
    'statusIndex' => 1,
    'top' => 2,
  ),
  array (
    'id' => 496,
    'name' => '红包雨(神秘矿场)',
    'nameType' => 'CUSTOM',
    'nameParams' => '{"key":"value"}',
    'condition' => $minaCondition,
    'category' => 'all',
    'type' => 'RedPacket',
    'bannerBackground' => 'https://upload-sys-pics.f-1-g-h.com/activityStar/bgStar/ACTIVITY_8.jpg',
    'sort' => 1,
    'showStartTime' => '2025-04-08T03:00:00.000Z',
    'showEndTime' => '2035-12-30T00:00:00.000Z',
    'ruleType' => 'DEFAULT',
    'rule' => '{"variablesValue":{"times":18,"duration":59,"rewardCount":520,"multiplier":1,"bettingOnly":"","utcValue":"Etc/GMT+3","ruleTimeShow":"2025-11-13 17:00:24 - 2025-12-23 23:59:59","timeType":"PERMANENT","limitType":"OFF","limitData":"","joinType":"RECHARGE","awardExpiredDays":0,"rewardAuditType":"Gift"}}',
    'tenantId' => 5657408,
    'previewText' => '',
    'status' => 'PROCESSING',
    'startTime' => '2025-11-13T20:00:24.000Z',
    'endTime' => '2035-12-30T00:00:00.000Z',
    'activityDetailSelect' => 'style_0',
    'statusIndex' => $minaActive,
    'top' => 2,
  ),
);
    $finalList = [];
    $processedIds = [];
    foreach ($activityList as $activity) {
        if (isset($dbPromos[$activity['id']])) {
            $row = $dbPromos[$activity['id']];
            if ($row['status'] == 1) {
                $activity['name'] = $row['titulo'];
                $imgUrl = $row['img'];
                if (!empty($imgUrl) && strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = $WG_BUCKET_SITE . '/uploads/' . $imgUrl;
                }
                $activity['bannerBackground'] = $imgUrl;
                $activity['statusIndex'] = $row['status'];
                if (!empty($row['activity_type'])) {
                    $activity['type'] = $row['activity_type'];
                }
                $finalList[] = $activity;
                $processedIds[$activity['id']] = true;
            }
        }
    }
    foreach ($dbPromos as $id => $row) {
        if (!isset($processedIds[$id]) && $row['status'] == 1) {
            $imgUrl = $row['img'];
            if (!empty($imgUrl) && strpos($imgUrl, 'http') !== 0) {
                $imgUrl = $WG_BUCKET_SITE . '/uploads/' . $imgUrl;
            }
            $finalList[] = [
                'id' => $row['id'],
                'name' => $row['titulo'],
                'nameType' => 'CUSTOM',
                'nameParams' => '{"key":"value"}',
                'condition' => '{"jumpType":"LINK"}',
                'category' => 'all',
                'type' => (!empty($row['activity_type']) ? $row['activity_type'] : 'Custom'),
                'bannerBackground' => $imgUrl,
                'sort' => 999,
                'showStartTime' => date('Y-m-d\TH:i:s.000\Z'),
                'showEndTime' => '2035-12-30T00:00:00.000Z',
                'ruleType' => 'DEFAULT',
                'rule' => '{"variablesValue":{}}',
                'tenantId' => 5657408,
                'previewText' => '',
                'status' => 'PROCESSING',
                'startTime' => date('Y-m-d\TH:i:s.000\Z'),
                'endTime' => '2035-12-30T00:00:00.000Z',
                'activityDetailSelect' => NULL,
                'statusIndex' => 1,
                'top' => 1
            ];
        }
    }
    sendTrpcResponse(["activityList" => $finalList]);
}
if ($path === '/api/frontend/trpc/activity.activityDetail' || $path === '/api/frontend/trpc/activity.activityDetailPublic') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $activityId = $json['activityId'] ?? 0;
    $type = $json['type'] ?? '';
    if ($activityId == 3177 || $type == 'RechargeBonus') {
        $data = [
            "activityDay" => 1,
            "homeEntry" => true,
            "activityConfig" => [
                [
                    "uuid" => "eee780e4ba3347ffa2a02d91b2780140",
                    "rechargeAmount" => 10000,
                    "rewardAmount" => 1000,
                    "auditMultiple" => 1,
                    "virtualRewardRatio" => 0
                ]
            ],
            "receivedLevelIds" => [],
            "appIconUrl" => $WG_BUCKET_SITE . "/uploads/icon_recharge.png",
            "rewardAuditType" => "GiftAndRecharge",
            "startTime" => "2026-01-01T09:19:52.000Z",
            "endTime" => "2035-12-30T00:00:00.000Z",
            "showStartTime" => "2025-08-10T15:16:23.000Z",
            "showEndTime" => "2035-12-30T00:00:00.000Z",
            "status" => "PROCESSING",
            "multilingual" => [
                "rule" => "{\"variablesValue\":{\"highestReward\":10,\"utcValue\":\"Etc/GMT+3\",\"ruleTimeShow\":\"2026-01-01 06:19:52 - 2026-01-04 23:59:59\",\"timeType\":\"PERMANENT\",\"limitType\":\"OFF\",\"limitData\":\"\",\"multiplier\":1,\"expiredAwardType\":\"ABANDONED\",\"awardType\":\"BALANCE\",\"awardExpiredDays\":0,\"rechargeType\":\"SINGLE_DEPOSIT\",\"activityDay\":1,\"include\":\"GiftAndRecharge\"}}",
                "ruleType" => "DEFAULT",
                "name" => "Recharge Bonus",
                "nameType" => "CUSTOM",
                "nameParams" => "{\"key\":\"value\"}",
                "previewText" => "",
                "activityDetailSelect" => null
            ],
            "meta" => [
                "values" => [
                    "startTime" => ["Date"],
                    "endTime" => ["Date"],
                    "showStartTime" => ["Date"],
                    "showEndTime" => ["Date"]
                ]
            ]
        ];
        sendTrpcResponse($data);
    } elseif ($activityId == 3036 || $type == 'RechargePromotion') {
        $data = [
            "activityDay" => 4,
            "homeEntry" => false,
            "activityConfig" => [
                [
                    "uuid" => "2658e9917efe4fb488a80f4dd4ba60be",
                    "rechargeAmount" => 20000,
                    "rewardAmount" => 1000,
                    "auditMultiple" => 2,
                    "virtualRewardRatio" => 0
                ],
                [
                    "uuid" => "12db948322f541768a55104d35cc64b2",
                    "rechargeAmount" => 30000,
                    "rewardAmount" => 2000,
                    "auditMultiple" => 3,
                    "virtualRewardRatio" => 0
                ]
            ],
            "receivedLevelIds" => [],
            "appIconUrl" => $WG_BUCKET_SITE . "/uploads/rechargePromotion_1.png",
            "rewardAuditType" => "Gift",
            "startTime" => "2025-10-12T15:56:32.000Z",
            "endTime" => "2035-12-30T00:00:00.000Z",
            "showStartTime" => "2025-09-06T03:00:00.000Z",
            "showEndTime" => "2035-12-30T00:00:00.000Z",
            "status" => "PROCESSING",
            "multilingual" => [
                "rule" => "{\"variablesValue\":{\"highestReward\":20,\"multiplier\":2,\"utcValue\":\"Etc/GMT+3\",\"ruleTimeShow\":\"2025-10-12 12:56:32 - 2025-12-07 23:59:59\",\"timeType\":\"PERMANENT\",\"limitType\":\"ON\",\"limitData\":\"Slot: PG, Tada, CQ9, , JDB, Spribe, PP, CP, FaChai, POPOK\",\"expiredAwardType\":\"ABANDONED\",\"awardType\":\"BALANCE\",\"awardExpiredDays\":0,\"rechargeType\":\"SINGLE_DEPOSIT\",\"activityDay\":4,\"include\":\"Gift\"}}",
                "ruleType" => "DEFAULT",
                "name" => "Recharge Promotion",
                "nameType" => "CUSTOM",
                "nameParams" => "{\"key\":\"value\"}",
                "previewText" => "",
                "activityDetailSelect" => null
            ],
            "meta" => [
                "values" => [
                    "startTime" => ["Date"],
                    "endTime" => ["Date"],
                    "showStartTime" => ["Date"],
                    "showEndTime" => ["Date"]
                ]
            ]
        ];
        sendTrpcResponse($data);
    } elseif ($activityId == 637 || $type == 'AssistanceCash') {
        $user = getCurrentUser($mysqli);
        $allRoundCount = ($user && isset($user['LuckyWheel'])) ? (int)$user['LuckyWheel'] : 0;
        
        // Buscar a soma de todos os award_count da tabela roletinha100 para o usuário
        $totalRangeAmount = 0;
        $totalDrawCount = 0;
        $firstSpinTime = null;
        $endSpinTime = null;
        if ($user) {
            $sumStmt = $mysqli->prepare("SELECT COALESCE(SUM(award_count), 0) as total_award, COUNT(*) as total_draws, MIN(created_at) as first_spin FROM roletinha100 WHERE user_id = ?");
            $sumStmt->bind_param("i", $user['id']);
            $sumStmt->execute();
            $sumRes = $sumStmt->get_result();
            if ($sumRow = $sumRes->fetch_assoc()) {
                $totalRangeAmount = (int)$sumRow['total_award'];
                $totalDrawCount = (int)$sumRow['total_draws'];
                if ($sumRow['first_spin']) {
                    $tz = new DateTimeZone('America/Sao_Paulo');
                    $startDt = new DateTime($sumRow['first_spin'], $tz);
                    $firstSpinTime = $startDt->format('Y-m-d\TH:i:s.vP');
                    // endTime = 4 dias após a primeira jogada
                    $endDt = clone $startDt;
                    $endDt->modify('+4 days');
                    $endSpinTime = $endDt->format('Y-m-d\TH:i:s.vP');
                }
            }
            $sumStmt->close();
        }
        
        $data = [
            "awardList" => [
                [
                    "uuid" => "b59e1b54342441a1be5ae1ca67c4b2b7",
                    "type" => "rangeAmount",
                    "amount" => 0,
                    "weight" => 100
                ],
                [
                    "uuid" => "1ffeff4741f242ccb07dca8771401d8d",
                    "type" => "fixedAmount",
                    "amount" => 5000,
                    "weight" => 0
                ],
                [
                    "uuid" => "d2a1098bc3e94cd2a5e19eef068e975d",
                    "type" => "fixedAmount",
                    "amount" => 18800,
                    "weight" => 0
                ],
                [
                    "uuid" => "c6134b2d7cd743dfa3bce96c51b22e19",
                    "type" => "fixedAmount",
                    "amount" => 10000,
                    "weight" => 0
                ],
                [
                    "uuid" => "6b21e455908a4e68a0091f947bf1de69",
                    "type" => "fixedAmount",
                    "amount" => 100000,
                    "weight" => 0
                ],
                [
                    "uuid" => "28d804b7cbbf47008ab7f04c7c28196a",
                    "type" => "bonus",
                    "amount" => 10800,
                    "weight" => 0
                ]
            ],
            "startTime" => $firstSpinTime,
            "endTime" => $endSpinTime,
            "rangeAmount" => $totalRangeAmount,
            "roundAmount" => 10000,
            "allRoundCount" => $allRoundCount,
            "drawCount" => 0,
            "shareDomain" => "https://" . $_SERVER['HTTP_HOST'],
            "rule" => "直属累计充值50，投注50\n\n4. Os bônus emitidos neste evento podem ser sacados sem apostas;\n\n5. Esta atividade é limitada às operações humanas normais do proprietário da conta. É proibido alugar, usar plug-ins, robôs, jogar com contas diferentes, escovação mútua, arbitragem, interfaces, protocolos, exploração de brechas, controle de grupo ou outras técnicas. significa participar, caso contrário será cancelado ou terá recompensas deduzidas, congeladas ou mesmo colocadas na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final deste evento.",
            "domainImg" => "",
            "multilingual" => [
                "rule" => "直属累计充值50，投注50\n\n4. Os bônus emitidos neste evento podem ser sacados sem apostas;\n\n5. Esta atividade é limitada às operações humanas normais do proprietário da conta. É proibido alugar, usar plug-ins, robôs, jogar com contas diferentes, escovação mútua, arbitragem, interfaces, protocolos, exploração de brechas, controle de grupo ou outras técnicas. significa participar, caso contrário será cancelado ou terá recompensas deduzidas, congeladas ou mesmo colocadas na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final deste evento.",
                "ruleType" => "CUSTOM",
                "name" => "助力领现金132456",
                "nameType" => "CUSTOM",
                "nameParams" => "{\"key\":\"value\"}",
                "previewText" => "",
                "activityDetailSelect" => "style_0"
            ]
        ];
        $meta = [
            "values" => []
        ];
        if ($firstSpinTime) {
            $meta["values"]["startTime"] = ["Date"];
            $meta["values"]["endTime"] = ["Date"];
        } else {
            $meta["values"]["startTime"] = ["undefined"];
            $meta["values"]["endTime"] = ["undefined"];
        }
        sendTrpcResponse($data, $meta);
    } elseif ($activityId == 641 || $type == 'RedPacket') {
        $_mc = getMinaConfig($mysqli); $_mc_tc = buildMinaTimeConfig($_mc);
        $data = [
            "JoinTypes" => "RECHARGE",
            "appIconUrl" => "https://upload-sys-pics.f-1-g-h.com/activityStar/redPacket/redPacket_10.png",
            "betAmount" => 0,
            "canReceive" => false,
            "dailyMaxCount" => (int)$_mc['daily_max_count'],
            "endTime" => "2035-12-30T00:00:00.000Z",
            "mainMediaShare" => "OFF",
            "maxAmount" => (int)$_mc['reward_max'],
            "multilingual" => [
                "activityDetailSelect" => "style_1",
                "name" => "神秘矿场",
                "nameParams" => "{\"variablesValue\":{\"resetType\":\"DAILY\",\"rechargeType\":\"FIRST\"}}",
                "nameType" => "DEFAULT",
                "previewText" => "",
                "rule" => "Detalhes do evento:\n1. A misteriosa mina é aberta " . count($_mc_tc) . " vezes por dia, e cada abertura dura " . ($_mc_tc[0]['durationIn'] ?? 59) . " minutos. A mina contém 100.000 cristais, e os jogadores podem minerá-los gratuitamente uma vez cada vez que a abrirem. Os cristais minerados são automaticamente convertidos em saldo a uma taxa de câmbio de 1:1;\n\n2. Somente membros que fizeram a recarga no mesmo dia podem participar. Quanto mais você recarregar, maiores serão as recompensas. [Certifique-se de fazer login durante o período acima para não perder as recompensas. [É altamente recomendável usar o APP móvel] Se você não tiver feito login, ele expirou (ou seja, se você não o reivindicou ativamente, ele será considerado como abandonado voluntariamente];\n\n3. As recompensas de atividades precisam ser coletadas manualmente; recompensas não coletadas serão consideradas inválidas;\n\n4. O bônus (excluindo o bônus principal) concedido nesta atividade só pode ser reivindicado após mais de uma aposta válida (as apostas são limitadas às máquinas caça-níqueis: PG, Tada, JDB, PP, CP, FaChai, G759, POPOK, PLAYSON);\n\n5. Esta atividade é limitada a operações manuais normais pelo proprietário da conta. É proibido alugar, usar plug-ins, robôs, apostar com contas diferentes, deslizar uns aos outros, arbitragem, interface, protocolo, explorar brechas, controle de grupo ou outros meios técnicos para participar. Caso contrário, a recompensa será cancelada ou deduzida, congelada ou até mesmo colocada na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final desta atividade.",
                "ruleType" => "CUSTOM"
            ],
            "receiveCount" => 0,
            "rechargeAmount" => 0,
            "rewardCount" => 0,
            "roundMaxAmount" => 10000000,
            "rule" => "Detalhes do evento:\n1. A misteriosa mina é aberta 12 vezes por dia, e cada abertura dura 59 minutos. A mina contém 100.000 cristais, e os jogadores podem minerá-los gratuitamente uma vez cada vez que a abrirem. Os cristais minerados são automaticamente convertidos em saldo a uma taxa de câmbio de 1:1;\n\n2. Somente membros que fizeram a recarga no mesmo dia podem participar. Quanto mais você recarregar, maiores serão as recompensas. [Certifique-se de fazer login durante o período acima para não perder as recompensas. [É altamente recomendável usar o APP móvel] Se você não tiver feito login, ele expirou (ou seja, se você não o reivindicou ativamente, ele será considerado como abandonado voluntariamente];\n\n3. As recompensas de atividades precisam ser coletadas manualmente; recompensas não coletadas serão consideradas inválidas;\n\n4. O bônus (excluindo o bônus principal) concedido nesta atividade só pode ser reivindicado após mais de uma aposta válida (as apostas são limitadas às máquinas caça-níqueis: PG, Tada, JDB, PP, CP, FaChai, G759, POPOK, PLAYSON);\n\n5. Esta atividade é limitada a operações manuais normais pelo proprietário da conta. É proibido alugar, usar plug-ins, robôs, apostar com contas diferentes, deslizar uns aos outros, arbitragem, interface, protocolo, explorar brechas, controle de grupo ou outros meios técnicos para participar. Caso contrário, a recompensa será cancelada ou deduzida, congelada ou até mesmo colocada na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final desta atividade.",
            "ruleType" => "CUSTOM",
            "startTime" => "2025-07-23T17:21:03.000Z",
            "timeConfig" => $_mc_tc
        ];
        sendTrpcResponse($data);
    } elseif ($activityId == 1887 || $type == 'SignIn') {
        $user = getCurrentUser($mysqli);
        $userId = $user ? $user['id'] : 0;
        $rewardConfig = [];
        $resConfig = $mysqli->query("SELECT * FROM signin_config ORDER BY day ASC");
        if ($resConfig) {
            while ($row = $resConfig->fetch_assoc()) {
                $rewardConfig[] = [
                    "day" => intval($row['day']),
                    "amountType" => $row['amount_type'],
                    "amountMax" => intval($row['amount_max']),
                    "amountMin" => intval($row['amount_min']),
                    "rechargeAmount" => intval($row['recharge_amount']),
                    "validBet" => intval($row['valid_bet']),
                    "extraReward" => intval($row['extra_reward']),
                    "iconType" => $row['icon_type'],
                    "icon" => $row['icon']
                ];
            }
        }
        $signInDays = 0;
        $isSignIn = false;
        $todayValidBet = 0;
        $todayValidRecharge = 0;
        if ($userId) {
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $stmt = $mysqli->prepare("SELECT id FROM signin_records WHERE user_id = ? AND date_record = ?");
            $stmt->bind_param("is", $userId, $today);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $isSignIn = true;
            }
            $stmt->close();
            $stmt = $mysqli->prepare("SELECT date_record FROM signin_records WHERE user_id = ? ORDER BY date_record DESC");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $dates = [];
            while ($row = $res->fetch_assoc()) {
                $dates[] = $row['date_record'];
            }
            $stmt->close();
            $streak = 0;
            $checkDate = $isSignIn ? $today : $yesterday;
            foreach ($dates as $d) {
                if ($d == $checkDate) {
                    $streak++;
                    $checkDate = date('Y-m-d', strtotime($d . ' -1 day'));
                } elseif ($d > $checkDate) {
                    continue; 
                } else {
                    break;
                }
            }
            $signInDays = $streak;
            $startOfDay = $today . ' 00:00:00';
            $stmt = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND created_at >= ? AND status_play = 1");
            $stmt->bind_param("is", $userId, $startOfDay);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $todayValidBet = floatval($row['total']) * 100;
            }
            $stmt->close();
            $stmt = $mysqli->prepare("SELECT SUM(valor) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago' AND data_registro >= ?");
            $stmt->bind_param("is", $userId, $startOfDay);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $todayValidRecharge = floatval($row['total']) * 100;
            }
            $stmt->close();
        }
        $data = [
            "signInDays" => $signInDays,
            "signInType" => "CONTINUOUS",
            "rechargeSuccessPopup" => "ON",
            "appShowRewardAmount" => "ON",
            "rewardList" => [],
            "rewardConfig" => $rewardConfig,
            "validBet" => $todayValidBet,
            "validRecharge" => $todayValidRecharge,
            "isSignIn" => $isSignIn,
            "currentTime" => gmdate("Y-m-d\TH:i:s.000\Z"),
            "cycleType" => "CYCLE",
            "rewardType" => "VIP_LEVEL_REWARDS",
            "appShowExtraRewardAmount" => "ON",
            "multilingual" => [
                "rule" => "{\"variablesValue\":{\"highestReward\":100,\"multiplier\":1,\"utcValue\":\"Etc/GMT+3\",\"ruleTimeShow\":\"2025-08-27 10:00:36 - 2025-11-12 23:59:59\",\"timeType\":\"PERMANENT\",\"limitType\":\"OFF\",\"limitData\":\"\",\"platforms\":\"iOSH5,AndroidH5,PWA,APK,iOSApp,iOSBookmark,APKRelease,DesktopOS\",\"signInType\":\"CONTINUOUS\",\"awardExpiredDays\":0,\"rewardAuditType\":\"Gift\"}}",
                "ruleType" => "DEFAULT",
                "name" => "checklist",
                "nameType" => "CUSTOM",
                "nameParams" => "{\"key\":\"value\"}",
                "previewText" => "",
                "activityDetailSelect" => "style_2"
            ],
            "meta" => [
                "values" => [
                    "currentTime" => ["Date"]
                ]
            ]
        ];
        sendTrpcResponse($data);
    } elseif ($activityId == 494 || $type == 'Rebate') {
        $data = [
            "startTime" => "2025-07-19T07:49:10.000Z",
            "endTime" => "2035-12-30T00:00:00.000Z",
            "rebateList" => (function($mysqli) use ($WG_BUCKET_SITE) {
                $list = [];
                $electronic = [];
                $video = [];
                $fishing = [];
                $chess = [];
                $query = "SELECT id, name, type, logo FROM provedores WHERE status = 1";
                $result = $mysqli->query($query);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $item = [
                            "platformId" => intval($row['id']),
                            "platformName" => $row['name'],
                            "logo" => resolveLogoUrl($row['logo'] ?? '', $WG_BUCKET_SITE),
                            "rebateRatioList" => [
                                ["uuid" => "", "conditionAmount" => 100, "rewardAmount" => 10],
                                ["uuid" => "", "conditionAmount" => 200, "rewardAmount" => 100]
                            ]
                        ];
                        $t = strtolower($row['type']);
                        if (strpos($t, 'live') !== false) {
                            $video[] = $item;
                        } elseif (strpos($t, 'fishing') !== false) {
                            $fishing[] = $item;
                        } elseif (strpos($t, 'chess') !== false) {
                            $chess[] = $item;
                        } else {
                            $electronic[] = $item;
                        }
                    }
                }
                if (!empty($electronic)) $list[] = ["gameType" => "ELECTRONIC", "platformRebateList" => $electronic];
                if (!empty($chess)) $list[] = ["gameType" => "CHESS", "platformRebateList" => $chess];
                if (!empty($fishing)) $list[] = ["gameType" => "FISHING", "platformRebateList" => $fishing];
                if (!empty($video)) $list[] = ["gameType" => "VIDEO", "platformRebateList" => $video];
                return $list;
            })($mysqli),
            "validBetList" => new stdClass(),
            "gameTypes" => ["ELECTRONIC", "FISHING", "CHESS", "VIDEO"],
            "rule" => '{"variablesValue":{"lowestRebateBet":1,"rechargeLimit":0,"highestReward":"1%","multiplier":0,"awardType":"ACTIVITY","rebateType":"NORECHARGE","utcValue":"Etc/GMT+3","ruleTimeShow":"2025-07-19 04:49:10 - 2035-12-30 23:59:59","timeType":"PERMANENT","limitType":"ON","limitData":"Slot: PG, Tada, CQ9, JDB, Spribe, PP, CP, G759, FaChai, POPOK, PLAYSON, RUBYPLAY, FASTSPIN, inout","awardExpiredDays":8}}',
            "ruleType" => "DEFAULT",
            "resetType" => "DAILY",
            "receiveAmount" => 0,
            "multilingual" => [
                "rule" => '{"variablesValue":{"lowestRebateBet":1,"rechargeLimit":0,"highestReward":"1%","multiplier":0,"awardType":"ACTIVITY","rebateType":"NORECHARGE","utcValue":"Etc/GMT+3","ruleTimeShow":"2025-07-19 04:49:10 - 2035-12-30 23:59:59","timeType":"PERMANENT","limitType":"ON","limitData":"Slot: PG, Tada, CQ9, JDB, Spribe, PP, CP, G759, FaChai, POPOK, PLAYSON, RUBYPLAY, FASTSPIN, inout","awardExpiredDays":8}}',
                "ruleType" => "DEFAULT",
                "name" => "实时返水",
                "nameType" => "CUSTOM",
                "nameParams" => '{"key":"value"}',
                "previewText" => '',
                "activityDetailSelect" => null
            ]
        ];
        $data['returnGoldValidBet'] = 0;
        $data['returnGold'] = 0;
        $data['curReturnGold'] = 0;
        $data['nextReturnGold'] = 0;
        $data['autoSendReturnGold'] = 0;
        $data['clientCalculateDelta'] = true;
        $data['totalValidBet'] = 0;
        $data['nextValidBet'] = 0;
        $data['returnGoldType'] = 0;
        $list = [];
        foreach ($data['rebateList'] as $rebateGroup) {
            $gameType = $rebateGroup['gameType'];
            $categoryName = $gameType;
            $categoryId = 0;
            if ($gameType == 'ELECTRONIC') {
                $categoryName = 'Slots';
                $categoryId = 3;
            } elseif ($gameType == 'FISHING') {
                $categoryName = 'Fishing';
                $categoryId = 5;
            } elseif ($gameType == 'CHESS') {
                $categoryName = 'Cards';
                $categoryId = 4;
            } elseif ($gameType == 'VIDEO') {
                $categoryName = 'Live';
                $categoryId = 1;
            }
            $innerList = [];
            foreach ($rebateGroup['platformRebateList'] as $platform) {
                $innerList[] = [
                    "vip" => 0,
                    "validBet" => 0,
                    "returnGold" => 0,
                    "rate" => 0,
                    "rateId" => -1,
                    "nextRate" => 0.05,
                    "nextRateId" => 0,
                    "remainingBet" => 100,
                    "curVip" => 0,
                    "nextVip" => 0,
                    "curBet" => 0,
                    "nextBet" => 100,
                    "gameSecondCate" => $platform['platformName'],
                    "gameCategory" => "",
                    "gameSecondCateId" => $platform['platformId'],
                    "gameCategoryId" => $categoryId,
                    "icon" => $platform['logo'] ?? "",
                    "weight" => 0
                ];
            }
            $list[] = [
                "name" => $categoryName,
                "categoryId" => $categoryId,
                "list" => $innerList
            ];
        }
        $data['list'] = $list;
        sendTrpcResponse($data);
    } elseif ($activityId == 641 || $type == 'RedPacket') {
        $_mc = getMinaConfig($mysqli); $_mc_tc = buildMinaTimeConfig($_mc);
        $response = [
            "JoinTypes" => "RECHARGE",
            "appIconUrl" => "https://upload-us.f-1-g-h.com/s4/1769743944180/24103.png",
            "betAmount" => 0,
            "canReceive" => false,
            "dailyMaxCount" => (int)$_mc['daily_max_count'],
            "endTime" => "2035-12-30T00:00:00.000Z",
            "mainMediaShare" => "OFF",
            "maxAmount" => (int)$_mc['reward_max'],
            "multilingual" => [
                "activityDetailSelect" => "style_1",
                "name" => "神秘矿场",
                "nameParams" => "{\"variablesValue\":{\"resetType\":\"DAILY\",\"rechargeType\":\"FIRST\"}}",
                "nameType" => "DEFAULT",
                "previewText" => "",
                "rule" => "Detalhes do evento:\n1. A misteriosa mina é aberta " . count($_mc_tc) . " vezes por dia, e cada abertura dura " . ($_mc_tc[0]['durationIn'] ?? 59) . " minutos. A mina contém 100.000 cristais, e os jogadores podem minerá-los gratuitamente uma vez cada vez que a abrirem. Os cristais minerados são automaticamente convertidos em saldo a uma taxa de câmbio de 1:1;\n\n2. Somente membros que fizeram a recarga no mesmo dia podem participar. Quanto mais você recarregar, maiores serão as recompensas. [Certifique-se de fazer login durante o período acima para não perder as recompensas. [É altamente recomendável usar o APP móvel] Se você não tiver feito login, ele expirou (ou seja, se você não o reivindicou ativamente, ele será considerado como abandonado voluntariamente];\n\n3. As recompensas de atividades precisam ser coletadas manualmente; recompensas não coletadas serão consideradas inválidas;\n\n4. O bônus (excluindo o bônus principal) concedido nesta atividade só pode ser reivindicado após mais de uma aposta válida (as apostas são limitadas às máquinas caça-níqueis: PG, Tada, JDB, PP, CP, FaChai, G759, POPOK, PLAYSON);\n\n5. Esta atividade é limitada a operações manuais normais pelo proprietário da conta. É proibido alugar, usar plug-ins, robôs, apostar com contas diferentes, deslizar uns aos outros, arbitragem, interface, protocolo, explorar brechas, controle de grupo ou outros meios técnicos para participar. Caso contrário, a recompensa será cancelada ou deduzida, congelada ou até mesmo colocada na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final desta atividade.",
                "ruleType" => "CUSTOM"
            ],
            "receiveCount" => 0,
            "rechargeAmount" => 0,
            "rewardCount" => 0,
            "roundMaxAmount" => 10000000,
            "rule" => "Detalhes do evento:\n1. A misteriosa mina é aberta 12 vezes por dia, e cada abertura dura 59 minutos. A mina contém 100.000 cristais, e os jogadores podem minerá-los gratuitamente uma vez cada vez que a abrirem. Os cristais minerados são automaticamente convertidos em saldo a uma taxa de câmbio de 1:1;\n\n2. Somente membros que fizeram a recarga no mesmo dia podem participar. Quanto mais você recarregar, maiores serão as recompensas. [Certifique-se de fazer login durante o período acima para não perder as recompensas. [É altamente recomendável usar o APP móvel] Se você não tiver feito login, ele expirou (ou seja, se você não o reivindicou ativamente, ele será considerado como abandonado voluntariamente];\n\n3. As recompensas de atividades precisam ser coletadas manualmente; recompensas não coletadas serão consideradas inválidas;\n\n4. O bônus (excluindo o bônus principal) concedido nesta atividade só pode ser reivindicado após mais de uma aposta válida (as apostas são limitadas às máquinas caça-níqueis: PG, Tada, JDB, PP, CP, FaChai, G759, POPOK, PLAYSON);\n\n5. Esta atividade é limitada a operações manuais normais pelo proprietário da conta. É proibido alugar, usar plug-ins, robôs, apostar com contas diferentes, deslizar uns aos outros, arbitragem, interface, protocolo, explorar brechas, controle de grupo ou outros meios técnicos para participar. Caso contrário, a recompensa será cancelada ou deduzida, congelada ou até mesmo colocada na lista negra;\n\n6. Para evitar diferenças na compreensão do texto, a plataforma reserva-se o direito de interpretação final desta atividade.",
            "ruleType" => "CUSTOM",
            "startTime" => "2025-07-23T17:21:03.000Z",
            "timeConfig" => $_mc_tc
        ];
        sendTrpcResponse($response);
    } else {
        $user = getCurrentUser($mysqli);
        if ($user) {
            $config_qry = "SELECT niveisbau, qntsbaus, pessoasbau FROM config WHERE id=1";
            $config_res = $mysqli->query($config_qry);
            $config = $config_res ? $config_res->fetch_assoc() : null;
            if (!$config) $config = ['niveisbau' => '10', 'qntsbaus' => 1, 'pessoasbau' => 1];
            $qntsbaus = (int)($config['qntsbaus'] ?? 1);
            $userId = $user['id'];
            $check_bau = $mysqli->prepare("SELECT id FROM bau WHERE id_user = ?");
            $check_bau->bind_param("i", $userId);
            $check_bau->execute();
            $check_bau_res = $check_bau->get_result();
            $existing_count = $check_bau_res->num_rows;
            $check_bau->close();
            if ($existing_count < $qntsbaus) {
                 for ($i = $existing_count + 1; $i <= $qntsbaus; $i++) {
                     $uuid = md5($userId . $i . time() . 'salt_bau_check');
                     $stmt = $mysqli->prepare("INSERT INTO bau (id_user, num, status, token) VALUES (?, ?, 'user novo', ?)");
                     $stmt->bind_param("iis", $userId, $i, $uuid);
                     $stmt->execute();
                     $stmt->close();
                 }
            }
        } else {
             $config_qry = "SELECT niveisbau, qntsbaus, pessoasbau FROM config WHERE id=1";
             $config_res = $mysqli->query($config_qry);
             $config = $config_res ? $config_res->fetch_assoc() : null;
             if (!$config) $config = ['niveisbau' => '10', 'qntsbaus' => 1, 'pessoasbau' => 1];
        }
        $afiliados_qry = "SELECT minDepForCpa, minResgate, pagar_baus, dep_on, bet_on FROM afiliados_config WHERE id=1";
        $afiliados_res = $mysqli->query($afiliados_qry);
        $afiliados = $afiliados_res ? $afiliados_res->fetch_assoc() : null;
        if (!$afiliados) $afiliados = ['minDepForCpa' => 0, 'minResgate' => 0, 'pagar_baus' => 1, 'dep_on' => 1, 'bet_on' => 1];
        $userBaus = [];
        $claimedBaus = [];
        if ($user) {
            $stmt = $mysqli->prepare("SELECT num, token, status, is_get FROM bau WHERE id_user = ? ORDER BY num ASC");
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            while($row = $res->fetch_assoc()) {
                $userBaus[$row['num']] = $row['token'];
                $claimedBaus[$row['num']] = ($row['status'] === 'claimed' || $row['is_get'] == 1);
            }
            $stmt->close();
        }
        $rewardConfig = [];
        $rewardList = [];
        $niveis = explode(',', $config['niveisbau'] ?? '');
        $count = (int)($config['qntsbaus'] ?? 0);
        $pessoas = (int)($config['pessoasbau'] ?? 1);
        $levels_count = count($niveis);
        $baus_por_nivel = $levels_count > 0 ? ceil($count / $levels_count) : 1;
        for ($i = 1; $i <= $count; $i++) {
            $nivel_index = floor(($i - 1) / $baus_por_nivel);
            $amount = isset($niveis[$nivel_index]) ? (float)$niveis[$nivel_index] : (float)end($niveis);
            $uuid = isset($userBaus[$i]) ? $userBaus[$i] : md5($i . 'salt_reward');
            $isOpen = isset($claimedBaus[$i]) && $claimedBaus[$i] === true;
            $rewardConfig[] = [
                "uuid" => $uuid,
                "userCount" => $i * $pessoas,
                "min" => 0,
                "max" => $amount * 100,
                "isOpen" => $isOpen,
                "rewardAmount" => $isOpen ? $amount * 100 : 0
            ];
            if ($isOpen) {
                $rewardList[] = [
                    "levelId" => $uuid,
                    "awardCount" => $amount * 100
                ];
            }
        }
        $pagar_baus_status = (($afiliados['pagar_baus'] ?? 1) == 1) ? "ON" : "OFF";
        $allCount = 0;
        $validCount = 0;
        if ($user) {
            $stmtAll = $mysqli->prepare("SELECT count(*) as c FROM usuarios WHERE invitation_code = ?");
            $stmtAll->bind_param("s", $user['invite_code']);
            $stmtAll->execute();
            $resAll = $stmtAll->get_result();
            if ($rowAll = $resAll->fetch_assoc()) $allCount = $rowAll['c'];
            $stmtAll->close();
            $minDep = (float)($afiliados['minDepForCpa'] ?? 0);
            $minBet = (float)($afiliados['minResgate'] ?? 0);
            if (($afiliados['dep_on'] ?? 1) == 0) {
                $minDep = 0;
            }
            if (($afiliados['bet_on'] ?? 1) == 0) {
                $minBet = 0;
            }
            $validQuery = "SELECT COUNT(*) as c FROM usuarios u WHERE u.invitation_code = ?";
            if ($minDep > 0 && $minBet > 0) {
                 $validQuery .= " AND (SELECT COALESCE(SUM(valor),0) FROM transacoes WHERE usuario = u.id AND status = 'pago') >= ? AND (SELECT COALESCE(SUM(bet_money),0) FROM historico_play WHERE id_user = u.id) >= ?";
                 $stmtValid = $mysqli->prepare($validQuery);
                 $stmtValid->bind_param("sdd", $user['invite_code'], $minDep, $minBet);
            } elseif ($minDep > 0) {
                 $validQuery .= " AND (SELECT COALESCE(SUM(valor),0) FROM transacoes WHERE usuario = u.id AND status = 'pago') >= ?";
                 $stmtValid = $mysqli->prepare($validQuery);
                 $stmtValid->bind_param("sd", $user['invite_code'], $minDep);
            } elseif ($minBet > 0) {
                 $validQuery .= " AND (SELECT COALESCE(SUM(bet_money),0) FROM historico_play WHERE id_user = u.id) >= ?";
                 $stmtValid = $mysqli->prepare($validQuery);
                 $stmtValid->bind_param("sd", $user['invite_code'], $minBet);
            } else {
                 $stmtValid = $mysqli->prepare($validQuery);
                 $stmtValid->bind_param("s", $user['invite_code']);
            }
            $stmtValid->execute();
            $resValid = $stmtValid->get_result();
            if ($rowValid = $resValid->fetch_assoc()) $validCount = $rowValid['c'];
            $stmtValid->close();
        }
        $validUsers = [
            "firstRechargeAmount" => ["amount" => 0, "status" => "OFF"],
            "recharge" => [
                "amount" => (float)($afiliados['minDepForCpa'] ?? 0) * 100,
                "status" => (($afiliados['dep_on'] ?? 1) == 1) ? "ON" : "OFF"
            ],
            "bet" => [
                "amount" => (float)($afiliados['minResgate'] ?? 0) * 100,
                "status" => (($afiliados['bet_on'] ?? 1) == 1) ? "ON" : "OFF"
            ],
            "rechargeDays" => ["days" => 0, "status" => "OFF"],
            "rechargeCount" => ["count" => 0, "status" => "OFF"],
            "type" => "ALL",
            "userLimit" => "NEW_REGISTER"
        ];
        $response = [
            "ruleType" => "DEFAULT",
            "rule" => "{\"variablesValue\":{\"highestReward\":88,\"multiplier\":1,\"limitType\":\"ON\",\"limitData\":\"Card Game: All games; Slot: Tada, CQ9, RUBYPLAY, inout, PLAYSON, POPOK, FaChai, G759, CP; Fishing events: All games; Live: All games\",\"validBetStatus\":\"ON\",\"limitAgencyType\":\"SELECT\",\"limitAgencyData\":\"Card Game: All games; Slot: PG, Tada, CQ9, JDB, Spribe, PP, CP, G759, FaChai, POPOK, PLAYSON, RUBYPLAY, FASTSPIN\",\"awardExpiredDays\":0}}",
            "startTime" => "2026-01-11T09:07:39.000Z",
            "endTime" => "2035-12-30T00:00:00.000Z",
            "allCount" => $allCount,
            "validCount" => $validCount,
            "validUsers" => $validUsers,
            "displayMode" => "BOX",
            "rewardType" => "FIXED",
            "isShow" => true,
            "rewardList" => $rewardList,
            "rewardConfig" => $rewardConfig,
            "multilingual" => [
                 "rule" => "{\"variablesValue\":{\"highestReward\":88,\"multiplier\":1,\"limitType\":\"ON\",\"limitData\":\"Card Game: All games; Slot: Tada, CQ9, RUBYPLAY, inout, PLAYSON, POPOK, FaChai, G759, CP; Fishing events: All games; Live: All games\",\"validBetStatus\":\"ON\",\"limitAgencyType\":\"SELECT\",\"limitAgencyData\":\"Card Game: All games; Slot: PG, Tada, CQ9, JDB, Spribe, PP, CP, G759, FaChai, POPOK, PLAYSON, RUBYPLAY, FASTSPIN\",\"awardExpiredDays\":0}}",
                 "ruleType" => "DEFAULT",
                 "name" => "Baú de Indicação",
                 "nameType" => "CUSTOM",
                 "nameParams" => "{\"key\":\"value\"}",
                 "previewText" => "",
                 "activityDetailSelect" => null
            ]
        ];
        sendTrpcResponse($response, [
            "values" => [
                "startTime" => ["Date"],
                "endTime" => ["Date"]
            ]
        ]);
    }
}
if ($path === '/api/frontend/trpc/reward.list') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    
    // Helpers para configurações do Envelope Vermelho
    $redEnvelopeGet = function() use ($mysqli) {
        $res = $mysqli->query("SELECT * FROM red_envelope_settings WHERE id=1");
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }
        return [
            'app_remark' => "🎉 Bônus liberado! Mais diversão te espera!",
            'double_min' => 1000,
            'double_max' => 1800,
            'amount_min' => 0.20,
            'amount_max' => 1.00,
        ];
    };
    $randFloat = function(float $min, float $max){
        if ($max < $min) { $t=$min; $min=$max; $max=$t; }
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    };
    
    if ($user) {
        $userId = $user['id'];
        
        $stmtCheck = $mysqli->prepare("SELECT id FROM red_pocket_rewards WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 28 HOUR)");
        $stmtCheck->bind_param("i", $userId);
        $stmtCheck->execute();
        $stmtCheck->store_result();
        $alreadyClaimed = $stmtCheck->num_rows > 0;
        $stmtCheck->close();
        
        if ($alreadyClaimed) {
            // Usuário já resgatou hoje - retornar array vazio (sem recompensa)
            $response = [];
        } else {
            // Usuário pode resgatar - gerar orderNo único
            $timestamp = round(microtime(true) * 1000); // timestamp em milissegundos
            // orderNo carrega amount (centavos) e doubleMultiplier (centésimos) para consistência no receive
            $batchNo = "AutoReward" . $timestamp;
            $cfg = $redEnvelopeGet();
            $dmin = (int)($cfg['double_min'] ?? 1000);
            $dmax = (int)($cfg['double_max'] ?? 1800);
            if ($dmax < $dmin) { $t=$dmin; $dmin=$dmax; $dmax=$t; }
            $amin = (float)($cfg['amount_min'] ?? 0.20);
            $amax = (float)($cfg['amount_max'] ?? 1.00);
            if ($amax < $amin) { $t=$amin; $amin=$amax; $amax=$t; }
            $amountReal = $randFloat($amin, $amax);
            $amountCents = (int)round($amountReal * 100);
            $minHund = (int)ceil($dmin / 100);
            $maxHund = (int)floor($dmax / 100);
            if ($maxHund < $minHund) { $maxHund = $minHund; }
            $doubleMul = mt_rand($minHund, $maxHund) * 100;
            $appRemark = $cfg['app_remark'] ?? "🎉 Bônus liberado! Mais diversão te espera!";
            $orderNo = "AR{$timestamp}_{$amountCents}_{$doubleMul}";
            
            $response = [[
                "amount" => $amountCents, // em centavos
                "appRemark" => $appRemark,
                "batchNo" => $batchNo,
                "doubleMultiplier" => $doubleMul,
                "id" => $user['id'],
                "isOpenDouble" => true,
                "operationType" => "manual_reward",
                "orderNo" => $orderNo,
                "rechargeRecordId" => null,
                "rewardType" => "manual_reward",
                "userId" => $user['id'],
                "userIsDouble" => null
            ]];
        }
    } else {
        $response = [];
    }
    
    sendTrpcResponse($response);
}
if (strpos($path, '/reward.receive') !== false) {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    
    $logFile = 'C:/xampp/cliente33/public/reward_debug.log';
    $rawInput = file_get_contents('php://input');
    file_put_contents($logFile, date('Y-m-d H:i:s') . " HIT reward.receive User: " . ($user['id'] ?? 'none') . "\n", FILE_APPEND);

    if ($user) {
        $userId = $user['id'];
        $stmtInterval = $mysqli->prepare("SELECT id FROM red_pocket_rewards WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 28 HOUR)");
        $stmtInterval->bind_param("i", $userId);
        $stmtInterval->execute();
        $stmtInterval->store_result();
        if ($stmtInterval->num_rows > 0) {
            $stmtInterval->close();
            sendTrpcResponse(["status" => "SUCCESS"]);
            exit;
        }
        $stmtInterval->close();

        $input = getTrpcInput();
        $data = $input['json'] ?? $input;
        
        $orderNo = $data['orderNo'] ?? '';
        $batchNo = $data['batchNo'] ?? '';
        
        // Carregar configurações Envelope Vermelho
        $cfg = null;
        $resCfg = $mysqli->query("SELECT * FROM red_envelope_settings WHERE id=1");
        if ($resCfg && $resCfg->num_rows>0){ $cfg = $resCfg->fetch_assoc(); }
        if (!$cfg){
            $cfg = ['amount_min'=>0.20,'amount_max'=>1.00];
        }
        $amin = (float)$cfg['amount_min'];
        $amax = (float)$cfg['amount_max'];
        if ($amax < $amin){ $t=$amin;$amin=$amax;$amax=$t; }
        // Decodificar amount e doubleMultiplier a partir do orderNo (formato: AR{ts}_{amountCents}_{doubleMul})
        $amount = null;
        $rolloverMultiple = null;
        if (!empty($orderNo) && strpos($orderNo, '_') !== false) {
            $parts = explode('_', $orderNo);
            if (count($parts) >= 3) {
                $amountCents = (int)$parts[1];
                $doubleMul = (int)$parts[2];
                $amountDec = $amountCents / 100.0;
                if ($amountDec < $amin) $amountDec = $amin;
                if ($amountDec > $amax) $amountDec = $amax;
                $amount = round($amountDec, 2);
                $rolloverMultiple = $doubleMul / 100.0; // ex.: 10100 -> 101.00
            }
        }
        // Fallback se não for possível decodificar: usa payload ou aleatório
        if ($amount === null) {
            if (isset($data['amount']) && is_numeric($data['amount'])) {
                $amountCents = (int)$data['amount'];
                $amountCandidate = $amountCents / 100.0;
                if ($amountCandidate < $amin) $amountCandidate = $amin;
                if ($amountCandidate > $amax) $amountCandidate = $amax;
                $amount = round($amountCandidate, 2);
            } else {
                $amount = round($amin + (mt_rand() / mt_getrandmax()) * ($amax - $amin), 2);
            }
        }

        if (empty($orderNo)) {
            file_put_contents($logFile, "Empty OrderNo\n", FILE_APPEND);
        }

        // Idempotency
        if (!empty($orderNo)) {
            $stmtCheck = $mysqli->prepare("SELECT id FROM red_pocket_rewards WHERE order_no = ?");
            $stmtCheck->bind_param("s", $orderNo);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows > 0) {
                $stmtCheck->close();
                file_put_contents($logFile, "Duplicate OrderNo: $orderNo\n", FILE_APPEND);
                sendTrpcResponse(["status" => "SUCCESS"]); 
                exit;
            }
            $stmtCheck->close();
        }

        // 1. Update Balance
        $oldBalance = $user['saldo'];
        $newBalance = $oldBalance + $amount;
        $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
        $stmt->bind_param("di", $newBalance, $user['id']);
        if ($stmt->execute()) {
             file_put_contents($logFile, "Balance Updated: $oldBalance -> $newBalance\n", FILE_APPEND);
        } else {
             file_put_contents($logFile, "Balance Update Error: " . $stmt->error . "\n", FILE_APPEND);
        }
        $stmt->close();
        
        // 2. Insert into red_pocket_rewards
        if (!empty($orderNo)) {
            $stmtInsert = $mysqli->prepare("INSERT INTO red_pocket_rewards (user_id, order_no, batch_no, amount, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmtInsert->bind_param("issd", $user['id'], $orderNo, $batchNo, $amount);
            if (!$stmtInsert->execute()) {
                 file_put_contents($logFile, "Insert Reward Error: " . $stmtInsert->error . "\n", FILE_APPEND);
            }
            $stmtInsert->close();
        }

        // 3. Audit Flow
        if ($rolloverMultiple === null) {
            // calcular padrão se não foi possível decodificar do orderNo
            $rolloverMultiple = 18.00;
        }
        $needFlow = $amount * $rolloverMultiple;
        $flowType = 'ACTIVITY';
        $activityName = 'RedPacket';
        $status = 'notStarted';
        
        $stmtAudit = $mysqli->prepare("INSERT INTO audit_flows (user_id, amount, flow_multiple, need_flow, current_flow, status, flow_type, activity_name) VALUES (?, ?, ?, ?, 0, ?, ?, ?)");
        $stmtAudit->bind_param("iddssss", $user['id'], $amount, $rolloverMultiple, $needFlow, $status, $flowType, $activityName);
        $stmtAudit->execute();
        $stmtAudit->close();
        
        // 4. Log Transaction
        $stmtLog = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, 'Reward Manual', NOW())");
        $stmtLog->bind_param("id", $user['id'], $amount);
        $stmtLog->execute();
        $stmtLog->close();
        
        sendTrpcResponse(["status" => "SUCCESS"]);
    } else {
        file_put_contents($logFile, "Login Required\n", FILE_APPEND);
        sendTrpcResponse(["status" => "ERROR", "message" => "Login required"]);
    }
}
if ($path === '/api/frontend/trpc/vip.info') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $vipLevel = $user['vip'] ?? 0;
        $userId = $user['id'];
        $totalValidBetAmount = 0;
        $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = ?");
        if ($stmtBet) {
            $stmtBet->bind_param("i", $userId);
            $stmtBet->execute();
            $betResult = $stmtBet->get_result();
            if ($betRow = $betResult->fetch_assoc()) {
                $totalValidBetAmount = (float)($betRow['total_apostado'] ?? 0) * 100;
            }
            $stmtBet->close();
        }
        $vipLevels = [];
        $stmt = $mysqli->prepare("SELECT * FROM vip_levels ORDER BY id_vip ASC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $vipLevels[$row['id_vip']] = $row;
            }
            $stmt->close();
        }
        $newVipLevel = $vipLevel;
        foreach ($vipLevels as $vData) {
            $metaCents = (float)$vData['meta'] * 100;
            if ($totalValidBetAmount >= $metaCents && $vData['id_vip'] > $newVipLevel) {
                $newVipLevel = $vData['id_vip'];
            }
        }
        if ($newVipLevel > $vipLevel) {
            $stmtUpdate = $mysqli->prepare("UPDATE usuarios SET vip = ? WHERE id = ?");
            $stmtUpdate->bind_param("ii", $newVipLevel, $userId);
            $stmtUpdate->execute();
            $stmtUpdate->close();
            $vipLevel = $newVipLevel;
        }
        $formatVipData = function($level, $row) {
            if (!$row) return [
                "id" => 0, "vipId" => 159, "level" => $level,
                "promotionBet" => 0, "promotionRecharge" => 0, "promotionReward" => 0,
                "dailyBet" => 0, "dailyReward" => 0, "weeklyBet" => 0, "weeklyReward" => 0,
                "monthlyBet" => 0, "monthlyReward" => 0, "retentionBet" => 0, "retentionRecharge" => 0,
                "createTime" => date('Y-m-d\TH:i:s.000\Z'), "updateTime" => date('Y-m-d\TH:i:s.000\Z'),
                "lastOperator" => null
            ];
            return [
                "id" => $row['id'],
                "vipId" => 159,
                "level" => (int)$row['id_vip'],
                "promotionBet" => (float)$row['meta'] * 100,
                "promotionRecharge" => 0,
                "promotionReward" => (float)$row['bonus'] * 100,
                "dailyBet" => (float)$row['meta'] * 0.60 * 100,
                "dailyReward" => (float)$row['bonus'] * 0.55 * 100,
                "weeklyBet" => (float)$row['meta'] * 0.90 * 100,
                "weeklyReward" => (float)$row['bonus'] * 0.05 * 100,
                "monthlyBet" => 0,
                "monthlyReward" => 0,
                "retentionBet" => 0,
                "retentionRecharge" => 0,
                "createTime" => date('Y-m-d\TH:i:s.000\Z'),
                "updateTime" => date('Y-m-d\TH:i:s.000\Z'),
                "lastOperator" => "system"
            ];
        };
        $currentVip = $formatVipData($vipLevel, $vipLevels[$vipLevel] ?? null);
        $nextVipLevelId = $vipLevel + 1;
        $nextVip = $formatVipData($nextVipLevelId, $vipLevels[$nextVipLevelId] ?? null);
        $response = [
            "status" => true,
            "data" => [
                "userId" => $user['id'],
                "userVipLevelId" => $vipLevel,
                "currentVipLevel" => $currentVip,
                "nextVipLevel" => $nextVip,
                "totalValidBetAmount" => $totalValidBetAmount,
                "totalRechargeAmount" => 0,
                "vipLevelCount" => count($vipLevels)
            ]
        ];
        sendTrpcResponse($response);
    } else {
         $response = [
            "status" => true,
            "data" => [
                "userId" => 0,
                "userVipLevelId" => 0,
                "currentVipLevel" => ["level" => 0],
                "nextVipLevel" => ["level" => 1], 
                "totalValidBetAmount" => 0,
                "totalRechargeAmount" => 0,
                "vipLevelCount" => 10
            ]
        ];
        sendTrpcResponse($response);
    }
}
if ($path === '/api/frontend/trpc/vip.receiveAll') {
    $rotaEncontrada = true;
    // Garante que a coluna created_at existe com DEFAULT correto
    @$mysqli->query("ALTER TABLE vip_rewards_log ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $receiveType = isset($json['receiveType']) ? $json['receiveType'] : '';
    $user = getCurrentUser($mysqli);
    $logFile = __DIR__ . '/vip_debug.log';
    $rawBody = file_get_contents('php://input');
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " vip.receiveAll | user=" . ($user['id'] ?? 'none') . " | vip=" . ($user['vip'] ?? '?') . " | receiveType=" . $receiveType . " | rawBody=" . $rawBody . "\n", FILE_APPEND);
    if ($user) {
        if ($receiveType === 'DAILY') {
             $today = date('Y-m-d');
             $stmtC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'DAILY' AND DATE(created_at) = CURDATE()");
             $stmtC->bind_param("i", $user['id']);
             $stmtC->execute();
             if ($stmtC->get_result()->num_rows > 0) {
                 sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Already claimed today"]);
             }
             $stmtC->close();
             $vipLevel = $user['vip'];
             if ($vipLevel <= 0) { sendApiError(400, "VIP Level 0 not eligible"); }
             $stmtL = $mysqli->prepare("SELECT meta, bonus FROM vip_levels WHERE id_vip = ?");
             $stmtL->bind_param("i", $vipLevel);
             $stmtL->execute();
             $resL = $stmtL->get_result();
             if ($rowL = $resL->fetch_assoc()) {
                  $dailyBetTarget = (float)$rowL['meta'] * 0.60;
                  $dailyReward = (float)$rowL['bonus'] * 0.55;
                  file_put_contents($logFile, date('[Y-m-d H:i:s]') . " DAILY | vipLevel=$vipLevel | meta={$rowL['meta']} | bonus={$rowL['bonus']} | dailyBetTarget=$dailyBetTarget | dailyReward=$dailyReward\n", FILE_APPEND);
                  // Se meta=0, bloqueia para evitar coleta infinita
                  if ($dailyBetTarget <= 0) {
                      file_put_contents($logFile, date('[Y-m-d H:i:s]') . " DAILY BLOCK: dailyBetTarget=0\n", FILE_APPEND);
                      sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Daily reward not configured for this VIP level"]);
                  }
                  $currentDailyBet = 0;
                  $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND DATE(created_at) = CURDATE()");
                  $stmtBet->bind_param("i", $user['id']);
                  $stmtBet->execute();
                  if ($rowB = $stmtBet->get_result()->fetch_assoc()) {
                      $currentDailyBet = (float)$rowB['total'] ?? 0;
                  }
                  $stmtBet->close();
                  file_put_contents($logFile, date('[Y-m-d H:i:s]') . " DAILY | currentDailyBet=$currentDailyBet vs target=$dailyBetTarget | today=$today\n", FILE_APPEND);
                  if ($currentDailyBet < $dailyBetTarget) {
                      file_put_contents($logFile, date('[Y-m-d H:i:s]') . " DAILY BLOCK: bet not met ($currentDailyBet < $dailyBetTarget)\n", FILE_APPEND);
                      sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Daily bet target not met"]);
                  }
                  $amountCents = $dailyReward * 100;
                  $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                  $stmtAdd->bind_param("di", $dailyReward, $user['id']);
                  $stmtAdd->execute();
                  $stmtAdd->close();
                  $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'DAILY', NOW())");
                  $stmtLog->bind_param("iid", $user['id'], $vipLevel, $amountCents);
                  $stmtLog->execute();
                  $stmtLog->close();
                  file_put_contents($logFile, date('[Y-m-d H:i:s]') . " DAILY SUCCESS | amountCents=$amountCents | credited=$dailyReward\n", FILE_APPEND);
                  sendTrpcResponse([
                        "status" => true,
                        "claimed_count" => 1,
                        "amount" => $amountCents,
                        "type" => "BALANCE",
                        "msg" => "Success"
                    ]);
             }
             $stmtL->close();
        } elseif ($receiveType === 'WEEKLY') {
             $stmtC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'WEEKLY' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
             $stmtC->bind_param("i", $user['id']);
             $stmtC->execute();
             if ($stmtC->get_result()->num_rows > 0) {
                 sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Already claimed this week"]);
             }
             $stmtC->close();
             $vipLevel = $user['vip'];
             if ($vipLevel <= 0) { sendApiError(400, "VIP Level 0 not eligible"); }
             $stmtL = $mysqli->prepare("SELECT meta, bonus FROM vip_levels WHERE id_vip = ?");
             $stmtL->bind_param("i", $vipLevel);
             $stmtL->execute();
             $resL = $stmtL->get_result();
             if ($rowL = $resL->fetch_assoc()) {
                  $weeklyBetTarget = (float)$rowL['meta'] * 0.90;
                  $weeklyReward = (float)$rowL['bonus'] * 0.05;
                  $currentWeeklyBet = 0;
                  $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
                  $stmtBet->bind_param("i", $user['id']);
                  $stmtBet->execute();
                  if ($rowB = $stmtBet->get_result()->fetch_assoc()) {
                      $currentWeeklyBet = (float)$rowB['total'] ?? 0;
                  }
                  $stmtBet->close();
                  if ($currentWeeklyBet < $weeklyBetTarget) {
                      sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Weekly bet target not met"]);
                  }
                  $amountCents = $weeklyReward * 100;
                  $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                  $stmtAdd->bind_param("di", $weeklyReward, $user['id']);
                  $stmtAdd->execute();
                  $stmtAdd->close();
                  $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'WEEKLY', NOW())");
                  $stmtLog->bind_param("iid", $user['id'], $vipLevel, $amountCents);
                  $stmtLog->execute();
                  $stmtLog->close();
                  sendTrpcResponse([
                        "status" => true,
                        "claimed_count" => 1,
                        "amount" => $amountCents,
                        "type" => "BALANCE",
                        "msg" => "Success"
                    ]);
             }
             $stmtL->close();
        } else {
        $userId = $user['id'];
        $currentVipLevel = $user['vip'];
        $totalClaimed = 0;
        $vipLevels = [];
        $stmt = $mysqli->prepare("SELECT id_vip, bonus FROM vip_levels WHERE id_vip <= ?");
        $stmt->bind_param("i", $currentVipLevel);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $vipLevels[$row['id_vip']] = $row['bonus'];
        }
        $stmt->close();
        $claimed = [];
        $stmtC = $mysqli->prepare("SELECT vip_level FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'UPGRADE'");
        $stmtC->bind_param("i", $userId);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        while ($rowC = $resC->fetch_assoc()) {
            $claimed[$rowC['vip_level']] = true;
        }
        $stmtC->close();
        $totalAmountCents = 0;
        $mysqli->begin_transaction();
        try {
            foreach ($vipLevels as $lvl => $bonus) {
                if (!isset($claimed[$lvl]) && $lvl > 0) {
                     $amountCents = (float)$bonus * 100;
                     $amountReais = (float)$bonus;
                     $stmtAdd = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                     $stmtAdd->bind_param("di", $amountReais, $userId);
                     $stmtAdd->execute();
                     $stmtAdd->close();
                     $stmtLog = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'UPGRADE', NOW())");
                     $stmtLog->bind_param("iid", $userId, $lvl, $amountCents);
                     $stmtLog->execute();
                     $stmtLog->close();
                     $totalClaimed++;
                     $totalAmountCents += $amountCents;
                }
            }
            $mysqli->commit();
            // Se não havia UPGRADE pendente, tenta processar como DAILY
            if ($totalClaimed === 0) {
                $today = date('Y-m-d');
                $stmtDC = $mysqli->prepare("SELECT id FROM vip_rewards_log WHERE user_id = ? AND reward_type = 'DAILY' AND DATE(created_at) = CURDATE()");
                $stmtDC->bind_param("i", $userId);
                $stmtDC->execute();
                $dailyAlreadyClaimed = $stmtDC->get_result()->num_rows > 0;
                $stmtDC->close();
                if ($dailyAlreadyClaimed) {
                    sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Already claimed today"]);
                }
                $stmtVip = $mysqli->prepare("SELECT meta, bonus FROM vip_levels WHERE id_vip = ?");
                $stmtVip->bind_param("i", $currentVipLevel);
                $stmtVip->execute();
                $resVip = $stmtVip->get_result();
                if ($rowVip = $resVip->fetch_assoc()) {
                    $dailyBetTarget = (float)$rowVip['meta'] * 0.60;
                    $dailyReward    = (float)$rowVip['bonus'] * 0.55;
                    if ($dailyBetTarget <= 0) {
                        sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Daily not configured"]);
                    }
                    $currentDailyBet = 0;
                    $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND DATE(created_at) = CURDATE()");
                    $stmtBet->bind_param("i", $userId);
                    $stmtBet->execute();
                    if ($rowB = $stmtBet->get_result()->fetch_assoc()) {
                        $currentDailyBet = (float)$rowB['total'];
                    }
                    $stmtBet->close();
                    if ($currentDailyBet < $dailyBetTarget) {
                        sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"Daily bet target not met"]);
                    }
                    $dailyAmountCents = $dailyReward * 100;
                    $stmtAdd2 = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
                    $stmtAdd2->bind_param("di", $dailyReward, $userId);
                    $stmtAdd2->execute();
                    $stmtAdd2->close();
                    $stmtLog2 = $mysqli->prepare("INSERT INTO vip_rewards_log (user_id, vip_level, reward_amount, reward_type, created_at) VALUES (?, ?, ?, 'DAILY', NOW())");
                    $stmtLog2->bind_param("iid", $userId, $currentVipLevel, $dailyAmountCents);
                    $stmtLog2->execute();
                    $stmtLog2->close();
                    sendTrpcResponse(["status"=>true, "claimed_count"=>1, "amount"=>$dailyAmountCents, "type"=>"BALANCE", "msg"=>"Success"]);
                }
                $stmtVip->close();
                sendTrpcResponse(["status"=>true, "claimed_count"=>0, "amount"=>0, "type"=>"BALANCE", "msg"=>"VIP data not found"]);
            }
             sendTrpcResponse([
                "status" => true, 
                "claimed_count" => $totalClaimed,
                "amount" => $totalAmountCents,
                "type" => "BALANCE",
                "msg" => "Success"
             ]);
         } catch (Exception $e) {
            $mysqli->rollback();
            sendApiError(500, "Transaction failed");
        }
    }
    } else {
        sendApiError(401, "Unauthorized");
    }
}
if ($path === '/api/frontend/trpc/favorite.create') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $gameId = isset($json['gameId']) ? intval($json['gameId']) : 0;
    $user = getCurrentUser($mysqli);
    if ($user && $gameId > 0) {
        $favs = [];
        if (!empty($user['favoritos'])) {
            $favs = explode(',', $user['favoritos']);
        }
        $favs = array_map('intval', $favs);
        if (!in_array($gameId, $favs)) {
            $favs[] = $gameId;
            $newFavs = implode(',', $favs);
            $stmt = $mysqli->prepare("UPDATE usuarios SET favoritos = ? WHERE id = ?");
            $stmt->bind_param("si", $newFavs, $user['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    sendTrpcResponse(null, [['values' => ['undefined']]]);
}
if ($path === '/api/frontend/trpc/favorite.del') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $gameId = isset($json['gameId']) ? intval($json['gameId']) : 0;
    $user = getCurrentUser($mysqli);
    if ($user && $gameId > 0) {
        $favs = [];
        if (!empty($user['favoritos'])) {
            $favs = explode(',', $user['favoritos']);
        }
        $favs = array_map('intval', $favs);
        $key = array_search($gameId, $favs);
        if ($key !== false) {
            unset($favs[$key]);
            $favs = array_values($favs);
            $newFavs = implode(',', $favs);
            $stmt = $mysqli->prepare("UPDATE usuarios SET favoritos = ? WHERE id = ?");
            $stmt->bind_param("si", $newFavs, $user['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    sendTrpcResponse(null, ['values' => ['undefined']]);
}
if ($path === '/api/frontend/trpc/avatarCount.avatarCount') {
    $rotaEncontrada = true;
    sendTrpcResponse(["count" => 12]); 
}
if ($path === '/api/frontend/trpc/game.end') {
    $rotaEncontrada = true;
    sendTrpcResponse(["status" => true]);
}
if ($path === '/api/frontend/trpc/user.assets') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $balance = 0;
    $freezeAmount = 0;
    $hasAssetPassword = false;
    $assetPassword = "";
    if ($user) {
        $balance = floatval($user['saldo'] ?? 0) * 100;
        if (isset($user['freeze']) && (int)$user['freeze'] === 1) {
            $freezeAmount = $balance;
        }
        $hasAssetPassword = !empty($user['senhaparasacar']);
        $assetPassword = $user['senhaparasacar'] ?? "";
    }
    $response = [
        "balance" => $balance,
        "yuebaoBalance" => 0,
        "safeBalance" => 0,
        "freezeAmount" => $freezeAmount,
        "commission" => floatval($user['saldo_afiliados'] ?? 0) * 100,
        "assetPassword" => $assetPassword,
        "isAssetPassword" => $hasAssetPassword,
        "passwordSwitch" => "ON"
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/user.bankList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $bankList = [];
    if ($user) {
        $stmt = $mysqli->prepare("SELECT id, realname, tipo, chave, cpf FROM metodos_pagamentos WHERE user_id = ? ORDER BY id DESC");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $bankList[] = [
                "id" => $row['id'],
                "name" => $row['tipo'],
                "realName" => $row['realname'],
                "account" => $row['chave'],
                "code" => $row['tipo'],
                "logo" => "" 
            ];
        }
        $stmt->close();
    }
    sendTrpcResponse($bankList);
}
if ($path === '/api/frontend/trpc/withdraw.getRealName') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    sendTrpcResponse([
        "realName" => $user['real_name'] ?? null
    ]);
}
if ($path === '/api/frontend/trpc/activity.rebateDetail') {
    $rotaEncontrada = true;
    sendTrpcResponse([
        "rebateDate" => date("Y-m-d"),
        "rebateAmount" => 0,
        "validBet" => 0,
        "status" => "RECEIVED"
    ]);
}
if ($path === '/api/frontend/trpc/activity.assistanceCashDetail') {
    $rotaEncontrada = true;
    sendTrpcResponse([
        "amount" => 0,
        "progress" => 0,
        "status" => "PROCESSING"
    ]);
}
if ($path === '/api/frontend/trpc/withdraw.type') {
    $rotaEncontrada = true;
    $response = [
        "withdrawSwitch" => true,
        "withdrawType" => [
            [
                "id" => 377,
                "name" => "PIX",
                "code" => "PIX",
                "minAmount" => (float)getConf('minsaque', 10) * 100,
                "maxAmount" => (float)getConf('maxsaque', 30000) * 100,
                "icon" => "",
                "amountButton" => "10,20,100,200,300,500,1000,5000",
                "isInputAmount" => true,
                "withdrawalAccountMax" => 1,
                "remind" => "",
                "sort" => 2,
                "isEdit" => false,
                "ratesJson" => "[{\"min\":0,\"max\":100,\"type\":\"fixed\",\"value\":0}]"
            ]
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/withdraw.getUserWithdrawInfo') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $userFlow = [];
    if ($user) {
        $userId = $user['id'];
        $dataSql = "SELECT * FROM audit_flows WHERE user_id = ? AND (status != 'finished' OR need_flow > current_flow) ORDER BY created_at DESC";
        $stmtData = $mysqli->prepare($dataSql);
        if ($stmtData) {
             $stmtData->bind_param("i", $userId);
             $stmtData->execute();
             $resData = $stmtData->get_result();
             while ($row = $resData->fetch_assoc()) {
                 $userFlow[] = [
                    "flowListId" => (int)$row['id'],
                    "userId" => (int)$row['user_id'],
                    "amount" => (float)$row['amount'] * 100,
                    "flowMultiple" => (float)$row['flow_multiple'],
                    "needFlow" => (float)$row['need_flow'] * 100,
                    "status" => $row['status'],
                    "flowType" => $row['flow_type'],
                    "createTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['created_at'])),
                    "updateTime" => date("Y-m-d\TH:i:s.000\Z", strtotime($row['updated_at'])),
                    "currentFlow" => (float)$row['current_flow'] * 100,
                    "releaseSetting" => $row['release_setting'],
                    "activityName" => $row['activity_name']
                 ];
             }
             $stmtData->close();
        }
    }
    $response = [
        "userFlow" => $userFlow,
        "RealNameData" => [
            "isRealName" => !empty($user['real_name']),
            "realName" => $user['real_name'] ?? null
        ],
        "shouldWaiveFee" => [
            "isFee" => false,
            "feeType" => "UP",
            "appAuditRecordsSwitch" => "ON"
        ],
        "withdrawalAccount" => "",
        "withdrawConfig" => [
            "appAuditRecordsSwitch" => "ON",
            "allowResetWithdrawPassword" => "ALLOW",
            "isCanSms" => true
        ]
    ];
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/withdraw.getWithdrawAccount') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $queryData = [];
    if ($user) {
        $resMethods = $mysqli->query("SELECT * FROM metodos_pagamentos WHERE user_id = '{$user['id']}' ORDER BY id DESC");
        if ($resMethods) {
            while ($row = $resMethods->fetch_assoc()) {
                $relatedCode = "pix_" . $row['id'];
                $pixType = strtoupper($row['tipo']); 
                $pixKey = $row['chave'];
                $realName = !empty($row['realname']) ? $row['realname'] : 'Usuario';
                $queryData[] = [
                    "id" => (string)($row['id'] . "1"), 
                    "relatedCode" => $relatedCode,
                    "tenantWithdrawTypeId" => 377, 
                    "valueType" => "BANKACCOUNT",
                    "value" => $pixKey,
                    "code" => $pixType,
                    "isDefault" => 0,
                    "icon" => ""
                ];
                $queryData[] = [
                    "id" => (string)($row['id'] . "2"),
                    "relatedCode" => $relatedCode,
                    "tenantWithdrawTypeId" => 377,
                    "valueType" => "REALNAME",
                    "value" => $realName,
                    "code" => "REALNAME",
                    "isDefault" => 0,
                    "icon" => ""
                ];
                $queryData[] = [
                    "id" => (string)($row['id'] . "3"),
                    "relatedCode" => $relatedCode,
                    "tenantWithdrawTypeId" => 377,
                    "valueType" => $pixType,
                    "value" => $pixKey,
                    "code" => $pixType,
                    "isDefault" => 0,
                    "icon" => ""
                ];
            }
        }
    } else {
    }
    sendTrpcResponse([
        "queryData" => $queryData,
        "withdrawalAccountMax" => 5,
        "withdrawalAccountCount" => count($queryData),
        "accountLimit" => "NoLimit",
        "feeType" => "UP"
    ]);
}
if ($path === '/api/frontend/trpc/withdraw.withdrawTypeAndSub') {
    $rotaEncontrada = true;
    sendTrpcResponse([
        [
            "id" => 377,
            "name" => "PIX",
            "code" => "PIX",
            "icon" => "",
            "sort" => 2,
            "maxAmount" => (float)getConf('maxsaque', 30000) * 100,
            "minAmount" => (float)getConf('minsaque', 10) * 100,
            "withdrawalAccountMax" => 1,
            "amountButton" => "10,20,100,200,300,500,1000,5000",
            "isInputAmount" => true,
            "ratesJson" => "[{\"min\":0,\"max\":100,\"type\":\"fixed\",\"value\":0}]",
            "isEdit" => false,
            "remind" => "",
            "tenantPayWithdrawTypeSub" => [
                ["id" => 1083, "code" => "PHONE", "icon" => "", "logo" => null, "name" => "Telefone", "sort" => 0, "bankSort" => null],
                ["id" => 1085, "code" => "CPF", "icon" => "", "logo" => null, "name" => "CPF", "sort" => 1, "bankSort" => null]
            ]
        ]
    ]);
}
if ($path === '/api/frontend/trpc/withdraw.createOrder') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "message" => "Unauthorized",
                "code" => -32001,
                "data" => ["code" => "UNAUTHORIZED", "httpStatus" => 401]
            ]
         ]);
         exit;
    }
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    if (isset($user['freeze']) && (int)$user['freeze'] === 1) {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "json" => [
                    "message" => "Saldo congelado. Entre em contato com o suporte.",
                    "code" => -32600,
                    "data" => ["code" => "BAD_REQUEST", "httpStatus" => 400, "path" => "withdraw.createOrder"]
                ]
            ]
         ]);
         exit;
    }
    $amountCents = $dataInput['amount'] ?? 0;
    $amount = (float)$amountCents / 100;
    $password = $dataInput['password'] ?? '';
    $pixKey = $dataInput['withdrawalAccount'] ?? '';
    $inputRealName = $dataInput['realName'] ?? $dataInput['name'] ?? '';
    $typeSubId = $dataInput['tenantWithdrawTypeSubId'] ?? 0;
    if ($password !== $user['senhaparasacar']) {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "json" => [
                    "message" => "Senha de saque incorreta",
                    "code" => -32600,
                    "data" => ["code" => "BAD_REQUEST", "httpStatus" => 400, "path" => "withdraw.createOrder"]
                ]
            ]
         ]);
         exit;
    }
    if ($user['saldo'] < $amount) {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "json" => [
                    "message" => "Saldo insuficiente",
                    "code" => -32600,
                    "data" => ["code" => "BAD_REQUEST", "httpStatus" => 400, "path" => "withdraw.createOrder"]
                ]
            ]
         ]);
         exit;
    }
    $minSaque = (float)getConf('minsaque', 10);
    $maxSaque = (float)getConf('maxsaque', 30000);
    if ($amount < $minSaque || $amount > $maxSaque) {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "json" => [
                    "message" => "Valor fora dos limites permitidos (Min: $minSaque, Max: $maxSaque)",
                    "code" => -32600,
                    "data" => ["code" => "BAD_REQUEST", "httpStatus" => 400, "path" => "withdraw.createOrder"]
                ]
            ]
         ]);
         exit;
    }
    if (!empty($pixKey)) {
        $stmtCheckDup = $mysqli->prepare("SELECT user_id FROM metodos_pagamentos WHERE chave = ?");
        $stmtCheckDup->bind_param("s", $pixKey);
        $stmtCheckDup->execute();
        $stmtCheckDup->store_result();
        if ($stmtCheckDup->num_rows > 0) {
            $stmtCheckDup->bind_result($existingUserId);
            $stmtCheckDup->fetch();
            if ($existingUserId != $user['id']) {
                $stmtCheckDup->close();
                 header('Content-Type: application/json');
                 echo json_encode([
                    "error" => [
                        "json" => [
                            "message" => "Chave PIX já cadastrada por outro usuário. Não é permitido sacar para esta chave.",
                            "code" => -32600,
                            "data" => ["code" => "BAD_REQUEST", "httpStatus" => 400, "path" => "withdraw.createOrder"]
                        ]
                    ]
                 ]);
                 exit;
            }
        }
        $stmtCheckDup->close();
    }
    $transacaoId = ((string) round(microtime(true) * 1000)) . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $stmt = $mysqli->prepare("INSERT INTO solicitacao_saques (id_user, valor, tipo, pix, telefone, data_registro, transacao_id, status, tipo_saque) VALUES (?, ?, 'PIX', ?, ?, NOW(), ?, 0, 0)");
    $stmt->bind_param("idsss", $user['id'], $amount, $pixKey, $user['celular'], $transacaoId);
    if ($stmt->execute()) {
        $stmt->close();
        if (!empty($pixKey)) {
            $stmtCheck = $mysqli->prepare("SELECT id FROM metodos_pagamentos WHERE chave = ?");
            if ($stmtCheck) {
                $stmtCheck->bind_param("s", $pixKey);
                $stmtCheck->execute();
                $stmtCheck->store_result();
                if ($stmtCheck->num_rows == 0) {
                    $realName = !empty($inputRealName) ? $inputRealName : ($user['real_name'] ?? 'Usuario');
                    if ($typeSubId == 1083) {
                        $pixType = 'PHONE';
                    } elseif ($typeSubId == 1085) {
                        $pixType = 'CPF';
                    } else {
                        if (strpos($pixKey, '@') !== false) $pixType = 'EMAIL';
                        elseif (strlen(preg_replace('/[^0-9]/', '', $pixKey)) == 11) $pixType = 'CPF'; 
                        elseif (strlen($pixKey) > 20) $pixType = 'EVP';
                        else $pixType = 'PHONE';
                    }
                    if ($pixType === 'CPF' || $pixType === 'PHONE') {
                        $stmtType = $mysqli->prepare("SELECT id FROM metodos_pagamentos WHERE user_id = ? AND tipo = ?");
                        $stmtType->bind_param("is", $user['id'], $pixType);
                        $stmtType->execute();
                        $stmtType->store_result();
                        $typeExists = $stmtType->num_rows > 0;
                        $stmtType->close();
                        $stmtTotal = $mysqli->prepare("SELECT COUNT(*) as total FROM metodos_pagamentos WHERE user_id = ?");
                        $stmtTotal->bind_param("i", $user['id']);
                        $stmtTotal->execute();
                        $resTotal = $stmtTotal->get_result();
                        $totalKeys = $resTotal->fetch_assoc()['total'];
                        $stmtTotal->close();
                        if (!$typeExists && $totalKeys < 2) {
                            $stmtMethod = $mysqli->prepare("INSERT INTO metodos_pagamentos (user_id, realname, tipo, chave, created_at) VALUES (?, ?, ?, ?, NOW())");
                            if ($stmtMethod) {
                                $stmtMethod->bind_param("isss", $user['id'], $realName, $pixType, $pixKey);
                                if (!$stmtMethod->execute()) {
                                    error_log("Failed to auto-save PIX key for user {$user['id']}: " . $stmtMethod->error);
                                }
                                $stmtMethod->close();
                            } else {
                                error_log("Failed to prepare PIX save statement: " . $mysqli->error);
                            }
                        }
                    }
                }
                $stmtCheck->close();
            } else {
                error_log("Failed to prepare PIX check statement: " . $mysqli->error);
            }
        }
        $stmtUpdate = $mysqli->prepare("UPDATE usuarios SET saldo = saldo - ? WHERE id = ?");
        $stmtUpdate->bind_param("di", $amount, $user['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // ── SAQUE AUTOMÁTICO VIA ECOMPAG ────────────────────────────────
        $cfg_auto = $mysqli->query("SELECT saque_automatico, limite_saque FROM config WHERE id = 1");
        $cfg_row  = $cfg_auto ? $cfg_auto->fetch_assoc() : [];
        $val_auto    = floatval($cfg_row['saque_automatico'] ?? 0);
        $limite_dia  = intval($cfg_row['limite_saque'] ?? 100);

        if ($val_auto > 0 && $amount <= $val_auto) {
            // Verificar limite diário
            $res_hoje = $mysqli->query(
                "SELECT COUNT(*) as c FROM solicitacao_saques WHERE status = 1 AND DATE(data_att) = CURDATE() AND tipo_saque = 0"
            );
            $count_hoje = intval($res_hoje ? $res_hoje->fetch_assoc()['c'] : 0);

            if ($count_hoje < $limite_dia) {
                // Buscar CPF e nome do usuário
                $cpf_auto = '';
                $nome_auto = $inputRealName ?: ($user['real_name'] ?? 'Cliente');
                $stmt_cpf = $mysqli->prepare("SELECT cpf FROM usuarios WHERE id = ? LIMIT 1");
                if ($stmt_cpf) {
                    $stmt_cpf->bind_param("i", $user['id']);
                    $stmt_cpf->execute();
                    $stmt_cpf->bind_result($cpf_auto);
                    $stmt_cpf->fetch();
                    $stmt_cpf->close();
                }
                // Buscar credenciais Ecompag
                $res_cred = $mysqli->query("SELECT client_id, client_secret FROM bspay WHERE id = 1 AND ativo = 1 LIMIT 1");
                $cred_auto = $res_cred ? $res_cred->fetch_assoc() : null;

                if ($cred_auto && !empty($cred_auto['client_id']) && !empty($cred_auto['client_secret'])) {
                    $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_auto);
                    $params_auto = http_build_query([
                        'client_id'     => $cred_auto['client_id'],
                        'client_secret' => $cred_auto['client_secret'],
                        'nome'          => $nome_auto ?: 'Cliente',
                        'cpf'           => $cpf_limpo,
                        'valor'         => number_format($amount, 2, '.', ''),
                        'chave_pix'     => $pixKey,
                        'descricao'     => 'Saque Auto - ID: ' . $transacaoId,
                        'urlnoty'       => ''
                    ]);

                    $curl_auto = curl_init('https://api.ecompag.com/v2/pix/payment.php');
                    curl_setopt_array($curl_auto, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $params_auto,
                        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                        CURLOPT_TIMEOUT        => 30,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                    ]);

                    $resp_auto  = curl_exec($curl_auto);
                    $curl_err   = curl_error($curl_auto);
                    $http_auto  = curl_getinfo($curl_auto, CURLINFO_HTTP_CODE);
                    curl_close($curl_auto);

                    $dados_auto = $resp_auto ? json_decode($resp_auto, true) : [];
                    $auto_ok    = !$curl_err && isset($dados_auto['statusCode']) && $dados_auto['statusCode'] === 200;

                    error_log("[SAQUE_AUTO] user={$user['id']} transacao=$transacaoId valor=$amount http=$http_auto ok=" . ($auto_ok ? 'SIM' : 'NAO') . " resp=" . substr($resp_auto, 0, 200));

                    if ($auto_ok) {
                        $stmt_ap = $mysqli->prepare(
                            "UPDATE solicitacao_saques SET status = 1, data_att = CONVERT_TZ(NOW(), '+00:00', '-03:00') WHERE transacao_id = ?"
                        );
                        if ($stmt_ap) {
                            $stmt_ap->bind_param("s", $transacaoId);
                            $stmt_ap->execute();
                            $stmt_ap->close();
                        }
                    }
                }
            }
        }
        // ── FIM SAQUE AUTOMÁTICO ─────────────────────────────────────────

        echo json_encode([
            "result" => [
                "data" => [
                    "json" => null,
                    "meta" => [
                        "values" => ["undefined"]
                    ]
                ]
            ]
        ]);
    } else {
         header('Content-Type: application/json');
         echo json_encode([
            "error" => [
                "json" => [
                    "message" => "Erro ao processar saque: " . $stmt->error,
                    "code" => -32600,
                    "data" => ["code" => "INTERNAL_SERVER_ERROR", "httpStatus" => 500, "path" => "withdraw.createOrder"]
                ]
            ]
         ]);
    }
    exit;
}
if ($path === '/api/frontend/trpc/withdraw.record') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse([
            "withdrawalOrderPage" => [],
            "sumValues" => null
        ]);
        exit;
    }
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    $page = $dataInput['page'] ?? 1;
    $pageSize = $dataInput['pageSize'] ?? 10;
    $startTime = $dataInput['startTime'] ?? null;
    $endTime = $dataInput['endTime'] ?? null;
    $offset = ($page - 1) * $pageSize;
    $query = "SELECT id, transacao_id, valor, status, data_registro, pix, tipo, tipo_saque FROM solicitacao_saques WHERE id_user = ?";
    $params = [$user['id']];
    $types = "i";
    if ($startTime && $endTime) {
        $start = date('Y-m-d H:i:s', strtotime($startTime));
        $end = date('Y-m-d H:i:s', strtotime($endTime));
        $query .= " AND data_registro BETWEEN ? AND ?";
        $params[] = $start;
        $params[] = $end;
        $types .= "ss";
    }
    $countQuery = str_replace("SELECT id, transacao_id, valor, status, data_registro, pix, tipo, tipo_saque", "SELECT COUNT(*) as total, SUM(valor) as total_valor", $query);
    $stmtCount = $mysqli->prepare($countQuery);
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result()->fetch_assoc();
    $total = $countResult['total'];
    $totalAmount = $countResult['total_valor'] ?? 0;
    $stmtCount->close();
    $query .= " ORDER BY data_registro DESC LIMIT ? OFFSET ?";
    $params[] = $pageSize;
    $params[] = $offset;
    $types .= "ii";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $withdrawalOrderPage = [];
    while ($row = $result->fetch_assoc()) {
        $statusMap = [
            0 => 'apply',
            1 => 'success',
            2 => 'refuse',
            3 => 'refuse',
            4 => 'success'
        ];
        $withdrawalOrderPage[] = [
            "id" => $row['id'],
            "orderNo" => $row['transacao_id'],
            "amount" => (float)$row['valor'] * 100, 
            "actualAmount" => (float)$row['valor'] * 100, 
            "fee" => 0,
            "status" => $statusMap[$row['status']] ?? 'apply',
            "withdrawalTime" => $row['data_registro'],
            "createTime" => $row['data_registro'],
            "remark" => "",
            "bankName" => "PIX",
            "accountHolder" => "",
            "bankCard" => $row['pix'],
            "failReason" => ""
        ];
    }
    $stmt->close();
    sendTrpcResponse([
        "withdrawalOrderPage" => $withdrawalOrderPage,
        "sumValues" => (float)$totalAmount * 100,
        "total" => $total
    ]);
}
if ($path === '/api/frontend/trpc/withdraw.editAssetPassword') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $trpcInput = getTrpcInput();
        $dataInput = $trpcInput['json'] ?? [];
        $password = $dataInput['password'] ?? '';
        if (!empty($password)) {
            $stmt = $mysqli->prepare("UPDATE usuarios SET senhaparasacar = ? WHERE id = ?");
            $stmt->bind_param("si", $password, $user['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    echo json_encode([
        "result" => [
            "data" => [
                "json" => null,
                "meta" => [
                    "values" => ["undefined"]
                ]
            ]
        ]
    ]);
    exit;
}
if ($path === '/api/frontend/trpc/activity.recordList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $input = getTrpcInput();
    $json = $input['json'] ?? $input;
    $page      = isset($json['page'])     ? intval($json['page'])     : 1;
    $pageSize  = isset($json['pageSize']) ? intval($json['pageSize']) : 15;
    $startTime = $json['startTime'] ?? null;
    $endTime   = $json['endTime']   ?? null;
    $offset = ($page - 1) * $pageSize;

    $status = $json['status'] ?? 'RECEIVED';
    $logFile = __DIR__ . '/vip_debug.log';
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " recordList | status=$status | startTime=" . ($json['startTime'] ?? 'null') . " | endTime=" . ($json['endTime'] ?? 'null') . " | user=" . ($user['id'] ?? 'none') . "\n", FILE_APPEND);

    $recordList = [];
    $allAmount  = 0;
    $total      = 0;

    // DISTRIBUTED = pendente (não coletado ainda) — não temos esse fluxo, retorna vazio
    if ($status === 'DISTRIBUTED') {
        sendTrpcResponse([
            'recordList'   => [],
            'total'        => 0,
            'all'          => ['allAmount' => 0],
            'randomAmount' => ['allMinAmount' => 0, 'allMaxAmount' => 0],
        ]);
    }

    if ($user) {
        $userId = $user['id'];
        $where  = "user_id = ?";
        $types  = "i";
        $params = [$userId];

        // Frontend manda datas em UTC, banco salva em UTC — usar gmdate para manter UTC
        if ($startTime) {
            $where .= " AND created_at >= ?";
            $types .= "s";
            $params[] = gmdate('Y-m-d H:i:s', strtotime($startTime));
        }
        if ($endTime) {
            $where .= " AND created_at <= ?";
            $types .= "s";
            $params[] = gmdate('Y-m-d H:i:s', strtotime($endTime));
        }

        $stmtCount = $mysqli->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(reward_amount),0) as total_amt FROM vip_rewards_log WHERE $where");
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $resCount  = $stmtCount->get_result()->fetch_assoc();
        $total     = (int)$resCount['cnt'];
        $allAmount = (float)$resCount['total_amt'];
        $stmtCount->close();

        $stmtR = $mysqli->prepare("SELECT id, reward_type, reward_amount, vip_level, created_at FROM vip_rewards_log WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $typesR  = $types . "ii";
        $paramsR = array_merge($params, [$pageSize, $offset]);
        $stmtR->bind_param($typesR, ...$paramsR);
        $stmtR->execute();
        $resR = $stmtR->get_result();

        $labelMap = [
            'DAILY'   => 'Bônus Diário VIP',
            'WEEKLY'  => 'Bônus Semanal VIP',
            'MONTHLY' => 'Bônus Mensal VIP',
            'UPGRADE' => 'Bônus Upgrade VIP',
        ];

        while ($row = $resR->fetch_assoc()) {
            $recordList[] = [
                'id'           => (int)$row['id'],
                'time'         => date('Y-m-d\TH:i:s.000\Z', strtotime($row['created_at'])),
                'activityType' => 'vip',
                'activityName' => $labelMap[$row['reward_type']] ?? ('VIP ' . $row['reward_type']),
                'rewardType'   => 'BALANCE',
                'awardCount'   => (float)$row['reward_amount'],
                'remake'       => [
                    'receiveType' => $row['reward_type'],
                    'vipLevel'    => (int)$row['vip_level'],
                ],
                'status'       => 'received',
            ];
        }
        $stmtR->close();
    }

    sendTrpcResponse([
        'recordList'   => $recordList,
        'total'        => $total,
        'all'          => ['allAmount' => $allAmount],
        'randomAmount' => ['allMinAmount' => 0, 'allMaxAmount' => 0],
    ]);
}
if ($path === '/api/frontend/trpc/activity.validUsers') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse([
            "pageData" => [],
            "total" => 0
        ]);
        exit;
    }
    $input = getTrpcInput();
    $data = $input['json'] ?? [];
    $page = isset($data['page']) ? (int)$data['page'] : 1;
    $pageSize = isset($data['pageSize']) ? (int)$data['pageSize'] : 10;
    if ($page < 1) $page = 1;
    if ($pageSize < 1) $pageSize = 10;
    $offset = ($page - 1) * $pageSize;
    $config_afiliados_qry = "SELECT minDepForCpa, minResgate FROM afiliados_config WHERE id = 1";
    $config_afiliados_resp = mysqli_query($mysqli, $config_afiliados_qry);
    $min_deposito = 20;
    $min_apostado = 30;
    if ($config_afiliados_resp && $row = mysqli_fetch_assoc($config_afiliados_resp)) {
        $min_deposito = $row['minDepForCpa'];
        $min_apostado = $row['minResgate'];
    }
    $inviteCode = $user['invite_code'];
    $stmtCount = $mysqli->prepare("SELECT COUNT(*) as total FROM usuarios WHERE invitation_code = ?");
    $stmtCount->bind_param("s", $inviteCode);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    $total = $resCount->fetch_assoc()['total'] ?? 0;
    $stmtCount->close();
    $stmt = $mysqli->prepare("SELECT id, mobile, data_registro FROM usuarios WHERE invitation_code = ? ORDER BY id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $inviteCode, $pageSize, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $pageData = [];
    while ($row = $res->fetch_assoc()) {
        $idConvidado = $row['id'];
        $qryDeposito = "SELECT valor, data_registro FROM transacoes WHERE usuario = $idConvidado AND status = 'pago' ORDER BY data_registro ASC";
        $resDeposito = mysqli_query($mysqli, $qryDeposito);
        $total_depositado = 0;
        $rechargeCount = 0;
        $depositDays = [];
        $firstRechargeAmount = 0;
        while ($drow = mysqli_fetch_assoc($resDeposito)) {
            if ($firstRechargeAmount == 0) {
                $firstRechargeAmount = $drow['valor'];
            }
            $total_depositado += $drow['valor'];
            $rechargeCount++;
            $day = date('Y-m-d', strtotime($drow['data_registro']));
            $depositDays[$day] = true;
        }
        $rechargeDays = count($depositDays);
        $qryAposta = "SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = $idConvidado AND status_play = '1'";
        $resAposta = mysqli_query($mysqli, $qryAposta);
        $total_apostado = ($resAposta && $rowBet = mysqli_fetch_assoc($resAposta)) ? ($rowBet['total_apostado'] ?? 0) : 0;
        $isValid = ($total_depositado >= $min_deposito && $total_apostado >= $min_apostado);
        $pageData[] = [
            "id" => $row['id'],
            "userId" => $row['id'],
            "username" => $row['mobile'],
            "createTime" => $row['data_registro'],
            "registerTime" => $row['data_registro'],
            "depositAmount" => (float)$total_depositado * 100,
            "recharge" => (float)$total_depositado * 100,
            "deposit" => (float)$total_depositado * 100,
            "total_deposit" => (float)$total_depositado * 100,
            "amount" => (float)$total_depositado * 100,
            "historicalPay" => (float)$total_depositado * 100,
            "firstRechargeAmount" => (float)$firstRechargeAmount * 100,
            "betAmount" => (float)$total_apostado * 100,
            "bet" => (float)$total_apostado * 100,
            "total_bet" => (float)$total_apostado * 100,
            "bet_amount" => (float)$total_apostado * 100,
            "historicalBetting" => (float)$total_apostado * 100,
            "valid" => $isValid,
            "status" => $isValid ? 1 : 0,
            "rechargeCount" => $rechargeCount,
            "rechargeDays" => $rechargeDays
        ];
    }
    $stmt->close();
    sendTrpcResponse([
        "pageData" => $pageData,
        "total" => $total
    ]);
}
if ($path === '/api/frontend/trpc/activity.sharePhone') {
    $rotaEncontrada = true;
    $data = [
        "usePhones" => [],
        "allPhones" => [
            "5511932025537",
            "5511952036871",
            "5511961974298",
            "5511965979673",
            "5511990088826",
            "5512981483134",
            "5515997490205",
            "5515997814789",
            "5516991844880",
            "5517996659528",
            "5519982120680",
            "5521965904180",
            "5521966399734",
            "5521974847099",
            "5521981613401",
            "5521982006292",
            "5521982737430",
            "5522998568334",
            "5524981475886",
            "5531992447609"
        ]
    ];
    sendTrpcResponse($data);
}

if ($path === '/api/frontend/trpc/activity.apply') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse(["status" => false, "message" => "Login required"]);
        exit;
    }
    $input = getTrpcInput();
    $data = $input['json'] ?? [];
    $applyInfo = $data['applyInfo'] ?? [];
    $isAgency = isset($applyInfo['type']) && $applyInfo['type'] === 'Agency';
    $isSignIn = isset($applyInfo['type']) && $applyInfo['type'] === 'SignIn';
    $isAssistanceCash = isset($applyInfo['type']) && $applyInfo['type'] === 'AssistanceCash';
    $rewardId = $applyInfo['info']['rewardId'] ?? '';
    $chestId = 0;
    $targetBauId = 0;
    if ($isAssistanceCash) {
        $activityId = $data['id'] ?? 637;

        $spinCheckStmt = $mysqli->prepare("SELECT LuckyWheel FROM usuarios WHERE id = ?");
        $spinCheckStmt->bind_param("i", $user['id']);
        $spinCheckStmt->execute();
        $spinRes = $spinCheckStmt->get_result();
        $userSpins = $spinRes->fetch_assoc()['LuckyWheel'] ?? 0;
        $spinCheckStmt->close();
        if ($userSpins <= 0) {
            sendTrpcResponse([
                "status" => false,
                "message" => "No spins available",
                "canReward" => false
            ]);
            exit;
        }
        $deductStmt = $mysqli->prepare("UPDATE usuarios SET LuckyWheel = LuckyWheel - 1 WHERE id = ?");
        $deductStmt->bind_param("i", $user['id']);
        $deductStmt->execute();
        $deductStmt->close();
        $roundStmt = $mysqli->prepare("SELECT id, current_amount, target_amount, end_time, draw_count FROM assistance_rounds WHERE user_id = ? AND status = 0 LIMIT 1");
        $roundStmt->bind_param("i", $user['id']);
        $roundStmt->execute();
        $roundRes = $roundStmt->get_result();
        $activeRound = $roundRes->fetch_assoc();
        $roundStmt->close();
        $totalAccumulated = 0;
        $drawCount = 0;
        $amountWon = 0;
        $awards = [
            ["uuid" => "b59e1b54342441a1be5ae1ca67c4b2b7", "type" => "rangeAmount", "amount" => 0, "weight" => 100],
            ["uuid" => "1ffeff4741f242ccb07dca8771401d8d", "type" => "fixedAmount", "amount" => 5000, "weight" => 0],
            ["uuid" => "d2a1098bc3e94cd2a5e19eef068e975d", "type" => "fixedAmount", "amount" => 18800, "weight" => 0],
            ["uuid" => "c6134b2d7cd743dfa3bce96c51b22e19", "type" => "fixedAmount", "amount" => 10000, "weight" => 0],
            ["uuid" => "6b21e455908a4e68a0091f947bf1de69", "type" => "fixedAmount", "amount" => 100000, "weight" => 0],
            ["uuid" => "28d804b7cbbf47008ab7f04c7c28196a", "type" => "bonus", "amount" => 10800, "weight" => 0]
        ];
        $selectedAward = $awards[0];
        if ($activeRound) {
            if (strtotime($activeRound['end_time']) < time()) {
                $expireStmt = $mysqli->prepare("UPDATE assistance_rounds SET status = 2 WHERE id = ?");
                $expireStmt->bind_param("i", $activeRound['id']);
                $expireStmt->execute();
                $expireStmt->close();
                $activeRound = null;
            } else {
                $totalAccumulated = $activeRound['current_amount'];
                $drawCount = $activeRound['draw_count'];
                
                // Lógica sugerida: máximo 9900. Se já estiver em 9900, ganha 0.
                if ($totalAccumulated >= 9900) {
                    $amountWon = 0;
                } else {
                    $amountWon = rand(10, 50);
                    if ($totalAccumulated + $amountWon > 9900) {
                        $amountWon = 9900 - $totalAccumulated;
                    }
                }
                
                $totalAccumulated += $amountWon;
                $drawCount++;
                $updateStmt = $mysqli->prepare("UPDATE assistance_rounds SET current_amount = ?, draw_count = ? WHERE id = ?");
                $updateStmt->bind_param("iii", $totalAccumulated, $drawCount, $activeRound['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
        if (!$activeRound) {
            $totalWeight = 0;
            foreach ($awards as $award) $totalWeight += $award['weight'];
            $rand = rand(1, $totalWeight);
            $currentWeight = 0;
            foreach ($awards as $award) {
                $currentWeight += $award['weight'];
                if ($rand <= $currentWeight) {
                    $selectedAward = $award;
                    break;
                }
            }
            if ($selectedAward['type'] === 'rangeAmount') {
                $amountWon = rand(9000, 9700);
            } else {
                $amountWon = $selectedAward['amount'];
            }
            $totalAccumulated = $amountWon;
            $drawCount = 1;
            $startTime = date('Y-m-d H:i:s');
            $endTime = date('Y-m-d H:i:s', strtotime('+4 days'));
            $insertStmt = $mysqli->prepare("INSERT INTO assistance_rounds (user_id, current_amount, target_amount, status, start_time, end_time, draw_count) VALUES (?, ?, 10000, 0, ?, ?, ?)");
            $insertStmt->bind_param("iissi", $user['id'], $totalAccumulated, $startTime, $endTime, $drawCount);
            $insertStmt->execute();
            $insertStmt->close();
        }
        $sumStmt = $mysqli->prepare("SELECT SUM(current_amount) as total_amount, SUM(draw_count) as total_draws FROM assistance_rounds WHERE user_id = ?");
        $sumStmt->bind_param("i", $user['id']);
        $sumStmt->execute();
        $sumRes = $sumStmt->get_result();
        $sumData = $sumRes->fetch_assoc();
        $sumStmt->close();
        $globalTotalAmount = $sumData['total_amount'] ?? $totalAccumulated;
        $globalTotalDraws = $sumData['total_draws'] ?? $drawCount;

        $canReward = ($totalAccumulated >= 10000);

        // Salvar dados na tabela roletinha100
        $saveStmt = $mysqli->prepare("INSERT INTO roletinha100 (user_id, uuid, type, amount, weight, award_count, can_reward) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $saveCanReward = $canReward ? 1 : 0;
        $saveStmt->bind_param("issiiis", $user['id'], $selectedAward['uuid'], $selectedAward['type'], $selectedAward['amount'], $selectedAward['weight'], $amountWon, $saveCanReward);
        $saveStmt->execute();
        $saveStmt->close();

        sendTrpcResponse([
            "uuid" => $selectedAward['uuid'],
            "type" => $selectedAward['type'],
            "amount" => $selectedAward['amount'],
            "weight" => $selectedAward['weight'],
            "awardCount" => $amountWon, 
            "rangeAmount" => (int)$globalTotalAmount, 
            "allRoundCount" => (int)$globalTotalDraws, 
            "drawCount" => (int)$drawCount, 
            "canReward" => $canReward
        ]);
        exit;
    }
    if ($isSignIn) {
        $today = date('Y-m-d');
        $checkStmt = $mysqli->prepare("SELECT id FROM signin_records WHERE user_id = ? AND date_record = ?");
        $checkStmt->bind_param("is", $user['id'], $today);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            sendTrpcResponse(["status" => false, "message" => "Already signed in today"]);
            exit;
        }
        $checkStmt->close();
        $streak = 0;
        $stmt = $mysqli->prepare("SELECT date_record FROM signin_records WHERE user_id = ? ORDER BY date_record DESC");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $dates = [];
        while ($row = $res->fetch_assoc()) {
            $dates[] = $row['date_record'];
        }
        $stmt->close();
        if (!empty($dates)) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $checkDate = $yesterday;
            foreach ($dates as $d) {
                if ($d == $checkDate) {
                    $streak++;
                    $checkDate = date('Y-m-d', strtotime($d . ' -1 day'));
                } else {
                    break;
                }
            }
        }
        $dayIndex = ($streak % 7) + 1;
        $stmtConfig = $mysqli->prepare("SELECT * FROM signin_config WHERE day = ?");
        $stmtConfig->bind_param("i", $dayIndex);
        $stmtConfig->execute();
        $resConfig = $stmtConfig->get_result();
        $config = $resConfig->fetch_assoc();
        $stmtConfig->close();
        if (!$config) {
            sendTrpcResponse(["status" => false, "message" => "Config not found for day $dayIndex"]);
            exit;
        }
        $startOfDay = $today . ' 00:00:00';
        $todayValidBet = 0;
        $stmtBet = $mysqli->prepare("SELECT SUM(bet_money) as total FROM historico_play WHERE id_user = ? AND created_at >= ? AND status_play = 1");
        $stmtBet->bind_param("is", $user['id'], $startOfDay);
        $stmtBet->execute();
        $resBet = $stmtBet->get_result();
        if ($rowBet = $resBet->fetch_assoc()) {
            $todayValidBet = floatval($rowBet['total']) * 100; 
        }
        $stmtBet->close();
        $todayValidRecharge = 0;
        $stmtDep = $mysqli->prepare("SELECT SUM(valor) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago' AND data_registro >= ?");
        $stmtDep->bind_param("is", $user['id'], $startOfDay);
        $stmtDep->execute();
        $resDep = $stmtDep->get_result();
        if ($rowDep = $resDep->fetch_assoc()) {
            $todayValidRecharge = floatval($rowDep['total']) * 100; 
        }
        $stmtDep->close();
        if ($todayValidBet < $config['valid_bet']) {
            sendTrpcResponse(["status" => false, "message" => "Bet requirement not met"]);
            exit;
        }
        if ($todayValidRecharge < $config['recharge_amount']) {
            sendTrpcResponse(["status" => false, "message" => "Deposit requirement not met"]);
            exit;
        }
        $rewardCents = 0;
        if ($config['amount_type'] === 'FIXED') {
            $rewardCents = $config['amount_min']; 
        } else {
            $rewardCents = rand($config['amount_min'], $config['amount_max']);
        }
        $rewardReais = $rewardCents / 100;
        $mysqli->begin_transaction();
        try {
            $newBalance = $user['saldo'] + $rewardReais;
            $updateUser = "UPDATE usuarios SET saldo = $newBalance WHERE id = '{$user['id']}'";
            if (!$mysqli->query($updateUser)) {
                throw new Exception("Failed to update balance");
            }
            $insertRecord = "INSERT INTO signin_records (user_id, day, reward_amount, date_record) VALUES (?, ?, ?, ?)";
            $stmtInsert = $mysqli->prepare($insertRecord);
            $stmtInsert->bind_param("iiis", $user['id'], $dayIndex, $rewardCents, $today);
            if (!$stmtInsert->execute()) {
                throw new Exception("Failed to record sign in");
            }
            $stmtInsert->close();
            $insertLog = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES ('{$user['id']}', '$rewardReais', 'SignIn', NOW())";
            if (!$mysqli->query($insertLog)) {
                throw new Exception("Failed to log transaction");
            }
            $mysqli->commit();
            $formattedReward = number_format($rewardReais, 2, ',', '.'); 
            $formattedRewardStr = number_format($rewardReais, 2, '.', '');
            sendTrpcResponse([
                "status" => true,
                "message" => "Success",
                "amount" => $rewardCents,
                "reward" => $rewardCents,
                "rewardAmount" => $rewardCents, 
                "redPacketamount" => $rewardCents, 
                "redPacketamountStr" => $formattedRewardStr,
                "promoteStatus" => 2,
                "id" => $dayIndex
            ]);
            exit;
        } catch (Exception $e) {
            $mysqli->rollback();
            sendTrpcResponse(["status" => false, "message" => "Transaction failed: " . $e->getMessage()]);
            exit;
        }
    }
    if ($isAgency && !empty($rewardId)) {
        $findBau = "SELECT id, num, status FROM bau WHERE token = '$rewardId' AND id_user = '{$user['id']}'";
        $findRes = $mysqli->query($findBau);
        if ($findRes->num_rows === 0) {
            sendTrpcResponse(["status" => false, "message" => "Chest not available"]);
            exit;
        }
        $bauRow = $findRes->fetch_assoc();
        $chestId = intval($bauRow['num']);
        $targetBauId = $bauRow['id'];
    } else {
        $chestId = $data['chestId'] ?? $data['id'] ?? 0;
        $chestId = intval($chestId);
        if ($chestId <= 0) {
            sendTrpcResponse(["status" => false, "message" => "Invalid parameters"]);
            exit;
        }
    }
    $config_afiliados_qry = "SELECT minDepForCpa, minResgate FROM afiliados_config WHERE id = 1";
    $config_afiliados_resp = mysqli_query($mysqli, $config_afiliados_qry);
    $min_deposito = 20;
    $min_apostado = 30;
    if ($config_afiliados_resp && $row = mysqli_fetch_assoc($config_afiliados_resp)) {
        $min_deposito = $row['minDepForCpa'];
        $min_apostado = $row['minResgate'];
    }
    $config_qry = "SELECT niveisbau, qntsbaus, pessoasbau FROM config";
    $config_resp = mysqli_query($mysqli, $config_qry);
    $config = mysqli_fetch_assoc($config_resp);
    $niveis_bau = explode(',', $config['niveisbau']);
    $quantidade_baus = $config['qntsbaus'];
    $pessoas_bau = $config['pessoasbau'];
    $baus_por_nivel = ceil($quantidade_baus / count($niveis_bau));
    if (!$isAgency && $chestId > $quantidade_baus) {
        sendTrpcResponse(["status" => false, "message" => "Chest not available"]);
        exit;
    }
    $nivel_index = floor(($chestId - 1) / $baus_por_nivel);
    $reward = isset($niveis_bau[$nivel_index]) ? (float) $niveis_bau[$nivel_index] : (float) end($niveis_bau);
    $required_referrals = $chestId * $pessoas_bau;
    if (!$isAgency) {
        $codigoConvite = $user['invite_code'];
        $qryConvidados = "SELECT id FROM usuarios WHERE invitation_code = '$codigoConvite'";
        $resConvidados = mysqli_query($mysqli, $qryConvidados);
        $validReferrals = 0;
        while ($row = mysqli_fetch_assoc($resConvidados)) {
            $idConvidado = $row['id'];
            $qryDeposito = "SELECT SUM(valor) as total_depositado FROM transacoes WHERE usuario = $idConvidado AND status = 'pago'";
            $resDeposito = mysqli_query($mysqli, $qryDeposito);
            $total_depositado = mysqli_fetch_assoc($resDeposito)['total_depositado'] ?? 0;
            $qryAposta = "SELECT SUM(bet_money) as total_apostado FROM historico_play WHERE id_user = $idConvidado AND status_play = '1'";
            $resAposta = mysqli_query($mysqli, $qryAposta);
            $total_apostado = mysqli_fetch_assoc($resAposta)['total_apostado'] ?? 0;
            if ($total_depositado >= $min_deposito && $total_apostado >= $min_apostado) {
                $validReferrals++;
            }
        }
        if ($validReferrals < $required_referrals) {
            sendTrpcResponse(["status" => false, "message" => "Condition not met"]);
            exit;
        }
    }
    $mysqli->begin_transaction();
    try {
        if ($isAgency) {
             $checkQry = "SELECT id, status FROM bau WHERE id = '$targetBauId' FOR UPDATE";
        } else {
             $checkQry = "SELECT id, status FROM bau WHERE id_user = '{$user['id']}' AND num = '$chestId' FOR UPDATE";
        }
        $checkRes = $mysqli->query($checkQry);
        if ($checkRes->num_rows > 0) {
             $row = $checkRes->fetch_assoc();
             if ($row['status'] === 'claimed') {
                  throw new Exception("Already collected");
             }
             $updateBau = "UPDATE bau SET status = 'claimed', is_get = 1 WHERE id = '{$row['id']}'";
             if (!$mysqli->query($updateBau)) {
                  throw new Exception("Failed to update chest status");
             }
        } else {
             if ($isAgency) {
                  throw new Exception("Chest not found during transaction");
             }
             $insertBau = "INSERT INTO bau (id_user, num, status, token, is_get) VALUES ('{$user['id']}', '$chestId', 'claimed', '{$user['token']}', 1)";
             if (!$mysqli->query($insertBau)) {
                 throw new Exception("Failed to insert chest record");
             }
        }
        $newBalance = $user['saldo'] + $reward;
        $updateUser = "UPDATE usuarios SET saldo = $newBalance WHERE id = '{$user['id']}'";
        if (!$mysqli->query($updateUser)) {
            throw new Exception("Failed to update balance");
        }
        $insertLog = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES ('{$user['id']}', '$reward', 'adicao', NOW())";
        if (!$mysqli->query($insertLog)) {
             throw new Exception("Failed to log transaction");
        }
        $mysqli->commit();
        sendTrpcResponse([
            "status" => true, 
            "message" => "Applied successfully",
            "redPacketamount" => (float)$reward,
            "redPacketAmount" => (float)$reward,
            "amount" => (float)$reward,
            "rewardAmount" => (float)$reward * 100
        ]);
    } catch (Exception $e) {
        $mysqli->rollback();
        sendTrpcResponse(["status" => false, "message" => $e->getMessage()]);
    }
}
if ($path === '/api/frontend/trpc/activity.assistanceCashAwards') {
    $rotaEncontrada = true;
    $users = [
        ["userId" => 7356731866, "amount" => 10000],
        ["userId" => 7911669331, "amount" => 10000],
        ["userId" => 9031149572, "amount" => 10000],
        ["userId" => 7631337726, "amount" => 10000],
        ["userId" => 8936918947, "amount" => 10000]
    ];
    sendTrpcResponse($users);
}
if ($path === '/api/frontend/trpc/activity.assistanceCashHelps') {
    $rotaEncontrada = true;
    sendTrpcResponse(["helps" => []]);
}
if ($path === '/api/frontend/trpc/activity.bonusPoolContributionList') {
    $rotaEncontrada = true;
    sendTrpcResponse(["reviewList" => [], "total" => 0]);
}
if ($path === '/api/frontend/trpc/activity.homeTop') {
    $rotaEncontrada = true;
    sendTrpcResponse([
        [
            "id" => 3177,
            "type" => "RechargeBonus",
            "homeTop" => false
        ]
    ]);
}
if ($path === '/api/frontend/trpc/activity.config') {
    $rotaEncontrada = true;
    sendTrpcResponse([
        "configList" => [
            "tabSort" => [
                "{\"title\":\"all\",\"sort\":1,\"isOpen\":true}",
                "{\"title\":\"ELECTRONIC\",\"sort\":2,\"isOpen\":true}",
                "{\"title\":\"CHESS\",\"sort\":3,\"isOpen\":false}",
                "{\"title\":\"FISHING\",\"sort\":4,\"isOpen\":false}",
                "{\"title\":\"VIDEO\",\"sort\":5,\"isOpen\":false}",
                "{\"title\":\"SPORTS\",\"sort\":6,\"isOpen\":false}",
                "{\"title\":\"LOTTERY\",\"sort\":7,\"isOpen\":false}"
            ]
        ]
    ]);
}
if ($path === '/api/frontend/trpc/home.hot') {
    $rotaEncontrada = true;
    $hotList = [];
    $stmt = $mysqli->prepare("SELECT * FROM games WHERE popular = 1 AND status = 1 LIMIT 20");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $hotList[] = formatHotGameData($row, $WG_BUCKET_SITE);
    }
    $fixedItems = [];
    $resP = $mysqli->query("SELECT * FROM provedores WHERE status = 1 ORDER BY id ASC");
    if ($resP) {
        $hotTopSort = 100000;
        while ($row = $resP->fetch_assoc()) {
            $platformCode = $row['code'];
            $fixedItems[] = [
                "background" => "",
                "gameCode" => "",
                "gameId" => "",
                "gameName" => "",
                "gameStatus" => "",
                "gameType" => "ELECTRONIC",
                "gameTypeStatus" => "ON",
                "horizontalScreen" => "",
                "hot" => true,
                "hotTop" => 0,
                "hotTopSort" => $hotTopSort,
                "id" => (int)$row['id'],
                "logo" => resolveLogoUrl($row['logo'] ?? '', $WG_BUCKET_SITE),
                "logoFlag" => "",
                "openType" => (normalizeProviderKey($platformCode) === 'KKGAME' || normalizeProviderKey($row['name']) === 'PG') ? 0 : 1,
                "platformCode" => $platformCode,
                "platformId" => (int)$row['id'],
                "platformName" => $row['name'],
                "platformStatus" => "ON",
                "regionCode" => "BR",
                "secondaryBackground" => "",
                "sort" => $hotTopSort,
                "status" => "ON",
                "target" => "gameList",
                "type" => "gameType"
            ];
            $hotTopSort -= 1;
        }
    }
    $hotList = array_merge($hotList, $fixedItems);
    sendTrpcResponse(["hotList" => $hotList]);
}
if ($path === '/api/frontend/trpc/banner.list') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $bannerType = $json['bannerType'] ?? 'lobby_carousel';
    $banners = [];
    if ($bannerType === 'lobby_carousel') {
        $stmt = $mysqli->prepare("SELECT * FROM banner WHERE status = 1 AND (type = 'lobby_carousel' OR type IS NULL) ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $banners[] = [
                "id" => $row['id'],
                "bannerType" => "lobby_carousel",
                "name" => $row['titulo'],
                "showName" => null,
                "imageUrl" => $WG_BUCKET_SITE . "/uploads/" . $row['img'],
                "shortName" => null,
                "iconUrlType" => "default",
                "defaultIconUrl" => null,
                "customIconUrl" => null,
                "targetType" => "internal",
                "targetValue" => "{\"type\":\"activity_list\",\"info\":\"string\"}",
                "status" => true,
                "sort" => $row['id'],
                "remark" => ""
            ];
        }
    } elseif ($bannerType === 'lobby_banner') {
        $stmt = $mysqli->prepare("SELECT * FROM banner WHERE status = 1 AND type = 'lobby_banner' ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $banners[] = [
                "id" => $row['id'],
                "bannerType" => "lobby_banner",
                "name" => $row['titulo'],
                "showName" => null,
                "imageUrl" => $WG_BUCKET_SITE . "/uploads/" . $row['img'],
                "shortName" => null,
                "iconUrlType" => "default",
                "defaultIconUrl" => null,
                "customIconUrl" => null,
                "targetType" => "internal",
                "targetValue" => "{\"type\":\"activity_list\",\"info\":\"string\"}",
                "status" => true,
                "sort" => $row['id'],
                "remark" => ""
            ];
        }
    } elseif ($bannerType === 'lobby_sidebar_banner') {
        $stmt = $mysqli->prepare("SELECT * FROM banner WHERE status = 1 AND type = 'lobby_sidebar_banner' ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $defaultIconUrl = !empty($row['defaultIconUrl']) ? $row['defaultIconUrl'] : $WG_BUCKET_SITE . "/uploads/Rebate.png";
            if (strpos($defaultIconUrl, 'http') !== 0) {
                // Se não começar com http, assume que é caminho local em uploads
                $defaultIconUrl = $WG_BUCKET_SITE . $defaultIconUrl;
            }
            
            $targetValue = !empty($row['targetValue']) ? $row['targetValue'] : "{\"type\":\"activity\",\"info\":{\"activityName\":\"代理宝箱\",\"activityId\":495}}";

            $banners[] = [
                "id" => $row['id'],
                "bannerType" => "lobby_sidebar_banner",
                "name" => $row['titulo'],
                "showName" => $row['titulo'],
                "imageUrl" => $WG_BUCKET_SITE . "/uploads/" . $row['img'],
                "shortName"  => $row['titulo'],
                "iconUrlType" => "default",
                "defaultIconUrl" => $defaultIconUrl,
                "customIconUrl" => "",
                "targetType" => "internal",
                "targetValue" => $targetValue,
                "status" => true,
                "sort" => $row['id'],
                "remark" => ""
            ];
        }
    }
    sendTrpcResponse($banners);
}
if ($path === '/api/frontend/trpc/home.platformList') {
    $rotaEncontrada = true;
    $platformList = [];
    $res = $mysqli->query("SELECT * FROM provedores WHERE status = 1 ORDER BY id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $gameCount = 0;
            $aliases = getProviderAliasesFromConfig($row);
            if (empty($aliases)) {
                $aliases = [normalizeProviderKey($row['code'])];
            }
            $ph = implode(',', array_fill(0, count($aliases), '?'));
            $types = str_repeat('s', count($aliases));
            $stmtCount = $mysqli->prepare("SELECT COUNT(*) as count FROM games WHERE UPPER(provider) IN ($ph)");
            if ($stmtCount) {
                $stmtCount->bind_param($types, ...$aliases);
                $stmtCount->execute();
                $resCount = $stmtCount->get_result();
                if ($resCount) {
                    $rowCount = $resCount->fetch_assoc();
                    $gameCount = $rowCount['count'];
                }
                $stmtCount->close();
            }
            // Se não tem jogos ativos, verificar se tem jogos inativos
            if ($gameCount == 0) {
                $stmtAny = $mysqli->prepare("SELECT COUNT(*) as count FROM games WHERE UPPER(provider) IN ($ph)");
                if ($stmtAny) {
                    $stmtAny->bind_param($types, ...$aliases);
                    $stmtAny->execute();
                    $resAny = $stmtAny->get_result();
                    $rowAny = $resAny->fetch_assoc();
                    $stmtAny->close();
                    if ($rowAny['count'] == 0) continue; // sem jogos nenhum, ocultar provedor
                    $gameCount = (int)$rowAny['count']; // mostrar total mesmo inativo
                }
            }
            $platformCode = $row['code'];
            $platformList[] = [
                "code" => $platformCode,
                "gameTypes" => [
                    [
                        "background" => "",
                        "gameCount" => (int)$gameCount,
                        "gameType" => "ELECTRONIC",
                        "gameTypeSort" => 99,
                        "gameTypeStatus" => "ON",
                        "secondaryBackground" => "",
                        "target" => "gameList"
                    ]
                ],
                "id" => (int)$row['id'],
                "logo" => resolveLogoUrl($row['logo'] ?? '', $WG_BUCKET_SITE),
                "name" => $row['name'],
                "openType" => 1,
                "sort" => (int)($row['id'] ?? 99),
                "status" => "ON"
            ];
        }
    }
    sendTrpcResponse(["platformList" => $platformList]);
}
if ($path === '/api/frontend/trpc/home.list') {
    $rotaEncontrada = true;
    $electronicList = [];
    $res = $mysqli->query("SELECT * FROM provedores WHERE status = 1 ORDER BY id ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $gameCount = 0;
            $aliases = getProviderAliasesFromConfig($row);
            if (empty($aliases)) {
                $aliases = [normalizeProviderKey($row['code'])];
            }
            $ph = implode(',', array_fill(0, count($aliases), '?'));
            $types = str_repeat('s', count($aliases));
            $stmtCount = $mysqli->prepare("SELECT COUNT(*) as count FROM games WHERE UPPER(provider) IN ($ph)");
            if ($stmtCount) {
                $stmtCount->bind_param($types, ...$aliases);
                $stmtCount->execute();
                $resCount = $stmtCount->get_result();
                if ($resCount) {
                    $rowCount = $resCount->fetch_assoc();
                    $gameCount = $rowCount['count'];
                }
                $stmtCount->close();
            }
            // Se não tem jogos ativos, verificar se tem jogos inativos
            if ($gameCount == 0) {
                $stmtAny = $mysqli->prepare("SELECT COUNT(*) as count FROM games WHERE UPPER(provider) IN ($ph)");
                if ($stmtAny) {
                    $stmtAny->bind_param($types, ...$aliases);
                    $stmtAny->execute();
                    $resAny = $stmtAny->get_result();
                    $rowAny = $resAny->fetch_assoc();
                    $stmtAny->close();
                    if ($rowAny['count'] == 0) continue; // sem jogos nenhum, ocultar provedor
                    $gameCount = (int)$rowAny['count']; // mostrar total mesmo inativo
                }
            }
            $platformCode = $row['code'];
            $electronicList[] = [
                "id" => (int)$row['id'],
                "hot" => 1,
                "code" => $platformCode,
                "logo" => resolveLogoUrl($row['logo'] ?? '', $WG_BUCKET_SITE),
                "name" => $row['name'],
                "sort" => 1,
                "status" => "ON",
                "target" => "gameList",
                "hotSort" => 1,
                "openType" => (normalizeProviderKey($platformCode) === 'KKGAME' || normalizeProviderKey($row['name']) === 'PG') ? 0 : 1,
                "gameCount" => (int)$gameCount,
                "background" => "",
                "restriction" => 0,
                "secondaryBackground" => ""
            ];
        }
    }
    $gameTypeList = [];
    $resCat = $mysqli->query("SELECT * FROM game_categories WHERE status = 1 ORDER BY sort ASC");
    if ($resCat) {
        while ($rowCat = $resCat->fetch_assoc()) {
            $slug = $rowCat['slug'] ?? '';
            $gameType = $slug === 'all' ? 'ALL' : strtoupper($slug);
            $platformList = $gameType === 'ELECTRONIC' ? $electronicList : [];
            $gameTypeList[] = [
                "gameType" => $gameType,
                "gameTypeSort" => (int)$rowCat['sort'],
                "gameTypeStatus" => "ON",
                "platformList" => $platformList
            ];
        }
    }
    if (empty($gameTypeList)) {
        $gameTypeList = [
            ["gameType"=>"ELECTRONIC","gameTypeSort"=>1300,"gameTypeStatus"=>"ON","platformList"=>$electronicList],
            ["gameType"=>"CHESS","gameTypeSort"=>1200,"gameTypeStatus"=>"ON","platformList"=>[]],
            ["gameType"=>"FISHING","gameTypeSort"=>1100,"gameTypeStatus"=>"ON","platformList"=>[]],
            ["gameType"=>"VIDEO","gameTypeSort"=>1000,"gameTypeStatus"=>"ON","platformList"=>[]],
            ["gameType"=>"SPORTS","gameTypeSort"=>900,"gameTypeStatus"=>"ON","platformList"=>[]],
            ["gameType"=>"LOTTERY","gameTypeSort"=>800,"gameTypeStatus"=>"ON","platformList"=>[]],
        ];
    }
    sendTrpcResponse(["gameTypeList" => $gameTypeList]);
}
if ($path === '/api/frontend/trpc/carouselConfig.list') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? [];
    $type = $json['type'] ?? '';
    if ($type === 'text') {
        $marqueeContent = "🔥🎊 Participe do Evento Super Popular da Caixa do Tesouro! Convide amigos e ganhe R$ 100 mil! 🎉🎁 Não perca! Visite panda.top para mais bônus";
        if (isset($mysqli)) {
            $stmt = $mysqli->prepare("SELECT marquee FROM config LIMIT 1");
            if ($stmt) {
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (!empty($row['marquee'])) {
                        $marqueeContent = $row['marquee'];
                    }
                }
                $stmt->close();
            }
        }
        $carouselConfig = [
            [
                "id" => 690,
                "name" => "11",
                "content" => $marqueeContent,
                "linkType" => "none",
                "linkValue" => ""
            ]
        ];
        sendTrpcResponse($carouselConfig);
    }
    $carouselConfig = [
        [
            "id" => 1,
            "redirectUrl" => "",
            "imgUrl" => $WG_BUCKET_SITE . "/uploads/banner1.png",
            "title" => "Banner 1",
            "sort" => 1
        ],
        [
            "id" => 2,
            "redirectUrl" => "",
            "imgUrl" => $WG_BUCKET_SITE . "/uploads/banner2.png",
            "title" => "Banner 2",
            "sort" => 2
        ]
    ];
    sendTrpcResponse($carouselConfig);
}
if ($path === '/api/frontend/trpc/announcement.loginIn') {
    $rotaEncontrada = true;
    $modais = [];
    $stmt = $mysqli->prepare("SELECT * FROM modais WHERE active = 1 AND (popupMethod = 'login' OR popupMethod = 'both')");
    $stmt->execute();
    $result = $stmt->get_result();
    $metaValues = [];
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['announcementType'] === 'text') {
            $row['content'] = 'data:text/html;base64,' . base64_encode($row['content']);
        }
        $modais[] = [
            "announcementType" => $row['announcementType'],
            "content" => $row['content'],
            "id" => (int)$row['id'],
            "imgType" => $row['imgType'],
            "imgUrl" => $row['imgUrl'],
            "popupMethod" => $row['popupMethod'],
            "title" => $row['title'],
            "type" => $row['type'],
            "updateTime" => date('Y-m-d\TH:i:s\Z', strtotime($row['updateTime'])),
            "value" => $row['value'],
            "valueType" => $row['valueType']
        ];
        $metaValues["$index.updateTime"] = ["Date"];
        $index++;
    }
    $meta = !empty($metaValues) ? ["values" => $metaValues] : null;
    sendTrpcResponse($modais, $meta);
}
if ($path === '/api/frontend/trpc/announcement.loginOut') {
    $rotaEncontrada = true;
    $modais = [];
    $stmt = $mysqli->prepare("SELECT * FROM modais WHERE active = 1 AND (popupMethod = 'logout' OR popupMethod = 'both')");
    $stmt->execute();
    $result = $stmt->get_result();
    $metaValues = [];
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['announcementType'] === 'text') {
            $row['content'] = 'data:text/html;base64,' . base64_encode($row['content']);
        }
        $modais[] = [
            "announcementType" => $row['announcementType'],
            "content" => $row['content'],
            "id" => (int)$row['id'],
            "imgType" => $row['imgType'],
            "imgUrl" => $row['imgUrl'],
            "popupMethod" => $row['popupMethod'],
            "title" => $row['title'],
            "type" => $row['type'],
            "updateTime" => date('Y-m-d\TH:i:s\Z', strtotime($row['updateTime'])),
            "value" => $row['value'],
            "valueType" => $row['valueType']
        ];
        $metaValues["$index.updateTime"] = ["Date"];
        $index++;
    }
    $meta = !empty($metaValues) ? ["values" => $metaValues] : null;
    sendTrpcResponse($modais, $meta);
}
if ($path === '/api/frontend/trpc/user.updateAvatar') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $input = getTrpcInput();
        $data = $input['json'] ?? [];
        $avatarName = $data['avatarName'] ?? $data['avatar'] ?? null;
        if ($avatarName) {
            $avatar = $mysqli->real_escape_string($avatarName);
            $stmt = $mysqli->prepare("UPDATE usuarios SET avatar = ? WHERE id = ?");
            $stmt->bind_param("si", $avatar, $user['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
    sendTrpcResponse(null, ["values" => ["undefined"]]);
}
if ($path === '/api/frontend/trpc/user.details') {
    $rotaEncontrada = true;
    $user = null;
    if (isset($_COOKIE['token_user'])) {
        $token = $_COOKIE['token_user'];
        $stmt = $mysqli->prepare("SELECT * FROM usuarios WHERE token = ? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
    }
    if ($user) {
        $userAvatar = $WG_BUCKET_SITE . "/uploads/default_avatar.jpg";
        if (!empty($user['avatar'])) {
            $userAvatar = $user['avatar'];
            if (strpos($userAvatar, 'http') !== 0) {
                 $userAvatar = "https://upload-us.f-1-g-h.com/avatar/" . $userAvatar;
            }
        }
        $userDetails = [
            "id" => $user['id'],
            "userName" => $user['email'] ?? $user['mobile'] ?? '',
            "phoneNumber" => $user['mobile'] ?? $user['celular'] ?? '',
            "email" => $user['email'] ?? '',
            "remark" => null,
            "type" => "normal",
            "tenantName" => "cliente33",
            "realName" => $user['nome'] ?? null,
            "regionName" => "Brasil",
            "historicalPay" => 0,
            "appType" => "DesktopOS",
            "firstRechargeAmount" => 0,
            "firstRechargeTime" => null,
            "withdrawCount" => 0,
            "avatar" => $userAvatar,
            "tgId" => null,
            "firstSetPassword" => null,
            "registerType" => "phone:phone",
            "rechargeCount" => 0,
            "userId" => $user['id'],
            "trialPlayBalance" => 0,
            "trialPlayWithdrawHint" => "1",
            "isNewMail" => false,
            "canApplyRegisterReward" => isset($user['canApplyRegisterReward']) ? (bool)$user['canApplyRegisterReward'] : true,
            "canUseRegisterRewardInfo" => isset($user['canApplyRegisterReward']) ? !((bool)$user['canApplyRegisterReward']) : false
        ];
        sendTrpcResponse($userDetails);
    } else {
        sendTrpcResponse(null);
    }
}
if ($path === '/api/frontend/trpc/game.sidebarList') {
    $rotaEncontrada = true;
    $sidebarList = [
        [
            "status" => "ON",
            "hot" => true,
            "sort" => 93085,
            "top" => false,
            "topSort" => 0,
            "logo" => "https://gamelogo.bcbd123.com/br/gamelogo/pg_name/FortuneTiger.jpg",
            "id" => 2196,
            "code" => "HHSC",
            "name" => "Fortune Tiger",
            "gameNameMultiLanguage" => [
                "br" => "",
                "en" => "Fortune Tiger",
                "hi" => "फॉर्च्यून टाइगर",
                "id" => "Harimau Keberuntungan",
                "vi" => "Hổ may mắn",
                "zh" => "虎虎生财"
            ],
            "externalGameId" => 40081000,
            "horizontalScreen" => false,
            "gameType" => "ELECTRONIC",
            "gameTypeStatus" => "ON",
            "platformId" => 23,
            "platformName" => "PG",
            "platformStatus" => "ON",
            "regionCode" => "BR",
            "platformCode" => "KKGAME",
            "logoFlag" => "PG_FortuneTiger",
            "index" => 10
        ],
        [
            "status" => "ON",
            "hot" => true,
            "sort" => 44040,
            "top" => false,
            "topSort" => 0,
            "logo" => "https://gamelogo.bcbd123.com/br/gamelogo/pg_name/FortuneRabbit.jpg",
            "id" => 2197,
            "code" => "JQT",
            "name" => "Fortune Rabbit",
            "gameNameMultiLanguage" => [
                "br" => "",
                "en" => "Fortune Rabbit",
                "hi" => "फॉर्च्यून खरगोश",
                "id" => "Kelinci Keberuntungan",
                "vi" => "Thỏ may mắn",
                "zh" => "金钱兔"
            ],
            "externalGameId" => 40080000,
            "horizontalScreen" => false,
            "gameType" => "ELECTRONIC",
            "gameTypeStatus" => "ON",
            "platformId" => 23,
            "platformName" => "PG",
            "platformStatus" => "ON",
            "regionCode" => "BR",
            "platformCode" => "KKGAME",
            "logoFlag" => "PG_FortuneRabbit",
            "index" => 11
        ],
        [
            "status" => "ON",
            "hot" => true,
            "sort" => 38865,
            "top" => false,
            "topSort" => 0,
            "logo" => "https://gamelogo.bcbd123.com/br/gamelogo/pg_name/FortuneOx.jpg",
            "id" => 2198,
            "code" => "SBJN",
            "name" => "Fortune Ox",
            "gameNameMultiLanguage" => [
                "br" => "",
                "en" => "Fortune Ox",
                "hi" => "फॉर्च्यून ऑक्स",
                "id" => "Kerbau Keberuntungan",
                "vi" => "Bò lộc",
                "zh" => "十倍金牛"
            ],
            "externalGameId" => 40080001,
            "horizontalScreen" => false,
            "gameType" => "ELECTRONIC",
            "gameTypeStatus" => "ON",
            "platformId" => 23,
            "platformName" => "PG",
            "platformStatus" => "ON",
            "regionCode" => "BR",
            "platformCode" => "KKGAME",
            "logoFlag" => "PG_FortuneOx",
            "index" => 12
        ],
        [
            "status" => "ON",
            "hot" => true,
            "sort" => 32280,
            "top" => false,
            "topSort" => 0,
            "logo" => "https://gamelogo.bcbd123.com/br/gamelogo/pg_name/FortuneDragon.jpg",
            "id" => 2278,
            "code" => "JLSB",
            "name" => "Fortune Dragon",
            "gameNameMultiLanguage" => [
                "br" => "",
                "en" => "Fortune Dragon",
                "hi" => "फॉर्च्यून ड्रैगन",
                "id" => "Naga Keberuntungan",
                "vi" => "Rồng may mắn",
                "zh" => "金龙送宝"
            ],
            "externalGameId" => 40080081,
            "horizontalScreen" => false,
            "gameType" => "ELECTRONIC",
            "gameTypeStatus" => "ON",
            "platformId" => 23,
            "platformName" => "PG",
            "platformStatus" => "ON",
            "regionCode" => "BR",
            "platformCode" => "KKGAME",
            "logoFlag" => "PG_FortuneDragon",
            "index" => 13
        ],
        [
            "status" => "ON",
            "hot" => true,
            "sort" => 7345,
            "top" => false,
            "topSort" => 0,
            "logo" => "https://gamelogo.bcbd123.com/br/gamelogo/pg_name/FortuneMouse.jpg",
            "id" => 2199,
            "code" => "SSFF",
            "name" => "Fortune Mouse",
            "gameNameMultiLanguage" => [
                "br" => "",
                "en" => "Fortune Mouse",
                "hi" => "फॉर्च्यून माउस",
                "id" => "Tikus Keberuntungan",
                "vi" => "Chuột may mắn",
                "zh" => "鼠鼠福福"
            ],
            "externalGameId" => 40080002,
            "horizontalScreen" => false,
            "gameType" => "ELECTRONIC",
            "gameTypeStatus" => "ON",
            "platformId" => 23,
            "platformName" => "PG",
            "platformStatus" => "ON",
            "regionCode" => "BR",
            "platformCode" => "KKGAME",
            "logoFlag" => "PG_FortuneMouse",
            "index" => 14
        ]
    ];
    sendTrpcResponse($sidebarList);
}
if ($path === '/api/frontend/trpc/home.nav') {
    $rotaEncontrada = true;
     $nav = [
        ["id"=>1, "name"=>"Inicio", "code"=>"home", "icon"=>$WG_BUCKET_SITE . "/uploads/nav_home.png", "target"=>"/"],
        ["id"=>2, "name"=>"Ao Vivo", "code"=>"live", "icon"=>$WG_BUCKET_SITE . "/uploads/nav_live.png", "target"=>"/live"],
        ["id"=>3, "name"=>"Esportes", "code"=>"sports", "icon"=>$WG_BUCKET_SITE . "/uploads/nav_sports.png", "target"=>"/sports"]
    ];
    sendTrpcResponse(["navList" => $nav]);
}
    if ($path === '/api/frontend/trpc/game.list') {
    $rotaEncontrada = true;
    $gameList = [];
    $input = getTrpcInput();
    $json = $input['json'] ?? $input;
    $isRecentRequest = isset($json['all']) && $json['all'] === true && isset($json['gameIdList']) && empty($json['gameIdList']);
    if ($isRecentRequest) {
        $user = getCurrentUser($mysqli);
        $gameList = [];
        $total = 0;
        if ($user) {
             $page = isset($json['page']) ? intval($json['page']) : 1;
             $pageSize = isset($json['pageSize']) ? intval($json['pageSize']) : 32;
             try {
                 $limitSearch = $pageSize * 10; 
                 $stmt = $mysqli->prepare("
                    SELECT nome_game 
                    FROM historico_play 
                    WHERE id_user = ? 
                    ORDER BY id DESC 
                    LIMIT ?
                 ");
                 $stmt->bind_param("ii", $user['id'], $limitSearch);
                 $stmt->execute();
                 $res = $stmt->get_result();
                 $recentCodes = [];
                 $seen = [];
                 while ($row = $res->fetch_assoc()) {
                     $gameCode = $row['nome_game'];
                     if (!empty($gameCode) && !isset($seen[$gameCode])) {
                         $seen[$gameCode] = true;
                         $recentCodes[] = $gameCode;
                         if (count($recentCodes) >= $pageSize) break;
                     }
                 }
                 $stmt->close();
                 $total = count($recentCodes);
                 if (!empty($recentCodes)) {
                     $codes = [];
                     $ids = [];
                     foreach ($recentCodes as $rc) {
                         if (is_numeric($rc)) {
                             $ids[] = intval($rc);
                             $codes[] = $rc;
                         } else {
                             $codes[] = $rc;
                         }
                     }
                     $conditions = [];
                     $types = "";
                     $params = [];
                     if (!empty($codes)) {
                         $inQueryCode = implode(',', array_fill(0, count($codes), '?'));
                         $conditions[] = "game_code IN ($inQueryCode)";
                         $types .= str_repeat('s', count($codes));
                         foreach ($codes as $c) $params[] = $c;
                     }
                     if (!empty($ids)) {
                         $inQueryId = implode(',', array_fill(0, count($ids), '?'));
                         $conditions[] = "id IN ($inQueryId)";
                         $types .= str_repeat('i', count($ids));
                         foreach ($ids as $i) $params[] = $i;
                     }
                     if (!empty($conditions)) {
                         $whereClause = implode(' OR ', $conditions);
                         $stmtGames = $mysqli->prepare("SELECT * FROM games WHERE $whereClause");
                         $stmtGames->bind_param($types, ...$params);
                         $stmtGames->execute();
                         $resGames = $stmtGames->get_result();
                         $gamesMap = [];
                         while ($game = $resGames->fetch_assoc()) {
                             $gamesMap[$game['game_code']] = formatGameData($game, $WG_BUCKET_SITE);
                             $gamesMap[$game['id']] = formatGameData($game, $WG_BUCKET_SITE);
                             $gamesMap[strval($game['id'])] = formatGameData($game, $WG_BUCKET_SITE);
                         }
                         $stmtGames->close();
                         foreach ($recentCodes as $code) {
                             if (isset($gamesMap[$code])) {
                                 $gData = $gamesMap[$code];
                                 $alreadyAdded = false;
                                 foreach ($gameList as $existing) {
                                     if ($existing['id'] === $gData['id']) {
                                         $alreadyAdded = true;
                                         break;
                                     }
                                 }
                                 if (!$alreadyAdded) {
                                     $gameList[] = $gData;
                                 }
                             }
                         }
                     }
                 }
             } catch (Exception $e) {
                 error_log("Error in recent games: " . $e->getMessage());
                 $gameList = [];
                 $total = 0;
             }
        }
        sendTrpcResponse([["gameList" => $gameList, "total" => $total]]);
        return;
    }
    $platformCode = $json['platformCode'] ?? ($json['code'] ?? '');
    $platformId = isset($json['platformId']) ? intval($json['platformId']) : 0;
    $page = isset($json['page']) ? intval($json['page']) : 1;
    $pageSize = isset($json['pageSize']) ? intval($json['pageSize']) : 32;
    $offset = ($page - 1) * $pageSize;
    $searchWord = $json['searchWord'] ?? ($json['gameName'] ?? '');
    $gameType = isset($json['gameType']) ? strtoupper($json['gameType']) : '';
    $isBatchRequest = isset($json[0]);
    $isSingleGlobalRequest = !$isBatchRequest && array_key_exists('platformId', $json) && empty($json['platformId']) && empty($json['platformCode']);
    if ($isSingleGlobalRequest) {
        $where = ["status = 1"];
        $types = "";
        $values = [];
        if (!empty($gameType)) {
             if ($gameType === 'ELECTRONIC') {
                  $where[] = "(UPPER(type) NOT IN ('LIVE', 'SPORTS') OR UPPER(game_type) NOT IN ('LIVE', 'SPORTS'))";
             } else {
                  $where[] = "(UPPER(type) = ? OR UPPER(game_type) = ?)";
                  $types .= "ss";
                  $values[] = $gameType;
                  $values[] = $gameType;
             }
        }
        if (!empty($searchWord)) {
            $where[] = "game_name LIKE ?";
            $types .= "s";
            $values[] = "%" . $searchWord . "%";
        }
        $whereSql = implode(" AND ", $where);
        $countSql = "SELECT COUNT(DISTINCT COALESCE(NULLIF(game_code,''), id)) as total FROM games WHERE $whereSql";
        $stmtC = $mysqli->prepare($countSql);
        if (!empty($types)) {
            $stmtC->bind_param($types, ...$values);
        }
        $stmtC->execute();
        $total = 0;
        $resC = $stmtC->get_result();
        if ($rowC = $resC->fetch_assoc()) {
            $total = $rowC['total'];
        }
        $stmtC->close();
        $sql = "SELECT * FROM games WHERE $whereSql ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmtG = $mysqli->prepare($sql);
        $limitTypes = $types . "ii";
        $limitValues = array_merge($values, [$pageSize, $offset]);
        $stmtG->bind_param($limitTypes, ...$limitValues);
        $stmtG->execute();
        $resG = $stmtG->get_result();
        $globalGames = [];
        $seenGlobal = [];
        while ($rowG = $resG->fetch_assoc()) {
            $key = $rowG['game_code'] ?? $rowG['id'];
            if ($key === null || $key === '') {
                $key = $rowG['id'];
            }
            if (isset($seenGlobal[$key])) {
                continue;
            }
            $seenGlobal[$key] = true;
            $globalGames[] = formatGameData($rowG, $WG_BUCKET_SITE);
        }
        $stmtG->close();
        sendTrpcResponse([
            "gameList" => $globalGames,
            "total" => $total
        ]);
        return;
    }
    if (empty($platformCode) && $platformId > 0) {
        $stmt_prov = $mysqli->prepare("SELECT code FROM provedores WHERE id = ?");
        $stmt_prov->bind_param("i", $platformId);
        $stmt_prov->execute();
        $res_prov = $stmt_prov->get_result();
        if ($row_prov = $res_prov->fetch_assoc()) {
             $platformCode = $row_prov['code'];
        }
        $stmt_prov->close();
    }
    $platformConfig = null;
    if (!empty($platformCode)) {
        $platformConfig = getProviderConfig($platformCode);
        if (!empty($platformConfig['code'])) {
            $platformCode = $platformConfig['code'];
        }
    }
    $targetProviders = [];
    if (!empty($platformCode)) {
        $stmtP = $mysqli->prepare("SELECT * FROM provedores WHERE UPPER(code) = UPPER(?) AND status = 1");
        $stmtP->bind_param("s", $platformCode);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        while ($rowP = $resP->fetch_assoc()) {
            $targetProviders[] = $rowP;
        }
        $stmtP->close();
        if (empty($targetProviders) && !empty($platformConfig)) {
            $targetProviders[] = $platformConfig;
        }
    } else {
        $resP = $mysqli->query("SELECT * FROM provedores WHERE status = 1 ORDER BY id ASC");
        if ($resP) {
            while ($rowP = $resP->fetch_assoc()) {
                $targetProviders[] = $rowP;
            }
        }
    }
    $finalList = [];
    $isMultiProviderRequest = empty($platformCode) && $platformId === 0 && !$isSingleGlobalRequest;
    foreach ($targetProviders as $prov) {
        $aliases = getProviderAliasesFromConfig($prov);
        if (empty($aliases)) {
            $aliases = [normalizeProviderKey($prov['code'])];
        }
        $placeholders = implode(',', array_fill(0, count($aliases), '?'));
        $where = ["status = 1", "UPPER(provider) IN ($placeholders)"];
        $types = str_repeat('s', count($aliases));
        $values = $aliases;
        if (!empty($gameType)) {
             if ($gameType === 'ELECTRONIC') {
                  $where[] = "(UPPER(type) NOT IN ('LIVE', 'SPORTS') OR UPPER(game_type) NOT IN ('LIVE', 'SPORTS'))";
             } else {
                  $where[] = "(UPPER(type) = ? OR UPPER(game_type) = ?)";
                  $types .= "ss";
                  $values[] = $gameType;
                  $values[] = $gameType;
             }
        }
        if (!empty($searchWord)) {
            $where[] = "game_name LIKE ?";
            $types .= "s";
            $values[] = "%" . $searchWord . "%";
        }
        $whereSql = implode(" AND ", $where);
        $countSql = "SELECT COUNT(DISTINCT COALESCE(NULLIF(game_code,''), id)) as total FROM games WHERE $whereSql";
        $stmtC = $mysqli->prepare($countSql);
        if (!empty($types)) {
            $stmtC->bind_param($types, ...$values);
        }
        $stmtC->execute();
        $total = 0;
        $resC = $stmtC->get_result();
        if ($rowC = $resC->fetch_assoc()) {
            $total = $rowC['total'];
        }
        $stmtC->close();
        $limitPerProvider = $isMultiProviderRequest ? min($pageSize, 30) : $pageSize;
        $sql = "SELECT * FROM games WHERE $whereSql ORDER BY id DESC LIMIT ? OFFSET ?";
        $stmtG = $mysqli->prepare($sql);
        $limitTypes = $types . "ii";
        $limitValues = array_merge($values, [$limitPerProvider, $offset]);
        $stmtG->bind_param($limitTypes, ...$limitValues);
        $stmtG->execute();
        $resG = $stmtG->get_result();
        $provGames = [];
        $seenProv = [];
        while ($rowG = $resG->fetch_assoc()) {
            $key = $rowG['game_code'] ?? $rowG['id'];
            if ($key === null || $key === '') {
                $key = $rowG['id'];
            }
            if (isset($seenProv[$key])) {
                continue;
            }
            $seenProv[$key] = true;
            $provGames[] = formatGameData($rowG, $WG_BUCKET_SITE);
        }
        $stmtG->close();
        if (count($provGames) > 0 || $total > 0) {
             $finalList[] = [
                 "gameList" => $provGames,
                 "total" => $total
             ];
        }
    }
    if (count($finalList) === 1 && $platformId > 0) {
        sendTrpcResponse($finalList[0]);
    } else {
        sendTrpcResponse($finalList);
    }
}
if ($path === '/api/frontend/trpc/favorite.list') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $json = $input['json'] ?? $input;
    $user = getCurrentUser($mysqli);
    $favorites = [];
    if ($user && !empty($user['favoritos'])) {
        $favIds = explode(',', $user['favoritos']);
        $favIds = array_map('intval', $favIds);
        $favIds = array_filter($favIds);
        if (!empty($favIds)) {
            $page = isset($json['page']) ? intval($json['page']) : 1;
            $pageSize = isset($json['pageSize']) ? intval($json['pageSize']) : 1000;
            $offset = ($page - 1) * $pageSize;
            $gameType = isset($json['gameType']) ? strtoupper($json['gameType']) : '';
            $where = ["id IN (" . implode(',', $favIds) . ")", "status = 1"];
            $types = "";
            $values = [];
            if (!empty($gameType)) {
                if ($gameType === 'ELECTRONIC') {
                     $where[] = "(UPPER(type) NOT IN ('LIVE', 'SPORTS') OR UPPER(game_type) NOT IN ('LIVE', 'SPORTS'))";
                } else {
                     $where[] = "(UPPER(type) = ? OR UPPER(game_type) = ?)";
                     $types .= "ss";
                     $values[] = $gameType;
                     $values[] = $gameType;
                }
            }
            $whereSql = implode(" AND ", $where);
            $sql = "SELECT * FROM games WHERE $whereSql LIMIT ? OFFSET ?";
            $types .= "ii";
            $values[] = $pageSize;
            $values[] = $offset;
            $stmt = $mysqli->prepare($sql);
            if ($types !== "") {
                $stmt->bind_param($types, ...$values);
            } else {
                 $stmt->bind_param("ii", $pageSize, $offset);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $gameData = formatGameData($row, $WG_BUCKET_SITE);
                $favorites[] = [
                     "id" => $gameData['id'],
                     "gameType" => $gameData['gameType'],
                     "platformId" => $gameData['platformId'],
                     "palateformLogo" => $WG_BUCKET_SITE . "/uploads/" . $gameData['platformCode'] . ".svg",
                     "plateformName" => $gameData['platformName'],
                     "plateformBackground" => "",
                     "plateformSecondaryBackground" => "",
                     "plateformTarget" => "gameList",
                     "gameId" => $gameData['id'],
                     "gameName" => $gameData['name'],
                     "gameLogo" => $gameData['logo'],
                     "gameStatus" => "ON",
                     "horizontalScreen" => $gameData['horizontalScreen'],
                     "top" => $gameData['top'],
                     "platformStatus" => "ON",
                     "gameTypeStatus" => "ON",
                     "regionCode" => "BR",
                     "platformCode" => $gameData['platformCode'],
                     "gameCode" => $gameData['code'],
                     "logoFlag" => $gameData['logoFlag']
                ];
            }
            $stmt->close();
        }
    }
    sendTrpcResponse(["favortieList" => $favorites]);
}
if ($path === '/api/frontend/trpc/avatarCount.avatarCount') {
    $rotaEncontrada = true;
    $avatars = [];
    for ($i = 1; $i <= 24; $i++) {
        $avatars[] = [
            "id" => $i,
            "url" => $WG_BUCKET_SITE . "/siteadmin/default/2D/img_txn{$i}.png",
            "isFree" => true
        ];
    }
    sendTrpcResponse(["avatarList" => $avatars]);
}
if ($path === '/api/frontend/trpc/game.end') {
    $rotaEncontrada = true;
    sendTrpcResponse(["status" => true]);
}
if ($path === '/api/frontend/trpc/withdraw.list') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $withdrawList = [];
    $total = 0;
    if ($user) {
        $page = isset($data['json']['page']) ? intval($data['json']['page']) : 1;
        $pageSize = isset($data['json']['pageSize']) ? intval($data['json']['pageSize']) : 20;
        $offset = ($page - 1) * $pageSize;
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM solicitacao_saques WHERE id_user = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = $res->fetch_assoc()['count'];
        $stmt = $mysqli->prepare("SELECT * FROM solicitacao_saques WHERE id_user = ? ORDER BY data_registro DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $user['id'], $pageSize, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $withdrawList[] = [
                "id" => $row['id'],
                "orderNo" => $row['transacao_id'],
                "amount" => floatval($row['valor']),
                "status" => intval($row['status']),
                "createTime" => strtotime($row['data_registro']) * 1000,
                "type" => $row['tipo']
            ];
        }
    }
    sendTrpcResponse([
        "withdrawList" => $withdrawList,
        "total" => $total,
        "totalPage" => ceil($total / 20)
    ]);
}
if ($path === '/api/frontend/trpc/withdraw.type') {
    $rotaEncontrada = true;
    $withdrawTypes = [
        [
            "id" => 1,
            "name" => "PIX",
            "code" => "PIX",
            "icon" => $WG_BUCKET_SITE . "/uploads/pix_icon.png",
            "minAmount" => 20,
            "maxAmount" => 10000
        ]
    ];
    $config_qry = "SELECT minsaque, maxsaque FROM config LIMIT 1";
    $config_resp = mysqli_query($mysqli, $config_qry);
    if ($config_resp && $row = mysqli_fetch_assoc($config_resp)) {
        if (isset($row['minsaque'])) $withdrawTypes[0]['minAmount'] = floatval($row['minsaque']);
        if (isset($row['maxsaque'])) $withdrawTypes[0]['maxAmount'] = floatval($row['maxsaque']);
    }
    sendTrpcResponse(["withdrawTypeList" => $withdrawTypes]);
}
if ($path === '/api/frontend/trpc/banner.quickEntryListPublic') {
    $rotaEncontrada = true;
    $quickEntryList = [];

    $stmt = $mysqli->prepare("SELECT id, img, redirect FROM floats WHERE tipo IN (0, 1, 3) AND status = 1 ORDER BY id DESC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $quickEntryList[] = [
                "id" => (int)$row['id'],
                "sort" => 0,
                "activityId" => 0,
                "imageUrl" => $WG_BUCKET_SITE . "/uploads/" . $row['img'],
                "targetType" => "external",
                "targetValue" => $row['redirect'],
                "isClose" => false,
                "activityType" => null,
                "activityDetailSelect" => null
            ];
        }
        $stmt->close();
    }
    
    sendTrpcResponse($quickEntryList);
}
if ($path === '/api/frontend/trpc/activity.receiveSignIn') {
    $rotaEncontrada = true;
    sendTrpcResponse(["status" => true, "message" => "Sign in successful"]);
}
if ($path === '/api/frontend/trpc/tenant.footerText') {
    $rotaEncontrada = true;
    $query = $mysqli->query("SELECT descricao FROM config LIMIT 1");
    $config = $query->fetch_assoc();
    $footerText = $config['descricao'] ?? "";
    sendTrpcResponse(["footerText" => $footerText]);
}
if ($path === '/api/frontend/trpc/mainMedia.list') {
    $rotaEncontrada = true;
    $stmt = $mysqli->prepare("SELECT telegram, whatsapp, facebook, instagram FROM config LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    $mediaList = [
        [
            "id" => 48,
            "type" => "18+",
            "link" => "#",
            "icon" => "https://upload-us.f-1-g-h.com/s4/1753299662216/18.png"
        ],
        [
            "id" => 49,
            "type" => "Telegram",
            "link" => $config['telegram'] ?? "#",
            "icon" => "https://upload-us.f-1-g-h.com/s4/1753299707643/telegram.png"
        ],
        [
            "id" => 50,
            "type" => "WhatsApp",
            "link" => $config['whatsapp'] ?? "#",
            "icon" => "https://upload-us.f-1-g-h.com/s4/1753299732537/whatsapp.png"
        ],
        [
            "id" => 51,
            "type" => "Facebook",
            "link" => $config['facebook'] ?? "#",
            "icon" => "https://upload-us.f-1-g-h.com/s4/1753299755935/facebook.png"
        ],
        [
            "id" => 360,
            "type" => "Instagram",
            "link" => $config['instagram'] ?? "#",
            "icon" => "https://upload-us.f-1-g-h.com/s4/1757396180898/5.png"
        ]
    ];
    sendTrpcResponse($mediaList);
}
if ($path === '/api/frontend/trpc/activity.rebateDetail') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $yesterdayBet = 0;
    $yesterdayRebate = 0;
    $totalRebate = 0;
    $rebateList = [];
    if ($user) {
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(bet_money), 0) FROM historico_play WHERE id_user = ? AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status_play = 1");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $yesterdayBet = floatval($stmt->get_result()->fetch_row()[0]);
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(valor), 0) FROM adicao_saldo WHERE id_user = ? AND (tipo LIKE '%rebate%' OR tipo = 'reembolso')");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $totalRebate = floatval($stmt->get_result()->fetch_row()[0]);
        $rebateRatio = 0.01;
        $yesterdayRebate = $yesterdayBet * $rebateRatio;
        $stmt = $mysqli->prepare("SELECT valor, data_registro FROM adicao_saldo WHERE id_user = ? AND (tipo LIKE '%rebate%' OR tipo = 'reembolso') ORDER BY data_registro DESC LIMIT 20");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rebateList[] = [
                "date" => date("Y-m-d", strtotime($row['data_registro'])),
                "amount" => floatval($row['valor']),
                "status" => 1 
            ];
        }
    }
    sendTrpcResponse([
        "yesterdayBetAmount" => $yesterdayBet,
        "yesterdayRebateAmount" => $yesterdayRebate,
        "totalRebateAmount" => $totalRebate,
        "rebateRatio" => 0.01,
        "rebateList" => $rebateList
    ]);
}
if ($path === '/api/frontend/trpc/activity.bettingRecord') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $list = [];
    $total = 0;
    if ($user) {
        $trpcInput = getTrpcInput();
        $dataInput = $trpcInput['json'] ?? [];
        $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
        $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
        $offset = ($page - 1) * $pageSize;
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM historico_play WHERE id_user = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['count'];
        $stmt = $mysqli->prepare("SELECT * FROM historico_play WHERE id_user = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $user['id'], $pageSize, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                "id" => $row['id'],
                "gameName" => $row['nome_game'],
                "betAmount" => floatval($row['bet_money']),
                "winAmount" => floatval($row['win_money']),
                "time" => strtotime($row['created_at']) * 1000,
                "orderId" => $row['txn_id'],
                "status" => 1 
            ];
        }
    }
    sendTrpcResponse([
        "list" => $list,
        "total" => $total,
        "totalPage" => ceil($total / 20)
    ]);
}
if ($path === '/api/frontend/trpc/record.gameRecord') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $list = [];
    $total = 0;
    if ($user) {
        $trpcInput = getTrpcInput();
        $dataInput = $trpcInput['json'] ?? [];
        $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
        $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
        $offset = ($page - 1) * $pageSize;
        $startTime = $dataInput['startTime'] ?? null;
        $endTime = $dataInput['endTime'] ?? null;
        $gameType = $dataInput['gameType'] ?? null;
        $platformId = $dataInput['platformId'] ?? null;
        $gameId = $dataInput['gameId'] ?? null;
        if ($startTime) $startTime = date('Y-m-d H:i:s', strtotime($startTime));
        if ($endTime) $endTime = date('Y-m-d H:i:s', strtotime($endTime));
        $where = ["h.id_user = ?"];
        $types = "i";
        $values = [$user['id']];
        if ($startTime && $endTime) {
            $where[] = "h.created_at BETWEEN ? AND ?";
            $types .= "ss";
            $values[] = $startTime;
            $values[] = $endTime;
        } elseif ($startTime) {
            $where[] = "h.created_at >= ?";
            $types .= "s";
            $values[] = $startTime;
        }
        $sqlBase = "FROM historico_play h LEFT JOIN games g ON h.nome_game = g.game_code WHERE " . implode(" AND ", $where);
        if ($gameType) {
            $sqlBase .= " AND g.game_type = ?";
            $types .= "s";
            $values[] = $gameType;
        }
        if ($platformId) {
             $sqlBase .= " AND g.provider = ?";
             $types .= "s";
             $values[] = $platformId;
        }
        if ($gameId) {
             $sqlBase .= " AND h.nome_game = ?";
             $types .= "s";
             $values[] = $gameId;
        }
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count " . $sqlBase);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        $sql = "SELECT h.*, g.game_name, g.provider, g.type, g.game_type, g.banner " . $sqlBase . " ORDER BY h.created_at DESC LIMIT ? OFFSET ?";
        $types .= "ii";
        $values[] = $pageSize;
        $values[] = $offset;
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $list[] = [
                "allBet" => floatval($row['bet_money']) * 100,
                "gameName" => $row['game_name'] ?? $row['nome_game'],
                "provider" => $row['provider'] ?? '',
                "type" => $row['type'] ?? '',
                "gameType" => $row['game_type'] ?? '',
                "banner" => $row['banner'] ?? '',
                "netProfit" => (floatval($row['win_money']) - floatval($row['bet_money'])) * 100,
                "recordId" => $row['txn_id'],
                "settleStatus" => intval($row['status_play']),
                "status" => ($row['win_money'] > 0) ? "WIN" : "LOSS",
                "settleTime" => strtotime($row['created_at']) * 1000,
                "tax" => 0,
                "validBet" => floatval($row['bet_money']) * 100,
                "winAmount" => floatval($row['win_money']) * 100,
                "currency" => "BRL", 
                "platformName" => $row['provider'] ?? ''
            ];
        }
        $stmt->close();
    }
    sendTrpcResponse([
        "gameRecordList" => $list,
        "total" => $total,
        "totalPage" => ceil($total / ($pageSize > 0 ? $pageSize : 20))
    ]);
}
if ($path === '/api/frontend/trpc/rank.userRank') {
    $rotaEncontrada = true;
    $fixedTop = [
        [
            "userId" => 343070232,
            "rankValue" => 33187,
            "avatar" => ""
        ],
        [
            "userId" => 39613515,
            "rankValue" => 11605,
            "avatar" => ""
        ],
        [
            "userId" => 592689378,
            "rankValue" => 25687,
            "avatar" => ""
        ]
    ];
    $randomUsers = [];
    for ($i = 0; $i < 97; $i++) {
        $randomUsers[] = [
            "userId" => rand(100000000, 999999999),
            "rankValue" => rand(5000, 20000),
            "avatar" => ""
        ];
    }
    $userRankList = array_merge($fixedTop, $randomUsers);
    sendTrpcResponse([
        "switch" => true,
        "rankType" => "bet",
        "userRankList" => $userRankList
    ]);
}
if ($path === '/api/frontend/trpc/record.userProfit') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $list = [];
    $total = 0;
    if ($user) {
        $trpcInput = getTrpcInput();
        $dataInput = $trpcInput['json'] ?? [];
        $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
        $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
        $offset = ($page - 1) * $pageSize;
        $startTime = $dataInput['startTime'] ?? null;
        $endTime = $dataInput['endTime'] ?? null;
        $gameType = $dataInput['gameType'] ?? null;
        $platformId = $dataInput['platformId'] ?? null;
        $gameId = $dataInput['gameId'] ?? null;
        if ($startTime) $startTime = date('Y-m-d H:i:s', strtotime($startTime));
        if ($endTime) $endTime = date('Y-m-d H:i:s', strtotime($endTime));
        $where = ["h.id_user = ?"];
        $types = "i";
        $values = [$user['id']];
        if ($startTime && $endTime) {
            $where[] = "h.created_at BETWEEN ? AND ?";
            $types .= "ss";
            $values[] = $startTime;
            $values[] = $endTime;
        } elseif ($startTime) {
             $where[] = "h.created_at >= ?";
             $types .= "s";
             $values[] = $startTime;
        }
        $sqlBase = "FROM historico_play h LEFT JOIN games g ON h.nome_game = g.game_code WHERE " . implode(" AND ", $where);
        if ($gameType) {
            $sqlBase .= " AND g.game_type = ?";
            $types .= "s";
            $values[] = $gameType;
        }
        if ($platformId) {
             $sqlBase .= " AND g.provider = ?";
             $types .= "s";
             $values[] = $platformId;
        }
        if ($gameId) {
             $sqlBase .= " AND h.nome_game = ?";
             $types .= "s";
             $values[] = $gameId;
        }
        $countSql = "SELECT COUNT(DISTINCT h.nome_game) as count " . $sqlBase;
        $stmt = $mysqli->prepare($countSql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        $sql = "SELECT h.nome_game, COUNT(*) as betitemNum, SUM(h.bet_money) as allBet, SUM(h.win_money) as winAmount, SUM(h.win_money - h.bet_money) as netProfit, MIN(h.created_at) as first_time, g.game_name, g.type as gameCategoryName, g.provider as platformName 
                " . $sqlBase . " 
                GROUP BY h.nome_game 
                ORDER BY MAX(h.created_at) DESC 
                LIMIT ? OFFSET ?";
        $types .= "ii";
        $values[] = $pageSize;
        $values[] = $offset;
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
             $list[] = [
                "allBet" => floatval($row['allBet']) * 100,
                "betitemNum" => intval($row['betitemNum']),
                "currency" => "BRL",
                "date" => date('Y-m-d', strtotime($row['first_time'])),
                "dateTimestamp" => strtotime($row['first_time']) * 1000,
                "gameCategoryName" => $row['gameCategoryName'] ?? '',
                "gameName" => $row['game_name'] ?? $row['nome_game'],
                "netProfit" => floatval($row['netProfit']) * 100,
                "platformName" => $row['platformName'] ?? '',
                "tax" => 0,
                "validBet" => floatval($row['allBet']) * 100,
                "winAmount" => floatval($row['winAmount']) * 100
             ];
        }
        $stmt->close();
    }
    sendTrpcResponse([
        "userDayProfitList" => $list,
        "total" => $total,
        "totalPage" => ceil($total / ($pageSize > 0 ? $pageSize : 20))
    ]);
}
if ($path === '/api/frontend/trpc/activity.assistanceCashDetail') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $amount = 0;
    $status = 0; 
    $inviteCount = 0;
    if ($user) {
        $inviteCount = isset($user['pessoas_convidadas']) ? intval($user['pessoas_convidadas']) : 0;
        $baus = getBoxList($mysqli, $user['token']);
        foreach ($baus as $bau) {
            if ($bau['promoteStatus'] == 0 || $bau['promoteStatus'] == 1) {
                $amount = $bau['expectedReward'];
                $status = ($bau['promoteStatus'] == 1) ? 1 : 0;
                break;
            }
        }
    }
    sendTrpcResponse([
        "activityId" => 637,
        "amount" => $amount,
        "status" => $status,
        "expireTime" => time() + 86400,
        "inviteCount" => $inviteCount
    ]);
}
if ($path === '/api/frontend/trpc/activity.chestList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $baus = getBoxList($mysqli, $user['token']);
        sendTrpcResponse(["chestList" => $baus]);
    } else {
        $baus = getBoxList($mysqli, ''); 
        sendTrpcResponse(["chestList" => $baus]);
    }
}
if ($path === '/api/frontend/trpc/invite.inviteInfo') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $inviteCode = "";
    $inviteLink = "";
    $totalReward = 0;
    $todayReward = 0;
    $totalPerson = 0;
    $todayPerson = 0;
    $takenCommission = 0;
    $outrosSubordinadosNovos = 0;
    $numeroDepositos = 0;
    $valorTotalDepositos = 0;
    $valorPrimeiroDeposito = 0;
    $depositoMedio = 0;
    $usuariosPrimeiroDeposito = 0;
    $valorNovoPrimeiroDeposito = 0;
    $usuariosNovoPrimeiroDeposito = 0;
    $valorSaque = 0;
    $numeroSaques = 0;
    if ($user) {
        $inviteCode = $user['invite_code'];
        $inviteLink = $WG_BUCKET_SITE . "/register?code=" . $inviteCode;
        $totalPerson = intval($user['pessoas_convidadas'] ?? 0) + intval($user['indicacoes_roubadas'] ?? 0);
        $today = date('Y-m-d');
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM usuarios WHERE invitation_code = ? AND DATE(data_registro) = ?");
        $stmt->bind_param("ss", $inviteCode, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $todayPerson = $res->fetch_assoc()['count'] ?? 0;
        $qryL2 = "SELECT COUNT(*) as count FROM usuarios WHERE DATE(data_registro) = ? AND invitation_code IN (SELECT invite_code FROM usuarios WHERE invitation_code = ?)";
        $stmt = $mysqli->prepare($qryL2);
        $stmt->bind_param("ss", $today, $inviteCode);
        $stmt->execute();
        $l2Count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $qryL3 = "SELECT COUNT(*) as count FROM usuarios WHERE DATE(data_registro) = ? AND invitation_code IN (SELECT invite_code FROM usuarios WHERE invitation_code IN (SELECT invite_code FROM usuarios WHERE invitation_code = ?))";
        $stmt = $mysqli->prepare($qryL3);
        $stmt->bind_param("ss", $today, $inviteCode);
        $stmt->execute();
        $l3Count = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $outrosSubordinadosNovos = $l2Count + $l3Count;
        $qryDeposits = "SELECT COUNT(*) as count, COALESCE(SUM(t.valor), 0) as total FROM transacoes t JOIN usuarios u ON t.usuario = u.id WHERE u.invitation_code = ? AND t.tipo = 'deposito' AND t.status = 'pago' AND DATE(t.data_registro) = ?";
        $stmt = $mysqli->prepare($qryDeposits);
        $stmt->bind_param("ss", $inviteCode, $today);
        $stmt->execute();
        $depData = $stmt->get_result()->fetch_assoc();
        $numeroDepositos = intval($depData['count']);
        $valorTotalDepositos = floatval($depData['total']);
        $depositoMedio = $numeroDepositos > 0 ? $valorTotalDepositos / $numeroDepositos : 0;
        $qryFirstDep = "SELECT COUNT(DISTINCT t.usuario) as users_count, COALESCE(SUM(t.valor), 0) as total_value FROM transacoes t JOIN usuarios u ON t.usuario = u.id WHERE u.invitation_code = ? AND t.tipo = 'deposito' AND t.status = 'pago' AND DATE(t.data_registro) = ? AND NOT EXISTS (SELECT 1 FROM transacoes t2 WHERE t2.usuario = t.usuario AND t2.tipo = 'deposito' AND t2.status = 'pago' AND t2.id < t.id)";
        $stmt = $mysqli->prepare($qryFirstDep);
        $stmt->bind_param("ss", $inviteCode, $today);
        $stmt->execute();
        $firstDepData = $stmt->get_result()->fetch_assoc();
        $usuariosPrimeiroDeposito = intval($firstDepData['users_count']);
        $valorPrimeiroDeposito = floatval($firstDepData['total_value']);
        $qryNewUserFirstDep = "SELECT COUNT(DISTINCT t.usuario) as users_count, COALESCE(SUM(t.valor), 0) as total_value FROM transacoes t JOIN usuarios u ON t.usuario = u.id WHERE u.invitation_code = ? AND DATE(u.data_registro) = ? AND t.tipo = 'deposito' AND t.status = 'pago' AND DATE(t.data_registro) = ? AND NOT EXISTS (SELECT 1 FROM transacoes t2 WHERE t2.usuario = t.usuario AND t2.tipo = 'deposito' AND t2.status = 'pago' AND t2.id < t.id)";
        $stmt = $mysqli->prepare($qryNewUserFirstDep);
        $stmt->bind_param("sss", $inviteCode, $today, $today);
        $stmt->execute();
        $newUserFirstDepData = $stmt->get_result()->fetch_assoc();
        $usuariosNovoPrimeiroDeposito = intval($newUserFirstDepData['users_count']);
        $valorNovoPrimeiroDeposito = floatval($newUserFirstDepData['total_value']);
        $qryWithdraw = "SELECT COUNT(*) as count, COALESCE(SUM(t.valor), 0) as total FROM transacoes t JOIN usuarios u ON t.usuario = u.id WHERE u.invitation_code = ? AND t.tipo = 'saque' AND t.status = 'pago' AND DATE(t.data_registro) = ?";
        $stmt = $mysqli->prepare($qryWithdraw);
        $stmt->bind_param("ss", $inviteCode, $today);
        $stmt->execute();
        $withdrawData = $stmt->get_result()->fetch_assoc();
        $numeroSaques = intval($withdrawData['count']);
        $valorSaque = floatval($withdrawData['total']);
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM adicao_saldo WHERE id_user = ? AND tipo LIKE '%comissao%'");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $totalReward = floatval($res->fetch_assoc()['total'] ?? 0);
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM adicao_saldo WHERE id_user = ? AND tipo LIKE '%comissao%' AND DATE(data_registro) = ?");
        $stmt->bind_param("is", $user['id'], $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $todayReward = floatval($res->fetch_assoc()['total'] ?? 0);
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM resgate_comissoes WHERE id_user = ? AND tipo = 'resgate'");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $takenCommission = floatval($res->fetch_assoc()['total'] ?? 0);
    }
    sendTrpcResponse([
        "inviteCode" => $inviteCode,
        "inviteLink" => $inviteLink,
        "totalReward" => $totalReward,
        "todayReward" => $todayReward,
        "reward" => [
            "amount" => $totalReward
        ],
        "commission" => [],
        "totalPerson" => $totalPerson,
        "todayPerson" => $todayPerson,
        "agentRate" => 0,
        "agentLevel" => 0,
        "takenCommission" => $takenCommission,
        "canWithdrawAmount" => max(0, $totalReward - $takenCommission),
        "dayDirectAdd" => $todayPerson,
        "dayTeamAdd" => $outrosSubordinadosNovos,
        "dayTeamRecharge" => $valorTotalDepositos,
        "dayTeamRechargeUserNum" => $numeroDepositos,
        "dayTeamFirstRechargeAmount" => $valorPrimeiroDeposito,
        "dayTeamFirstRechargeNum" => $usuariosPrimeiroDeposito,
        "dayTeamFirstRechargeAmountSameDay" => $valorNovoPrimeiroDeposito,
        "dayTeamFirstRechargeNumSameDay" => $usuariosNovoPrimeiroDeposito,
        "dayTeamWithdrawals" => $valorSaque,
        "dayTeamWithdrawCount" => $numeroSaques,
        "averageDeposit" => $depositoMedio
    ]);
}
if ($path === '/api/frontend/trpc/invite.recordList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $recordList = [];
    $total = 0;
    if ($user) {
        $inviteCode = $user['invite_code'];
        $page = isset($data['json']['page']) ? intval($data['json']['page']) : 1;
        $pageSize = isset($data['json']['pageSize']) ? intval($data['json']['pageSize']) : 20;
        $offset = ($page - 1) * $pageSize;
        $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM usuarios WHERE invitation_code = ?");
        $stmt->bind_param("s", $inviteCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = $res->fetch_assoc()['count'];
        $stmt = $mysqli->prepare("SELECT id, mobile, data_registro FROM usuarios WHERE invitation_code = ? ORDER BY data_registro DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("sii", $inviteCode, $pageSize, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
             $recordList[] = [
                 "userId" => $row['id'],
                 "username" => substr($row['mobile'], 0, 3) . '****' . substr($row['mobile'], -4),
                 "registerTime" => strtotime($row['data_registro']) * 1000,
                 "depositAmount" => 0,
                 "betAmount" => 0,
                 "commission" => 0
             ];
        }
        foreach ($recordList as &$record) {
             $uid = $record['userId'];
             $q = "SELECT SUM(valor) as t FROM transacoes WHERE usuario = $uid AND tipo = 'deposito' AND status = 'pago'";
             $r = $mysqli->query($q);
             $record['depositAmount'] = floatval($r->fetch_assoc()['t'] ?? 0);
             $q = "SELECT SUM(bet_money) as t FROM historico_play WHERE id_user = $uid AND status_play = '1'";
             $r = $mysqli->query($q);
             $record['betAmount'] = floatval($r->fetch_assoc()['t'] ?? 0);
             $record['commission'] = 0; 
        }
    }
    sendTrpcResponse([
        "recordList" => $recordList,
        "total" => $total,
        "totalPage" => ceil($total / 20)
    ]);
}
if ($path === '/api/frontend/trpc/redeemCode.info') {
    $rotaEncontrada = true;
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    $code = isset($dataInput['code']) ? trim($dataInput['code']) : '';
    if (empty($code)) {
        http_response_code(400);
        echo json_encode([
            "error" => [
                "json" => [
                    "message" => "O código Redem não existe",
                    "code" => -32600,
                    "data" => [
                        "code" => "BAD_REQUEST",
                        "httpStatus" => 400,
                        "path" => "redeemCode.info"
                    ]
                ]
            ]
        ]);
        exit;
    }
    $user = getCurrentUser($mysqli);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $stmt = $mysqli->prepare("SELECT id, nome, status, qtd_insert, range_valor FROM cupom WHERE nome = ? LIMIT 1");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['status'] != 1) {
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "O código Redem não existe",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "redeemCode.info"
                        ]
                    ]
                ]
            ]);
            exit;
        }
        $stmtUsed = $mysqli->prepare("SELECT id FROM cupom_usados WHERE id_user = ? AND codigo = ? LIMIT 1");
        $stmtUsed->bind_param("is", $user['id'], $code);
        $stmtUsed->execute();
        if ($stmtUsed->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "Você já resgatou este código.",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "redeemCode.info"
                        ]
                    ]
                ]
            ]);
            exit;
        }
        $stmtUsed->close();
        if ($row['qtd_insert'] <= 0) {
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "Cupom esgotado.",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "redeemCode.info"
                        ]
                    ]
                ]
            ]);
            exit;
        }
        $range = $row['range_valor'];
        $reward = 0;
        if (strpos($range, ',') !== false) {
            $parts = explode(',', $range);
            $min = floatval($parts[0]);
            $max = floatval($parts[1]);
            $reward = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        } else {
            $reward = floatval($range);
        }
        $reward = round($reward, 2);
        if ($reward <= 0) {
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "Erro no valor do cupom.",
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "redeemCode.info"
                        ]
                    ]
                ]
            ]);
            exit;
        }
        $mysqli->begin_transaction();
        try {
            $stmtUpdate = $mysqli->prepare("UPDATE cupom SET qtd_insert = qtd_insert - 1 WHERE id = ? AND qtd_insert > 0");
            $stmtUpdate->bind_param("i", $row['id']);
            $stmtUpdate->execute();
            if ($stmtUpdate->affected_rows === 0) {
                throw new Exception("Cupom esgotado durante processamento.");
            }
            $stmtSaldo = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?");
            $stmtSaldo->bind_param("di", $reward, $user['id']);
            $stmtSaldo->execute();
            $stmtLog = $mysqli->prepare("INSERT INTO cupom_usados (id_user, codigo, valor, bonus, data_registro) VALUES (?, ?, 0, ?, NOW())");
            $stmtLog->bind_param("isd", $user['id'], $code, $reward);
            $stmtLog->execute();
            $mysqli->commit();
            sendTrpcResponse([
                "rewardAmount" => $reward * 100
            ]);
        } catch (Exception $e) {
            $mysqli->rollback();
            http_response_code(400);
            echo json_encode([
                "error" => [
                    "json" => [
                        "message" => "Erro ao processar resgate: " . $e->getMessage(),
                        "code" => -32600,
                        "data" => [
                            "code" => "BAD_REQUEST",
                            "httpStatus" => 400,
                            "path" => "redeemCode.info"
                        ]
                    ]
                ]
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "error" => [
                "json" => [
                    "message" => "O código Redem não existe",
                    "code" => -32600,
                    "data" => [
                        "code" => "BAD_REQUEST",
                        "httpStatus" => 400,
                        "path" => "redeemCode.info"
                    ]
                ]
            ]
        ]);
        exit;
    }
}
if ($path === '/api/frontend/trpc/redeemCode.getRedeemCodeConfig') {
    $rotaEncontrada = true;
    $data = [
        "image" => "https://upload-us.f-1-g-h.com/s1/1753127713275/changshang.png",
        "introText" => "Por favor, insira o código de resgate",
        "LinkType" => "InternalLink",
        "value" => "/Redeem",
        "valueType" => "CODE",
        "activityName" => "Página de Código de Resgate"
    ];
    sendTrpcResponse($data);
}
if ($path === '/api/frontend/trpc/invite.reward') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $available = floatval($user['saldo_afiliados']);
    $config_afiliados_qry = "SELECT minResgate FROM afiliados_config WHERE id = 1";
    $config_afiliados_resp = mysqli_query($mysqli, $config_afiliados_qry);
    $minResgate = 0;
    if ($config_afiliados_resp && $row = mysqli_fetch_assoc($config_afiliados_resp)) {
        $minResgate = floatval($row['minResgate']);
    }
    if ($available < $minResgate) {
         sendTrpcResponse(["status" => false, "msg" => "Mínimo para resgate: R$ " . number_format($minResgate, 2, ',', '.')]);
         exit;
    }
    if ($available <= 0) {
         sendTrpcResponse(["status" => false, "msg" => "Saldo insuficiente."]);
         exit;
    }
    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("UPDATE usuarios SET saldo_afiliados = 0, saldo = saldo + ? WHERE id = ?");
        $stmt->bind_param("di", $available, $user['id']);
        $stmt->execute();
        $stmt = $mysqli->prepare("INSERT INTO resgate_comissoes (id_user, valor, tipo, data_registro) VALUES (?, ?, 'resgate', NOW())");
        $stmt->bind_param("id", $user['id'], $available);
        $stmt->execute();
        $mysqli->commit();
        sendTrpcResponse(["status" => true, "msg" => "Comissão resgatada com sucesso!"]);
    } catch (Exception $e) {
        $mysqli->rollback();
        sendTrpcResponse(["status" => false, "msg" => "Erro ao processar resgate."]);
    }
}
if ($path === '/api/frontend/trpc/deposit.create' || $path === '/api/frontend/trpc/recharge.create' || $path === '/api/frontend/trpc/pay.create') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    $amount = floatval($dataInput['amount'] ?? 0);
    if ($amount <= 0 && isset($_POST['amount'])) {
        $amount = floatval($_POST['amount']);
    }
    $amount = $amount / 100;
    if ($amount <= 0) {
        sendTrpcResponse(["status" => false, "msg" => "Valor inválido."]);
        exit;
    }
    $nome = $user['real_name'] ?? 'Cliente';
    $id = $user['id'];
    $comissao = null; 
    $afiliado_id = null; 
    $payTypeSubListId = isset($dataInput['payTypeSubListId']) ? intval($dataInput['payTypeSubListId']) : null;
    $joinBonus = true;
    if (isset($dataInput['participateReward'])) {
        $joinBonus = $dataInput['participateReward'];
    } elseif (isset($dataInput['joinBonus'])) {
        $joinBonus = $dataInput['joinBonus'];
    }
    if ($joinBonus === 0 || $joinBonus === '0' || $joinBonus === false || $joinBonus === 'false') {
        $payTypeSubListId = null;
        $joinBonus = 0; 
    } else {
        $joinBonus = 1; 
    }
    prodLog("----------------------------------------------------------------");
    prodLog("API: Iniciando solicitacao de deposito (pay.create/deposit.create)");
    prodLog("API: Usuario ID: " . $id . " | Nome: " . $nome);
    prodLog("API: Valor (reais): " . $amount);
    prodLog("API: payTypeSubListId: " . $payTypeSubListId);
    try {
        prodLog("API: Chamando next_sistemas_qrcode...");
        $result = next_sistemas_qrcode($amount, $nome, $id, $comissao, $afiliado_id, $payTypeSubListId, $joinBonus);
        prodLog("API: Retorno de next_sistemas_qrcode: " . print_r($result, true));
    } catch (Exception $e) {
        prodLog("API: Exception em next_sistemas_qrcode: " . $e->getMessage());
        $result = null;
    } catch (Error $e) {
        prodLog("API: Fatal Error em next_sistemas_qrcode: " . $e->getMessage());
        $result = null;
    }
    if ($result && (isset($result['qrcode']) || isset($result['qr_code_image']))) {
         $payUrl = $result['code'] ?? '';
         $qrCode = $result['qr_code_image'] ?? ($result['qrcode'] ?? '');
         $createTime = date("Y-m-d\TH:i:sP");
         $expireTime = date("Y-m-d\TH:i:sP", time() + 86400); 
         sendTrpcResponse([
             "amount" => $amount * 100, 
             "code" => 2000,
             "createTime" => $createTime,
             "expireTime" => $expireTime,
             "msg" => "",
             "orderNo" => $result['transacao_id'],
             "payUrl" => $payUrl, 
             "qrCode" => $qrCode, 
             "redirectType" => "DEFAULT",
         ], [
             "values" => [
                 "createTime" => ["Date"],
                 "expireTime" => ["Date"]
             ]
         ]);
    } else {
        $createTime = date("Y-m-d\TH:i:sP");
        $expireTime = date("Y-m-d\TH:i:sP", time() + 86400);
        sendTrpcResponse([
            "code" => 2003,
            "orderNo" => "",
            "payUrl" => "",
            "createTime" => $createTime,
            "expireTime" => $expireTime,
            "msg" => "Falha ao criar pedido, tente novamente mais tarde! Se ocorrerem várias falhas, entre em contato com o atendimento ao cliente online.",
            "redirectType" => "DEFAULT",
            "amount" => $amount * 100
        ], [
            "values" => [
                "createTime" => ["Date"],
                "expireTime" => ["Date"]
            ]
        ]);
    }
}
if ($path === '/api/frontend/trpc/deposit.list' || $path === '/api/frontend/trpc/recharge.list' || $path === '/api/frontend/trpc/recharge.getRechargeRecordList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        if ($path === '/api/frontend/trpc/recharge.getRechargeRecordList') {
             sendTrpcResponse(["list" => [], "total" => 0]);
        } else {
             sendTrpcResponse(["list" => [], "total" => 0, "totalPage" => 0]);
        }
        exit;
    }
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
    $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
    $offset = ($page - 1) * $pageSize;
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM transacoes WHERE usuario = ? AND tipo = 'deposito'");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['count'];
    $stmt = $mysqli->prepare("SELECT * FROM transacoes WHERE usuario = ? AND tipo = 'deposito' ORDER BY data_registro DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("iii", $user['id'], $pageSize, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $list = [];
    while ($row = $res->fetch_assoc()) {
        $statusInt = 0; 
        $statusStr = "Pendente";
        if ($row['status'] == 'pago') {
            $statusInt = 1; 
            $statusStr = "Sucesso";
        } else if ($row['status'] == 'cancelado') {
            $statusInt = 2;
            $statusStr = "Cancelado";
        }
        $item = [
            "orderId" => $row['transacao_id'],
            "amount" => floatval($row['valor']),
            "status" => $statusInt, 
            "statusStr" => $statusStr,
            "createTime" => strtotime($row['data_registro']) * 1000,
            "payType" => "PIX"
        ];
        if ($path === '/api/frontend/trpc/recharge.getRechargeRecordList') {
             $item["id"] = $row['id'];
             $item["statusName"] = $statusStr;
             $item["paymentName"] = "PIX";
        }
        $list[] = $item;
    }
    if ($path === '/api/frontend/trpc/recharge.getRechargeRecordList') {
        sendTrpcResponse([
            "list" => $list,
            "total" => $total
        ]);
    } else {
        sendTrpcResponse([
            "list" => $list,
            "total" => $total,
            "totalPage" => ceil($total / $pageSize)
        ]);
    }
}
if ($path === '/api/frontend/trpc/agency.softwareList') {
    $rotaEncontrada = true;
    $data = [
        "software" => [
            "{\"name\":\"Email\",\"type\":\"Email\",\"isOpen\":true,\"sort\":9}",
            "{\"name\":\"YouTube\",\"type\":\"YouTube\",\"isOpen\":true,\"sort\":8}",
            "{\"name\":\"Kwai\",\"type\":\"Kwai\",\"isOpen\":true,\"sort\":7}",
            "{\"name\":\"Twitter\",\"type\":\"Twitter\",\"isOpen\":true,\"sort\":6}",
            "{\"name\":\"WhatsApp\",\"type\":\"WhatsApp\",\"isOpen\":true,\"sort\":5}",
            "{\"name\":\"TikTok\",\"type\":\"TikTok\",\"isOpen\":true,\"sort\":4}",
            "{\"name\":\"Instagram\",\"type\":\"Instagram\",\"isOpen\":true,\"sort\":3}",
            "{\"name\":\"Telegram\",\"type\":\"Telegram\",\"isOpen\":true,\"sort\":2}",
            "{\"name\":\"Facebook\",\"type\":\"Facebook\",\"isOpen\":true,\"sort\":15}"
        ]
    ];
    sendTrpcResponse($data);
}
if ($path === '/api/frontend/trpc/agency.info') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $timeType = $dataInput['timeType'] ?? 'today';
    $startDate = date('Y-m-d 00:00:00');
    $endDate = date('Y-m-d 23:59:59');
    if ($timeType === 'yesterday') {
        $startDate = date('Y-m-d 00:00:00', strtotime('yesterday'));
        $endDate = date('Y-m-d 23:59:59', strtotime('yesterday'));
    } elseif ($timeType === 'week') {
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
    } elseif ($timeType === 'lastWeek') {
        $startDate = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday last week'));
    } elseif ($timeType === 'month') {
        $startDate = date('Y-m-01 00:00:00');
        $endDate = date('Y-m-t 23:59:59');
    } elseif ($timeType === 'lastMonth') {
        $startDate = date('Y-m-01 00:00:00', strtotime('last month'));
        $endDate = date('Y-m-t 23:59:59', strtotime('last month'));
    }
    $userId = 0;
    $parentId = 0;
    $dayDirectAdd = 0;
    $dayTeamAdd = 0;
    $dayDirectCommission = 0;
    $dayTeamCommission = 0;
    $dayDirectAchievement = 0;
    $dayTeamAchievement = 0;
    $claimedCommission = 0;
    $directCount = 0;
    $teamCount = 0;
    $unclaimedCommission = 0;
    $claimedCommission = 0;
    $dayClaimedCommission = 0;
    $dayDirectValidBetting = 0;
    $dayDirectWinsLose = 0;
    $dayDirectWithdrawals = 0;
    if ($user) {
        $userId = $user['id'];
        $parentId = $user['afiliado'] ?? 0;
        $unclaimedCommission = floatval($user['saldo_afiliados'] ?? 0) * 100; 
        $myInviteCode = $user['invite_code'];
        $cpaLvl1 = 0; $cpaLvl2 = 0; $cpaLvl3 = 0;
        $stmtConfig = $mysqli->prepare("SELECT cpaLvl1, cpaLvl2, cpaLvl3 FROM afiliados_config LIMIT 1");
        if ($stmtConfig) {
            $stmtConfig->execute();
            $resConfig = $stmtConfig->get_result();
            if ($rowConfig = $resConfig->fetch_assoc()) {
                $cpaLvl1 = floatval($rowConfig['cpaLvl1']);
                $cpaLvl2 = floatval($rowConfig['cpaLvl2']);
                $cpaLvl3 = floatval($rowConfig['cpaLvl3']);
            }
            $stmtConfig->close();
        }
        $stmt = $mysqli->prepare("SELECT id, data_registro, invite_code FROM usuarios WHERE invitation_code = ?");
        $stmt->bind_param("s", $myInviteCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $level1Ids = [];
        $level1IdsToday = []; 
        $level1InviteCodes = [];
        while ($row = $res->fetch_assoc()) {
            $level1Ids[] = $row['id'];
            if (!empty($row['invite_code'])) {
                $level1InviteCodes[] = $row['invite_code'];
            }
            $directCount++;
            if ($row['data_registro'] >= $startDate && $row['data_registro'] <= $endDate) {
                $dayDirectAdd++;
                $level1IdsToday[] = $row['id'];
            }
        }
        $stmt->close();
        $level2Ids = [];
        $level2InviteCodes = [];
        if (!empty($level1InviteCodes)) {
            $inClause = "'" . implode("','", $level1InviteCodes) . "'";
            $qry = "SELECT id, data_registro, invite_code FROM usuarios WHERE invitation_code IN ($inClause)";
            $res = $mysqli->query($qry);
            while ($row = $res->fetch_assoc()) {
                $level2Ids[] = $row['id'];
                if (!empty($row['invite_code'])) {
                    $level2InviteCodes[] = $row['invite_code'];
                }
                if ($row['data_registro'] >= $startDate && $row['data_registro'] <= $endDate) {
                    $dayTeamAdd++;
                }
            }
        }
        $level3Ids = [];
        if (!empty($level2InviteCodes)) {
            $inClause = "'" . implode("','", $level2InviteCodes) . "'";
            $qry = "SELECT id, data_registro FROM usuarios WHERE invitation_code IN ($inClause)";
            $res = $mysqli->query($qry);
            while ($row = $res->fetch_assoc()) {
                $level3Ids[] = $row['id'];
                if ($row['data_registro'] >= $startDate && $row['data_registro'] <= $endDate) {
                    $dayTeamAdd++;
                }
            }
        }
        $teamCount = $directCount + count($level2Ids) + count($level3Ids);
        $claimedCommission = floatval($user['comissao_recebida'] ?? 0) * 100; 
        $dayClaimedCommission = $claimedCommission;
        $dayTeamRecharge = 0; 
        $dayTeamRechargeUserNum = 0; 
        $dayTeamFirstRechargeAmount = 0; 
        $dayTeamFirstRechargeNum = 0; 
        $dayTeamFirstRechargeAmountSameDay = 0; 
        $dayTeamFirstRechargeNumSameDay = 0; 
        $dayTeamWithdrawals = 0; 
        $dayTeamWithdrawCount = 0; 
        if (!empty($level1Ids)) {
            $idsStr = implode(',', $level1Ids);
            $qry = "SELECT SUM(valor) as total, COUNT(DISTINCT usuario) as num_users FROM transacoes WHERE usuario IN ($idsStr) AND tipo='deposito' AND status='pago' AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $res = $mysqli->query($qry);
            if ($res && $row = $res->fetch_assoc()) {
                $dayDirectAchievement = floatval($row['total'] ?? 0) * 100; 
                $dayTeamRecharge = $dayDirectAchievement; 
                $dayTeamRechargeUserNum = intval($row['num_users']);
                $dayDirectCommission = $dayDirectAchievement * ($cpaLvl1 / 100);
            }
            $qryWithdraw = "SELECT SUM(valor) as total, COUNT(*) as num FROM transacoes WHERE usuario IN ($idsStr) AND tipo='saque' AND status='pago' AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $resWithdraw = $mysqli->query($qryWithdraw);
            if ($resWithdraw && $row = $resWithdraw->fetch_assoc()) {
                $dayTeamWithdrawals = floatval($row['total'] ?? 0) * 100;
                $dayTeamWithdrawCount = intval($row['num']);
            }
            $qryWithdrawPending = "SELECT SUM(valor) as total, COUNT(*) as num FROM solicitacao_saques WHERE id_user IN ($idsStr) AND status=0 AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $resWithdrawPending = $mysqli->query($qryWithdrawPending);
            if ($resWithdrawPending && $rowPending = $resWithdrawPending->fetch_assoc()) {
                $dayTeamWithdrawals += floatval($rowPending['total'] ?? 0) * 100;
                $dayTeamWithdrawCount += intval($rowPending['num']);
            }
            $dayDirectWithdrawals = $dayTeamWithdrawals;
            $qryFirst = "SELECT COUNT(DISTINCT t.usuario) as num, SUM(t.valor) as total
                         FROM transacoes t
                         WHERE t.usuario IN ($idsStr) 
                         AND t.tipo = 'deposito' 
                         AND t.status = 'pago' 
                         AND t.data_registro >= '$startDate' AND t.data_registro <= '$endDate'
                         AND NOT EXISTS (
                             SELECT 1 FROM transacoes t2 
                             WHERE t2.usuario = t.usuario 
                             AND t2.tipo = 'deposito' 
                             AND t2.status = 'pago' 
                             AND t2.id < t.id
                         )";
            $resFirst = $mysqli->query($qryFirst);
            if ($resFirst && $row = $resFirst->fetch_assoc()) {
                $dayTeamFirstRechargeAmount = floatval($row['total'] ?? 0) * 100;
                $dayTeamFirstRechargeNum = intval($row['num']);
            }
            $qryPlay = "SELECT SUM(bet_money) as total_bet, SUM(win_money) as total_win FROM historico_play WHERE id_user IN ($idsStr) AND status_play = 1 AND created_at >= '$startDate' AND created_at <= '$endDate'";
            $resPlay = $mysqli->query($qryPlay);
            if ($resPlay && $rowPlay = $resPlay->fetch_assoc()) {
                $dayDirectValidBetting = floatval($rowPlay['total_bet'] ?? 0) * 100; 
                $totalWin = floatval($rowPlay['total_win'] ?? 0) * 100; 
                $dayDirectWinsLose = $totalWin - $dayDirectValidBetting;
            }
        }
        if (!empty($level1IdsToday)) {
            $idsStrToday = implode(',', $level1IdsToday);
            $qryFirstToday = "SELECT COUNT(DISTINCT t.usuario) as num, SUM(t.valor) as total
                              FROM transacoes t
                              WHERE t.usuario IN ($idsStrToday) 
                              AND t.tipo = 'deposito' 
                              AND t.status = 'pago' 
                              AND t.data_registro >= '$startDate' AND t.data_registro <= '$endDate'
                              AND NOT EXISTS (
                                  SELECT 1 FROM transacoes t2 
                                  WHERE t2.usuario = t.usuario 
                                  AND t2.tipo = 'deposito' 
                                  AND t2.status = 'pago' 
                                  AND t2.id < t.id
                              )";
            $resFirstToday = $mysqli->query($qryFirstToday);
            if ($resFirstToday && $row = $resFirstToday->fetch_assoc()) {
                $dayTeamFirstRechargeAmountSameDay = floatval($row['total'] ?? 0) * 100;
                $dayTeamFirstRechargeNumSameDay = intval($row['num']);
            }
        }
        $commissionLvl2 = 0;
        if (!empty($level2Ids)) {
            $idsStr2 = implode(',', $level2Ids);
            $qry2 = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr2) AND tipo='deposito' AND status='pago' AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $res2 = $mysqli->query($qry2);
            if ($res2 && $row2 = $res2->fetch_assoc()) {
                $depLvl2 = floatval($row2['total'] ?? 0) * 100;
                $commissionLvl2 = $depLvl2 * ($cpaLvl2 / 100);
            }
        }
        $commissionLvl3 = 0;
        if (!empty($level3Ids)) {
            $idsStr3 = implode(',', $level3Ids);
            $qry3 = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr3) AND tipo='deposito' AND status='pago' AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $res3 = $mysqli->query($qry3);
            if ($res3 && $row3 = $res3->fetch_assoc()) {
                $depLvl3 = floatval($row3['total'] ?? 0) * 100;
                $commissionLvl3 = $depLvl3 * ($cpaLvl3 / 100);
            }
        }
        $dayTeamCommission = $commissionLvl2 + $commissionLvl3;
        $allTeamIds = array_merge($level2Ids, $level3Ids);
        if (!empty($allTeamIds)) {
            $idsStr = implode(',', $allTeamIds);
            $qry = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr) AND tipo='deposito' AND status='pago' AND data_registro >= '$startDate' AND data_registro <= '$endDate'";
            $res = $mysqli->query($qry);
            if ($res && $row = $res->fetch_assoc()) {
                $dayTeamAchievement = floatval($row['total'] ?? 0) * 100;
            }
        }
    }
    $data = [
        "info" => [
            "userId" => $userId,
            "parentId" => $parentId,
            "dayDirectAdd" => $dayDirectAdd,
            "dayTeamAdd" => $dayTeamAdd, 
            "dayDirectCommission" => $dayDirectCommission,
            "dayTeamCommission" => $dayTeamCommission,
            "dayDirectAchievement" => $dayDirectAchievement,
            "dayTeamAchievement" => $dayTeamAchievement,
            "daySettlementCommission" => 0,
            "dayClaimedCommission" => $dayClaimedCommission,
            "unclaimedCommission" => $unclaimedCommission, 
            "dayDirectValidBetting" => $dayDirectValidBetting,
            "dayDirectWinsLose" => $dayDirectWinsLose,
            "dayDirectRecharge" => $dayDirectAchievement,
            "dayDirectWithdrawals" => $dayDirectWithdrawals,
            "dayTeamRecharge" => $dayTeamRecharge,
            "dayTeamFirstRechargeAmount" => $dayTeamFirstRechargeAmount,
            "dayTeamFirstRechargeNum" => $dayTeamFirstRechargeNum,
            "dayTeamFirstRechargeAmountSameDay" => $dayTeamFirstRechargeAmountSameDay,
            "dayTeamFirstRechargeNumSameDay" => $dayTeamFirstRechargeNumSameDay,
            "dayTeamWithdrawals" => $dayTeamWithdrawals,
            "dayTeamWithdrawCount" => $dayTeamWithdrawCount,
            "dayTeamRechargeUserNum" => $dayTeamRechargeUserNum,
            "directCount" => $directCount,
            "teamCount" => $teamCount,
            "claimedCommission" => $claimedCommission,
            "directAchievement" => 0,
            "teamAchievement" => 0,
            "commission" => $unclaimedCommission,
            "lastCommission" => 0,
            "type" => "noGameType"
        ]
    ];
    sendTrpcResponse($data);
}
if ($path === '/api/frontend/trpc/agency.reward') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $amount = floatval($user['saldo_afiliados'] ?? 0);
        if ($amount > 0) {
            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare("UPDATE usuarios SET saldo = saldo + ?, comissao_recebida = comissao_recebida + ?, saldo_afiliados = 0 WHERE id = ?");
                $stmt->bind_param("ddi", $amount, $amount, $user['id']);
                $stmt->execute();
                $mysqli->commit();
                sendTrpcResponse(["success" => true]); 
            } catch (Exception $e) {
                $mysqli->rollback();
                sendTrpcResponse(["error" => "Erro no processamento"], 500);
            }
        } else {
             sendTrpcResponse(["success" => false, "message" => "Saldo insuficiente"]);
        }
    } else {
        sendTrpcResponse(["error" => "Unauthorized"], 401);
    }
}
if ($path === '/api/frontend/trpc/agency.myAchievement') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $directList = [];
    $total = 0;
    if ($user) {
        $myInviteCode = $user['invite_code'];
        $cpaLvl1 = 0;
        $stmtConfig = $mysqli->prepare("SELECT cpaLvl1 FROM afiliados_config LIMIT 1");
        if ($stmtConfig) {
            $stmtConfig->execute();
            $resConfig = $stmtConfig->get_result();
            if ($rowConfig = $resConfig->fetch_assoc()) {
                $cpaLvl1 = floatval($rowConfig['cpaLvl1']);
            }
            $stmtConfig->close();
        }
        $stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM usuarios WHERE invitation_code = ?");
        $stmt->bind_param("s", $myInviteCode);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $total = intval($row['total']);
        }
        $stmt->close();
        $stmt = $mysqli->prepare("SELECT id, mobile, data_registro, invite_code FROM usuarios WHERE invitation_code = ? ORDER BY id DESC LIMIT 50");
        $stmt->bind_param("s", $myInviteCode);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $subId = $row['id'];
            $subInviteCode = $row['invite_code'];
            $recharge = 0;
            $totalFlow = 0;
            $subAccount = 0;
            $achievement = 0;
            $qry = "SELECT SUM(valor) as total FROM transacoes WHERE usuario = $subId AND tipo='deposito' AND status='pago'";
            $resSub = $mysqli->query($qry);
            if ($resSub && $rowSub = $resSub->fetch_assoc()) {
                $recharge = floatval($rowSub['total'] ?? 0) * 100; 
            }
            $qryPlay = "SELECT SUM(bet_money) as total_bet FROM historico_play WHERE id_user = $subId AND status_play = 1";
            $resPlay = $mysqli->query($qryPlay);
            if ($resPlay && $rowPlay = $resPlay->fetch_assoc()) {
                $totalFlow = floatval($rowPlay['total_bet'] ?? 0) * 100; 
            }
            if (!empty($subInviteCode)) {
                $qrySubCount = "SELECT COUNT(*) as count FROM usuarios WHERE invitation_code = '$subInviteCode'";
                $resSubCount = $mysqli->query($qrySubCount);
                if ($resSubCount && $rowSubCount = $resSubCount->fetch_assoc()) {
                    $subAccount = intval($rowSubCount['count']);
                }
            }
            $achievement = $recharge * ($cpaLvl1 / 100);
            $directList[] = [
                "userId" => $row['id'],
                "username" => substr($row['mobile'], 0, 3) . '****' . substr($row['mobile'], -2),
                "registerDate" => $row['data_registro'],
                "subAccount" => $subAccount, 
                "totalFlow" => $totalFlow,   
                "achievement" => $achievement, 
                "recharge" => $recharge      
            ];
        }
        $stmt->close();
    }
    $info = [
        "directList" => $directList,
        "total" => $total
    ];
    sendTrpcResponse(["info" => $info]);
}
if ($path === '/api/frontend/trpc/agency.achievementDetail') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $targetUserId = $dataInput['userId'] ?? 0;
    $stmt = $mysqli->prepare("SELECT id, invite_code FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $targetUserId);
    $stmt->execute();
    $res = $stmt->get_result();
    $targetUser = $res->fetch_assoc();
    $stmt->close();
    if ($targetUser) {
        $userId = $targetUser['id'];
        $inviteCode = $targetUser['invite_code'];
        $startOfDay = date('Y-m-d 00:00:00');
        $cpaLvl1 = 0; $cpaLvl2 = 0; $cpaLvl3 = 0;
        $stmtConfig = $mysqli->prepare("SELECT cpaLvl1, cpaLvl2, cpaLvl3 FROM afiliados_config LIMIT 1");
        if ($stmtConfig) {
            $stmtConfig->execute();
            $resConfig = $stmtConfig->get_result();
            if ($rowConfig = $resConfig->fetch_assoc()) {
                $cpaLvl1 = floatval($rowConfig['cpaLvl1']);
                $cpaLvl2 = floatval($rowConfig['cpaLvl2']);
                $cpaLvl3 = floatval($rowConfig['cpaLvl3']);
            }
            $stmtConfig->close();
        }
        $directCount = 0;
        $dayDirectAdd = 0;
        $dayDirectAgentAdd = 0;
        $level1Ids = [];
        $level1InviteCodes = [];
        $stmt = $mysqli->prepare("SELECT id, invite_code, data_registro FROM usuarios WHERE invitation_code = ?");
        $stmt->bind_param("s", $inviteCode);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $level1Ids[] = $row['id'];
            if(!empty($row['invite_code'])) $level1InviteCodes[] = $row['invite_code'];
            $directCount++;
            if ($row['data_registro'] >= $startOfDay) {
                $dayDirectAdd++;
                if(!empty($row['invite_code'])) $dayDirectAgentAdd++;
            }
        }
        $stmt->close();
        $level2Ids = [];
        $level2InviteCodes = [];
        $dayTeamAddL2 = 0;
        if (!empty($level1InviteCodes)) {
            $codesStr = "'" . implode("','", $level1InviteCodes) . "'";
            $qry = "SELECT id, invite_code, data_registro FROM usuarios WHERE invitation_code IN ($codesStr)";
            $res = $mysqli->query($qry);
            while($row = $res->fetch_assoc()) {
                $level2Ids[] = $row['id'];
                if(!empty($row['invite_code'])) $level2InviteCodes[] = $row['invite_code'];
                if ($row['data_registro'] >= $startOfDay) $dayTeamAddL2++;
            }
        }
        $level3Ids = [];
        $dayTeamAddL3 = 0;
        if (!empty($level2InviteCodes)) {
            $codesStr = "'" . implode("','", $level2InviteCodes) . "'";
            $qry = "SELECT id, data_registro FROM usuarios WHERE invitation_code IN ($codesStr)";
            $res = $mysqli->query($qry);
            while($row = $res->fetch_assoc()) {
                $level3Ids[] = $row['id'];
                if ($row['data_registro'] >= $startOfDay) $dayTeamAddL3++;
            }
        }
        $teamCount = $directCount + count($level2Ids) + count($level3Ids);
        $dayTeamAdd = $dayDirectAdd + $dayTeamAddL2 + $dayTeamAddL3;
        $dayDirectCommission = 0;
        $dayDirectValidBetting = 0;
        if (!empty($level1Ids)) {
            $idsStr1 = implode(',', $level1Ids);
            $qry1 = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr1) AND tipo='deposito' AND status='pago' AND data_registro >= '$startOfDay'";
            $res1 = $mysqli->query($qry1);
            if ($res1 && $row1 = $res1->fetch_assoc()) {
                $depLvl1 = floatval($row1['total'] ?? 0) * 100;
                $dayDirectCommission = $depLvl1 * ($cpaLvl1 / 100);
            }
            $qryPlay = "SELECT SUM(bet_money) as total_bet FROM historico_play WHERE id_user IN ($idsStr1) AND created_at >= '$startOfDay' AND status_play = 1";
            $resPlay = $mysqli->query($qryPlay);
             if ($resPlay && $rowPlay = $resPlay->fetch_assoc()) {
                $dayDirectValidBetting = floatval($rowPlay['total_bet'] ?? 0) * 100;
            }
        }
        $commissionLvl2 = 0;
        if (!empty($level2Ids)) {
            $idsStr2 = implode(',', $level2Ids);
            $qry2 = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr2) AND tipo='deposito' AND status='pago' AND data_registro >= '$startOfDay'";
            $res2 = $mysqli->query($qry2);
            if ($res2 && $row2 = $res2->fetch_assoc()) {
                $depLvl2 = floatval($row2['total'] ?? 0) * 100;
                $commissionLvl2 = $depLvl2 * ($cpaLvl2 / 100);
            }
        }
        $commissionLvl3 = 0;
        if (!empty($level3Ids)) {
            $idsStr3 = implode(',', $level3Ids);
            $qry3 = "SELECT SUM(valor) as total FROM transacoes WHERE usuario IN ($idsStr3) AND tipo='deposito' AND status='pago' AND data_registro >= '$startOfDay'";
            $res3 = $mysqli->query($qry3);
            if ($res3 && $row3 = $res3->fetch_assoc()) {
                $depLvl3 = floatval($row3['total'] ?? 0) * 100;
                $commissionLvl3 = $depLvl3 * ($cpaLvl3 / 100);
            }
        }
        $dayTeamCommission = $commissionLvl2 + $commissionLvl3;
        $achievement = $dayDirectCommission + $dayTeamCommission;
        sendTrpcResponse([
            "info" => [
                "directCount" => $directCount,
                "teamCount" => $teamCount,
                "achievement" => $achievement,
                "totalFlow" => $dayDirectValidBetting,
                "directAdd" => $dayDirectAdd,
                "teamAdd" => $dayTeamAdd,
                "directAgentAdd" => $dayDirectAgentAdd
            ]
        ]);
    } else {
        sendApiError(404, "User not found");
    }
}
if ($path === '/api/frontend/trpc/agency.myCommission') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendApiError(401, "Unauthorized");
    }
    $input = getTrpcInput();
    $json = $input['json'] ?? $input;
    $page = isset($json['page']) ? intval($json['page']) : 1;
    $pageSize = isset($json['pageSize']) ? intval($json['pageSize']) : 10;
    $timeType = $json['timeType'] ?? 'today';
    $offset = ($page - 1) * $pageSize;
    $startDate = date('Y-m-d 00:00:00');
    $endDate = date('Y-m-d 23:59:59');
    if ($timeType === 'yesterday') {
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
    } elseif ($timeType === 'week') {
        $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
    } elseif ($timeType === 'lastWeek') {
        $startDate = date('Y-m-d 00:00:00', strtotime('monday last week'));
        $endDate = date('Y-m-d 23:59:59', strtotime('sunday last week'));
    } elseif ($timeType === 'month') {
        $startDate = date('Y-m-d 00:00:00', strtotime('first day of this month'));
        $endDate = date('Y-m-d 23:59:59', strtotime('last day of this month'));
    } elseif ($timeType === 'lastMonth') {
        $startDate = date('Y-m-d 00:00:00', strtotime('first day of last month'));
        $endDate = date('Y-m-d 23:59:59', strtotime('last day of last month'));
    }
    $total = 0;
    $stmtCount = $mysqli->prepare("SELECT COUNT(*) as total FROM usuarios WHERE invitation_code = ?");
    $stmtCount->bind_param("s", $user['invite_code']);
    $stmtCount->execute();
    $resCount = $stmtCount->get_result();
    if ($rowCount = $resCount->fetch_assoc()) {
        $total = $rowCount['total'];
    }
    $stmtCount->close();
    $stmtList = $mysqli->prepare("SELECT id, invite_code FROM usuarios WHERE invitation_code = ? ORDER BY data_registro DESC LIMIT ? OFFSET ?");
    $stmtList->bind_param("sii", $user['invite_code'], $pageSize, $offset);
    $stmtList->execute();
    $resList = $stmtList->get_result();
    $list = [];
    while ($row = $resList->fetch_assoc()) {
        $affId = $row['id'];
        $affCode = $row['invite_code'];
        $subAccount = 0;
        $l1Ids = [];
        $resL1 = $mysqli->query("SELECT id, invite_code FROM usuarios WHERE invitation_code = '$affCode'");
        $subAccount += $resL1->num_rows;
        while ($r = $resL1->fetch_assoc()) $l1Ids[] = $r['invite_code'];
        if (!empty($l1Ids)) {
             $l1Ids = array_map(function($v) use ($mysqli) { return $mysqli->real_escape_string($v); }, $l1Ids);
             $l1Codes = "'" . implode("','", $l1Ids) . "'";
             $resL2 = $mysqli->query("SELECT id, invite_code FROM usuarios WHERE invitation_code IN ($l1Codes)");
             $subAccount += $resL2->num_rows;
             $l2Ids = [];
             while ($r = $resL2->fetch_assoc()) $l2Ids[] = $r['invite_code'];
             if (!empty($l2Ids)) {
                 $l2Ids = array_map(function($v) use ($mysqli) { return $mysqli->real_escape_string($v); }, $l2Ids);
                 $l2Codes = "'" . implode("','", $l2Ids) . "'";
                 $resL3 = $mysqli->query("SELECT COUNT(*) as c FROM usuarios WHERE invitation_code IN ($l2Codes)");
                 if ($r3 = $resL3->fetch_assoc()) $subAccount += $r3['c'];
             }
        }
        $totalFlow = 0;
        $stmtFlow = $mysqli->prepare("SELECT COALESCE(SUM(bet_money),0) as total FROM historico_play WHERE id_user = ? AND created_at BETWEEN ? AND ?");
        $stmtFlow->bind_param("iss", $affId, $startDate, $endDate);
        $stmtFlow->execute();
        if ($rFlow = $stmtFlow->get_result()->fetch_assoc()) {
            $totalFlow = floatval($rFlow['total']) * 100;
        }
        $stmtFlow->close();
        $recharge = 0;
        $stmtRecharge = $mysqli->prepare("SELECT COALESCE(SUM(valor),0) as total FROM transacoes WHERE usuario = ? AND tipo='deposito' AND status='pago' AND data_registro BETWEEN ? AND ?");
        $stmtRecharge->bind_param("iss", $affId, $startDate, $endDate);
        $stmtRecharge->execute();
        if ($rRecharge = $stmtRecharge->get_result()->fetch_assoc()) {
            $recharge = floatval($rRecharge['total']) * 100;
        }
        $stmtRecharge->close();
        $achievement = 0;
        $stmtAch = $mysqli->prepare("SELECT COALESCE(SUM(valor),0) as total FROM adicao_saldo WHERE id_user = ? AND tipo LIKE 'comissao%' AND data_registro BETWEEN ? AND ?");
        $stmtAch->bind_param("iss", $affId, $startDate, $endDate);
        $stmtAch->execute();
        if ($rAch = $stmtAch->get_result()->fetch_assoc()) {
            $achievement = floatval($rAch['total']);
        }
        $stmtAch->close();
        $list[] = [
            "userId" => $affId,
            "subAccount" => $subAccount,
            "totalFlow" => $totalFlow,
            "achievement" => $achievement,
            "recharge" => $recharge
        ];
    }
    $stmtList->close();
    sendTrpcResponse([
        "list" => $list,
        "total" => $total
    ]);
}
if ($path === '/api/frontend/trpc/agency.myCommissionDetail') {
    $rotaEncontrada = true;
    sendTrpcResponse(["detail" => []]);
}
if ($path === '/api/frontend/trpc/agency.ratList') {
    $rotaEncontrada = true;
    $info = "[{\"gameType\":\"ELECTRONIC\",\"level\":1,\"needFlow\":1000000,\"rat\":2},{\"gameType\":\"CHESS\",\"level\":1,\"needFlow\":1000000,\"rat\":2},{\"gameType\":\"FISHING\",\"level\":1,\"needFlow\":1000000,\"rat\":2},{\"gameType\":\"VIDEO\",\"level\":1,\"needFlow\":1000000,\"rat\":2},{\"gameType\":\"SPORTS\",\"level\":1,\"needFlow\":1000000,\"rat\":2},{\"gameType\":\"LOTTERY\",\"level\":1,\"needFlow\":1000000,\"rat\":2}]";
    sendTrpcResponse(["info" => $info]);
}
if ($path === '/api/frontend/trpc/deposit.getPaymentMethods' || $path === '/api/frontend/trpc/recharge.getPaymentMethods') {
    $rotaEncontrada = true;
    $minDep = 10;
    $stmt = $mysqli->prepare("SELECT mindep FROM config WHERE id = 1");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $minDep = floatval($row['mindep']);
        }
    }
    sendTrpcResponse([
        "methods" => [
            [
                "id" => 1,
                "name" => "PIX",
                "icon" => "/assets/pix.png", 
                "minAmount" => $minDep * 100, 
                "maxAmount" => 100000 * 100,
                "extra" => "Instantâneo"
            ]
        ]
    ]);
}
if ($path === '/api/frontend/trpc/pay.orderStatus') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $orderNo = $dataInput['orderNo'] ?? '';
    if (empty($orderNo)) {
        sendApiError(400, "OrderNo required");
    }
    $stmt = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id = ?");
    $stmt->bind_param("s", $orderNo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
         $rawStatus = $row['status'];
         $status = "BE_PAID";
         if ($rawStatus == 'pago' || $rawStatus == 'PAID' || $rawStatus == 'paid' || $rawStatus == 1 || $rawStatus == '1') {
             $status = "PAID";
         } else if ($rawStatus == 'cancelado' || $rawStatus == 'CANCEL' || $rawStatus == 'cancel' || $rawStatus == 2 || $rawStatus == '2') {
             $status = "CANCEL";
         }
         sendTrpcResponse([
             "status" => $status
         ]);
    } else {
        sendApiError(404, "Order not found");
    }
}
if ($path === '/api/frontend/trpc/pay.recordList') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse(["recordList" => [], "total" => 0, "totalAmount" => 0]);
        exit;
    }
    $input = getTrpcInput();
    $dataInput = $input['json'] ?? $input;
    $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
    $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
    $offset = ($page - 1) * $pageSize;
    $startTime = $dataInput['startTime'] ?? null;
    $endTime = $dataInput['endTime'] ?? null;
    $where = "usuario = ? AND tipo = 'deposito'";
    $types = "i";
    $params = [$user['id']];
    if ($startTime) {
        $startDt = date('Y-m-d H:i:s', strtotime($startTime));
        $where .= " AND data_registro >= ?";
        $types .= "s";
        $params[] = $startDt;
    }
    if ($endTime) {
        $endDt = date('Y-m-d H:i:s', strtotime($endTime));
        $where .= " AND data_registro <= ?";
        $types .= "s";
        $params[] = $endDt;
    }
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count, SUM(valor) as total_amount FROM transacoes WHERE $where");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $total = $stats['count'] ?? 0;
    $totalAmount = floatval($stats['total_amount'] ?? 0) * 100; 
    $sql = "SELECT * FROM transacoes WHERE $where ORDER BY data_registro DESC LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $typesLimit = $types . "ii";
    $paramsLimit = array_merge($params, [$pageSize, $offset]);
    $stmt->bind_param($typesLimit, ...$paramsLimit);
    $stmt->execute();
    $res = $stmt->get_result();
    $recordList = [];
    while ($row = $res->fetch_assoc()) {
        $rawStatus = $row['status'];
        $statusStr = "BE_PAID";
        if ($rawStatus == 'pago' || $rawStatus == 'PAID' || $rawStatus == 'paid' || $rawStatus == 1 || $rawStatus == '1') $statusStr = "PAID";
        elseif ($rawStatus == 'cancelado' || $rawStatus == 'CANCEL' || $rawStatus == 'cancel' || $rawStatus == 2 || $rawStatus == '2') $statusStr = "CANCEL";
        $recordList[] = [
            "orderNo" => $row['transacao_id'],
            "amount" => floatval($row['valor']) * 100, 
            "payAmount" => floatval($row['valor']) * 100, 
            "payTypeName" => "PIX",
            "status" => $statusStr,
            "createTime" => strtotime($row['data_registro']) * 1000
        ];
    }
    sendTrpcResponse([
        "recordList" => $recordList,
        "total" => $total,
        "totalAmount" => $totalAmount
    ]);
}
if ($path === '/api/frontend/trpc/pay.list') {
    $rotaEncontrada = true;
    $minDep = 10;
    $resConfig = $mysqli->query("SELECT mindep FROM config WHERE id = 1");
    if ($resConfig && $rowConfig = $resConfig->fetch_assoc()) {
        $minDep = floatval($rowConfig['mindep']);
    }
    $user = getCurrentUser($mysqli);
    $hasDeposits = false;
    if ($user) {
        $stmtDep = $mysqli->prepare("SELECT id FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago' LIMIT 1");
        $stmtDep->bind_param("i", $user['id']);
        $stmtDep->execute();
        if ($stmtDep->get_result()->num_rows > 0) {
            $hasDeposits = true;
        }
        $stmtDep->close();
    }
    $payTypeSubList = [];
    $qry = $mysqli->query("SELECT * FROM pay_type_sub_list WHERE status = 1 ORDER BY sort_order ASC");
    if ($qry) {
        while ($row = $qry->fetch_assoc()) {
            $tagVal = $row['tag_value'];
            $tags = $row['tags'];
            if ($row['bonus_active'] == 0 || $hasDeposits) {
                $tagVal = "";
                if ($tags === 'GIVE_AWAY') {
                    $tags = 'RECOMMEND';
                }
            }
            $payTypeSubList[] = [
                "id" => intval($row['id']),
                "code" => "",
                "icon" => "",
                "name" => $row['name'],
                "sort" => intval($row['sort_order']),
                "tags" => $tags,
                "isHot" => 0,
                "remark" => null,
                "status" => "ON",
                "isNative" => 0,
                "showName" => strtolower(str_replace(['(', ')', ' '], '', $row['name'])),
                "tagValue" => $tagVal,
                "maxAmount" => floatval($row['max_amount']) * 100,
                "minAmount" => floatval($row['min_amount']) * 100,
                "description" => $row['description'],
                "fixedAmount" => $row['fixed_amount'],
                "processMode" => "THREE_PARTY_PAYMENT",
                "discountRatio" => 3000,
                "isInputAmount" => 1,
                "userLevelLimit" => "387,2943,2859,2812,2810,2808,2131,2902,2926,2809,1995"
            ];
        }
    }
    if (empty($payTypeSubList)) {
        $payTypeSubList = [
            [
                "id" => 3536,
                "code" => "",
                "icon" => "",
                "name" => "PIX(1)",
                "sort" => 555,
                "tags" => "RECOMMEND",
                "isHot" => 0,
                "remark" => null,
                "status" => "ON",
                "isNative" => 0,
                "showName" => "pix1",
                "tagValue" => "",
                "maxAmount" => 100000000,
                "minAmount" => 1000,
                "description" => "1243565555555",
                "fixedAmount" => "10,20,30,50,100,500,1000,2000,10000",
                "processMode" => "THREE_PARTY_PAYMENT",
                "discountRatio" => 3000,
                "isInputAmount" => 1,
                "userLevelLimit" => "387,2943,2859,2812,2810,2808,2131,2902,2926,2809,1995"
            ]
        ];
    }
    $globalBonus = "";
    if (!empty($payTypeSubList)) {
        foreach ($payTypeSubList as $pt) {
            if (!empty($pt['tagValue'])) {
                $globalBonus = $pt['tagValue'];
                break;
            }
        }
    }
    $tagsGlobal = "GIVE_AWAY";
    if ($hasDeposits) {
        $tagsGlobal = "RECOMMEND";
        $globalBonus = "";
    }
    $tenantPayTypeList = [
        [
            "id" => 1711,
            "status" => "ON",
            "sort" => 0,
            "tags" => $tagsGlobal,
            "tagValue" => $globalBonus,
            "name" => "Depósito on-line",
            "code" => "",
            "logo" => "",
            "type" => "PAY",
            "remark" => null,
            "payWithdrawTypeId" => 36,
            "maxAmount" => 0,
            "minAmount" => 0,
            "fixedAmount" => "",
            "isInputAmount" => false,
            "payTypeSubList" => $payTypeSubList
        ]
    ];
    $rechargeRewardInfo = [
        "id" => 497,
        "name" => "充值赠送(本金+赠送)",
        "type" => "RechargeReward",
        "banner" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "bannerUrl" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "img" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "image" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "bg" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "backgroundImage" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "icon" => "/uploads/rechargeReward_Platform_Layout1.jpg",
        "rewardRate" => array_map(function($amt) use ($payTypeSubList) {
            $rate = 0;
            foreach ($payTypeSubList as $pt) {
                if (!empty($pt['tagValue']) && $amt >= $pt['minAmount'] && $amt <= $pt['maxAmount']) {
                    $rate = floatval($pt['tagValue']) * 100;
                    break;
                }
            }
            return ["amount" => $amt, "rate" => $rate, "betMultiple" => 1];
        }, [2000, 4900, 5000, 9900, 10000, 39900, 40000, 100000000])
    ];
    if ($hasDeposits) {
        $rechargeRewardInfo = null;
    }
    sendTrpcResponse([
        "tenantPayTypeList" => $tenantPayTypeList,
        "rechargeMultiple" => 2,
        "rewardMultiple" => 3,
        "rechargeRewardInfo" => $rechargeRewardInfo
    ]);
}
if ($path === '/api/frontend/trpc/recharge.getPaymentType') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $hasDeposits = false;
    if ($user) {
        $stmtDep = $mysqli->prepare("SELECT id FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago' LIMIT 1");
        $stmtDep->bind_param("i", $user['id']);
        $stmtDep->execute();
        if ($stmtDep->get_result()->num_rows > 0) {
            $hasDeposits = true;
        }
        $stmtDep->close();
    }
    $cuponsAtivos = [];
    try {
        $sql = "SELECT valor, qtd_insert FROM cupom WHERE status = 1 ORDER BY valor ASC";
        $result = $mysqli->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cuponsAtivos[] = $row;
            }
        }
    } catch (Exception $e) {
        $cuponsAtivos = [];
    }
    $minDep = 10;
    $resConfig = $mysqli->query("SELECT mindep FROM config WHERE id = 1");
    if ($resConfig && $rowConfig = $resConfig->fetch_assoc()) {
        $minDep = floatval($rowConfig['mindep']);
    }
    $valoresSugeridos = [];
    if (count($cuponsAtivos) > 0) {
        $valoresSugeridos = [$minDep];
        $valoresSemBonus = [100, 1000, 5000];
        $indexSemBonus = 0;
        foreach ($cuponsAtivos as $cupom) {
            if (!in_array(floatval($cupom['valor']), $valoresSugeridos)) {
                $valoresSugeridos[] = floatval($cupom['valor']);
            }
            if (count($valoresSugeridos) < 8 && $indexSemBonus < count($valoresSemBonus)) {
                if (!in_array($valoresSemBonus[$indexSemBonus], $valoresSugeridos)) {
                    $valoresSugeridos[] = $valoresSemBonus[$indexSemBonus];
                }
                $indexSemBonus++;
            }
            if (count($valoresSugeridos) >= 8) break;
        }
        $valoresExtras = [500, 3000, 10000, 50000];
        foreach ($valoresExtras as $extra) {
            if (count($valoresSugeridos) >= 8) break;
            if (!in_array($extra, $valoresSugeridos)) {
                $valoresSugeridos[] = $extra;
            }
        }
    } else {
        $valoresSugeridos = [$minDep, 50, 100, 500, 1000, 3000, 5000, 10000];
        $valoresSugeridos = array_unique($valoresSugeridos);
        if (count($valoresSugeridos) < 8) {
            $extras = [50000, 20000, 15000];
            foreach ($extras as $extra) {
                if (count($valoresSugeridos) >= 8) break;
                if (!in_array($extra, $valoresSugeridos)) {
                    $valoresSugeridos[] = $extra;
                }
            }
        }
    }
    sort($valoresSugeridos);
    $valoresSugeridos = array_slice($valoresSugeridos, 0, 8);
    $recommendList = [];
    $cuponsMap = [];
    foreach ($cuponsAtivos as $cupom) {
        $cuponsMap[strval(floatval($cupom['valor']))] = $cupom['qtd_insert'];
    }
    foreach ($valoresSugeridos as $valor) {
        $valStr = strval(floatval($valor));
        $item = [
            "amount" => (string)$valor,
            "bonus" => "",
        ];
        if (isset($cuponsMap[$valStr]) && !$hasDeposits) {
             $item["bonus"] = (string)$cuponsMap[$valStr];
        }
        $recommendList[] = $item;
    }
    $remarkHtml = '';
    sendTrpcResponse([
        "typeList" => [
            [
                "id" => 1,
                "name" => "PIX",
                "icon" => "/assets/pix.png",
                "sort" => 1,
                "paymentMethodList" => [
                    [
                        "id" => 1,
                        "name" => "PIX",
                        "icon" => "/assets/pix.png",
                        "minAmount" => $minDep,
                        "maxAmount" => 100000,
                        "extra" => "Instantâneo",
                        "recommendList" => $recommendList,
                        "remark" => $remarkHtml
                    ]
                ]
            ]
        ]
    ]);
}
if ($path === '/api/frontend/trpc/RechargePromotion') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/RechargeBonus') {
    $rotaEncontrada = true;
    sendTrpcResponse([]);
}
if ($path === '/api/frontend/trpc/invite.promoteDetails') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
        sendTrpcResponse([
            "details" => [],
            "total" => 0
        ]);
        exit;
    }
    $invite_code = $user['invite_code'];
    $parentName = $user['mobile'];
    $stmt = $mysqli->prepare("SELECT id, mobile, data_registro FROM usuarios WHERE invitation_code = ?");
    $stmt->bind_param("s", $invite_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = [];
    while ($indicado = $result->fetch_assoc()) {
        $details[] = [
            'id' => $indicado['id'],
            'activeId' => 271, 
            'parentId' => $user['id'],
            'parentName' => $parentName,
            'parentCurrency' => 'BRL',
            'useridx' => $indicado['id'],
            'userName' => $indicado['mobile'],
            'userCurrency' => 'BRL',
            'isPass' => 1,
            'registerTime' => strtotime($indicado['data_registro']) * 1000,
            'remark' => '',
            'updateTime' => strtotime($indicado['data_registro']) * 1000,
            'siteCode' => '',
            'siteName' => ''
        ];
    }
    sendTrpcResponse([
        "details" => $details,
        "total" => count($details)
    ]);
}
if ($path === '/api/frontend/trpc/record.assetsChange') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if (!$user) {
         sendTrpcResponse(["list" => [], "total" => 0, "totalPage" => 0]);
         exit;
    }
    $trpcInput = getTrpcInput();
    $dataInput = $trpcInput['json'] ?? [];
    $page = isset($dataInput['page']) ? intval($dataInput['page']) : 1;
    $pageSize = isset($dataInput['pageSize']) ? intval($dataInput['pageSize']) : 20;
    $offset = ($page - 1) * $pageSize;
    $startTime = $dataInput['startTime'] ?? null;
    $endTime = $dataInput['endTime'] ?? null;
    $changeType = $dataInput['changeType'] ?? null;
    $whereTransacoes = "usuario = " . $user['id'];
    $whereAdicao = "id_user = " . $user['id'];
    $whereSaques = "id_user = " . $user['id'];
    if ($startTime) {
        $startTimeStr = date('Y-m-d H:i:s', strtotime($startTime));
        $whereTransacoes .= " AND data_registro >= '$startTimeStr'";
        $whereAdicao .= " AND data_registro >= '$startTimeStr'";
        $whereSaques .= " AND data_registro >= '$startTimeStr'";
    }
    if ($endTime) {
        $endTimeStr = date('Y-m-d H:i:s', strtotime($endTime));
        $whereTransacoes .= " AND data_registro <= '$endTimeStr'";
        $whereAdicao .= " AND data_registro <= '$endTimeStr'";
        $whereSaques .= " AND data_registro <= '$endTimeStr'";
    }
    $unionParts = [];
    $countUnionParts = [];
    if (empty($changeType) || $changeType == 1) {
        $whereT = $whereTransacoes . " AND tipo = 'deposito'";
        $unionParts[] = "SELECT id, transacao_id, valor, tipo, data_registro, 'transacao' as source FROM transacoes WHERE $whereT";
        $countUnionParts[] = "SELECT COUNT(*) as cnt FROM transacoes WHERE $whereT";
    }
    if (empty($changeType) || $changeType == 2) {
        $unionParts[] = "SELECT id, transacao_id, valor, 'saque' as tipo, data_registro, 'saque_table' as source FROM solicitacao_saques WHERE $whereSaques";
        $countUnionParts[] = "SELECT COUNT(*) as cnt FROM solicitacao_saques WHERE $whereSaques";
        $whereLegacySaque = $whereTransacoes . " AND tipo = 'saque'";
        $unionParts[] = "SELECT id, transacao_id, valor, tipo, data_registro, 'transacao' as source FROM transacoes WHERE $whereLegacySaque";
        $countUnionParts[] = "SELECT COUNT(*) as cnt FROM transacoes WHERE $whereLegacySaque";
    }
    if ($changeType == 3) {
        $unionParts[] = "SELECT id, NULL as transacao_id, valor, tipo, data_registro, 'bonus' as source FROM adicao_saldo WHERE $whereAdicao";
        $countUnionParts[] = "SELECT COUNT(*) as cnt FROM adicao_saldo WHERE $whereAdicao";
    }
    if (empty($unionParts)) {
        sendTrpcResponse(["list" => [], "total" => 0, "totalPage" => 0]);
        exit;
    }
    $countSqlInner = implode(" UNION ALL ", $countUnionParts);
    $countSql = "
        SELECT SUM(cnt) as total FROM (
            $countSqlInner
        ) as t
    ";
    $total = 0;
    $resCount = $mysqli->query($countSql);
    if ($resCount) {
        $total = intval($resCount->fetch_assoc()['total']);
    }
    $sumRecharge = 0;
    $sumWithdraw = 0;
    $sumReward = 0;
    $stmtRecharge = $mysqli->prepare("SELECT SUM(valor) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito'");
    $stmtRecharge->bind_param("i", $user['id']);
    $stmtRecharge->execute();
    $resRecharge = $stmtRecharge->get_result();
    if ($rowRecharge = $resRecharge->fetch_assoc()) {
        $sumRecharge = (float)($rowRecharge['total'] ?? 0) * 100;
    }
    $stmtRecharge->close();
    $stmtWithdraw = $mysqli->prepare("SELECT SUM(valor) as total FROM transacoes WHERE usuario = ? AND tipo = 'saque'");
    $stmtWithdraw->bind_param("i", $user['id']);
    $stmtWithdraw->execute();
    $resWithdraw = $stmtWithdraw->get_result();
    if ($rowWithdraw = $resWithdraw->fetch_assoc()) {
        $sumWithdraw = (float)($rowWithdraw['total'] ?? 0) * 100;
    }
    $stmtWithdraw->close();
    $stmtWithdraw2 = $mysqli->prepare("SELECT SUM(valor) as total FROM solicitacao_saques WHERE id_user = ?");
    $stmtWithdraw2->bind_param("i", $user['id']);
    $stmtWithdraw2->execute();
    $resWithdraw2 = $stmtWithdraw2->get_result();
    if ($rowWithdraw2 = $resWithdraw2->fetch_assoc()) {
        $sumWithdraw += (float)($rowWithdraw2['total'] ?? 0) * 100;
    }
    $stmtWithdraw2->close();
    $sumReward = 0; 
    $sqlInner = implode(" UNION ALL ", $unionParts);
    $sql = "
        $sqlInner
        ORDER BY data_registro DESC
        LIMIT $pageSize OFFSET $offset
    ";
    $res = $mysqli->query($sql);
    $list = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $amount = floatval($row['valor']);
            $typeStr = $row['tipo'];
            $source = $row['source'];
            $changeType = 0;
            $changeName = ucfirst($typeStr);
            if ($source == 'transacao' || $source == 'saque_table') {
                if ($typeStr == 'deposito') {
                    $changeType = 1;
                    $changeName = "Depósito";
                } elseif ($typeStr == 'saque') {
                    $changeType = 2;
                    $changeName = "Saque";
                    if ($amount > 0) {
                        $amount = -$amount;
                    }
                }
            } else {
                $changeType = 3; 
                if (strpos($typeStr, 'comissao') !== false) {
                    $changeName = "Comissão";
                } elseif (strpos($typeStr, 'bonus') !== false) {
                    $changeName = "Bônus";
                }
            }
            $amountChange = $amount * 100;
            $externalId = $row['transacao_id'] ?: $row['id'];
            $list[] = [
                "id" => (string)$externalId,
                "externalRelated" => (string)$externalId,
                "amountChange" => $amountChange,
                "beforeAmount" => 0,
                "afterAmount" => 0,
                "changeType" => $changeType,
                "changeTwoType" => $typeStr,
                "changeName" => $changeName,
                "createTime" => strtotime($row['data_registro']) * 1000,
                "remark" => $typeStr
            ];
        }
    }
    sendTrpcResponse([
        "assetsChangeList" => $list,
        "total" => $total,
        "totalPage" => ceil($total / $pageSize),
        "totalRechargeAmountChange" => $sumRecharge,
        "totalWithdrawAmountChange" => $sumWithdraw,
        "totalRewardAmountChange" => $sumReward
    ]);
}
if ($path === '/api/frontend/trpc/game.login') {
    $rotaEncontrada = true;
    $input = getTrpcInput();
    $gameId = $input['json']['gameId'] ?? null;
    if (!$gameId) {
         $gameId = $data['json']['gameId'] ?? null;
    }
    $user = getCurrentUser($mysqli);
    if ($user) {
        if ($gameId) {
            $stmtGame = $mysqli->prepare("SELECT game_code, provider, api FROM games WHERE id = ?");
            $stmtGame->bind_param("s", $gameId);
            $stmtGame->execute();
            $resGame = $stmtGame->get_result();
            if ($resGame->num_rows > 0) {
                $gameData = $resGame->fetch_assoc();
                $provider = $gameData['provider'];
                $gameCode = $gameData['game_code'];
                $api = $gameData['api'] ?? '';
                $apiNormalized = strtolower(trim($api));
                $providerKey = normalizeProviderKey($provider);
                $saldo = $user['saldo'];
                $email = $user['mobile']; 
                $gameLaunch = [];
                if ($apiNormalized === 'playfiver') {
                     $gameLaunch = pegarLinkJogoApiPlayFiver($provider, $gameCode, $email, $saldo);
                } elseif ($apiNormalized === 'pgclone') {
                     $gameLaunch = pegarLinkJogoPGClone($provider, $gameCode, $email, $saldo);
                } elseif ($apiNormalized === 'ppclone') {
                     $gameLaunch = pegarLinkJogoPPClone($provider, $gameCode, $email, $saldo);
                } else {
                     $gameLaunch = pegarLinkJogoigamewin($provider, $gameCode, $email);
                }
                if (isset($gameLaunch['gameURL']) && !empty($gameLaunch['gameURL'])) {
                    $loginUrl = $gameLaunch['gameURL'];
                    sendTrpcResponse([
                         "loginUrl" => $loginUrl
                    ]);
                } else {
                    sendApiError(500, "Erro ao obter URL do jogo: " . ($gameLaunch['error'] ?? 'Erro desconhecido'));
                }
            } else {
                 sendApiError(404, "Jogo não encontrado");
            }
            $stmtGame->close();
        } else {
            sendApiError(400, "gameId não fornecido");
        }
    } else {
         sendApiError(401, "Sessão inválida");
    }
}
if ($path === '/api/frontend/trpc/user.getHasFirstRechargeAd') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    $response = [
        "hasSentFirstRechargeAd" => null
    ];
    if ($user) {
        $userId = $user['id'];
        $stmt = $mysqli->prepare("SELECT * FROM transacoes WHERE usuario = ? AND tipo = 'deposito' ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $row = $result->fetch_assoc()) {
                $amount = (float)$row['valor'] * 100;
                $time = gmdate("Y-m-d\TH:i:s.000\Z", strtotime($row['data_registro']));
                $response["hasSentFirstRechargeAd"] = [
                    "firstRechargeAmount" => (int)$amount,
                    "firstRechargeTime" => $time,
                    "hasSentFirstRechargeAd" => null,
                    "orderNo" => $row['transacao_id'],
                    "userId" => (int)$userId
                ];
            }
            $stmt->close();
        }
    }
    sendTrpcResponse($response);
}
if ($path === '/api/frontend/trpc/user.setHasFirstRechargeAd') {
    $rotaEncontrada = true;
    $user = getCurrentUser($mysqli);
    if ($user) {
        $input = getTrpcInput();
        $data = $input['json'] ?? [];
        $type = $data['hasSentAdType'] ?? null;
    }
    sendTrpcResponse(null, ["values" => ["undefined"]]);
}
if (!$rotaEncontrada) {
    http_response_code(404);
    echo json_encode(['error' => 'Route not found']);
}