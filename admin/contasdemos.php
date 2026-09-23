<?php include 'partials/html.php' ?>

<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
session_start();
include_once "services/database.php";
include_once "services/funcao.php";
include_once 'logs/registrar_logs.php';
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once "validar_2fa.php";
include_once "services/CSRF_Protect.php";
include_once 'services/checa_login_adm.php';
$csrf = new CSRF_Protect();

checa_login_adm();

function registrarInfluencerPGClone($username)
{
    global $mysqli;
    
    $config_stmt = $mysqli->prepare("SELECT * FROM pgclone WHERE id = 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();
    
    if (!$config || $config['ativo'] != 1) {
        throw new Exception("PGClone não está configurada ou ativa");
    }
    
    $data = [
        'credentials' => [
            'agentCode' => $config['agent_code'],
            'agentToken' => $config['agent_token'],
            'secretKey' => $config['agent_secret']
        ],
        'user' => [
            'username' => $username,
            'isinfluencer' => 1
        ]
    ];
    
    $json_data = json_encode($data);
    
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $origin = $protocol . "://" . $host;
    
    error_log("==================== INFLUENCER REQUEST ====================");
    error_log("[INFLUENCER] Username: $username");
    error_log("[INFLUENCER] Origin: $origin");
    error_log("[INFLUENCER] Endpoint: https://pgclone.com/api/update");
    error_log("[INFLUENCER] Request Body: " . $json_data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://pgclone.com/api/update');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Origin: ' . $origin,
        'Referer: ' . $origin . '/callback',
        'Content-Length: ' . strlen($json_data)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log("[INFLUENCER] HTTP Code: $http_code");
    if ($curl_error) {
        error_log("[INFLUENCER] cURL Error: $curl_error");
    }
    error_log("[INFLUENCER] Response: " . ($response ?: 'Empty'));
    error_log("===========================================================");
    
    if ($http_code == 200) {
        $data_response = json_decode($response, true);
        if (isset($data_response['success']) && $data_response['success'] === true) {
            return [
                'success' => true,
                'message' => 'Influencer ativado com sucesso',
                'data' => $data_response
            ];
        } else {
            $error_msg = $data_response['error'] ?? 'Erro desconhecido';
            throw new Exception("API Error: " . $error_msg);
        }
    } else {
        throw new Exception("HTTP $http_code" . ($curl_error ? " - $curl_error" : "") . " - Response: " . substr($response, 0, 200));
    }
}

// ==================== ADICIONADO: FUNÇÃO PARA ABRIR JOGO NA IGAMEWIN ====================
function abrirJogoIGameWin($username, $saldo)
{
    global $mysqli;
    
    // Buscar o jogo Sweet Kingdom
    $stmt = $mysqli->prepare("SELECT game_code, provider FROM games WHERE game_name LIKE '%sweet%kingdom%' AND api = 'iGameWin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $game_code = $row['game_code'];
        $provider = $row['provider'];
        
        $config_stmt = $mysqli->prepare("SELECT * FROM igamewin WHERE ativo = 1 LIMIT 1");
        $config_stmt->execute();
        $config = $config_stmt->get_result()->fetch_assoc();
        
        if ($config) {
            $dataRequest = array(
                "method"        => "game_launch",
                "agent_code"    => $config['agent_code'],
                "agent_token"   => $config['agent_token'],
                "user_code"     => $username,
                "provider_code" => $provider,
                "game_code"     => $game_code,
                "lang"          => "pt"
            );
            
            $json_data = json_encode($dataRequest);
            
            $ch = curl_init($config['url']);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $data_response = json_decode($response, true);
                if (isset($data_response['launch_url'])) {
                    return $data_response['launch_url'];
                }
            }
            
            throw new Exception("Erro ao abrir jogo: Resposta inválida da API");
        }
        
        throw new Exception("iGameWin não está configurada ou ativa");
    }
    
    throw new Exception("Jogo Sweet Kingdom não encontrado");
}

// ==================== ADICIONADO: FUNÇÃO PARA IGAMEWIN ====================
function registrarInfluencerIGameWin($username)
{
    global $mysqli;
    
    $config_stmt = $mysqli->prepare("SELECT * FROM igamewin WHERE ativo = 1 LIMIT 1");
    $config_stmt->execute();
    $config = $config_stmt->get_result()->fetch_assoc();
    $config_stmt->close();
    
    if (!$config) {
        throw new Exception("iGameWin não está configurada ou ativa");
    }
    
    $dataRequest = array(
        "method"       => "set_demo",
        "agent_code"   => $config['agent_code'],
        "agent_token"  => $config['agent_token'],
        "user_code"    => $username,
        "is_demo"      => 1
    );
    
    $json_data = json_encode($dataRequest);
    
    error_log("==================== INFLUENCER IGAMEWIN REQUEST ====================");
    error_log("[IGAMEWIN] Username: $username");
    error_log("[IGAMEWIN] Endpoint: {$config['url']}");
    error_log("[IGAMEWIN] Request Body: " . $json_data);
    
    $ch = curl_init($config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log("[IGAMEWIN] HTTP Code: $http_code");
    if ($curl_error) {
        error_log("[IGAMEWIN] cURL Error: $curl_error");
    }
    error_log("[IGAMEWIN] Response: " . ($response ?: 'Empty'));
    error_log("================================================================");
    
    if ($http_code == 200) {
        $data_response = json_decode($response, true);
        if (isset($data_response['status']) && intval($data_response['status']) === 1) {
            return [
                'success' => true,
                'message' => 'Influencer ativado com sucesso',
                'data' => $data_response
            ];
        } else {
            $error_msg = $data_response['msg'] ?? 'Erro desconhecido';
            throw new Exception("API Error: " . $error_msg);
        }
    } else {
        throw new Exception("HTTP $http_code" . ($curl_error ? " - $curl_error" : "") . " - Response: " . substr($response, 0, 200));
    }
}

function criarContasDemo($quantidade, $saldo, $abrir_jogo = false)
{
    global $mysqli;
    
    $contas_criadas = [];
    $erros = [];
    $debug_logs = [];
    
    for ($i = 0; $i < $quantidade; $i++) {
        $debug_log = [];
        
        $random_id = rand(10000, 99999);
        $username = "demo" . $random_id;  // MUDOU: removido sufixo "pg"
        $password = "demo" . rand(1000, 9999);
        $token = md5(uniqid($username, true));
        $invite_code = (string)$random_id;
        
        $debug_log[] = "1. Criando conta: $username";
        
        $stmt = $mysqli->prepare("INSERT INTO usuarios 
            (id, mobile, celular, password, saldo, senhaparasacar, url, token, data_registro, invite_code, statusaff, lobby, vip) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 1, 1, 0)");
        
        $url = "https://" . $_SERVER['HTTP_HOST'];
        
        $stmt->bind_param(
            "isssdssss",
            $random_id,
            $username,
            $username,
            $password,
            $saldo,
            $password,
            $url,
            $token,
            $invite_code
        );
        
        if ($stmt->execute()) {
            $debug_log[] = "2. ✓ Conta criada no banco local";
            
            $conta = [
                'id' => $random_id,
                'username' => $username,
                'password' => $password,
                'saldo' => $saldo,
                'token' => $token,
                'game_url' => null,
                'game_url_igamewin' => null,
                'influencer_status' => 'aguardando',
                'igamewin_status' => 'aguardando',
                'debug' => []
            ];
            
            $debug_log[] = "3. Aguardando 4 segundos antes de abrir os jogos...";
            sleep(4);
            
            // ========== ABRIR JOGO FORTUNE TIGER (PGCLONE) ==========
            if ($abrir_jogo) {
                try {
                    $debug_log[] = "4. Abrindo jogo Fortune Tiger (PGClone)...";
                    $game_url = abrirJogoDemo($username, $saldo, $token);
                    $conta['game_url'] = $game_url;
                    $debug_log[] = "5. ✓ Fortune Tiger aberto com sucesso";
                } catch (Exception $e) {
                    $conta['game_url_error'] = $e->getMessage();
                    $debug_log[] = "5. ✗ Erro ao abrir Fortune Tiger: " . $e->getMessage();
                }
                
                // ========== ABRIR JOGO SWEET KINGDOM (IGAMEWIN) ==========
                try {
                    $debug_log[] = "6. Abrindo jogo Sweet Kingdom (iGameWin)...";
                    $game_url_igw = abrirJogoIGameWin($username, $saldo);
                    $conta['game_url_igamewin'] = $game_url_igw;
                    $debug_log[] = "7. ✓ Sweet Kingdom aberto com sucesso";
                } catch (Exception $e) {
                    $conta['game_url_igamewin_error'] = $e->getMessage();
                    $debug_log[] = "7. ✗ Erro ao abrir Sweet Kingdom: " . $e->getMessage();
                }
            } else {
                $debug_log[] = "4. Jogos não solicitados";
            }
            
            $debug_log[] = "8. Aguardando mais 4 segundos antes de ativar influencers...";
            sleep(4);
            
            // ========== ATIVAR PGCLONE ==========
            try {
                $debug_log[] = "9. Enviando requisição para ativar influencer PGClone...";
                $result = registrarInfluencerPGClone($username);
                $conta['influencer_status'] = 'ativo';
                $conta['influencer_data'] = $result;
                $debug_log[] = "10. ✓ Influencer PGClone ativado com sucesso!";
            } catch (Exception $e) {
                $conta['influencer_status'] = 'erro';
                $conta['influencer_error'] = $e->getMessage();
                $debug_log[] = "10. ✗ Erro ao ativar influencer PGClone: " . $e->getMessage();
            }
            
            // ========== ATIVAR IGAMEWIN (ADICIONADO) ==========
            try {
                $debug_log[] = "11. Enviando requisição para ativar influencer iGameWin...";
                $result = registrarInfluencerIGameWin($username);
                $conta['igamewin_status'] = 'ativo';
                $conta['igamewin_data'] = $result;
                $debug_log[] = "12. ✓ Influencer iGameWin ativado com sucesso!";
            } catch (Exception $e) {
                $conta['igamewin_status'] = 'erro';
                $conta['igamewin_error'] = $e->getMessage();
                $debug_log[] = "12. ✗ Erro ao ativar influencer iGameWin: " . $e->getMessage();
            }
            
            $conta['debug'] = $debug_log;
            $contas_criadas[] = $conta;
            
        } else {
            $erros[] = "Erro ao criar conta $username: " . $stmt->error;
            $debug_log[] = "✗ ERRO: " . $stmt->error;
        }
        
        $debug_logs[$username] = $debug_log;
        $stmt->close();
    }
    
    return [
        'sucesso' => count($contas_criadas),
        'erros' => count($erros),
        'contas' => $contas_criadas,
        'mensagens_erro' => $erros,
        'debug_logs' => $debug_logs
    ];
}

function abrirJogoDemo($username, $saldo, $token)
{
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT game_code, provider FROM games WHERE game_name LIKE '%fortune%tiger%' AND api = 'PGClone' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $game_code = $row['game_code'];
        $provider = $row['provider'];
        
        $config_stmt = $mysqli->prepare("SELECT * FROM pgclone WHERE id = 1");
        $config_stmt->execute();
        $config = $config_stmt->get_result()->fetch_assoc();
        
        if ($config && $config['ativo'] == 1) {
            $data = [
                'agentToken' => $config['agent_token'],
                'secretKey' => $config['agent_secret'],
                'user_code' => $username,
                'provider_code' => 'PGSOFT',
                'game_code' => $game_code,
                'user_balance' => floatval($saldo)
            ];
            
            $json_data = json_encode($data);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['url'] . '/api/v1/game_launch');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json_data)
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $data_response = json_decode($response, true);
                if (isset($data_response['launch_url'])) {
                    return $data_response['launch_url'];
                }
            }
            
            throw new Exception("Erro ao abrir jogo: Resposta inválida da API");
        }
        
        throw new Exception("PGClone não está configurada ou ativa");
    }
    
    throw new Exception("Jogo Fortune Tiger não encontrado");
}

