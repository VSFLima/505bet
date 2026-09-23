<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
session_start();
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
require_once "config.php";
require_once DASH . "/services/database.php";
require_once DASH . "/services/funcao.php";
require_once DASH . "/services/crud.php";
require_once DASH . "/services/CSRF_Protect.php";
require_once DASH . "/services/pega-ip.php";
require_once DASH . "/services/ip-crawler.php";
$csrf = new CSRF_Protect();
$ads_tipo = !empty($_GET['utm_ads']) ? PHP_SEGURO($_GET['utm_ads']) : null;
$url_atual = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$referencia = $_SERVER['HTTP_REFERER'] ?? $url_atual;
$data_hoje = date("Y-m-d");
$hora_hoje = date("H:i:s");
// Resolver gargalo: só buscar geolocalização se for necessário inserir
$id_user = 1;
$stmt = $mysqli->prepare("SELECT 1 FROM visita_site WHERE data_cad = ? AND ip_visita = ?");
$stmt->bind_param("ss", $data_hoje, $ip);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $data_us = null;
    if (!empty($_SESSION['ip_geo']) &&
        isset($_SESSION['ip_geo']['ip'], $_SESSION['ip_geo']['data']) &&
        $_SESSION['ip_geo']['ip'] === $ip &&
        $_SESSION['ip_geo']['data'] === $data_hoje &&
        is_array($_SESSION['ip_geo']['info'])) {
        $data_us = $_SESSION['ip_geo']['info'];
    } else {
        $data_us = ip_F($ip);
        $_SESSION['ip_geo'] = [
            'ip' => $ip,
            'data' => $data_hoje,
            'info' => $data_us,
        ];
    }

    if (
        $browser !== "Unknown Browser" &&
        $os !== "Unknown OS Platform" &&
        isset($data_us['pais']) && $data_us['pais'] === "Brazil"
    ) {
        $stmt = $mysqli->prepare(
            "INSERT INTO visita_site (
                nav_os, mac_os, ip_visita, refer_visita, data_cad, hora_cad, id_user,
                pais, cidade, estado, ads_tipo
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssssssss",
            $browser, $os, $ip, $referencia, $data_hoje, $hora_hoje,
            $id_user, $data_us['pais'], $data_us['cidade'], $data_us['regiao'], $ads_tipo
        );
        $stmt->execute();
    }
}
$activeLayout = 'Layout2';
$activeTheme = 'ChalcedonyGreen';

$res = $mysqli->query("SELECT nome_cor, valor_cor FROM temas ORDER BY id DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    if (!empty($row['nome_cor'])) $activeLayout = $row['nome_cor'];
    if (!empty($row['valor_cor'])) $activeTheme = $row['valor_cor'];
}

// Override via GET parameters for theme preview
if (isset($_GET['layout']) && !empty($_GET['layout'])) {
    $activeLayout = $_GET['layout'];
}
if (isset($_GET['theme']) && !empty($_GET['theme'])) {
    $activeTheme = $_GET['theme'];
}

// ============================================
// BUSCAR CONFIGURAÇÕES GERAIS
// ============================================
$config = [
    'nome' => '',
    'descricao' => '',
    'logo' => '',
    'favicon' => '',
    'img_seo' => '',
];

try {
    $result_conf = $mysqli->query("SELECT * FROM config LIMIT 1");
    if ($result_conf && $row_conf = $result_conf->fetch_assoc()) {
        $config = array_merge($config, $row_conf);
    }

    $image_fields = ['logo', 'favicon', 'img_seo'];
    foreach ($image_fields as $field) {
        if (!empty($config[$field]) && strpos($config[$field], 'http') !== 0) {
            if (strpos($config[$field], '/') !== 0) {
                $config[$field] = '/uploads/' . $config[$field];
            }
            $config[$field] = $base_url . $config[$field];
        }
    }
} catch (Exception $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
}

