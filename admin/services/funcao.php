<?php
date_default_timezone_set('America/Sao_Paulo');
include_once __DIR__ . '/../../config.php';
//=======================================#
$pasta_url = '/';
//=======================================#

// Função para detectar se a conexão é HTTPS
function pega_http_https()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
}

// Função para gerar a URL base do sistema
function url_sistema()
{
    global $pasta_url;
    $protocol = pega_http_https();
    $system_url = $protocol . "://" . filter_var($_SERVER['HTTP_HOST'], FILTER_SANITIZE_URL) . $pasta_url;
    return rtrim($system_url, '/');
}

// Variáveis de configuração
$tipoAPI_SUITPAY = 1; // Modo sandbox 0 // real 1
$contato_suporte = "#";
$refer_padrao = 'af21Zd9OXRc';  // Refer padrão
$status_telefone = 1; // 0 desliga - 1 liga
$modo_plataforma = 1; // 0 Desenvolvimento - 1 Produção

// URLs padrão do sistema
if (php_sapi_name() != "cli") {
    $url_base = url_sistema() . '/';
    $url_public = url_sistema() . '/public';
    $url_notificacao_ipn = url_sistema() . '/gateway/suitpay';

    $painel_user = url_sistema() . '/user';
    $painel_sair = url_sistema() . '/user/sair';
    $painel_minhaconta = url_sistema() . '/user/minha-conta';
    $painel_sacar = url_sistema() . '/user/sacar';
    $painel_deposito = url_sistema() . '/user/deposito';
    $painel_afiliado = url_sistema() . '/user/afiliado';
    $painel_afiliado_ver = url_sistema() . '/user/ver/';
    $painel_afiliado_historico_de_saque = url_sistema() . '/user/historico-de-saque';
    $painel_afiliado_bonus_historico = url_sistema() . '/user/bonus-historico';
    $painel_afiliado_hist_depositos = url_sistema() . '/user/hist-depositos';
    $painel_afiliado_all_games = url_sistema() . '/user/all-games';
    $painel_afiliado_aovivo = url_sistema() . '/user/aovivo';
    $painel_afiliado_cassino = url_sistema() . '/user/cassino';
    $painel_afiliado_popular = url_sistema() . '/user/popular';
    $painel_provedores = url_sistema() . '/user/provedores-games';
    $painel_pgsoft = url_sistema() . '/user/pgsoft';
    $painel_pragmatic = url_sistema() . '/user/pragmatic';
    $painel_eguzi = url_sistema() . '/user/eguzi';
    $painel_evolution = url_sistema() . '/user/evolution';
    $painel_evoplay = url_sistema() . '/user/evoplay';
    $pegarsaldo = url_sistema() . '/user/pegarSaldo';

    $painel_gerapix_api = url_sistema() . '/user/form-gerapix-api.php';
    $url_api_gatewayPix = url_sistema() . '/api/v1/pixqr.php';
    $painel_afiliado_sol_saque = url_sistema() . '/user/form-sol-saque.php';
    $painel_afiliado_sol_saque_afiliados = url_sistema() . '/user/form-sol-saque-afiliados.php';

    // URLs para páginas públicas
    $cassino_aovivo = url_sistema() . '/public/aovivo';
    $page_cassino = url_sistema() . '/public/cassino';
    $page_provedores = url_sistema() . '/public/provedores-games';
    $page_pgsoft = url_sistema() . '/public/pgsoft';
    $page_pragmatic = url_sistema() . '/public/pragmatic';
    $page_eguzi = url_sistema() . '/public/eguzi';
    $page_evolution = url_sistema() . '/public/evolution';
    $page_evoplay = url_sistema() . '/public/evoplay';
    $termos = url_sistema() . '/public/termos-de-uso';
    $faq = url_sistema() . '/public/faq';
    $gambling = url_sistema() . '/public/gambling';
    $popular = url_sistema() . '/public/popular';
    $indique = url_sistema() . '/public/indique';
    $registrar = url_sistema() . '/public/registrar';
    $login = url_sistema() . '/public/login';

    // URLs do painel administrativo
    $painel_adm = $url_base . DASH . '/';
    $painel_adm_temas = $painel_adm . 'temas';
    $painel_adm_acessar = $painel_adm . 'login';
    $painel_adm_sair = $painel_adm . 'sair';
    $painel_adm_administradores = $painel_adm . 'administradores';
    $painel_adm_cupons = $painel_adm . 'cupons';
    $painel_adm_cpa = $painel_adm . 'config_afiliados';
    $painel_adm_slider_front = $painel_adm . 'banners';
    $painel_adm_front_site = $painel_adm . 'configuracoes';
    $painel_adm_suit_pay = $painel_adm . 'gateway';
    $painel_adm_provedores_games = $painel_adm . 'api';
    $painel_adm_slots_games = $painel_adm . 'slots-games';
    $painel_adm_financeiro_sistema = $painel_adm . 'configuracoes';
    $painel_adm_listar_usuarios = $painel_adm . 'usuarios';
    $painel_adm_exportar = $painel_adm . 'exportar';
    $painel_adm_ver_usuarios = $painel_adm . 'detalhes_usuario=';
    $painel_adm_saldo_api_js = $painel_adm . 'saldo-api-js';
    $painel_adm_depositos_pendentes = $painel_adm . 'depositos-pendentes';
    $painel_adm_all_depositos = $painel_adm . 'all-depositos';
    $painel_adm_saques_pendentes = $painel_adm . 'saques_pen';
    $painel_adm_all_saques = $painel_adm . 'all01-saques';
    $painel_adm_view_game = $painel_adm . 'games=';

    // Documentos e uploads
    $docs_site = $url_base . 'front-cassino/';
    $docs_uploads = $url_base . 'uploads/';
    $docs_uploads_img_triste = $url_base . 'front-cassino/images/triste.png';
    $docs_app_adm = $url_base . 'front-dash/';
}