$toastType = null;
$toastMessage = '';
$resultado = null;

// VERIFICA SE HÁ MENSAGEM NA SESSÃO (após redirect)
if (isset($_SESSION['toast_type'])) {
    $toastType = $_SESSION['toast_type'];
    unset($_SESSION['toast_type']);
}

if (isset($_SESSION['toast_message'])) {
    $toastMessage = $_SESSION['toast_message'];
    unset($_SESSION['toast_message']);
}

if (isset($_SESSION['resultado'])) {
    $resultado = $_SESSION['resultado'];
    unset($_SESSION['resultado']);
}

// PROCESSA O POST E REDIRECIONA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['criar_contas'])) {
    $quantidade = intval($_POST['quantidade']);
    $saldo = floatval($_POST['saldo']);
    $abrir_jogo = true; // Sempre abre os jogos automaticamente
    
    if ($quantidade > 0 && $quantidade <= 100 && $saldo >= 0) {
        $resultado_criacao = criarContasDemo($quantidade, $saldo, $abrir_jogo);
        
        // Salva o resultado na sessão
        $_SESSION['resultado'] = $resultado_criacao;
        
        if ($resultado_criacao['sucesso'] > 0) {
            $_SESSION['toast_type'] = 'success';
            $_SESSION['toast_message'] = "{$resultado_criacao['sucesso']} contas demo criadas e ativadas em PGClone + iGameWin!";
        } else {
            $_SESSION['toast_type'] = 'error';
            $_SESSION['toast_message'] = "Erro ao criar contas demo";
        }
    } else {
        $_SESSION['toast_type'] = 'error';
        $_SESSION['toast_message'] = "Dados inválidos. Quantidade deve ser entre 1 e 100.";
    }
    
    // REDIRECIONA para evitar reenvio do formulário
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$contas_demo_qry = "SELECT * FROM usuarios WHERE mobile LIKE 'demo%' ORDER BY id DESC LIMIT 50";
$contas_demo_result = mysqli_query($mysqli, $contas_demo_qry);
$contas_demo = [];
while ($row = mysqli_fetch_assoc($contas_demo_result)) {
    $contas_demo[] = $row;
}
?>

