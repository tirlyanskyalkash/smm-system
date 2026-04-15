<?php

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';
$chatId = '-1003210157990';
$message = 'Тест из SMM-системы! 🚀';

$url = "https://api.telegram.org/bot{$token}/sendMessage";

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

$response = curl_exec($ch);
curl_close($ch);

echo "Response: " . $response . "\n";