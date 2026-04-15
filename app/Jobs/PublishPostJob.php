<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\PublicationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $post;
    protected $platformIds;

    public function __construct(Post $post, array $platformIds)
    {
        $this->post = $post;
        $this->platformIds = $platformIds;
    }

    public function handle(PublicationService $publicationService)
    {
        // Если пост уже опубликован — не публикуем
        if ($this->post->status === 'published') {
            return;
        }
        
        $results = $publicationService->publishToMultiplePlatforms($this->post, $this->platformIds);
        
        // Логирование результатов уже внутри publishToMultiplePlatforms
    }
}