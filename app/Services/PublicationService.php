<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Platform;
use App\Models\PublicationLog;
use App\Contracts\SocialPublisher;
use Illuminate\Support\Facades\DB;

class PublicationService
{
    protected $publishers = [];
    
    public function registerPublisher(string $type, SocialPublisher $publisher)
    {
        $this->publishers[$type] = $publisher;
    }
    
    protected function getPublisher(Platform $platform): SocialPublisher
    {
        if (!isset($this->publishers[$platform->type])) {
            throw new \Exception("Нет адаптера для платформы: {$platform->type}");
        }
        
        return $this->publishers[$platform->type];
    }
    
    public function publishToPlatform(Post $post, Platform $platform): array
    {
        $publisher = $this->getPublisher($platform);
        
        // Логируем попытку публикации
        $log = PublicationLog::create([
            'post_id' => $post->id,
            'platform_id' => $platform->id,
            'action' => 'publish',
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);
        
        try {
            $result = $publisher->publish($post, $platform);
            
            // Обновляем связь post_platform
            DB::table('post_platform')
                ->where('post_id', $post->id)
                ->where('platform_id', $platform->id)
                ->update([
                    'status' => $result['success'] ? 'success' : 'error',
                    'error_message' => $result['success'] ? null : $result['message'],
                    'external_post_id' => $result['external_id'],
                    'published_at' => $result['success'] ? now() : null,
                ]);
            
            // Обновляем лог
            $log->update([
                'status' => $result['success'] ? 'success' : 'error',
                'response_data' => $result,
                'error_message' => $result['success'] ? null : $result['message'],
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $log->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    public function publishToMultiplePlatforms(Post $post, array $platformIds): array
    {
        $results = [];
        
        foreach ($platformIds as $platformId) {
            $platform = Platform::find($platformId);
            if ($platform && $platform->is_active) {
                $results[$platformId] = $this->publishToPlatform($post, $platform);
            }
        }
        
        // Обновляем статус поста
        $allSuccess = collect($results)->every(fn($r) => $r['success'] === true);
        $hasErrors = collect($results)->contains(fn($r) => $r['success'] === false);
        
        if ($allSuccess) {
            $post->update(['status' => 'published', 'published_at' => now()]);
        } elseif ($hasErrors && $post->status !== 'published') {
            $post->update(['status' => 'error']);
        }
        
        return $results;
    }
}