// Função para impedir caracteres especiais (input sanitization)
function PHP_SEGURO($string)
{
    global $mysqli;
    $string = trim($string);
    $string = mysqli_real_escape_string($mysqli, $string);  // Prevenir SQL Injection
    $string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); // Prevenir XSS
    return $string;
}

// Função para gerar alertas Swal
function alertas_swall($tipo, $titulo, $tempo)
{
    switch ($tipo) {
        case 'erro':
            $alerta_x = '<script>
                            $(document).ready(function(){
                            Swal.fire({
                            position: "center",
                            icon: "error",
                            title: "' . $titulo . '",
                            showConfirmButton: false,
                            timer: ' . $tempo . '
                            });
                        });
                </script>';
            break;
        case 'ok':
            $alerta_x = '<script>
                            $(document).ready(function(){
                            Swal.fire({
                              position: "center",
                              icon: "success",
                              title: "' . $titulo . '",
                              showConfirmButton: false,
                              timer: ' . $tempo . '
                            });
                        });
                </script>';
            break;
        case 'aviso':
            $alerta_x = '<script>
                            $(document).ready(function(){
                            Swal.fire({
                            position: "center",
                            icon: "warning",
                            title: "' . $titulo . '",
                            showConfirmButton: false,
                            timer: ' . $tempo . '
                            });
                        });
                </script>';
            break;
    }
    return $alerta_x;
}

// Função para alertas Toastr
function alertas_toaster($tipo, $titulo, $timer = null)
{
    switch ($tipo) {
        case 'erro':
            $alerta_x = '<script>
                            $(document).ready(function(){
                                Command: toastr["error"]("' . $titulo . '")
                                toastr.options = {
                                    "closeButton": false,
                                    "debug": false,
                                    "progressBar": true,
                                    "positionClass": "toast-bottom-center",
                                    "timeOut": "5000",
                                    "extendedTimeOut": "1000",
                                    "showMethod": "fadeIn",
                                    "hideMethod": "fadeOut"
                                }
                            });
                        </script>';
            break;
        case 'ok':
            $alerta_x = '<script>
                            $(document).ready(function(){
                                Command: toastr["success"]("", "' . $titulo . '")
                                toastr.options = {
                                    "closeButton": false,
                                    "debug": false,
                                    "progressBar": true,
                                    "positionClass": "toast-bottom-center",
                                    "timeOut": "5000",
                                    "extendedTimeOut": "1000",
                                    "showMethod": "fadeIn",
                                    "hideMethod": "fadeOut"
                                }
                            });
                        </script>';
            break;
        case 'aviso':
            $alerta_x = '<script>
                            $(document).ready(function(){
                                Command: toastr["warning"]("' . $titulo . '")
                                toastr.options = {
                                    "closeButton": false,
                                    "debug": false,
                                    "progressBar": true,
                                    "positionClass": "toast-bottom-center",
                                    "timeOut": "5000",
                                    "extendedTimeOut": "1000",
                                    "showMethod": "fadeIn",
                                    "hideMethod": "fadeOut"
                                }
                            });
                        </script>';
            break;
    }
    return $alerta_x;
}

