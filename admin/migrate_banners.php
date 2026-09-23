<?php
include 'services/database.php';

// 1. Add 'type' column if not exists
$checkColumn = $mysqli->query("SHOW COLUMNS FROM banner LIKE 'type'");
if ($checkColumn->num_rows == 0) {
    echo "Adding 'type' column...\n";
    $mysqli->query("ALTER TABLE banner ADD COLUMN type VARCHAR(50) DEFAULT 'lobby_carousel'");
} else {
    echo "'type' column already exists.\n";
}

// 2. Add 'link' column if not exists (since lobby banners have targetValue/link logic usually, but the admin only edits title/img/status. The hardcoded API has 'targetValue'. I should probably add this too to be complete, but the user only asked for editing in admin which currently only has title/img. I will stick to what the admin supports: title, img, status. The hardcoded 'targetValue' might be lost if I don't support it, but for now I will just support what is requested. I'll stick to 'type').

// 3. Insert hardcoded lobby banners if they don't exist
$lobbyBanners = [
    ['titulo' => 'Banner Lobby 1', 'img' => '502.png', 'status' => 1, 'type' => 'lobby_banner'],
    ['titulo' => 'Banner Lobby 2', 'img' => '503.png', 'status' => 1, 'type' => 'lobby_banner'],
    ['titulo' => 'Banner Lobby 3', 'img' => '22.png', 'status' => 1, 'type' => 'lobby_banner']
];

foreach ($lobbyBanners as $banner) {
    $check = $mysqli->query("SELECT id FROM banner WHERE img = '{$banner['img']}' AND type = 'lobby_banner'");
    if ($check->num_rows == 0) {
        echo "Inserting {$banner['titulo']}...\n";
        $stmt = $mysqli->prepare("INSERT INTO banner (titulo, img, status, type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $banner['titulo'], $banner['img'], $banner['status'], $banner['type']);
        $stmt->execute();
    }
}

echo "Migration done.\n";
?>
