<?php
include_once __DIR__ . '/database.php';
include_once __DIR__ . '/crud.php';

function logAfiliacao($message) {
    // Caminho dinâmico para o arquivo de log na raiz (public/errorlog.log)
    $logFile = dirname(dirname(__DIR__)) . '/errorlog.log';
    $timestamp = date('d-M-Y H:i:s T');
    $formattedMessage = "[$timestamp] [AFILIACAO] $message" . PHP_EOL;
    
    // Tenta escrever no arquivo e também no log de erro do PHP como backup
    if (file_put_contents($logFile, $formattedMessage, FILE_APPEND) === false) {
        error_log("[AFILIACAO ERROR] Falha ao escrever no log: $logFile. Msg: $message");
    }
}

/**
 * Busca a configuração de afiliados
 */
function getAfiliadosConfig() {
    global $mysqli;
    $qry = "SELECT * FROM afiliados_config WHERE id = 1";
    $res = mysqli_query($mysqli, $qry);
    $config = mysqli_fetch_assoc($res);
    
    if (!$config) {
        logAfiliacao("ERRO: Configuração de afiliados não encontrada.");
    }
    
    return $config;
}

/**
 * Busca a hierarquia de afiliados de um usuário (até 3 níveis)
 * @param int $user_id ID do usuário que fez o depósito
 * @return array Array com os afiliados nos 3 níveis
 */
function getHierarquiaAfiliados($user_id) {
    global $mysqli;
    
    logAfiliacao("Buscando hierarquia para usuário ID: $user_id");
    
    $hierarquia = [
        'nivel1' => null,
        'nivel2' => null,
        'nivel3' => null
    ];

    $qry = "SELECT invitation_code FROM usuarios WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || !$user['invitation_code']) {
        logAfiliacao("Usuário $user_id não tem invitation_code ou não foi encontrado.");
        return $hierarquia;
    }
    
    logAfiliacao("Usuário $user_id convidado por código: " . $user['invitation_code']);

    $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
    $stmt = $mysqli->prepare($qry);
    $stmt->bind_param("s", $user['invitation_code']);
    $stmt->execute();
    $result = $stmt->get_result();
    $nivel1 = $result->fetch_assoc();
    
    if ($nivel1) {
        $hierarquia['nivel1'] = $nivel1;
        logAfiliacao("Nível 1 encontrado: ID " . $nivel1['id']);

        if ($nivel1['invitation_code']) {
            $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
            $stmt = $mysqli->prepare($qry);
            $stmt->bind_param("s", $nivel1['invitation_code']);
            $stmt->execute();
            $result = $stmt->get_result();
            $nivel2 = $result->fetch_assoc();
            
            if ($nivel2) {
                $hierarquia['nivel2'] = $nivel2;
                logAfiliacao("Nível 2 encontrado: ID " . $nivel2['id']);
                
                if ($nivel2['invitation_code']) {
                    $qry = "SELECT id, mobile, invite_code, invitation_code FROM usuarios WHERE invite_code = ?";
                    $stmt = $mysqli->prepare($qry);
                    $stmt->bind_param("s", $nivel2['invitation_code']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $nivel3 = $result->fetch_assoc();
                    
                    if ($nivel3) {
                        $hierarquia['nivel3'] = $nivel3;
                        logAfiliacao("Nível 3 encontrado: ID " . $nivel3['id']);
                    }
                }
            }
        }
    } else {
        logAfiliacao("Nenhum usuário encontrado com o código " . $user['invitation_code']);
    }
    
    return $hierarquia;
}

/**
 * Verifica se deve aplicar a comissão baseado na chance CPA
 * @param float $chanceCpa Porcentagem de chance (0-100)
 * @return bool
 */
function aplicarChanceCpa($chanceCpa) {
    if ($chanceCpa >= 100) {
        logAfiliacao("Chance CPA é 100% ou mais. Aprovado.");
        return true;
    }
    
    $random = mt_rand(1, 100);
    $aprovado = $random <= $chanceCpa;
    logAfiliacao("Sorteio Chance CPA: Random=$random, Chance=$chanceCpa. Resultado: " . ($aprovado ? "Aprovado" : "Reprovado"));
    return $aprovado;
}

/**
 * Credita comissão no saldo de afiliados
 * @param int $user_id ID do afiliado
 * @param float $valor Valor da comissão
 * @param int $nivel Nível da comissão (1, 2 ou 3)
 * @param int $depositante_id ID de quem fez o depósito
 * @param float $valor_deposito Valor original do depósito
 * @param float $porcentagem Porcentagem aplicada
 */
