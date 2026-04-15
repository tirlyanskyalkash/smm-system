<?php

namespace App\Contracts;

use App\Models\Post;
use App\Models\Platform;

interface SocialPublisher
{
    /**
     * Публикация поста на платформу
     *
     * @param Post $post
     * @param Platform $platform
     * @return array ['success' => bool, 'external_id' => string|null, 'message' => string|null]
     */
    public function publish(Post $post, Platform $platform): array;
    
    /**
     * Проверка валидности токена/подключения
     *
     * @param Platform $platform
     * @return bool
     */
    public function validateCredentials(Platform $platform): bool;
    
    /**
     * Получение информации об аккаунте/канале/группе
     *
     * @param Platform $platform
     * @return array
     */
    public function getAccountInfo(Platform $platform): array;
}