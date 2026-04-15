<?php

namespace App\Filament\Widgets;

use App\Models\Platform;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PlatformsPostsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Platform::query()
                    ->withCount('posts')
                    ->withCount(['posts as published_count' => function($query) {
                        $query->where('post_platform.status', 'published');
                    }])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Платформа / Аккаунт')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'telegram' => 'info',
                        'vk' => 'danger',
                        'dzen' => 'warning',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'telegram' => 'Telegram',
                        'vk' => 'VK',
                        'dzen' => 'Дзен',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('external_id')
                    ->label('ID канала/группы'),
                
                Tables\Columns\TextColumn::make('posts_count')
                    ->label('Всего постов')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('published_count')
                    ->label('Опубликовано')
                    ->sortable()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
            ])
            ->defaultSort('posts_count', 'desc');
    }
}