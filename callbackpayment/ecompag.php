<?php
// ============================================================
// ECOMPAG WEBHOOK
// ============================================================

// Log function FIRST — before anything else, so we always get logs
function log_ecompag($msg) {
    $logfile = __DIR__ . '/ecompag.log';
    $date    = date('d-M-Y H:i:s T');
    file_put_contents($logfile, "[$date] $msg" . PHP_EOL, FILE_APPEND);
}

// Capturar erros fatais no log
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        log_ecompag("FATAL ERROR: " . $err['message'] . " em " . $err['file'] . " linha " . $err['line']);
    }
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    log_ecompag("PHP ERROR [$errno]: $errstr em $errfile linha $errline");
    return false;
});

log_ecompag("=== Webhook recebido ===");

// Ler payload bruto logo de cara
$raw_input = file_get_contents("php://input");
log_ecompag("Payload RAW: " . ($raw_input ?: '(vazio)'));
log_ecompag("Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
log_ecompag("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'N/A'));

// Includes após o log estar funcional
try {
    include_once "../config.php";
    include_once('../'.DASH.'/services/database.php');
    include_once('../'.DASH.'/services/funcao.php');
    include_once('../'.DASH.'/services/crud.php');
    include_once('../'.DASH.'/services/afiliacao.php');
    log_ecompag("Includes carregados com sucesso.");
} catch (Throwable $t) {
    log_ecompag("ERRO nos includes: " . $t->getMessage());
    http_response_code(500);
    exit;
}

global $mysqli;

if (!$mysqli) {
    log_ecompag("ERRO: \$mysqli não disponível após includes.");
    http_response_code(500);
    exit;
}

// Decodificar JSON
$data = json_decode($raw_input, true);
if ($data === null) {
    log_ecompag("ERRO JSON decode: " . json_last_error_msg() . " | Raw: $raw_input");
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'JSON inválido']);
    exit;
}

// Extrair campos — sem depender de PHP_SEGURO() para não morrer se não existir
$idTransaction     = trim($data['transactionId']   ?? '');
$typeTransaction   = trim($data['transactionType'] ?? '');
$statusTransaction = trim($data['status']          ?? '');

log_ecompag("Parsed: ID=$idTransaction | Type=$typeTransaction | Status=$statusTransaction");

// ============================================================
// FUNÇÕES
// ============================================================

function verificarBonus($userId, $valorPago, $transacao_id = null) {
    global $mysqli;
    log_ecompag("verificarBonus: User=$userId, Valor=$valorPago, TransID=$transacao_id");

    $sqlCount = "SELECT COUNT(*) as total FROM transacoes WHERE usuario = ? AND tipo = 'deposito' AND status = 'pago'";
    $stmtCount = $mysqli->prepare($sqlCount);
    $stmtCount->bind_param("i", $userId);
    $stmtCount->execute();
    $totalPaid = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    log_ecompag("verificarBonus: Total depósitos pagos: $totalPaid");
    if ($totalPaid > 1) return 0;

    $tagValue = 0; $payTypeId = 0;

    if ($transacao_id) {
        $st = $mysqli->prepare("SELECT pay_type_sub_list_id, join_bonus FROM transacoes WHERE transacao_id = ?");
        $st->bind_param("s", $transacao_id);
        $st->execute();
        if ($row = $st->get_result()->fetch_assoc()) {
            $payTypeId = $row['pay_type_sub_list_id'];
            if (isset($row['join_bonus']) && $row['join_bonus'] != 1) {
                log_ecompag("verificarBonus: join_bonus != 1. Sem bônus.");
                $st->close(); return 0;
            }
        }
        $st->close();
    }

    if ($payTypeId) {
        $st = $mysqli->prepare("SELECT tag_value, bonus_active FROM pay_type_sub_list WHERE id = ?");
        $st->bind_param("i", $payTypeId);
        $st->execute();
        if ($row = $st->get_result()->fetch_assoc()) {
            if ($row['bonus_active'] == 1) {
                $tagValue = ($valorPago * floatval($row['tag_value'])) / 100;
                log_ecompag("verificarBonus: Bônus por ID: $tagValue");
            }
        }
        $st->close();
    } else {
        $st = $mysqli->prepare("SELECT tag_value FROM pay_type_sub_list WHERE status=1 AND bonus_active=1 AND ?>=min_amount AND ?<=max_amount ORDER BY id DESC LIMIT 1");
        $st->bind_param("dd", $valorPago, $valorPago);
        $st->execute();
        if ($row = $st->get_result()->fetch_assoc()) {
            $tagValue = ($valorPago * floatval($row['tag_value'])) / 100;
            log_ecompag("verificarBonus: Bônus genérico: $tagValue");
        } else {
            log_ecompag("verificarBonus: Nenhuma regra encontrada.");
        }
        $st->close();
    }

    log_ecompag("verificarBonus: Retornando $tagValue");
    return $tagValue;
}

