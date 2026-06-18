<?php

function getCurlRequest($url, $headers = null, $post = null, $timeout = 20){
    
    $timeoutConnect = 5;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    if(substr($url, 0, 5) == 'https'){
        curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    }
    else
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    }
    if ($post && !empty($post)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    if ($headers && !empty($headers)) {
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutConnect);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

    $response = curl_exec($curl);

    $result['error'] = "";
    if (curl_errno($curl)) {        
        $result['error'] = curl_error($curl);
        return "";            
    }

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $result['header'] = substr($response, 0, $header_size);
    $result['body'] = substr( $response, $header_size );

    curl_close($curl);

    return $result['body'];
}

function getCurlRequest2($url, $headers = null, $post = null, $customRequest = null, $timeout = 20){
    
    $timeoutConnect = 5;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);

    if(substr($url, 0, 5) == 'https'){
		curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	}
	else
	{
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	}
    
    if($post && !empty($post)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    }
    if($headers && !empty($headers)) {
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    if($customRequest && !empty($customRequest)) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $customRequest);
    }

    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutConnect);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
    
    $response = curl_exec($curl);

    $result['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result['error'] = "";
    if (curl_errno($curl)) {        
        $result['error'] = curl_error($curl);
    }

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    // $result['header'] = substr($response, 0, $header_size);
    $result['body'] = substr( $response, $header_size );

    curl_close($curl);

    return $result;
}

function getCurlRequestWithProxy($url, $headers = null, $post = null, $customRequest = null, $timeout = 20){
    
    $timeoutConnect = 5;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);

    if(substr($url, 0, 5) == 'https'){
		curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
	}
	else
	{
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	}

    if( array_key_exists('app.proxy', $_ENV) && strlen($_ENV['app.proxy']) > 10 )
    {
        $proxyType=CURLPROXY_SOCKS5;
        $proxyUrl = $_ENV['app.proxy'];
        $proxyAuth = "jmaster:startend";
        // Specify proxy type 
        curl_setopt($curl, CURLOPT_PROXYTYPE, $proxyType);
        // Set proxy server and port
        curl_setopt($curl, CURLOPT_PROXY, $proxyUrl);
        // Optional: Proxy authentication
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxyAuth);
    }
    
    if(!is_null($post)) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
    } 

    if($headers && !empty($headers)) {
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    }
    if($customRequest && !empty($customRequest)) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $customRequest);
    }

    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutConnect);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);
    
    $response = curl_exec($curl);

    $result['code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $result['error'] = "";
    if (curl_errno($curl)) {        
        $result['error'] = curl_error($curl);
    }

    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    // $result['header'] = substr($response, 0, $header_size);
    $result['body'] = substr( $response, $header_size );

    curl_close($curl);

    return $result;
}

function writeLog($content, $level = 'debug'){
    
    if(!LOG_WRITE)
        return;

    // debug(기본) 호출은 기록하지 않음. 운영 필수 로그만 writeLog($msg, 'ops') 로 남김.
    if ($level !== 'ops') {
        return;
    }

    $tmNow = time() ;
    $nHour = date("G",$tmNow);
    $nMin = date("i",$tmNow);
    $nSec = date("s",$tmNow);

    $sDate = date( 'Y-m-d', $tmNow);
    $fLog = fopen(LOG_FILE.$sDate, "a") ;

    $tContent = "[".$nHour.":".$nMin.":".$nSec."] ".$contenet."\r\n";

    fputs($fLog, $tContent);
    fclose($fLog);
}

?>