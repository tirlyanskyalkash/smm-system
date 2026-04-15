<?php

namespace App\Services\Vk;

use App\Contracts\SocialPublisher;
use App\Models\Post;
use App\Models\Platform;
use VK\Client\VKApiClient;
use Illuminate\Support\Facades\Log;

class VkPublisher implements SocialPublisher
{
    protected $vk;
    
    protected function getApiClient(Platform $platform)
    {
        $accessToken = decrypt($platform->access_token);
        return new VKApiClient();
    }
    
    public function publish(Post $post, Platform $platform): array
    {
        try {
            $accessToken = decrypt($platform->access_token);
            $vk = $this->getApiClient($platform);
            $groupId = str_replace('-', '', $platform->external_id);
            
            $message = $post->content;
            $media = $post->media;
            
            // Подготовка вложений
            $attachments = [];
            
            if (!empty($media)) {
                foreach ($media as $index => $mediaPath) {
                    $fullPath = storage_path('app/public/' . $mediaPath);
                    
                    if (file_exists($fullPath)) {
                        // Загрузка фото на сервер VK
                        $uploadServer = $vk->getUploadServer($accessToken, 'photos', [
                            'group_id' => $groupId,
                        ]);
                        
                        // Здесь нужна логика загрузки фото
                        // Для упрощения пока пропустим, отправим без фото
                    }
                }
            }
            
            // Публикация на стену
            $response = $vk->wallPost($accessToken, [
                'owner_id' => '-' . $groupId,
                'message' => $message,
                'from_group' => 1,
            ]);
            
            return [
                'success' => true,
                'external_id' => $response['post_id'] ?? null,
                'message' => 'Успешно опубликовано в VK',
            ];
            
        } catch (\Exception $e) {
            Log::error('VK publish error: ' . $e->getMessage(), [
                'post_id' => $post->id,
                'platform_id' => $platform->id,
            ]);
            
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
            $accessToken = decrypt($platform->access_token);
            $vk = $this->getApiClient($platform);
            
            // Пробуем получить информацию о пользователе
            $vk->usersGet($accessToken, [
                'user_ids' => 'me',
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('VK validation error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function getAccountInfo(Platform $platform): array
    {
        try {
            $accessToken = decrypt($platform->access_token);
            $vk = $this->getApiClient($platform);
            
            $user = $vk->usersGet($accessToken, [
                'fields' => 'first_name,last_name,domain',
            ]);
            
            if (!empty($user)) {
                return [
                    'name' => $user[0]['first_name'] . ' ' . $user[0]['last_name'],
                    'id' => $user[0]['id'],
                    'domain' => $user[0]['domain'] ?? '',
                ];
            }
            
            return ['error' => 'User not found'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}