// Função de criptografia AES-256-CBC
function CRIPT_AES($action, $string)
{
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = getenv('ENCRYPTION_SECRET_KEY');
    $secret_iv = getenv('ENCRYPTION_SECRET_IV');

    $key = hash('sha256', $secret_key);
    $iv = substr(hash('sha256', $secret_iv), 0, 16);

    if ($action == 'encrypt') {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if ($action == 'decrypt') {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

// Funções de criptografia base64
function encodeAll($string)
{
    return base64_encode(base64_encode(base64_encode(base64_encode($string))));
}

function decodeAll($string)
{
    return base64_decode(base64_decode(base64_decode(base64_decode($string))));
}

// Função para gerar URLs amigáveis
function url_amigavel($nom_tag, $slug = "-")
{
    $string = strtolower($nom_tag);

    $ascii['a'] = range(224, 230);
    $ascii['e'] = range(232, 235);
    $ascii['i'] = range(236, 239);
    $ascii['o'] = array_merge(range(242, 246), array(240, 248));
    $ascii['u'] = range(249, 252);

    $ascii['b'] = array(223);
    $ascii['c'] = array(231);
    $ascii['d'] = array(208);
    $ascii['n'] = array(241);
    $ascii['y'] = array(253, 255);

    foreach ($ascii as $key => $item) {
        $acentos = '';
        foreach ($item as $codigo) $acentos .= chr($codigo);
        $troca[$key] = '/[' . $acentos . ']/i';
    }

    $string = preg_replace(array_values($troca), array_keys($troca), $string);

    if ($slug) {
        $string = preg_replace('/[^a-z0-9]/i', $slug, $string);
        $string = preg_replace('/' . $slug . '{2,}/i', $slug, $string);
        $string = trim($string, $slug);
    }
    return $string;
}

// Funções para gerar tokens e códigos
function gerar_pass_key($largura = 16)
{
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"), 0, $largura);
}

function token_aff($largura = 8)
{
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"), 0, $largura);
}

function token_id_transacao($largura = 18)
{
    return substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"), 0, $largura);
}

// Função para formatar valores em reais
function Reais2($value)
{
    return number_format($value, 2, ',', '.');
}

// Função para remover caracteres não numéricos
function somente_numeros($var)
{
    return preg_replace('/[^0-9]/', '', $var);
}

// Funções para exibir datas
function ver_data($dta_pagamento)
{
    return date('d/m/Y H:i:s', strtotime($dta_pagamento));
}

function ver_data2($dta_pagamento)
{
    return date('d/m/Y', strtotime($dta_pagamento));
}

function ver_data_hoje($dta_pagamento, $hora)
{
    return date('d/m/Y H:i:s', strtotime($dta_pagamento . ' ' . $hora));
}

// Função para validar CPF
function validarCPF($cpf)
{
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) != 11 || preg_match('/^(\d)\1*$/', $cpf)) return false;

    $digitoVerificador1 = intval($cpf[9]);
    $digitoVerificador2 = intval($cpf[10]);

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digitoCalculado1 = ($resto < 2) ? 0 : 11 - $resto;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digitoCalculado2 = ($resto < 2) ? 0 : 11 - $resto;

    return ($digitoCalculado1 == $digitoVerificador1 && $digitoCalculado2 == $digitoVerificador2);
}
