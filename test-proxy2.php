<?php

// Свежие прокси из публичных источников (на сегодня)
$proxies = [
    '103.152.116.218:8080',
    '103.152.116.219:8080',
    '103.152.116.220:8080',
    '45.94.211.113:8080',
    '45.94.211.114:8080',
    '45.94.211.115:8080',
    '195.211.217.14:3128',
    '195.211.217.15:3128',
    '195.211.217.16:3128',
    '185.128.139.182:3128',
    '185.128.139.183:3128',
    '185.128.139.184:3128',
    '194.34.133.113:3128',
    '194.34.133.114:3128',
    '194.34.133.115:3128',
];

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';
$url = "https://api.telegram.org/bot{$token}/getMe";

$workingProxy = null;

foreach ($proxies as $proxy) {
    echo "Testing proxy: {$proxy}... ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_PROXY, $proxy);
    curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ WORKING!\n";
        $workingProxy = $proxy;
        break;
    } else {
        echo "❌ Failed (HTTP: {$httpCode})\n";
    }
}

if ($workingProxy) {
    echo "\n🎉 WORKING PROXY: {$workingProxy}\n";
    echo "Добавь в .env: TELEGRAM_PROXY={$workingProxy}\n";
} else {
    echo "\n❌ Ни один прокси не сработал. Попробуй включить VPN и выполнить публикацию.\n";
}