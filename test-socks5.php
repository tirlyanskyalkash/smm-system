<?php

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';

// SOCKS5 прокси
$socksProxies = [
    '127.0.0.1:1080',
    '127.0.0.1:10808',
    '127.0.0.1:9050',
    '127.0.0.1:9150',
    '185.217.1.94:1080',
    '45.8.106.14:1080',
    '94.140.190.34:1080',
];

$mirrors = [
    'https://api.telegram.org',
    'https://tg.i-c-a.su',
    'https://telegra.ph/api',
    'https://tapi.bale.ai',
];

echo "=== ТЕСТ SOCKS5 ПРОКСИ + ЗЕРКАЛА ===\n\n";

foreach ($mirrors as $mirror) {
    $url = "{$mirror}/bot{$token}/getMe";
    
    foreach ($socksProxies as $proxy) {
        echo "Тест: {$mirror} через SOCKS5 {$proxy}... ";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ РАБОТАЕТ!\n";
            echo "Зеркало: {$mirror}\n";
            echo "Прокси: {$proxy}\n";
            break 2;
        } else {
            echo "❌ HTTP {$httpCode}\n";
        }
    }
}