function registrarBonusUsado($userId, $valorPago, $bonusRecebido) {
    global $mysqli;
    if ($bonusRecebido <= 0) return false;
    $st = $mysqli->prepare("INSERT INTO cupom_usados (id_user, valor, bonus, data_registro) VALUES (?, ?, ?, NOW())");
    $st->bind_param("idi", $userId, $valorPago, $bonusRecebido);
    return $st->execute();
}

function adicionarSaldoUsuarioWebhook($id_user, $valor) {
    global $mysqli;
    log_ecompag("adicionarSaldo: User=$id_user, Valor=$valor");

    $st = $mysqli->prepare("SELECT saldo FROM usuarios WHERE id = ?");
    if (!$st) { log_ecompag("ERRO prepare SELECT saldo: " . $mysqli->error); return false; }
    $st->bind_param("i", $id_user);
    $st->execute();
    $res = $st->get_result();
    if ($res->num_rows === 0) { log_ecompag("ERRO: usuário $id_user não encontrado."); $st->close(); return false; }
    $saldo_atual = floatval($res->fetch_assoc()['saldo']);
    $novo_saldo  = $saldo_atual + floatval($valor);
    $st->close();

    log_ecompag("adicionarSaldo: Atual=$saldo_atual | Novo=$novo_saldo");

    $st2 = $mysqli->prepare("UPDATE usuarios SET saldo = ? WHERE id = ?");
    if (!$st2) { log_ecompag("ERRO prepare UPDATE saldo: " . $mysqli->error); return false; }
    $st2->bind_param("di", $novo_saldo, $id_user);
    if (!$st2->execute()) { log_ecompag("ERRO execute UPDATE saldo: " . $st2->error); $st2->close(); return false; }
    $st2->close();

    $dr  = date('Y-m-d H:i:s');
    $tp  = 'deposito';
    $st3 = $mysqli->prepare("INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)");
    if ($st3) { $st3->bind_param("idss", $id_user, $valor, $tp, $dr); $st3->execute(); $st3->close(); }

    log_ecompag("adicionarSaldo: Sucesso. Novo saldo=$novo_saldo");
    return true;
}

