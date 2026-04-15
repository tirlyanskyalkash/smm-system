<?php

$token = '8633401826:AAEufPjAOuYZrgKONaLSQ9JSyV29lLTFO4k';

$url = "https://api.telegram.org/bot{$token}/getMe";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

echo "Response: " . $response . "\n";
if ($error) {
    echo "cURL Error: " . $error . "\n";
}