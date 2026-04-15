<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';
$chatId = '-1003210157990';

$url = "https://api.telegram.org/bot{$token}/sendMessage";

$response = Http::post($url, [
    'chat_id' => $chatId,
    'text' => 'Тест из SMM-системы! 🚀',
]);

echo "Response: " . $response->body() . "\n";