function busca_valor_ipn($transacao_id) {
    global $mysqli;
    $res = mysqli_query($mysqli, "SELECT usuario, valor FROM transacoes WHERE transacao_id='" . mysqli_real_escape_string($mysqli, $transacao_id) . "'");
    if (!$res || mysqli_num_rows($res) === 0) {
        log_ecompag("busca_valor_ipn: Transação '$transacao_id' NÃO encontrada no banco.");
        return false;
    }
    $row       = mysqli_fetch_assoc($res);
    $userId    = $row['usuario'];
    $valorPago = $row['valor'];
    log_ecompag("busca_valor_ipn: Transação encontrada. User=$userId, Valor=$valorPago");

    $bonus      = verificarBonus($userId, $valorPago, $transacao_id);
    $valorTotal = $valorPago + $bonus;
    log_ecompag("busca_valor_ipn: Bônus=$bonus | Total=$valorTotal");

    $ok = adicionarSaldoUsuarioWebhook($userId, $valorTotal);

    if ($ok) {
        if (function_exists('criarAuditFlowDeposito')) {
            criarAuditFlowDeposito($userId, $valorPago);
            log_ecompag("AuditFlow criado.");
        }
        if ($bonus > 0) { registrarBonusUsado($userId, $valorPago, $bonus); log_ecompag("Bônus registrado."); }

        if (function_exists('processarTodasComissoes')) {
            try {
                $r = processarTodasComissoes($userId, $valorPago);
                log_ecompag("processarTodasComissoes: " . ($r ? 'TRUE' : 'FALSE'));
            } catch (Throwable $t) { log_ecompag("ERRO processarTodasComissoes: " . $t->getMessage()); }
        } else {
            log_ecompag("AVISO: processarTodasComissoes não encontrada.");
        }

        $su = $mysqli->prepare("SELECT username FROM usuarios WHERE id = ?");
        if ($su) {
            $su->bind_param("i", $userId); $su->execute();
            $urow = $su->get_result()->fetch_assoc(); $su->close();
            if (function_exists('WebhookPixPagos')) {
                WebhookPixPagos($urow['username'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $valorPago);
                log_ecompag("WebhookPixPagos enviado.");
            }
        }
    } else {
        log_ecompag("ERRO: adicionarSaldoUsuarioWebhook retornou FALSE.");
    }

    return $ok;
}

function att_paymentpix($transacao_id) {
    global $mysqli;

    $sc = $mysqli->prepare("SELECT status FROM transacoes WHERE transacao_id = ?");
    if ($sc) {
        $sc->bind_param("s", $transacao_id); $sc->execute();
        if ($row = $sc->get_result()->fetch_assoc()) {
            if ($row['status'] === 'pago') {
                log_ecompag("Transação '$transacao_id' já estava paga. Ignorando duplicata.");
                $sc->close(); return 2;
            }
        }
        $sc->close();
    }

    $up = $mysqli->prepare("UPDATE transacoes SET status='pago' WHERE transacao_id=?");
    $up->bind_param("s", $transacao_id);
    if ($up->execute()) {
        log_ecompag("Status atualizado para 'pago'. Processando crédito...");
        return busca_valor_ipn($transacao_id) ? 1 : 0;
    } else {
        log_ecompag("ERRO UPDATE transacao: " . $mysqli->error);
        return 0;
    }
}

// ============================================================
// PROCESSAMENTO
// ============================================================

if ($idTransaction && strtoupper($typeTransaction) === 'RECEIVEPIX' && strtoupper($statusTransaction) === 'PAID') {
    log_ecompag("Condição RECEIVEPIX+PAID satisfeita. Processando ID=$idTransaction");
    $resultado = att_paymentpix($idTransaction);
    log_ecompag("att_paymentpix retornou: $resultado");

    if ($resultado == 2) {
        // Já pago — só dispara webhook de notificação
        $q = $mysqli->prepare("SELECT t.usuario, t.valor, u.username as nome FROM transacoes t JOIN usuarios u ON u.id=t.usuario WHERE t.transacao_id=? LIMIT 1");
        if ($q) {
            $q->bind_param("s", $idTransaction); $q->execute();
            if ($row = $q->get_result()->fetch_assoc()) {
                if (function_exists('WebhookPixPagos'))
                    WebhookPixPagos($row['username'] ?? 'Usuário', $_SERVER['HTTP_HOST'], $row['valor'] ?? 0);
            }
            $q->close();
        }
    }
} else {
    log_ecompag("Condição NÃO satisfeita — nenhuma ação tomada.");
    log_ecompag("  Esperado: Type=RECEIVEPIX, Status=PAID");
    log_ecompag("  Recebido: Type=$typeTransaction, Status=$statusTransaction, ID=$idTransaction");
}

http_response_code(200);
echo json_encode(['status' => 'success']);
log_ecompag("Resposta 200 enviada. Fim do processamento.");