<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PublicationService;
use App\Services\Telegram\TelegramPublisher;
use App\Services\Vk\VkPublisher;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PublicationService::class, function ($app) {
            $service = new PublicationService();
            
            // Регистрируем адаптеры для платформ
            $service->registerPublisher('telegram', new TelegramPublisher());
            $service->registerPublisher('vk', new VkPublisher());
            
            return $service;
        });
    }

    public function boot(): void
    {
        //
    }
}