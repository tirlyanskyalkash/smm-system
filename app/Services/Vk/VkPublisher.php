<?php

namespace App\Services\Vk;

use App\Contracts\SocialPublisher;
use App\Models\Post;
use App\Models\Platform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VkPublisher implements SocialPublisher
{
    public function publish(Post $post, Platform $platform): array
    {
        try {
            $accessToken = $platform->access_token;
            $groupId = $platform->external_id;
            
            // Конвертируем HTML в текст
            $message = $this->htmlToText($post->content);
            
            // Параметры для VK API
            $params = [
                'owner_id' => '-' . $groupId,
                'from_group' => 1,
                'message' => $message,
                'access_token' => $accessToken,
                'v' => '5.131',
            ];
            
            $response = Http::asForm()->post('https://api.vk.com/method/wall.post', $params);
            $data = $response->json();
            
            if (isset($data['error'])) {
                return [
                    'success' => false,
                    'external_id' => null,
                    'message' => 'VK ошибка: ' . ($data['error']['error_msg'] ?? 'Неизвестная ошибка'),
                ];
            }
            
            return [
                'success' => true,
                'external_id' => $data['response']['post_id'] ?? null,
                'message' => 'Успешно опубликовано в VK',
            ];
            
        } catch (\Exception $e) {
            Log::error('VK publish error: ' . $e->getMessage());
            return [
                'success' => false,
                'external_id' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    protected function htmlToText(string $html): string
    {
        $text = preg_replace('/<p[^>]*>/', '', $html);
        $text = str_replace('</p>', "\n\n", $text);
        $text = preg_replace('/<br[^>]*>/', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
    
    public function validateCredentials(Platform $platform): bool
    {
        try {
            $response = Http::get('https://api.vk.com/method/groups.getById', [
                'access_token' => $platform->access_token,
                'v' => '5.131',
            ]);
            $data = $response->json();
            return !isset($data['error']);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function getAccountInfo(Platform $platform): array
    {
        try {
            $response = Http::get('https://api.vk.com/method/groups.getById', [
                'access_token' => $platform->access_token,
                'v' => '5.131',
            ]);
            $data = $response->json();
            
            if (!isset($data['error']) && isset($data['response'][0])) {
                $group = $data['response'][0];
                return [
                    'name' => $group['name'],
                    'id' => $group['id'],
                ];
            }
            return ['error' => 'Не удалось получить информацию'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}