<head>
    <?php $title = "Criar Contas Demo em Massa"; ?>
    <?php include 'partials/title-meta.php' ?>
    <?php include 'partials/head-css.php' ?>
    <style>
        .conta-card {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: #191a1a;
        }
        .conta-card:hover {
            background: #191a1a;
        }
        .copy-btn {
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">
                
                <!-- Formulário de Criação -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Criar Contas Demo em Massa</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="criar_contas" value="1">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Quantidade de Contas</label>
                                            <input type="number" name="quantidade" class="form-control" 
                                                min="1" max="100" value="10" required>
                                            <small class="text-muted">Máximo: 100 contas por vez</small>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">Saldo Inicial (R$)</label>
                                            <input type="number" name="saldo" class="form-control" 
                                                step="0.01" min="0" value="1000.00" required>
                                            <small class="text-muted">Saldo para cada conta</small>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="alert alert-warning">
                                        <strong>⏱️ Tempo de Processamento:</strong>
                                        <ul class="mb-0">
                                            <li>Cada conta leva aproximadamente <strong>8 segundos</strong> para ser criada</li>
                                            <li>4 segundos para abrir os jogos (Fortune Tiger + Sweet Kingdom)</li>
                                            <li>4 segundos para ativar influencers (PGClone + iGameWin)</li>
                                            <li>Para 10 contas: ~1 minuto e 20 segundos</li>
                                            <li>Aguarde o processo completo antes de usar as contas</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <strong>ℹ️ Informações - SISTEMA DE CRIAR DEMOS (PGCLONE E IGAMEWIN):</strong>
                                        <ul class="mb-0">
                                            <li>Todas as contas terão <strong>statusaff = 1</strong> (Afiliado ativo)</li>
                                            <li>Username: <code>demo[número aleatório]</code></li>
                                            <li>Senha: <code>demo[4 dígitos]</code></li>
                                            <li>Lobby habilitado automaticamente</li>
                                            <li><strong>🎮 Jogos:</strong> Fortune Tiger + Sweet Kingdom (abertos automaticamente)</li>
                                            <li><strong>✅ PGClone ativado</strong> (isinfluencer = 1)</li>
                                            <li><strong>✅ iGameWin ativado</strong> (is_demo = 1)</li>
                                            <li>Debug disponível para cada conta criada</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-users"></i> Criar Contas Demo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resultado da Criação -->
                <?php if ($resultado && $resultado['sucesso'] > 0): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h4 class="card-title text-white mb-0">
                                    ✅ Contas Criadas: <?= $resultado['sucesso'] ?>
                                </h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($resultado['contas'] as $conta): ?>
                                    <div class="col-md-6">
                                        <div class="conta-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-user"></i> <?= $conta['username'] ?>
                                                    </h6>
                                                    <p class="mb-1">
                                                        <strong>Senha:</strong> 
                                                        <code><?= $conta['password'] ?></code>
                                                        <i class="fas fa-copy copy-btn ms-2" 
                                                            onclick="copiarTexto('<?= $conta['password'] ?>')" 
                                                            title="Copiar senha"></i>
                                                    </p>
                                                    <p class="mb-1">
                                                        <strong>Saldo:</strong> R$ <?= number_format($conta['saldo'], 2, ',', '.') ?>
                                                    </p>
                                                    <p class="mb-0">
                                                        <strong>ID:</strong> <?= $conta['id'] ?>
                                                    </p>
                                                </div>
                                                <span class="badge bg-success">DEMO</span>
                                            </div>
                                            
                                            <!-- Status PGClone -->
                                            <div class="mb-2">
                                                <small>
                                                    <strong>PGClone:</strong>
                                                    <?php if ($conta['influencer_status'] === 'ativo'): ?>
                                                        <span class="badge bg-success">✓ Ativado</span>
                                                    <?php elseif ($conta['influencer_status'] === 'erro'): ?>
                                                        <span class="badge bg-danger" title="<?= htmlspecialchars($conta['influencer_error'] ?? '') ?>">✗ Erro</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">⏳ Processando...</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <!-- Status iGameWin -->
                                            <div class="mb-2">
                                                <small>
                                                    <strong>iGameWin:</strong>
                                                    <?php if ($conta['igamewin_status'] === 'ativo'): ?>
                                                        <span class="badge bg-success">✓ Ativado</span>
                                                    <?php elseif ($conta['igamewin_status'] === 'erro'): ?>
                                                        <span class="badge bg-danger" title="<?= htmlspecialchars($conta['igamewin_error'] ?? '') ?>">✗ Erro</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">⏳ Processando...</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            
                                            <?php if (!empty($conta['game_url'])): ?>
                                            <div class="mt-2">
                                                <a href="<?= $conta['game_url'] ?>" target="_blank" class="btn btn-sm btn-primary w-100">
                                                    <i class="fas fa-gamepad"></i> Abrir Fortune Tiger
                                                </a>
                                            </div>
                                            <?php elseif (isset($conta['game_url_error'])): ?>
                                            <div class="mt-2">
                                                <small class="text-danger">❌ <?= htmlspecialchars($conta['game_url_error']) ?></small>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($conta['game_url_igamewin'])): ?>
                                            <div class="mt-2">
                                                <a href="<?= $conta['game_url_igamewin'] ?>" target="_blank" class="btn btn-sm btn-success w-100">
                                                    <i class="fas fa-gamepad"></i> Abrir Sweet Kingdom
                                                </a>
                                            </div>
                                            <?php elseif (isset($conta['game_url_igamewin_error'])): ?>
                                            <div class="mt-2">
                                                <small class="text-danger">❌ <?= htmlspecialchars($conta['game_url_igamewin_error']) ?></small>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-3">
                                    <button class="btn btn-success" onclick="exportarContas()">
                                        <i class="fas fa-download"></i> Exportar Lista
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contas Demo Existentes -->
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Últimas 50 Contas Demo</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Senha</th>
                                                <th>Saldo</th>
                                                <th>Status</th>
                                                <th>Data Criação</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($contas_demo)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">Nenhuma conta demo encontrada</td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($contas_demo as $conta): ?>
                                            <tr>
                                                <td><?= $conta['id'] ?></td>
                                                <td><code><?= $conta['mobile'] ?></code></td>
                                                <td>
                                                    <code><?= $conta['password'] ?></code>
                                                    <i class="fas fa-copy copy-btn ms-1" 
                                                        onclick="copiarTexto('<?= $conta['password'] ?>')" 
                                                        title="Copiar"></i>
                                                </td>
                                                <td>R$ <?= number_format($conta['saldo'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if ($conta['statusaff'] == 1): ?>
                                                        <span class="badge bg-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inativo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($conta['data_registro'])) ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" 
                                                        onclick="deletarConta(<?= $conta['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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

    <script>
        function showToast(type, message){window.showToast(type,message);}

        function copiarTexto(texto) {
            navigator.clipboard.writeText(texto).then(() => {
                showToast('success', 'Copiado: ' + texto);
            }).catch(() => {
                showToast('error', 'Erro ao copiar');
            });
        }

        function exportarContas() {
            const contas = <?= json_encode($resultado['contas'] ?? []) ?>;
            let texto = "CONTAS DEMO CRIADAS - UNIFICADAS\n\n";
            
            contas.forEach((conta, index) => {
                texto += `Conta ${index + 1}:\n`;
                texto += `Username: ${conta.username}\n`;
                texto += `Senha: ${conta.password}\n`;
                texto += `Saldo: R$ ${conta.saldo}\n`;
                texto += `\nPGClone: ${conta.influencer_status}\n`;
                if (conta.game_url) {
                    texto += `Link Fortune Tiger: ${conta.game_url}\n`;
                }
                texto += `\niGameWin: ${conta.igamewin_status}\n`;
                if (conta.game_url_igamewin) {
                    texto += `Link Sweet Kingdom: ${conta.game_url_igamewin}\n`;
                }
                texto += '\n';
            });
            
            const blob = new Blob([texto], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'contas_demo_' + new Date().getTime() + '.txt';
            a.click();
            window.URL.revokeObjectURL(url);
            
            showToast('success', 'Arquivo baixado!');
        }

        function abrirJogoConta(username, saldo, token) {
            showToast('info', 'Abrindo jogo...');
        }

        function deletarConta(id) {
            if (confirm('Deseja realmente deletar esta conta demo?')) {
                fetch('ajax/deletar_conta_demo.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', 'Conta deletada!');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('error', 'Erro ao deletar conta');
                    }
                });
            }
        }
    </script>

    <?php if ($toastType && $toastMessage): ?>
        <script>
            showToast('<?= $toastType ?>', '<?= $toastMessage ?>');
        </script>
    <?php endif; ?>

</body>
</html>
