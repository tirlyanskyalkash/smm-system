<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Jobs\PublishPostJob;
use Illuminate\Console\Command;

class CheckScheduledPosts extends Command
{
    protected $signature = 'posts:check-scheduled';
    protected $description = 'Проверяет запланированные посты и отправляет их в очередь';

    public function handle()
    {
        $posts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();
        
        foreach ($posts as $post) {
            $platformIds = $post->platforms()->pluck('platforms.id')->toArray();
            
            if (!empty($platformIds)) {
                PublishPostJob::dispatch($post, $platformIds);
                $this->info("Пост #{$post->id} отправлен в очередь");
                
                // Обновляем статус, чтобы не отправить повторно
                $post->update(['status' => 'processing']);
            }
        }
        
        $this->info("Проверено. Найдено постов: " . $posts->count());
    }
}