function creditarComissaoAfiliado($user_id, $valor, $nivel, $depositante_id, $valor_deposito, $porcentagem) {
    global $mysqli;
    
    logAfiliacao("Creditando comissão: Afiliado=$user_id, Valor=$valor, Nivel=$nivel, Deposito=$valor_deposito, %=$porcentagem");
    
    $qry = "UPDATE usuarios SET saldo_afiliados = saldo_afiliados + ? WHERE id = ?";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        logAfiliacao("ERRO Prepare Update Saldo: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("di", $valor, $user_id);
    
    if (!$stmt->execute()) {
        logAfiliacao("ERRO Execute Update Saldo: " . $stmt->error);
        return false;
    }

    $data_registro = date('Y-m-d H:i:s');
    $tipo = "comissao_cpa_nivel_{$nivel}";
    $valor_centavos = intval($valor * 100);
    
    $qry = "INSERT INTO adicao_saldo (id_user, valor, tipo, data_registro) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($qry);
    
    if (!$stmt) {
        logAfiliacao("ERRO Prepare Insert AdicaoSaldo: " . $mysqli->error);
        return false;
    }
    
    $stmt->bind_param("iiss", $user_id, $valor_centavos, $tipo, $data_registro);
    
    if (!$stmt->execute()) {
        logAfiliacao("ERRO Execute Insert AdicaoSaldo: " . $stmt->error);
        return false;
    }
    
    logAfiliacao("SUCESSO: Comissão creditada para afiliado $user_id");
    return true;
}

/**
 * Processa as comissões CPA para um depósito
 * @param int $user_id ID do usuário que fez o depósito
 * @param float $valor_deposito Valor do depósito
 */
function processarCpa($user_id, $valor_deposito) {
    global $mysqli;

    logAfiliacao("Iniciando processamento CPA: Usuario=$user_id, Deposito=$valor_deposito");

    $config = getAfiliadosConfig();
    if (!$config) {
        logAfiliacao("ABORTANDO: Configuração inválida.");
        return false;
    }
    
    if ($valor_deposito < $config['minDepForCpa']) {
        logAfiliacao("ABORTANDO: Valor do depósito ($valor_deposito) menor que mínimo para CPA ({$config['minDepForCpa']})");
        return false;
    }
    
    if (!aplicarChanceCpa($config['chanceCpa'])) {
        logAfiliacao("ABORTANDO: Reprovado no teste de chance CPA.");
        return false;
    }

    $hierarquia = getHierarquiaAfiliados($user_id);
    $comissoes_processadas = 0;

    $getPorcentagem = function($afiliado_id, $nivel, $global_valor) use ($mysqli) {
        $coluna = "cpaLvl{$nivel}";
        $qry = "SELECT {$coluna} FROM usuarios WHERE id = ?";
        $stmt = $mysqli->prepare($qry);
        $stmt->bind_param("i", $afiliado_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        
        $valor_final = floatval($global_valor);
        if ($row && isset($row[$coluna]) && floatval($row[$coluna]) > 0) {
            $valor_final = floatval($row[$coluna]);
            logAfiliacao("Usando CPA personalizado para afiliado $afiliado_id (Nivel $nivel): $valor_final%");
        } else {
            logAfiliacao("Usando CPA global para afiliado $afiliado_id (Nivel $nivel): $valor_final%");
        }
        return $valor_final;
    };

    if ($hierarquia['nivel1']) {
        $porcentagem_nivel1 = $getPorcentagem($hierarquia['nivel1']['id'], 1, $config['cpaLvl1']);
        if ($porcentagem_nivel1 > 0) {
            $comissao_nivel1 = ($valor_deposito * $porcentagem_nivel1) / 100;
            logAfiliacao("Nível 1: Calculado $comissao_nivel1 ($porcentagem_nivel1%)");
            if (creditarComissaoAfiliado(
                $hierarquia['nivel1']['id'], 
                $comissao_nivel1, 
                1, 
                $user_id,
                $valor_deposito,
                $porcentagem_nivel1
            )) {
                $comissoes_processadas++;
            }
        } else {
            logAfiliacao("Nível 1: Porcentagem zerada.");
        }

        if ($hierarquia['nivel2']) {
            $porcentagem_nivel2 = $getPorcentagem($hierarquia['nivel2']['id'], 2, $config['cpaLvl2']);
            if ($porcentagem_nivel2 > 0) {
                $comissao_nivel2 = ($valor_deposito * $porcentagem_nivel2) / 100;
                logAfiliacao("Nível 2: Calculado $comissao_nivel2 ($porcentagem_nivel2%)");
                if (creditarComissaoAfiliado(
                    $hierarquia['nivel2']['id'], 
                    $comissao_nivel2, 
                    2, 
                    $user_id,
                    $valor_deposito,
                    $porcentagem_nivel2
                )) {
                    $comissoes_processadas++;
                }
            } else {
                logAfiliacao("Nível 2: Porcentagem zerada.");
            }

            if ($hierarquia['nivel3']) {
                $porcentagem_nivel3 = $getPorcentagem($hierarquia['nivel3']['id'], 3, $config['cpaLvl3']);
                if ($porcentagem_nivel3 > 0) {
                    $comissao_nivel3 = ($valor_deposito * $porcentagem_nivel3) / 100;
                    logAfiliacao("Nível 3: Calculado $comissao_nivel3 ($porcentagem_nivel3%)");
                    if (creditarComissaoAfiliado(
                        $hierarquia['nivel3']['id'], 
                        $comissao_nivel3, 
                        3, 
                        $user_id,
                        $valor_deposito,
                        $porcentagem_nivel3
                    )) {
                        $comissoes_processadas++;
                    }
                } else {
                    logAfiliacao("Nível 3: Porcentagem zerada.");
                }
            }
        }
    } else {
        logAfiliacao("Nenhum afiliado de Nível 1 encontrado.");
    }

    $resultado = $comissoes_processadas > 0;
    logAfiliacao("Fim processamento CPA. Total processado: $comissoes_processadas. Retorno: " . ($resultado ? "TRUE" : "FALSE"));
    return $resultado;
}

/**
 * Função principal para processar comissões CPA de um depósito
 * @param int $user_id ID do usuário que fez o depósito
 * @param float $valor_deposito Valor do depósito
 */
function processarTodasComissoes($user_id, $valor_deposito) {
    logAfiliacao("DEBUG: Entrou em processarTodasComissoes(User=$user_id, Valor=$valor_deposito)");

    $cpa_processado = processarCpa($user_id, $valor_deposito);
    
    if ($cpa_processado) {
        return true;
    } else {
        return false;
    }
}

?>