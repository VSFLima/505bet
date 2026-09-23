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

# Função para buscar todos os jogos da tabela games com paginação e pesquisa
function get_games($limit, $offset, $search = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $qry = "SELECT * FROM games 
            WHERE game_name LIKE '%$search%' 
            AND api = 'iGameWin' 
            LIMIT $limit OFFSET $offset";
    $result = mysqli_query($mysqli, $qry);
    $games = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = $row;
    }
    return $games;
}

# Função para contar o total de jogos com pesquisa
function count_games($search = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $qry = "SELECT COUNT(*) as total 
            FROM games 
            WHERE game_name LIKE '%$search%' 
            AND api = 'iGameWin'";
    $result = mysqli_query($mysqli, $qry);
    return mysqli_fetch_assoc($result)['total'];
}

# Função para obter estatísticas dos jogos
function get_games_stats($search = '')
{
    global $mysqli;
    $search = $mysqli->real_escape_string($search);
    $stats = [
        'total' => 0,
        'active' => 0,
        'popular' => 0,
        'providers' => 0
    ];
    
    // Total de jogos
    $qry = "SELECT COUNT(*) as total FROM games WHERE api = 'iGameWin' AND game_name LIKE '%$search%'";
    $result = mysqli_query($mysqli, $qry);
    $stats['total'] = mysqli_fetch_assoc($result)['total'];
    
    // Jogos ativos
    $qry = "SELECT COUNT(*) as active FROM games WHERE api = 'iGameWin' AND status = 1 AND game_name LIKE '%$search%'";
    $result = mysqli_query($mysqli, $qry);
    $stats['active'] = mysqli_fetch_assoc($result)['active'];
    
    // Jogos populares
    $qry = "SELECT COUNT(*) as popular FROM games WHERE api = 'iGameWin' AND popular = 1 AND game_name LIKE '%$search%'";
    $result = mysqli_query($mysqli, $qry);
    $stats['popular'] = mysqli_fetch_assoc($result)['popular'];
    
    // Número de provedores únicos
    $qry = "SELECT COUNT(DISTINCT provider) as providers FROM games WHERE api = 'iGameWin' AND game_name LIKE '%$search%'";
    $result = mysqli_query($mysqli, $qry);
    $stats['providers'] = mysqli_fetch_assoc($result)['providers'];
    
    return $stats;
}

# Função para atualizar os dados do jogo
function update_game($data)
{
    global $mysqli;
    
    $data['type'] = 'slot';  // Valor fixo para 'Tipo'
    $data['game_type'] = 3;  // Valor fixo para 'Game Type'

    $qry = $mysqli->prepare("UPDATE games SET 
        game_name = ?, 
        game_code = ?, 
        banner = ?, 
        provider = ?, 
        type = ?, 
        game_type = ?, 
        api = ? 
        WHERE id = ?");
    
    $qry->bind_param(
        "sssssssi", 
        $data['game_name'], 
        $data['game_code'], 
        $data['banner'], 
        $data['provider'], 
        $data['type'], 
        $data['game_type'], 
        $data['api'], 
        $data['id']
    );
    
    return $qry->execute();
}

function add_game($data)
{
    global $mysqli;
    $data['status'] = 1;

    $qry = $mysqli->prepare("INSERT INTO games (game_name, game_code, banner, provider, type, game_type, api, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$qry) {
        var_dump($mysqli->error);
        die("Erro ao preparar a consulta.");
    }

    $qry->bind_param(
        "ssssssss",
        $data['game_name'],
        $data['game_code'],
        $data['banner'],
        $data['provider'],
        $data['type'],
        $data['game_type'],
        $data['api'],
        $data['status']
    );

    if (!$qry->execute()) {
        var_dump($qry->error);
        die("Erro ao executar a consulta.");
    }

    return true;
}

function delete_game($id)
{
    global $mysqli;
    $qry = $mysqli->prepare("DELETE FROM games WHERE id = ?");
    $qry->bind_param("i", $id);
    return $qry->execute();
}

$toastType = null; 
$toastMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_game'])) {
        $game_id = intval($_POST['id']);
        if (delete_game($game_id)) {
            $toastType = 'success';
            $toastMessage = 'Jogo excluído com sucesso!';
        } else {
            $toastType = 'error';
            $toastMessage = 'Erro ao excluir o jogo. Tente novamente.';
        }
    } else {
        $data = [
            'game_name' => $_POST['game_name'],
            'game_code' => $_POST['game_code'],
            'banner' => $_POST['banner'],
            'provider' => $_POST['provider'],
            'type' => $_POST['type'],
            'game_type' => $_POST['game_type'],
            'api' => $_POST['api'],
        ];

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $data['id'] = intval($_POST['id']);
            if (update_game($data)) {
                $toastType = 'success';
                $toastMessage = 'Jogo atualizado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao atualizar o jogo. Tente novamente.';
            }
        } else {
            if (add_game($data)) {
                $toastType = 'success';
                $toastMessage = 'Jogo adicionado com sucesso!';
            } else {
                $toastType = 'error';
                $toastMessage = 'Erro ao adicionar o jogo. Tente novamente.';
            }
        }
    }
}

