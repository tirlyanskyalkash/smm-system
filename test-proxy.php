<?php

$proxies = [
    '185.217.1.94:3128',
    '45.143.158.134:3128',
    '188.130.128.177:8080',
    '45.8.106.14:3128',
    '94.140.190.34:3128',
];

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';
$url = "https://api.telegram.org/bot{$token}/getMe";

foreach ($proxies as $proxy) {
    echo "Testing proxy: {$proxy}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ PROXY WORKING: {$proxy}\n";
        break;
    } else {
        echo "❌ Failed\n";
    }
}