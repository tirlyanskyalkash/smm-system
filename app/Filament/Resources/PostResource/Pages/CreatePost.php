<?php

namespace App\Filament\Resources\PostResource\Pages;

use App\Filament\Resources\PostResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    protected static string $resource = PostResource::class;
    
    // Отключаем стандартные кнопки
    protected function getFormActions(): array
    {
        return [];
    }
    
    // Переопределяем заголовок (public, не protected)
    public function getTitle(): string
    {
        return 'Создание поста';
    }
    
    // Отключаем редирект после создания (у нас своя логика)
    protected function getRedirectUrl(): string
    {
        return '';
    }
}