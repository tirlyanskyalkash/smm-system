<?php

namespace App\Services\Telegram;

use App\Contracts\SocialPublisher;
use App\Models\Post;
use App\Models\Platform;
use Illuminate\Support\Facades\Log;

class TelegramPublisher implements SocialPublisher
{
    public function publish(Post $post, Platform $platform): array
    {
        try {
            $token = $platform->access_token;
            $chatId = $platform->external_id;
            $message = strip_tags($post->content);
            
            // ТОТ САМЫЙ РАБОЧИЙ КОД ИЗ ТВОЕГО ФАЙЛА
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
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $responseData = json_decode($response, true);
                if (isset($responseData['ok']) && $responseData['ok'] === true) {
                    return [
                        'success' => true,
                        'external_id' => $responseData['result']['message_id'] ?? null,
                        'message' => 'Успешно опубликовано в Telegram',
                    ];
                }
            }
            
            return [
                'success' => false,
                'external_id' => null,
                'message' => 'Telegram API ошибка: ' . $response . ' | Error: ' . $error,
            ];
            
        } catch (\Exception $e) {
            Log::error('Telegram publish error: ' . $e->getMessage());
            return [
                'success' => false,
                'external_id' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    public function validateCredentials(Platform $platform): bool
    {
        try {
            $token = $platform->access_token;
            $url = "https://api.telegram.org/bot{$token}/getMe";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $data = json_decode($response, true);
            return $httpCode === 200 && isset($data['ok']) && $data['ok'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getAccountInfo(Platform $platform): array
    {
        try {
            $token = $platform->access_token;
            $url = "https://api.telegram.org/bot{$token}/getMe";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            if (isset($data['ok']) && $data['ok'] === true) {
                $bot = $data['result'];
                return [
                    'name' => $bot['first_name'] . ($bot['last_name'] ?? ''),
                    'username' => $bot['username'] ?? '',
                    'id' => $bot['id'] ?? '',
                ];
            }
            return ['error' => 'Не удалось получить информацию'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}