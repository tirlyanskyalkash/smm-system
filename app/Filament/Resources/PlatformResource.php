<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlatformResource\Pages;
use App\Models\Platform;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static ?string $navigationLabel = 'Площадки';
    
    protected static ?string $pluralLabel = 'Площадки';
    
    protected static ?string $navigationGroup = 'Настройки';

    // Только админ может видеть площадки
    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canEdit($record): bool
    {
        return auth()->user()?->role === 'admin';
    }
    
    public static function canDelete($record): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Тип платформы')
                    ->options([
                        'telegram' => 'Telegram',
                        'vk' => 'VK',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('external_id')
                    ->label('ID канала/группы')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('access_token')
                    ->label('Токен доступа')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true),
                Forms\Components\Textarea::make('comment')
                    ->label('Комментарий')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_id')
                    ->label('Внешний ID'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'telegram' => 'Telegram',
                        'vk' => 'VK',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активность'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatforms::route('/'),
            'create' => Pages\CreatePlatform::route('/create'),
            'edit' => Pages\EditPlatform::route('/{record}/edit'),
        ];
    }
}