$language = isset($config['language']) && $config['language'] !== '' ? $config['language'] : 'pt-BR';
$phoneCode = isset($config['phoneCode']) && $config['phoneCode'] !== '' ? $config['phoneCode'] : '+55';
$currency = isset($config['currency']) && $config['currency'] !== '' ? $config['currency'] : 'BRL';
$timezoneConfig = isset($config['timezone']) && $config['timezone'] !== '' ? $config['timezone'] : 'Etc/GMT+3';
$regionNameConfig = isset($config['regionName']) && $config['regionName'] !== '' ? $config['regionName'] : 'Brasil';
$regionIdConfig = isset($config['regionId']) && (int)$config['regionId'] > 0 ? (int)$config['regionId'] : 1;
$regionCode = 'BR';
if ($language === 'en-US') {
    $regionCode = 'US';
} elseif ($language === 'es-ES') {
    $regionCode = 'ES';
} elseif ($language === 'hi-IN') {
    $regionCode = 'IN';
} elseif ($language === 'id-ID') {
    $regionCode = 'ID';
} elseif ($language === 'vi-VN') {
    $regionCode = 'VN';
} elseif ($language === 'zh-CN') {
    $regionCode = 'CN';
}

$online_count = get_online_count();
$assetVersion = time();
$supportIconActive = isset($config['support_icon_active']) ? (int)$config['support_icon_active'] : 1;

