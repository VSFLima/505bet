<?php
function pegaIP() {
 
    // Verifique se há IP compartilhado da Internet
    if ( !empty( $_SERVER['HTTP_CLIENT_IP'] ) && validar_ip( $_SERVER['HTTP_CLIENT_IP'] )) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
 
    // Verifique se há endereços IP passando por proxies
    if ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
 
        // Verifique se existem vários endereços IP em var
        if ( strpos( $_SERVER['HTTP_X_FORWARDED_FOR'], ',' ) !== false ) {
            $lista_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
 
            foreach ( $lista_ip as $ip) {
                if (validar_ip($ip))
                    return $ip;
            }
        } else {
 
            if ( validar_ip( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ){
                return $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        }
    }
 
    if ( !empty( $_SERVER['HTTP_X_FORWARDED'] ) && validar_ip( $_SERVER['HTTP_X_FORWARDED'] ) ){
        return $_SERVER['HTTP_X_FORWARDED'];
    }
 
    if ( !empty( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) && validar_ip( $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'] ) ){
        return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }
 
    if ( !empty( $_SERVER['HTTP_FORWARDED_FOR'] ) && validar_ip( $_SERVER['HTTP_FORWARDED_FOR'] ) ){
        return $_SERVER['HTTP_FORWARDED_FOR'];
    }
 
    if ( !empty( $_SERVER['HTTP_FORWARDED'] ) && validar_ip( $_SERVER['HTTP_FORWARDED'] ) ){
        return $_SERVER['HTTP_FORWARDED'];
    }
 
    // Retornar endereço IP não confiável, pois tudo o mais falhou
    return $_SERVER['REMOTE_ADDR'];
}
 
/**
 * Garante que um endereço IP seja um endereço IP válido e não se enquadre
 * uma faixa de rede privada.
 */
function validar_ip($ip) {
 
    if ( strtolower( $ip ) === 'unknown' ){
        return false;
    }
 
    // endereço de rede IPv4
    $ip = ip2long($ip);
 
    // Se o endereço IP estiver definido e não for equivalente a 255.255.255.255
    if ($ip !== false && $ip !== -1) {
        // Certifique-se de obter uma representação longa não assinada do endereço IP
        // devido a discrepâncias entre sistemas operacionais de 32 e 64 bits e
        // números assinados (ints padrão para logado no PHP)
        $ip = sprintf('%u', $ip);
 
        // Fazer verificação de faixa de rede privada
        if ( $ip >= 0 && $ip <= 50331647 ){
            return false;
        }
 
        if ( $ip >= 167772160 && $ip <= 184549375 ){
            return false;
        }
 
        if ( $ip >= 2130706432 && $ip <= 2147483647 ){
            return false;
        }
 
        if ( $ip >= 2851995648 && $ip <= 2852061183 ){
            return false;
        }
 
        if ( $ip >= 2886729728 && $ip <= 2887778303 ){
            return false;
        }
 
        if ( $ip >= 3221225984 && $ip <= 3221226239 ){
            return false;
        }
 
        if ( $ip >= 3232235520 && $ip <= 3232301055 ){
            return false;
        }
 
        if ( $ip >= 4294967040 ){
            return false;
        }
 
    }
    return true;
}
//função capta dados de navegação
$user_agent     =   $_SERVER['HTTP_USER_AGENT'];

function getOS() { 

    global $user_agent;

    $os_platform    =   "Unknown OS Platform";

    $os_array       =   array(
                            '/windows nt 10/i'     =>  'Windows 10',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win98/i'              =>  'Windows 98',
                            '/win95/i'              =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/linux/i'              =>  'Linux',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile'
                        );

    foreach ($os_array as $regex => $value) { 

        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }

    }   

    return $os_platform;

}

function getBrowser() {

    global $user_agent;

    $browser        =   "Unknown Browser";

    $browser_array  =   array(
                            '/msie/i'       =>  'Internet Explorer',
                            '/firefox/i'    =>  'Firefox',
                            '/safari/i'     =>  'Safari',
                            '/chrome/i'     =>  'Chrome',
                            '/edge/i'       =>  'Edge',
                            '/opera/i'      =>  'Opera',
                            '/netscape/i'   =>  'Netscape',
                            '/maxthon/i'    =>  'Maxthon',
                            '/konqueror/i'  =>  'Konqueror',
                            '/mobile/i'     =>  'Handheld Browser'
                        );

    foreach ($browser_array as $regex => $value) { 

        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
        }

    }

    return $browser;

}


$os      =   getOS();
$browser =   getBrowser();
$ip = pegaIP();
?>