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
            
            $htmlContent = $post->content;
            
            // Конвертируем HTML в текст с сохранением переносов
            $text = $this->htmlToTelegramText($htmlContent);
            
            $mirrors = [
                'https://api.telegram.org',
                'https://tg.i-c-a.su',
                'https://telegra.ph/api',
                'https://tapi.bale.ai',
            ];
            
            $lastError = null;
            
            foreach ($mirrors as $mirror) {
                $url = "{$mirror}/bot{$token}/sendMessage";
                
                $data = http_build_query([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/x-www-form-urlencoded',
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200) {
                    $responseData = json_decode($response, true);
                    if (isset($responseData['ok']) && $responseData['ok'] === true) {
                        return [
                            'success' => true,
                            'external_id' => $responseData['result']['message_id'] ?? null,
                            'message' => 'Успешно опубликовано в Telegram через ' . $mirror,
                        ];
                    }
                }
                
                $lastError = "HTTP {$httpCode}: {$response}";
            }
            
            return [
                'success' => false,
                'external_id' => null,
                'message' => 'Telegram API ошибка: ' . $lastError,
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
    
    /**
     * Конвертирует HTML в текст с сохранением структуры для Telegram
     */
    protected function htmlToTelegramText(string $html): string
    {
        // Заменяем блоки <p> на двойной перенос строки
        $text = preg_replace('/<p[^>]*>/', '', $html);
        $text = str_replace('</p>', "\n\n", $text);
        
        // Заменяем <br> на перенос строки
        $text = preg_replace('/<br[^>]*>/', "\n", $text);
        $text = preg_replace('/<br\/>/', "\n", $text);
        
        // Заменяем <div> на переносы
        $text = preg_replace('/<div[^>]*>/', '', $text);
        $text = str_replace('</div>', "\n", $text);
        
        // Заменяем списки
        $text = preg_replace('/<ul[^>]*>/', "\n", $text);
        $text = preg_replace('/<\/ul>/', "\n", $text);
        $text = preg_replace('/<li[^>]*>/', '• ', $text);
        $text = str_replace('</li>', "\n", $text);
        
        // Заменяем заголовки
        $text = preg_replace('/<h[1-6][^>]*>/', "\n<b>", $text);
        $text = preg_replace('/<\/h[1-6]>/', "</b>\n\n", $text);
        
        // Сохраняем жирный и курсив для HTML-форматирования Telegram
        // <b>, <strong>, <i>, <em>, <a>, <code>, <pre> остаются
        
        // Удаляем все остальные HTML-теги, но сохраняем полезные
        $text = strip_tags($text, '<b><strong><i><em><a><code><pre><u><s>');
        
        // Декодируем HTML-сущности
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Очищаем лишние переносы (максимум 2 подряд)
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        
        // Убираем пробелы в начале и конце строк
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        // Убираем пустые строки в начале и конце
        $text = trim($text);
        
        return $text;
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