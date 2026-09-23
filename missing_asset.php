<?php
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);
$relativePath = ltrim($path, '/');

if (strpos($relativePath, '..') !== false) {
    http_response_code(400);
    exit;
}

$localPath = __DIR__ . '/' . $relativePath;
$ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
$mime = 'application/octet-stream';

switch ($ext) {
    case 'png': $mime = 'image/png'; break;
    case 'jpg':
    case 'jpeg': $mime = 'image/jpeg'; break;
    case 'gif': $mime = 'image/gif'; break;
    case 'svg': $mime = 'image/svg+xml'; break;
    case 'webp': $mime = 'image/webp'; break;
    case 'ico': $mime = 'image/x-icon'; break;
}

if (file_exists($localPath)) {
    header("Content-Type: $mime");
    readfile($localPath);
    exit;
}

$remoteBases = [
    "https://panda99.vip/",
    "https://a89s.com/",
    "https://upload-sys-pics.bcbd123.com/",
    "https://upload-sys-pics.f-1-g-h.com/",
    "https://upload-us.bcbd123.com/",
    "https://upload-us.f-1-g-h.com/"
];

// Map local img_icons back to icons for remote fetch if needed
$remoteRelativePath = $relativePath;
if (strpos($relativePath, 'img_icons/') === 0) {
    $remoteRelativePath = str_replace('img_icons/', 'icons/', $relativePath);
}

foreach ($remoteBases as $remoteBase) {
    $remoteUrl = $remoteBase . $remoteRelativePath;
    $content = @file_get_contents($remoteUrl);

    if ($content !== false && !empty($content)) {
        $dir = dirname($localPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($localPath, $content);
        header("Content-Type: $mime");
        echo $content;
        exit;
    }
}

http_response_code(404);
