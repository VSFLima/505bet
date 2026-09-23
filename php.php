<?php
include_once __DIR__ . "/config.php";
include_once __DIR__ . "/" . DASH . "/services/database.php";
global $mysqli;

$homeListPath = __DIR__ . "/api1/frontend/trpc/home.list";
$providers = [];
if (file_exists($homeListPath)) {
    $homeList = json_decode(file_get_contents($homeListPath), true);
    $gameTypeList = $homeList['result']['data']['json']['gameTypeList'] ?? [];
    foreach ($gameTypeList as $gameType) {
        $platformList = $gameType['platformList'] ?? [];
        foreach ($platformList as $platform) {
            $code = $platform['code'] ?? '';
            if ($code === '') {
                continue;
            }
            $providers[$code] = [
                'code' => $code,
                'name' => $platform['name'] ?? $code
            ];
        }
    }
}
ksort($providers);

$action = $_POST['action'] ?? '';
$providerInput = trim($_POST['provider_code'] ?? '');
$providerSelect = trim($_POST['provider_select'] ?? '');
$providerCode = $providerInput !== '' ? $providerInput : $providerSelect;
$resultMessage = '';
$resultType = '';
$importSummary = null;
$apiResponse = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'fetch') {
    if ($providerCode === '') {
        $resultType = 'error';
        $resultMessage = 'Informe um provider_code';
    } else {
        $payload = [
            'method' => 'game_list',
            'agent_code' => 'kaiooxbet',
            'agent_token' => '40a5d1d8b20c11f09c3cbc2411881493',
            'provider_code' => $providerCode
        ];

        $ch = curl_init('https://igamewin.com/api/v1');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError) {
            $resultType = 'error';
            $resultMessage = $curlError !== '' ? $curlError : 'Falha ao chamar API';
        } else {
            $apiResponse = json_decode($response, true);
            if (!is_array($apiResponse)) {
                $resultType = 'error';
                $resultMessage = 'Resposta inválida da API';
            } elseif (($apiResponse['status'] ?? 0) != 1) {
                $resultType = 'error';
                $resultMessage = $apiResponse['msg'] ?? 'Erro na API';
            } else {
                $games = $apiResponse['games'] ?? [];
                $imported = 0;
                $updated = 0;
                $skipped = 0;

                $selectStmt = $mysqli->prepare("SELECT id FROM games WHERE game_code = ? AND provider = ? LIMIT 1");
                $insertStmt = $mysqli->prepare("INSERT INTO games (game_code, game_name, banner, status, provider, popular, type, game_type, api) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $updateStmt = $mysqli->prepare("UPDATE games SET game_name = ?, banner = ?, status = ? WHERE id = ?");

                foreach ($games as $game) {
                    $gameCode = (string)($game['game_code'] ?? '');
                    $gameName = (string)($game['game_name'] ?? '');
                    $banner = (string)($game['banner'] ?? '');
                    $status = (int)($game['status'] ?? 0);

                    if ($gameCode === '' || $gameName === '') {
                        $skipped++;
                        continue;
                    }

                    $selectStmt->bind_param("ss", $gameCode, $providerCode);
                    $selectStmt->execute();
                    $res = $selectStmt->get_result();
                    if ($res && $row = $res->fetch_assoc()) {
                        $gameId = (int)$row['id'];
                        $updateStmt->bind_param("ssii", $gameName, $banner, $status, $gameId);
                        if ($updateStmt->execute()) {
                            $updated++;
                        } else {
                            $skipped++;
                        }
                    } else {
                        $popular = 0;
                        $type = 'slot';
                        $gameType = 'ELECTRONIC';
                        $api = 'igamewin';
                        $insertStmt->bind_param("sssisssss", $gameCode, $gameName, $banner, $status, $providerCode, $popular, $type, $gameType, $api);
                        if ($insertStmt->execute()) {
                            $imported++;
                        } else {
                            $skipped++;
                        }
                    }
                }

                $selectStmt->close();
                $insertStmt->close();
                $updateStmt->close();

                $importSummary = [
                    'imported' => $imported,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'total' => count($games)
                ];
                $resultType = 'success';
                $resultMessage = 'Importação concluída';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Importar jogos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; }
        .container { max-width: 900px; margin: 30px auto; padding: 20px; }
        .card { background: #111827; border-radius: 8px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.25); }
        .row { display: flex; gap: 16px; flex-wrap: wrap; }
        .field { flex: 1; min-width: 220px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; }
        input, select { width: 100%; padding: 10px 12px; border-radius: 6px; border: 1px solid #334155; background: #0b1220; color: #e2e8f0; }
        button { padding: 12px 18px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; font-weight: 600; cursor: pointer; }
        .msg { margin-top: 16px; padding: 12px; border-radius: 6px; }
        .msg.success { background: #064e3b; color: #a7f3d0; }
        .msg.error { background: #7f1d1d; color: #fecaca; }
        .summary { margin-top: 16px; background: #0b1220; border: 1px solid #1f2937; border-radius: 6px; padding: 12px; }
        pre { white-space: pre-wrap; background: #0b1220; padding: 12px; border-radius: 6px; border: 1px solid #1f2937; color: #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Importar jogos da iGameWin</h2>
            <form method="post">
                <input type="hidden" name="action" value="fetch">
                <div class="row">
                    <div class="field">
                        <label>Selecionar provider_code do home.list</label>
                        <select name="provider_select">
                            <option value="">Selecionar</option>
                            <?php foreach ($providers as $p): ?>
                                <option value="<?php echo htmlspecialchars($p['code']); ?>" <?php echo $providerCode === $p['code'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name'] . ' (' . $p['code'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Provider_code manual</label>
                        <input type="text" name="provider_code" value="<?php echo htmlspecialchars($providerCode); ?>" placeholder="PRAGMATIC">
                    </div>
                </div>
                <div style="margin-top: 16px;">
                    <button type="submit">Salvar no banco</button>
                </div>
            </form>

            <?php if ($resultMessage !== ''): ?>
                <div class="msg <?php echo $resultType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($resultMessage); ?>
                </div>
            <?php endif; ?>

            <?php if ($importSummary): ?>
                <div class="summary">
                    <div>Total: <?php echo (int)$importSummary['total']; ?></div>
                    <div>Inseridos: <?php echo (int)$importSummary['imported']; ?></div>
                    <div>Atualizados: <?php echo (int)$importSummary['updated']; ?></div>
                    <div>Ignorados: <?php echo (int)$importSummary['skipped']; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($apiResponse): ?>
                <div style="margin-top: 16px;">
                    <strong>Resposta da API</strong>
                    <pre><?php echo htmlspecialchars(json_encode($apiResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)); ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
