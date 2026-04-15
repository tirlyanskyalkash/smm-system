<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicationLogResource\Pages;
use App\Models\PublicationLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PublicationLogResource extends Resource
{
    protected static ?string $model = PublicationLog::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Логи публикаций';
    
    protected static ?string $pluralLabel = 'Логи публикаций';
    
    protected static ?string $navigationGroup = 'Журналы';

    // Только админ может видеть логи
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canEdit($record): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Действие')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'publish' => 'success',
                        'schedule' => 'info',
                        'test' => 'warning',
                        default => 'secondary',
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        'pending' => 'warning',
                        default => 'secondary',
                    }),
                TextColumn::make('post.title')
                    ->label('Пост')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('platform.name')
                    ->label('Площадка')
                    ->searchable(),
                TextColumn::make('error_message')
                    ->label('Ошибка')
                    ->limit(50)
                    ->color('danger')
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'publish' => 'Публикация',
                        'schedule' => 'Запланировано',
                        'test' => 'Тест',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Успешно',
                        'error' => 'Ошибка',
                        'pending' => 'В ожидании',
                    ]),
                Tables\Filters\SelectFilter::make('platform_id')
                    ->label('Площадка')
                    ->relationship('platform', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPublicationLogs::route('/'),
        ];
    }
}