// Ler minaActive do banco
$minaActive = 1;
$_minaRes = $mysqli->query("SELECT active FROM mina_config WHERE id=1");
if ($_minaRes && $_minaRow = $_minaRes->fetch_assoc()) {
    $minaActive = (int)$_minaRow['active'];
}
?>
<!doctype html>
<html lang="en" translate="no" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="<?= htmlspecialchars($config['descricao']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($config['descricao']) ?>">
    <meta property="og:image" content="<?= $config['img_seo'] ?>">
    <meta property="og:image:alt" content="<?= $config['img_seo'] ?>">
    <meta property="og:image:secure_url" content="<?= $config['img_seo'] ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($config['nome']) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($config['nome']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $url_atual ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:description" content="<?= htmlspecialchars($config['descricao']) ?>">
    <meta name="twitter:image" content="<?= $config['img_seo'] ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($config['nome']) ?>">
    <link rel="apple-touch-icon-precomposed" href="<?= $config['favicon'] ?>"
        sizes="192x192">
    <link rel="apple-touch-icon" href="<?= $config['favicon'] ?>" sizes="180x180">
    <link rel="apple-touch-icon" href="<?= $config['favicon'] ?>" sizes="120x120">
    <link rel="apple-touch-icon" href="<?= $config['favicon'] ?>" sizes="152x152">
    <link rel="shortcut icon" href="<?= $config['favicon'] ?>" sizes="32x32">
    <link rel="icon" href="<?= $config['favicon'] ?>" sizes="32x32">
    <title><?= htmlspecialchars($config['nome']) ?></title>

    <?php if (!empty($config['facebookads'])): ?>
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?= htmlspecialchars($config['facebookads']) ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?= htmlspecialchars($config['facebookads']) ?>&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Facebook Pixel Code -->
    <?php endif; ?>

    <?php if (!empty($config['googleAnalytics'])): ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($config['googleAnalytics']) ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', '<?= htmlspecialchars($config['googleAnalytics']) ?>');
    </script>
    <?php endif; ?>

    <script>
        window.__APP_CONFIG__ = {
            "domainInfo": {
                "result": {
                    "data": {
                        "json": {
                            "info": {
                                "tenantId": 1247858,
                                "type": "app",
                                "tenantName": "<?= $config['nome'] ?>",
                                "regionId": <?= $regionIdConfig ?>,
                                "regionName": "<?= htmlspecialchars($regionNameConfig) ?>",
                                "timezone": "<?= htmlspecialchars($timezoneConfig) ?>",
                                "currency": "<?= htmlspecialchars($currency) ?>",
                                "language": "<?= htmlspecialchars($language) ?>",
                                "rechargeRatio": 10000,
                                "phoneCode": "<?= htmlspecialchars($phoneCode) ?>",
                                "code": "<?= htmlspecialchars($regionCode) ?>",
                                "domian": "<?= $config['nome'] ?>",
                                "landing": null,
                                "templateId": null,
                                "jumpDomain": null,
                                "templateName": null,
                                "style": null,
                                "styleConfig": null,
                                "authentication": null,
                                "isSeo": false,
                                "seoSiteName": null,
                                "seoLogo": null,
                                "seoSiteDesc": null,
                                "skinType": "<?= $activeLayout ?>",
                                "skinTwoType": "<?= $activeLayout . ':' . $activeTheme ?>",
                                "domainId": 12759,
                                "jumpDomainType": "main",
                                "isNativeApp": false,
                                "jumpWay": "auto",
                                "jumpWayImg": "",
                                "otherConfig": {},
                                "imitationAppType": null,
                                "landingDomainList": [],
                                "domainAntiSealingList": [],
                                "apkDownloadUrlConfig": {
                                    "list": [],
                                    "isOpen": false,
                                    "isOpenDownloadPageJumpForIos": false
                                },
                                "antiSealingLandingDomainList": [],
                                "seo": {
                                    "title": "<?= $config['nome'] ?>",
                                    "description": "<?= $config['descricao'] ?>",
                                    "image": "<?= $config['img_seo'] ?>"
                                }
                            },
                            "configList": {
                                "siteName": "<?= $config['nome'] ?>",
                                "appIcon": "<?= $config['favicon'] ?>",
                                "siteLogo": "<?= $config['logo'] ?>",
                                "paymentPartnerPic": "",
                                "appLanguage": [
                                    "<?= htmlspecialchars($language) ?>"
                                ]
                            },
                            "loginConfig": {
                                "allowUserChangePassword": true,
                                "allowChangeAssetPassword": true,
                                "allowChangePhone": true,
                                "allowChangeEmail": true,
                                "allowEmailPhoneLogin": true
                            },
                            "flag": "nK2SPT5Tf0O65TbvZTQyZYHMTsNXu+KssN7r1btkj61WJvkHvoucbi3HHlEnVOp6eMA5Nl5eyhBpzDM1jUFz5ZdmdvoScFFLw/8RmKiwtiQCsv6tN537JS7rojiWaO3sexna+EsLJCv4e9c4xK4qngx986IwpmArzy5hkQ96Grt/NSrIPD4o"

                        }
                    }
                }
            },
            "channelInfo": {
                "result": {
                    "data": {
                        "json": {
                            "config": {
                                "pointType": "Facebook",
                                "pointParams": "",
                                "domainId": 0,
                                "frontConfig": "{\"android\":{\"downloadBtn\":true,\"guideInstall\":true,\"popupType\":\"NORMAL\",\"showGiftAmountType\":0,\"showGiftAmount\":0,\"showGiftMaxAmount\":0,\"popupTime\":\"RECHARGE\",\"popupInterval\":\"1\",\"installType\":\"PWA+APK\",\"installUrl\":\"<?= $config['link_app_android'] ?>\"},\"ios\":{\"downloadBtn\":true,\"guideInstall\":true,\"popupType\":\"NORMAL\",\"showGiftAmountType\":0,\"showGiftAmount\":0,\"showGiftMaxAmount\":0,\"popupTime\":\"RECHARGE\",\"popupInterval\":\"1\",\"installType\":\"APPSTORE\",\"installUrl\":\"<?= $config['link_app_ios'] ?>\",\"iosPackageId\":0,\"iosAddressType\":\"normal\"}}",
                                "isInstallSendMoney": false,
                                "installSendMoneyType": "OFF",
                                "installSendMoney": 0,
                                "auditMultiple": 0
                            }
                        }
                    }
                }
            },
            "tenantInfo": {
                "result": {
                    "data": {
                        "json": {
                            "withdrawPasswordAuthMethod": "NONE",
                            "phoneCode": "<?= htmlspecialchars($phoneCode) ?>",
                            "code": "<?= htmlspecialchars($regionCode) ?>",
                            "id": 1247858,
                            "name": "<?= $config['nome'] ?>",
                            "enabled": true,
                            "skinType": "<?= $activeLayout ?>",
                            "skinTwoType": "<?= $activeLayout . ':' . $activeTheme ?>",
                            "homeType": "Platform",
                            "region": {
                                "id": <?= $regionIdConfig ?>,
                                "name": "<?= htmlspecialchars($regionNameConfig) ?>",
                                "timezone": "<?= htmlspecialchars($timezoneConfig) ?>",
                                "currency": "<?= htmlspecialchars($currency) ?>",
                                "language": "<?= htmlspecialchars($language) ?>",
                                "rechargeRatio": 10000,
                                "phoneCode": "<?= htmlspecialchars($phoneCode) ?>",
                                "code": "<?= htmlspecialchars($regionCode) ?>",
                                "withdrawalConfig": "WithdrawFirst"
                            },
                            "siteName": "<?= $config['nome'] ?>",
                            "appIcon": "<?= $config['favicon'] ?>",
                            "siteLogo": "<?= $config['logo'] ?>",
                            "paymentPartnerPic": "",
                            "appLanguage": [
                                "<?= htmlspecialchars($language) ?>"
                            ],
                            "appDefaultLanguage": "en-US",
                            "pwaInstallType": "own",
                            "jpushAppKey": "e20ea5a254b9213c22431008",
                            "reportTimeRange": 7,
                            "gamePartnerPic": "<?= $config['gamePartnerPic'] ?? '' ?>",
                            "openNoticeTextType": "Default",
                            "openNoticeText": "",
                            "sidebarBannerStyle": "style2",
                            "gameLogoStyle": "style1",
                            "homeVideoSwitch": "OFF",
                            "homeVideoUrl": "",
                            "homeNavType": "Platform",
                            "gameLogoLanguage": "en",
                            "homeHotGameRowCount": 0,
                            "homeHotGameColumnCount": 0,
                            "homeGameRowCount": 0,
                            "homeGameColumnCount": 0,
                            "homeAppDownloadGuideSwitch": false,
                            "allowUserChangePassword": true,
                            "allowChangeAssetPassword": true,
                            "allowChangePhone": true,
                            "allowChangeEmail": true,
                            "allowEmailPhoneLogin": true,
                            "registerBindGcashMaya": false,
                            "needAge": false,
                            "buttonShowAmount": "<?php
                                $bc = null;
                                $bcRes = @$mysqli->query('SELECT active, valor_min, valor_max FROM cadastro_bonus_config WHERE id=1');
                                if ($bcRes) $bc = $bcRes->fetch_assoc();
                                if (!empty($bc['active']) && isset($bc['valor_min'], $bc['valor_max'])) {
                                    $fmtMin = rtrim(rtrim(number_format($bc['valor_min']/100, 2, '.', ''), '0'), '.');
                                    $fmtMax = rtrim(rtrim(number_format($bc['valor_max']/100, 2, '.', ''), '0'), '.');
                                    echo $fmtMin . '-' . $fmtMax;
                                } else {
                                    echo '';
                                }
                            ?>",
                            "rewardSwitch": <?= (!empty($bc['active'])) ? 'true' : 'false' ?>,
                            "switch": false,
                            "background": "",
                            "previewText": "",
                            "target": {
                                "type": "internal",
                                "targetValue": {
                                    "type": "redeem_code",
                                    "info": "/Redeem"
                                }
                            },
                            "prizePoolValuesList": [],
                            "announcementPopupWay": "merge",
                            "announcementLabelStyle": "bottom",
                            "gameLogoUrl": "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST']) ?>/api/frontend/game-logo",
                            "currencySymbol": "fiat",
                            "avatarBucket": {
                                "url": "https://upload-us.f-1-g-h.com/avatar/",
                                "female": "20",
                                "male": "20"
                            },
                            "rankConfig": {
                                "userRankSwitch": <?= isset($config['userRankSwitch']) && (int)$config['userRankSwitch'] === 1 ? 'true' : 'false' ?>
                            }
                        }
                    }
                }
            },
            "agencyConfig": {
                "result": {
                    "data": {
                        "json": {
                            "templateInfo": {
                                "config": "[{\"gameType\":\"ELECTRONIC\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"CHESS\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"FISHING\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"VIDEO\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"SPORTS\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"LOTTERY\",\"level\":1,\"needFlow\":100,\"rat\":50},{\"gameType\":\"ELECTRONIC\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"CHESS\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"FISHING\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"VIDEO\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"SPORTS\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"LOTTERY\",\"level\":2,\"needFlow\":100000000,\"rat\":100},{\"gameType\":\"ELECTRONIC\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"CHESS\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"FISHING\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"VIDEO\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"SPORTS\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"LOTTERY\",\"level\":3,\"needFlow\":200000000,\"rat\":200},{\"gameType\":\"ELECTRONIC\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"CHESS\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"FISHING\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"VIDEO\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"SPORTS\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"LOTTERY\",\"level\":4,\"needFlow\":300000000,\"rat\":300},{\"gameType\":\"ELECTRONIC\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"CHESS\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"FISHING\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"VIDEO\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"SPORTS\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"LOTTERY\",\"level\":5,\"needFlow\":1000000000,\"rat\":400},{\"gameType\":\"ELECTRONIC\",\"level\":6,\"needFlow\":3000000000,\"rat\":500},{\"gameType\":\"CHESS\",\"level\":6,\"needFlow\":3000000000,\"rat\":500},{\"gameType\":\"FISHING\",\"level\":6,\"needFlow\":3000000000,\"rat\":500},{\"gameType\":\"VIDEO\",\"level\":6,\"needFlow\":3000000000,\"rat\":500},{\"gameType\":\"SPORTS\",\"level\":6,\"needFlow\":3000000000,\"rat\":500},{\"gameType\":\"LOTTERY\",\"level\":6,\"needFlow\":3000000000,\"rat\":500}]",
                                "agencyMode": "unlimitedLevel",
                                "achievementType": "validBet",
                                "commissionType": "noGameType",
                                "type": "noGameType"
                            },
                            "configList": {
                                "agencyMode": "unlimitedLevel",
                                "commissionType": "noGameType",
                                "achievementType": "validBet",
                                "advertising_en": "<?= $config['advertising_local'] ?? '' ?>",
                                "advertising_local": "<?= $config['advertising_local'] ?? '' ?>",
                                "tabSort": [
                                    "{\"title\":\"MyAgency\",\"isOpen\":true,\"sort\":6}",
                                    "{\"title\":\"PromotionTutorial\",\"isOpen\":true,\"sort\":5}",
                                    "{\"title\":\"MyPerformance\",\"isOpen\":true,\"sort\":4}",
                                    "{\"title\":\"MyCommission\",\"isOpen\":true,\"sort\":3}",
                                    "{\"title\":\"CommissionRatio\",\"isOpen\":true,\"sort\":2}",
                                    "{\"title\":\"DirectAccount\",\"isOpen\":1248680527158,\"sort\":1}"
                                ],
                                "siteName": "<?= $config['nome'] ?>",
                                "siteUrl": "<?= $url_atual ?>",
                                "logo": "<?= $config['logo'] ?>",
                                "icon": "<?= $config['promo_icon'] ?? 'https://upload-sys-pics.f-1-g-h.com/promotion/icon/activity/activity_1.png' ?>",
                                "background": "<?= $config['promo_bg'] ?? 'https://upload-sys-pics.f-1-g-h.com/promotion/promotionbg/promotion1.jpg' ?>",
                                "intro": "<?= $config['intro'] ?? '' ?>",
                                "customTutorial": false,
                                "tutorial_en": "",
                                "tutorial_local": "",
                                "jumpType": "home",
                                "software": [
                                    "{\"name\":\"Facebook\",\"type\":\"Facebook\",\"isOpen\":true,\"sort\":10}",
                                    "{\"name\":\"WhatsApp\",\"type\":\"WhatsApp\",\"isOpen\":true,\"sort\":9}",
                                    "{\"name\":\"Telegram\",\"type\":\"Telegram\",\"isOpen\":true,\"sort\":8}",
                                    "{\"name\":\"YouTube\",\"type\":\"YouTube\",\"isOpen\":true,\"sort\":6}",
                                    "{\"name\":\"Twitter\",\"type\":\"Twitter\",\"isOpen\":true,\"sort\":6}",
                                    "{\"name\":\"TikTok\",\"type\":\"TikTok\",\"isOpen\":true,\"sort\":4}",
                                    "{\"name\":\"Instagram\",\"type\":\"Instagram\",\"isOpen\":true,\"sort\":7}",
                                    "{\"name\":\"Kwai\",\"type\":\"Kwai\",\"isOpen\":true,\"sort\":2}",
                                    "{\"name\":\"Email\",\"type\":\"Email\",\"isOpen\":true,\"sort\":1}"
                                ],
                                "commissionDistributeTime": "08:00:00",
                                "shareTextType": "Default",
                                "shareText": ""
                            },
                            "ossUrl": "https://upload-oss-4s.f-1-q-h.com"
                        }
                    }
                }
            },


           "apiUrl": "<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . htmlspecialchars($_SERVER['HTTP_HOST']) ?>",
            "from": "origin_config",
            "version": "v197",
            "VITE_CAPTCHA_SCENE_ID": "erus05quz",
            "VITE_CAPTCHA_PREFIX": "1sdd581",
            "SENTRY_AUTH_TOKEN": "",
            "CURRENT_SERVER": "us4",
            "redirectUrl": null,
            "seo": {
                "title": "<?= $config['nome'] ?>",
                "description": "<?= $config['descricao'] ?>",
                "image": "<?= $config['img_seo'] ?>",
                "url": "<?= $url_atual ?>"
            },
            "linkIcon": {
                "imgUrl": "<?= $config['favicon'] ?>"
            },
            "responseErrorInfo": {
                "domainInfo": null,
                "channelInfo": null,
                "tenantInfo": null,
                "agencyConfig": null
            },
            "supportIconActive": <?= $supportIconActive ?>,
            "minaActive": <?= $minaActive ?>
        };
    </script>

    
    <script>
        // 添加检查是否在 iframe 中的函数
        function isInIframe() {
            const url = new URL(window.location.href);
            return url.searchParams.get('unTopWindow') === 'true' && url.searchParams.get('domainType') !== 'google';
        }
        async function getKeyFromDb(id, resolve, reject) {
            const store = await openDb();
            const getRequest = store.get(id);
            getRequest.onsuccess = () => resolve([[id], getRequest.result]);
            getRequest.onerror = (e) => reject(e.target.error);
        }

        async function openDb() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open("_ionicstorage", 2);
                request.onerror = (event) => reject(event.target.error);
                request.onsuccess = (event) => {
                    const db = event.target.result;
                    const transaction = db.transaction(["_ionickv"], "readwrite");
                    const store = transaction.objectStore("_ionickv");
                    resolve(store);
                }
                    ;
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains("_ionickv")) {
                        db.createObjectStore("_ionickv");
                    }
                }
                    ;
            }
            );
        }

        async function useStore(list) {
            // 批量从 indexedDB 中获取数据
            const res = await Promise.all(list.map((id) => new Promise((resolve, reject) => getKeyFromDb(id, resolve, reject))));
            try {
                return Object.fromEntries(res);
            } catch (e) {
                // 兼容性处理
                const obj = {};
                res.forEach(([key, value]) => {
                    obj[key] = value;
                }
                );
                return obj;
            }
        }
        async function setKeyToDb(id, value) {
            return new Promise((resolve, reject) => {
                openDb().then((store) => {
                    const putRequest = store.put(value, id);
                    putRequest.onsuccess = () => resolve(true);
                    putRequest.onerror = (event) => reject(event.target.error);
                }
                );
            }
            );
        }
        function initAccount() {
            console.warn("initAccount");
            useStore(['token', 'account', 'password', 'loginType']).then(res => {
                console.warn("res", isInIframe());
                if (isInIframe()) {
                    console.warn("isInIframe");
                    if (res.token) {
                        console.warn("fixToken from iframe");
                        window.parent.postMessage({
                            type: "fixToken",
                            accountInfo: res
                        }, "*");
                    }
                } else {
                    console.warn("fixToken from parent");
                    window.addEventListener("message", (event) => {
                        if (event.data.type === "fixToken") {
                            console.warn("fixToken from parent");
                            const { token, account, password, loginType } = event.data.accountInfo;
                            setKeyToDb('token', token);
                            setKeyToDb('account', account);
                            setKeyToDb('password', password);
                            setKeyToDb('loginType', loginType);
                        }
                    }
                    );
                }
            }
            ).catch(err => {
                console.warn(err, "fixToken error");
            }
            );
        }
        function onPageReturn(callback) {
            const handleReturn = () => {
                if (document.visibilityState === 'visible') {
                    callback();
                }
            }
                ;
            const events = ['visibilitychange', 'focus', 'pageshow', 'DOMContentLoaded'];
            events.forEach(event => document.addEventListener(event, handleReturn));
            return () => {
                events.forEach(event => document.removeEventListener(event, handleReturn));
            }
                ;
        }
        onPageReturn(initAccount);
    </script>
    <style>
        html,body{height:100%}
        body{background:radial-gradient(1200px circle at 20% 0%,#eaf1ff 0,#f7f9ff 40%,#eef2f7 100%);color:#1f2937}
        @keyframes _qrFadeIn{from{opacity:0}to{opacity:1}}
        @keyframes _qrPop{from{transform:scale(0)}to{transform:scale(1)}}

    </style>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="google" content="notranslate" />
    <title></title>
    <base href="/" />
    <meta name="theme-color" content="#f5f7fb">
    <meta name="color-scheme" content="light dark" />
    <meta name="viewport"
        content="viewport-fit=cover,width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
    <meta name="format-detection" content="telephone=no" />
    <meta name="msapplication-tap-highlight" content="no" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($config['nome']) ?>" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black" />
    <meta name="twitter:site" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin="">
    <link rel="preconnect" href="https://challenges.cloudflare.com" crossorigin="anonymous">
    <script src="https://o.alicdn.com/captcha-frontend/aliyunCaptcha/AliyunCaptcha.js" defer="defer"></script>
    <script src="https://telegram.org/js/telegram-web-app.js?58"></script>
    <script>
        // 判断是不是三星浏览器
        function isSamsungBrowser() {
            // 获取用户代理字符串
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;

            // 将用户代理字符串转换为小写，方便检查
            const uaLowerCase = userAgent.toLowerCase();

            // 检查是否包含 "SamsungBrowser" 字符串
            if (uaLowerCase.includes('samsungbrowser')) {
                return true;
            }

            // 备用检测: 检查特定的三星设备型号 + "Version" 字符串 (一些旧版三星浏览器)
            const samsungDeviceRegex = /(samsung|sm-|gt-|sch-)/;
            if (samsungDeviceRegex.test(uaLowerCase) && uaLowerCase.includes('version')) {
                return true;
            }

            // 如果没有匹配到上述条件，则不是三星浏览器
            return 1248680527158;
        }
        //添加全局变量
        window.isSamsungBrowser = isSamsungBrowser
        try {
            sessionStorage.setItem('href', location.href.replace(/([^:])\/\//g, '$1/'));
        } catch (error) {
            console.error('can not set sessionStorage:', error);
        }
        if (isSamsungBrowser() && "serviceWorker" in navigator) {
            navigator.serviceWorker.register("/sw.produce.min.2.1.6.js").then((registration) => {
                window.registration = registration;
            }
            ).catch(function (error) {
                if (window.jsSentryError) {
                    window.jsSentryError(error);
                }
            });
        }
    </script>
    <script type="module" crossorigin src="/assets/index-T2Rmfk75.js"></script>
    <link rel="modulepreload" crossorigin href="/assets/index-T2Rmfk75.js">
    <link rel="modulepreload" crossorigin href="/assets/vendor_modules-Bo-19cQw.js">
    <link rel="stylesheet" crossorigin href="/assets/index-Cl834OsA.css?v=8">
    <link rel="stylesheet" crossorigin href="/assets/vendor_modules-9b7WOkhW.css">
    <?php if (!$supportIconActive): ?>
    <style>.support{display:none!important}</style>
    <?php endif; ?>
    <?php if (!$minaActive): ?>
    <style>.red-packet-rain{display:none!important}</style>
    <?php endif; ?>
    <script type="module">
        import.meta.url;
        import("_").catch(() => 1);
        async function* g() { }
        ; if (location.protocol != "file:") {
            window.__vite_is_modern_browser = true
        }
    </script>
    <script type="module">
        !function () {
            if (window.__vite_is_modern_browser)
                return;
            console.warn("vite: loading legacy chunks, syntax error above and the same error below should be ignored");
            var e = document.getElementById("vite-legacy-polyfill")
                , n = document.createElement("script");
            n.src = e.src,
                n.onload = function () {
                    System.import(document.getElementById('vite-legacy-entry').getAttribute('data-src'))
                }
                ,
                document.body.appendChild(n)
        }();
    </script>
</head>

<body>
    <div id="click-language" style="display:none;"></div>
    <div id="app"></div>
    <script nomodule>
        !function () {
            var e = document
                , t = e.createElement("script");
            if (!("noModule" in t) && "onbeforeload" in t) {
                var n = !1;
                e.addEventListener("beforeload", (function (e) {
                    if (e.target === t)
                        n = !0;
                    else if (!e.target.hasAttribute("nomodule") || !n)
                        return;
                    e.preventDefault()
                }
                ), !0),
                    t.type = "module",
                    t.src = ".",
                    e.head.appendChild(t),
                    t.remove()
            }
        }();
    </script>
    


</body>

</html>