# Configurações de paginação e pesquisa
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 8;  // Número de jogos por página
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$total_games = count_games($search);
$total_pages = ceil($total_games / $limit);

# Buscar jogos com a pesquisa
$games = get_games($limit, $offset, $search);
$games_stats = get_games_stats($search);
?>

<head>
    <?php $title = "Gerenciamento de Jogos iGameWin";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php include 'partials/head-css.php' ?>

    <style>
        :root {
            --secondary-bg: #303231;
            --accent-color: #8b5cf6;
            --secondary-accent: #10b981;
            --info-accent: #3b82f6;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --border-color: #4b5563;
            --hover-bg: rgba(75, 85, 99, 0.3);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-success: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --gradient-info: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            --gradient-warning: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --gradient-purple: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            color: var(--text-primary) !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .page-wrapper {
            background: var(--primary-bg) !important;
            min-height: 100vh;
        }

        .page-content {
            background: var(--primary-bg) !important;
            min-height: 100vh;
            padding: 20px;
        }

        /* Header Section */
        .games-header {
            background: var(--gradient-purple);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 32px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(162, 155, 254, 0.3);
        }

        .games-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="3" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="30" r="2" fill="rgba(255,255,255,0.05)"/></svg>') repeat;
            animation: gameFloat 20s ease-in-out infinite;
        }

        @keyframes gameFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(180deg); }
        }

        .games-header h4 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 2;
        }

        .games-header p {
            margin: 16px 0 0 0;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        /* Stats Overview */
        .games-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--secondary-bg) !important;
            border-radius: 20px;
            padding: 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 2px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(162, 155, 254, 0.4);
            border-color: var(--accent-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            border-radius: 20px 20px 0 0;
        }

        .stat-card.total::before {
            background: var(--gradient-purple);
        }

        .stat-card.active::before {
            background: var(--gradient-success);
        }

        .stat-card.popular::before {
            background: var(--gradient-warning);
        }

        .stat-card.providers::before {
            background: var(--gradient-info);
        }

        .stat-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            position: relative;
        }

        .stat-icon.total {
            background: var(--gradient-purple);
        }

        .stat-icon.active {
            background: var(--gradient-success);
        }

        .stat-icon.popular {
            background: var(--gradient-warning);
        }

        .stat-icon.providers {
            background: var(--gradient-info);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--text-primary) !important;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary) !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        /* Search Bar */
        .search-container {
            background: var(--secondary-bg);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 32px;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .search-container:hover {
            border-color: var(--accent-color);
            box-shadow: 0 8px 25px rgba(162, 155, 254, 0.2);
        }

        .search-form {
            width: 100%;
        }

        .search-wrapper {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-group {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--primary-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            transition: all 0.3s ease;
            min-width: 300px;
            position: relative;
            overflow: hidden;
        }

        .search-input-group:focus-within {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(162, 155, 254, 0.25);
            transform: translateY(-2px);
        }

        .search-icon {
            padding: 0 16px;
            color: var(--text-secondary);
            font-size: 18px;
            display: flex;
            align-items: center;
        }

        .search-input-modern {
            flex: 1;
            background: transparent !important;
            border: none !important;
            color: var(--text-primary) !important;
            padding: 16px 0 !important;
            font-size: 16px;
            outline: none;
        }

        .search-input-modern::placeholder {
            color: var(--text-secondary) !important;
            font-style: italic;
        }

        .btn-search {
            background: var(--gradient-purple) !important;
            border: none !important;
            color: white !important;
            padding: 12px 20px !important;
            border-radius: 0 10px 10px 0 !important;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 60px;
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #6c5ce7, #5a52d5) !important;
            transform: scale(1.05);
        }

        .btn-search::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-search:hover::before {
            left: 100%;
        }

        .btn-add {
            background: var(--gradient-success) !important;
            border: none !important;
            color: white !important;
            padding: 16px 24px !important;
            border-radius: 12px !important;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #059669, #047857) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
        }

        /* Responsive adjustments for search */
        @media (max-width: 768px) {
            .search-wrapper {
                flex-direction: column;
                gap: 12px;
            }

            .search-input-group {
                min-width: 100%;
                order: 1;
            }

            .btn-add {
                width: 100%;
                justify-content: center;
                order: 2;
            }

            .btn-text {
                display: inline;
            }
        }

        @media (max-width: 480px) {
            .search-container {
                padding: 16px;
            }

            .search-input-group {
                min-width: 100%;
            }

            .btn-search .btn-text,
            .btn-add .btn-text {
                display: none;
            }

            .btn-search,
            .btn-add {
                padding: 16px !important;
            }
        }

        /* Cards and Tables */
        .games-card {
            background: var(--secondary-bg) !important;
            border: 2px solid var(--border-color) !important;
            border-radius: 20px !important;
            margin-bottom: 32px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .games-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 50px rgba(162, 155, 254, 0.3);
            border-color: var(--accent-color);
        }

        .games-card-header {
            background: var(--gradient-purple);
            padding: 24px 32px;
            position: relative;
            overflow: hidden;
        }

        .games-card-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="25" fill="rgba(255,255,255,0.1)"/></svg>');
            transform: translate(30%, -30%);
        }

        .games-card-title {
            color: white !important;
            font-size: 24px !important;
            font-weight: 800 !important;
            margin: 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 2;
        }

        .games-card-body {
            background: var(--secondary-bg) !important;
            padding: 32px !important;
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 16px;
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            /* Remover scrollbar completamente */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE e Edge */
        }

        /* Remover scrollbar do WebKit (Chrome, Safari) */
        .table-responsive::-webkit-scrollbar {
            display: none;
        }

        .table {
            margin: 0 !important;
            color: var(--text-primary) !important;
            background: transparent !important;
        }

        .table thead th {
            background: var(--primary-bg) !important;
            border: none !important;
            color: var(--text-primary) !important;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 20px 16px !important;
            border-bottom: 2px solid var(--border-color) !important;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table tbody td {
            background: var(--secondary-bg) !important;
            border: none !important;
            color: var(--text-primary) !important;
            padding: 16px !important;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color) !important;
            white-space: nowrap;
        }

        .table tbody tr:hover {
            background: rgba(162, 155, 254, 0.1) !important;
        }

        .table tbody tr:last-child td {
            border-bottom: none !important;
        }

        /* Mobile table improvements */
        @media (max-width: 768px) {
            .table {
                min-width: 900px; /* Força scroll horizontal no mobile */
                font-size: 14px;
            }

            .table thead th {
                padding: 12px 8px !important;
                font-size: 12px;
            }

            .table tbody td {
                padding: 12px 8px !important;
            }

            .game-preview {
                width: 60px;
                height: 60px;
            }

            .form-check-group {
                flex-direction: column;
                gap: 4px;
            }

            .form-check-inline {
                margin-bottom: 0;
            }

            .d-flex.gap-2 {
                flex-direction: column;
                gap: 4px !important;
            }

            .btn-sm {
                padding: 6px 12px !important;
                font-size: 12px !important;
            }
        }

        @media (max-width: 480px) {
            .table {
                min-width: 800px;
            }

            .table thead th,
            .table tbody td {
                padding: 8px 6px !important;
            }

            .game-preview {
                width: 50px;
                height: 50px;
            }

            .badge {
                font-size: 9px !important;
                padding: 3px 6px !important;
            }
        }

        /* Desktop */
        @media (min-width: 769px) {
            .table {
                width: 100%; /* Tabela ocupa largura total no desktop */
            }
        }

        /* Game Cards Grid */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }

        .game-card {
            background: var(--secondary-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }

        .game-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(162, 155, 254, 0.3);
            border-color: var(--accent-color);
        }

        .game-banner {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-bottom: 2px solid var(--border-color);
        }

        .game-content {
            padding: 20px;
        }

        .game-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .game-provider {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .game-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .game-status {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .status-group {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        /* Form Controls */
        .form-control, .form-select {
            background: var(--primary-bg) !important;
            border: 2px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            border-radius: 8px !important;
            padding: 12px 16px !important;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: var(--primary-bg) !important;
            border-color: var(--accent-color) !important;
            color: var(--text-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(162, 155, 254, 0.25);
        }

        .form-control::placeholder {
            color: var(--text-secondary) !important;
        }

        .form-label {
            color: var(--text-primary) !important;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Radio Buttons */
        .form-check-group {
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
        }

        .form-check-inline {
            margin-right: 0 !important;
        }

        .form-check-input[type="radio"] {
            background-color: var(--primary-bg);
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            width: 18px;
            height: 18px;
        }

        .form-check-input[type="radio"]:checked {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
        }

        .form-check-input[type="radio"]:focus {
            box-shadow: 0 0 0 0.25rem rgba(162, 155, 254, 0.25);
        }

        .form-check-label {
            color: var(--text-primary) !important;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 6px;
        }

        .form-check-label .badge {
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .form-check-input[type="radio"]:checked + .form-check-label .badge {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        /* Buttons */
        .btn {
            border-radius: 8px !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            padding: 12px 20px !important;
            font-size: 14px !important;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-purple) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(162, 155, 254, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(162, 155, 254, 0.6) !important;
        }

        .btn-success {
            background: var(--gradient-success) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6) !important;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #dc2626) !important;
            border: none !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }

        .btn-danger:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.6) !important;
        }

        .btn-secondary {
            background: var(--border-color) !important;
            border: none !important;
            color: var(--text-primary) !important;
        }

        .btn-secondary:hover {
            background: #6b7280 !important;
            transform: translateY(-2px) !important;
        }

        /* Modal Styling */
        .modal-content {
            background: var(--secondary-bg) !important;
            border: 2px solid var(--border-color) !important;
            border-radius: 16px !important;
        }

        .modal-header {
            background: var(--secondary-bg) !important;
            border-bottom: 2px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .modal-body {
            background: var(--secondary-bg) !important;
            color: var(--text-primary) !important;
        }

        .modal-footer {
            background: var(--secondary-bg) !important;
            border-top: 2px solid var(--border-color) !important;
        }

        .modal-title {
            color: var(--text-primary) !important;
            font-weight: 700;
        }

        .btn-close {
            background: transparent !important;
            border: none !important;
            color: var(--text-primary) !important;
            opacity: 0.8;
        }

        .btn-close:hover {
            opacity: 1;
            color: var(--danger-color) !important;
        }

        /* Pagination */
        .pagination .page-link {
            background: var(--secondary-bg) !important;
            border: 2px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            margin: 0 2px !important;
            border-radius: 8px !important;
            transition: all 0.3s ease;
        }

        .pagination .page-link:hover {
            background: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            color: white !important;
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: var(--accent-color) !important;
            border-color: var(--accent-color) !important;
            color: white !important;
        }

        /* Toast Styling */
        .toast {
            background: var(--secondary-bg) !important;
            border: 2px solid var(--border-color) !important;
            border-radius: 12px !important;
            color: var(--text-primary) !important;
        }

        .toast-header {
            background: var(--secondary-bg) !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
        }

        .toast-body {
            color: var(--text-primary) !important;
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
        }

        .fade-in-delay-1 {
            animation: fadeInUp 0.6s ease 0.1s forwards;
            opacity: 0;
        }

        .fade-in-delay-2 {
            animation: fadeInUp 0.6s ease 0.2s forwards;
            opacity: 0;
        }

        .fade-in-delay-3 {
            animation: fadeInUp 0.6s ease 0.3s forwards;
            opacity: 0;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-content {
                padding: 16px;
            }
            
            .games-header {
                padding: 24px;
            }
            
            .games-header h4 {
                font-size: 24px;
            }
            
            .games-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .games-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .games-card-body {
                padding: 20px !important;
            }
        }

        @media (max-width: 480px) {
            .games-stats {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
        }

        /* Game Preview */
        .game-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border-color);
        }

        /* Badge Styling */
        .badge {
            padding: 6px 12px !important;
            border-radius: 20px !important;
            font-size: 11px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bg-success {
            background: rgba(16, 185, 129, 0.2) !important;
            color: var(--success-color) !important;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .bg-danger {
            background: rgba(239, 68, 68, 0.2) !important;
            color: var(--danger-color) !important;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .bg-warning {
            background: rgba(245, 158, 11, 0.2) !important;
            color: var(--warning-color) !important;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
    </style>
</head>

<body>
    <?php include 'partials/topbar.php' ?>
    <?php include 'partials/startbar.php' ?>

    <div class="page-wrapper">
        <div class="page-content">
            <div class="container-xxl">

                <!-- Header -->
                <div class="games-header fade-in">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4>
                                <i class="bi bi-controller"></i>
                                Gerenciamento de Jogos iGameWin
                            </h4>
                            <p>Gerencie todos os jogos do provedor iGameWin • Total: <?= $total_games; ?> jogos disponíveis</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="games-stats">
                    <div class="stat-card total fade-in-delay-1">
                        <div class="stat-icon total">
                            <i class="bi bi-collection"></i>
                        </div>
                        <div class="stat-value"><?= $games_stats['total']; ?></div>
                        <div class="stat-label">Total de Jogos</div>
                    </div>
                    <div class="stat-card active fade-in-delay-2">
                        <div class="stat-icon active">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div class="stat-value"><?= $games_stats['active']; ?></div>
                        <div class="stat-label">Jogos Ativos</div>
                    </div>
                    <div class="stat-card popular fade-in-delay-3">
                        <div class="stat-icon popular">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <div class="stat-value"><?= $games_stats['popular']; ?></div>
                        <div class="stat-label">Jogos Populares</div>
                    </div>
                    <div class="stat-card providers fade-in-delay-1">
                        <div class="stat-icon providers">
                            <i class="bi bi-hdd-network"></i>
                        </div>
                        <div class="stat-value"><?= $games_stats['providers']; ?></div>
                        <div class="stat-label">Provedores</div>
                    </div>
                </div>

                <!-- Search and Actions -->
                <div class="search-container fade-in-delay-2">
                    <form method="GET" action="" class="search-form">
                        <div class="search-wrapper">
                            <div class="search-input-group">
                                <div class="search-icon">
                                    <i class="bi bi-search"></i>
                                </div>
                                <input type="text" name="search" class="search-input-modern" 
                                       placeholder="Pesquisar pelo nome do jogo..." 
                                       value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-search" type="submit">
                                    <i class="bi bi-search"></i>
                                    <span class="btn-text">Buscar</span>
                                </button>
                            </div>
                            <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addGameModal">
                                <i class="bi bi-plus-circle"></i>
                                <span class="btn-text">Adicionar</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Games Table -->
                <div class="games-card fade-in-delay-3">
                    <div class="games-card-header">
                        <div class="games-card-title">
                            <div>
                                <i class="bi bi-list-ul me-2"></i>
                                Lista de Jogos
                            </div>
                            <span class="badge bg-light text-dark"><?= count($games); ?> de <?= $total_games; ?></span>
                        </div>
                    </div>
                    <div class="games-card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Jogo</th>
                                        <th>Provedor</th>
                                        <th>Popular</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($games)): ?>
                                        <?php foreach ($games as $game): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= htmlspecialchars($game['banner']) ?>" 
                                                             alt="<?= htmlspecialchars($game['game_name']) ?>" 
                                                             class="game-preview"
                                                             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23374151%22/><text x=%2250%22 y=%2250%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22 font-size=%2212%22>IMG</text></svg>'">
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($game['game_name']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($game['game_code']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= htmlspecialchars($game['provider']) ?></span>
                                                </td>
                                                <td>
                                                    <div class="form-check-group">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="popular_<?= $game['id'] ?>" 
                                                                   id="popular_sim_<?= $game['id'] ?>" 
                                                                   value="1" 
                                                                   <?= $game['popular'] == 1 ? 'checked' : '' ?> 
                                                                   onclick="updateGameSetting(<?= $game['id'] ?>, 'popular', 1)">
                                                            <label class="form-check-label" for="popular_sim_<?= $game['id'] ?>">
                                                                <span class="badge bg-success">Sim</span>
                                                            </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="popular_<?= $game['id'] ?>" 
                                                                   id="popular_nao_<?= $game['id'] ?>" 
                                                                   value="0" 
                                                                   <?= $game['popular'] == 0 ? 'checked' : '' ?> 
                                                                   onclick="updateGameSetting(<?= $game['id'] ?>, 'popular', 0)">
                                                            <label class="form-check-label" for="popular_nao_<?= $game['id'] ?>">
                                                                <span class="badge bg-danger">Não</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="form-check-group">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="status_<?= $game['id'] ?>" 
                                                                   id="status_ativo_<?= $game['id'] ?>" 
                                                                   value="1" 
                                                                   <?= $game['status'] == 1 ? 'checked' : '' ?> 
                                                                   onclick="updateGameSetting(<?= $game['id'] ?>, 'status', 1)">
                                                            <label class="form-check-label" for="status_ativo_<?= $game['id'] ?>">
                                                                <span class="badge bg-success">Ativo</span>
                                                            </label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" 
                                                                   name="status_<?= $game['id'] ?>" 
                                                                   id="status_inativo_<?= $game['id'] ?>" 
                                                                   value="0" 
                                                                   <?= $game['status'] == 0 ? 'checked' : '' ?> 
                                                                   onclick="updateGameSetting(<?= $game['id'] ?>, 'status', 0)">
                                                            <label class="form-check-label" for="status_inativo_<?= $game['id'] ?>">
                                                                <span class="badge bg-danger">Inativo</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-primary btn-sm" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editGameModal<?= $game['id'] ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-danger btn-sm" 
                                                                onclick="confirmDelete(<?= $game['id'] ?>, '<?= addslashes($game['game_name']) ?>')">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="bi bi-controller" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 12px;"></i>
                                                <strong>Nenhum jogo encontrado</strong><br>
                                                <small class="text-muted">
                                                    <?= $search ? 'Tente uma pesquisa diferente' : 'Adicione seu primeiro jogo iGameWin' ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?= $page == 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" aria-label="Página anterior">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                            
                                    <?php
                                    $range = 2;
                                    $start = max(1, $page - $range);
                                    $end = min($total_pages, $page + $range);
                            
                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                            
                                    <?php if ($end < $total_pages): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                            
                                    <li class="page-item <?= $page == $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" aria-label="Próxima página">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal de adição de jogo -->
                <div class="modal fade" id="addGameModal" tabindex="-1" aria-labelledby="addGameModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addGameModalLabel">
                                    <i class="bi bi-plus-circle me-2"></i>Adicionar Novo Jogo
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="POST" action="">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="game_name" class="form-label">
                                                    <i class="bi bi-controller me-1"></i>Nome do Jogo
                                                </label>
                                                <input type="text" name="game_name" class="form-control" 
                                                       placeholder="Ex: Fortune Tiger" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="game_code" class="form-label">
                                                    <i class="bi bi-code-slash me-1"></i>Código do Jogo
                                                </label>
                                                <input type="text" name="game_code" class="form-control" 
                                                       placeholder="Ex: fortune-tiger" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="provider" class="form-label">
                                                    <i class="bi bi-building me-1"></i>Provedor
                                                </label>
                                                <input type="text" name="provider" class="form-control" 
                                                       placeholder="Ex: PragmaticPlay" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label for="banner" class="form-label">
                                                    <i class="bi bi-image me-1"></i>URL do Banner
                                                </label>
                                                <input type="text" name="banner" class="form-control" 
                                                       placeholder="https://exemplo.com/banner.jpg" required>
                                                <div class="form-text">URL da imagem do banner do jogo</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x me-1"></i>Cancelar
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check me-1"></i>Adicionar Jogo
                                    </button>
                                </div>
                                <!-- Campos ocultos -->
                                <input type="hidden" name="api" value="iGameWin">
                                <input type="hidden" name="type" value="slot">
                                <input type="hidden" name="game_type" value="3">
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modais de Edição -->
                <?php foreach ($games as $game): ?>
                    <div class="modal fade" id="editGameModal<?= $game['id'] ?>" tabindex="-1" aria-labelledby="editGameModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editGameModalLabel">
                                        <i class="bi bi-pencil me-2"></i>Editar Jogo
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="game_name" class="form-label">
                                                        <i class="bi bi-controller me-1"></i>Nome do Jogo
                                                    </label>
                                                    <input type="text" name="game_name" class="form-control" 
                                                           value="<?= htmlspecialchars($game['game_name']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="game_code" class="form-label">
                                                        <i class="bi bi-code-slash me-1"></i>Código do Jogo
                                                    </label>
                                                    <input type="text" name="game_code" class="form-control" 
                                                           value="<?= htmlspecialchars($game['game_code']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="provider" class="form-label">
                                                        <i class="bi bi-building me-1"></i>Provedor
                                                    </label>
                                                    <input type="text" name="provider" class="form-control" 
                                                           value="<?= htmlspecialchars($game['provider']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="api" class="form-label">
                                                        <i class="bi bi-hdd-network me-1"></i>API Usada
                                                    </label>
                                                    <select name="api" class="form-control" required>
                                                        <option value="PGClone" <?= $game['api'] == 'PGClone' ? 'selected' : '' ?>>PGClone</option>
                                                        <option value="PlayFiver" <?= $game['api'] == 'PlayFiver' ? 'selected' : '' ?>>PlayFiver</option>
                                                        <option value="iGameWin" <?= $game['api'] == 'iGameWin' ? 'selected' : '' ?>>iGameWin</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="banner" class="form-label">
                                                        <i class="bi bi-image me-1"></i>URL do Banner
                                                    </label>
                                                    <input type="text" name="banner" class="form-control" 
                                                           value="<?= htmlspecialchars($game['banner']) ?>" required>
                                                    <div class="mt-2">
                                                        <img src="<?= htmlspecialchars($game['banner']) ?>" 
                                                             alt="Preview do Banner" 
                                                             class="img-thumbnail" 
                                                             style="max-width: 150px; max-height: 150px;"
                                                             onerror="this.style.display='none'">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            <i class="bi bi-x me-1"></i>Cancelar
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save me-1"></i>Salvar Alterações
                                        </button>
                                    </div>
                                    <!-- Campos ocultos -->
                                    <input type="hidden" name="type" value="slot">
                                    <input type="hidden" name="game_type" value="3">
                                    <input type="hidden" name="id" value="<?= $game['id'] ?>">
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div><!-- container -->
            
            <?php include 'partials/endbar.php' ?>
        </div><!-- page content -->
    </div><!-- page-wrapper -->


    <?php include 'partials/vendorjs.php' ?>
    <script src="assets/js/app.js"></script>

    <script>
        // Initialize animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats counters
            animateCounters();
            
            // Add loading animation to buttons on form submit
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        setLoadingState(submitBtn, true);
                    }
                });
            });

            // Add hover effects to game cards
            const gameCards = document.querySelectorAll('.game-card');
            gameCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-6px) scale(1.01)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Enhanced search functionality
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.addEventListener('input', debounce(function() {
                    // You can add real-time search here if needed
                }, 300));
            }
        });

        // Animate stat counters
        function animateCounters() {
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(stat => {
                const value = parseInt(stat.textContent);
                if (!isNaN(value) && value > 0) {
                    animateCounter(stat, value);
                }
            });
        }

        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 30;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 50);
        }

        // Enhanced loading states
        function setLoadingState(button, isLoading) {
            if (isLoading) {
                button.dataset.originalText = button.innerHTML;
                button.innerHTML = '<div class="spinner-border spinner-border-sm me-2" role="status"></div>Processando...';
                button.disabled = true;
                button.classList.add('loading');
            } else {
                button.innerHTML = button.dataset.originalText;
                button.disabled = false;
                button.classList.remove('loading');
            }
        }

        
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Confirm delete function
        function confirmDelete(gameId, gameName) {
            if (confirm(`Tem certeza que deseja excluir o jogo "${gameName}"?\n\nEsta ação não pode ser desfeita.`)) {
                // Create and submit form for deletion
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = gameId;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_game';
                deleteInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Update game setting function
        function updateGameSetting(gameId, field, value) {
            // Adicionar feedback visual imediato
            const radioButtons = document.querySelectorAll(`input[name="${field}_${gameId}"]`);
            radioButtons.forEach(radio => {
                if (radio.value == value) {
                    radio.checked = true;
                } else {
                    radio.checked = false;
                }
            });

            const formData = new FormData();
            formData.append('action', 'update_game_setting');
            formData.append('id', gameId);
            formData.append('field', field);
            formData.append('value', value);

            fetch('ajax/att_jogos_all.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message || 'Configuração atualizada com sucesso!');
                    
                    // Adicionar animação de sucesso
                    const targetRadio = document.querySelector(`#${field}_${value == 1 ? 'sim' : (field === 'status' ? 'ativo' : 'nao')}_${gameId}`);
                    if (targetRadio) {
                        const label = targetRadio.nextElementSibling;
                        if (label) {
                            label.style.animation = 'successPulse 0.6s ease';
                            setTimeout(() => {
                                label.style.animation = '';
                            }, 600);
                        }
                    }
                } else {
                    showToast('error', data.message || 'Erro ao atualizar a configuração.');
                    
                    // Reverter a mudança visual em caso de erro
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao tentar atualizar a configuração.');
                
                // Reverter a mudança visual em caso de erro
                location.reload();
            });
        }

        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
            
            .spinner-border-sm {
                width: 1rem;
                height: 1rem;
                border-width: 0.125em;
            }
            
            .loading {
                position: relative;
                pointer-events: none;
                opacity: 0.7;
            }
            
            .success-animation {
                animation: successPulse 0.6s ease;
            }
            
            @keyframes successPulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            /* Enhanced radio button styling */
            .form-check-input[type="radio"]:checked {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='2' fill='%23fff'/%3e%3c/svg%3e");
            }
            
            /* Smooth transitions for all interactive elements */
            .btn, .form-control, .form-select, .form-check-input, .stat-card, .game-card {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            /* Loading overlay */
            .table-loading {
                position: relative;
                opacity: 0.6;
                pointer-events: none;
            }
            
            .table-loading::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.1);
                border-radius: 16px;
                z-index: 1000;
            }
        `;
        document.head.appendChild(style);

        // Enhanced form validation
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = 'var(--danger-color)';
                    field.style.boxShadow = '0 0 0 0.25rem rgba(239, 68, 68, 0.25)';
                    isValid = false;
                    
                    setTimeout(() => {
                        field.style.borderColor = '';
                        field.style.boxShadow = '';
                    }, 3000);
                }
            });
            
            return isValid;
        }

        // Real-time banner preview
        document.addEventListener('DOMContentLoaded', function() {
            const bannerInputs = document.querySelectorAll('input[name="banner"]');
            bannerInputs.forEach(input => {
                input.addEventListener('input', function() {
                    const previewImg = this.parentNode.querySelector('img');
                    if (previewImg && this.value) {
                        previewImg.src = this.value;
                        previewImg.style.display = 'block';
                    }
                });
            });
        });

        // Enhanced table interactions
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.zIndex = '10';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.zIndex = 'auto';
                });
            });

            // Configurar scroll suave para mobile apenas
            const tableResponsive = document.querySelector('.table-responsive');
            if (tableResponsive) {
                // Adicionar touch feedback para mobile
                if ('ontouchstart' in window) {
                    tableResponsive.style.cursor = 'grab';
                    
                    tableResponsive.addEventListener('touchstart', function() {
                        this.style.cursor = 'grabbing';
                    });
                    
                    tableResponsive.addEventListener('touchend', function() {
                        this.style.cursor = 'grab';
                    });
                }
            }

            // Melhorar cliques em botões pequenos no mobile
            const smallButtons = document.querySelectorAll('.btn-sm');
            smallButtons.forEach(button => {
                button.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                    this.style.transform = 'scale(0.95)';
                    this.style.transition = 'transform 0.1s ease';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                    setTimeout(() => {
                        this.style.transition = '';
                    }, 100);
                });
            });
        });

        // Auto-refresh stats (optional)
        function refreshStats() {
            // This could fetch updated stats via AJAX
            // For now, just animate the counters again
            setTimeout(() => {
                animateCounters();
            }, 30000); // Refresh every 30 seconds
        }

        // Initialize refresh
        refreshStats();
    </script>


</body>
</html>