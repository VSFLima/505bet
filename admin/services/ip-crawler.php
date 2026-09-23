<?php
	function ip_F($str){
	//Getting Country, City, Region, Map Location and Internet Service Provider
	$useragent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36';
    $url = 'https://extreme-ip-lookup.com/json/' . $str;
    $ch  = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
    curl_setopt($ch, CURLOPT_REFERER, "https://google.com");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $ipcontent = curl_exec($ch);
    curl_close($ch);

	$ip_data = @json_decode($ipcontent);
	if ($ip_data && $ip_data->{'status'} == 'success') {
		$country      = $ip_data->{'country'};
		$country_code = $ip_data->{'countryCode'};
		$region       = $ip_data->{'region'};
		$city         = $ip_data->{'city'};
		$latitude     = $ip_data->{'lat'};
		$longitude    = $ip_data->{'lon'};
		$isp          = $ip_data->{'isp'};
	} else {
		$country      = "Unknown";
		$country_code = "XX";
		$region       = "Unknown";
		$city         = "Unknown";
		$latitude     = "0";
		$longitude    = "0";
		$isp          = "Unknown";
	}	
	
	try {
		//$cidade  = $ip_data->{'city'};
		$ip_array = array(
			"pais" => $country,
			"codigo_pais" => $country_code,
			"regiao" => $region,
			"cidade" => $city,
			"isp" => $isp
		);
	} catch (Exception $e) {
		$ip_array = array(
			"pais" => null,
			"codigo_pais" => null,
			"regiao" => null,
			"cidade" => null,
			"isp" => null
		);
	}
	
	return $ip_array;
	}


?>
