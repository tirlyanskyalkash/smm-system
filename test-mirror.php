<?php

$mirrors = [
    'https://api.telegram.org',
    'https://telegram.zerobytes.xyz',
    'https://tg.i-c-a.su',
    'https://tapi.bale.ai',
];

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';
$chatId = '-1003210157990';
$message = 'Тест зеркала!';

foreach ($mirrors as $mirror) {
    $url = "{$mirror}/bot{$token}/sendMessage";
    echo "Testing: {$mirror}\n";
    
    $data = http_build_query([
        'chat_id' => $chatId,
        'text' => $message,
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo "✅ WORKING! Сообщение отправлено через {$mirror}\n";
        break;
    } else {
        echo "❌ Failed (HTTP: {$httpCode})\n";
    }
}