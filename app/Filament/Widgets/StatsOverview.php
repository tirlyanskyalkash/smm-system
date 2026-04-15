<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\Platform;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Всего постов', Post::count())
                ->description('Создано в системе')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            
            Stat::make('Опубликовано', Post::where('status', 'published')->count())
                ->description('Успешно опубликовано')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            
            Stat::make('Запланировано', Post::where('status', 'scheduled')->count())
                ->description('Ожидают публикации')
                ->icon('heroicon-o-calendar')
                ->color('warning'),
            
            Stat::make('Черновики', Post::where('status', 'draft')->count())
                ->description('Требуют доработки')
                ->icon('heroicon-o-pencil')
                ->color('gray'),
            
            Stat::make('Площадки', Platform::count())
                ->description('Подключено аккаунтов')
                ->icon('heroicon-o-globe-alt')
                ->color('info'),
        ];
    }
}