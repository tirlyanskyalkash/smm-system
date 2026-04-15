<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Services\PublicationService;
use App\Services\AiGenerationService;
use App\Jobs\PublishPostJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Посты';

    protected static ?string $pluralLabel = 'Посты';

    public static function canViewAny(): bool
    {
        return true;
    }
    
    public static function canCreate(): bool
    {
        return true;
    }
    
    public static function canEdit($record): bool
    {
        return true;
    }
    
    public static function canDelete($record): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')
                    ->default(fn() => Auth::id()),
                
                Forms\Components\TextInput::make('title')
                    ->label('Заголовок')
                    ->maxLength(255),
                
                // Блок AI-генерации
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('ai_topic')
                                    ->label('Тема для генерации')
                                    ->placeholder('Например: Как продвигать Telegram-канал')
                                    ->live(),
                                Forms\Components\Select::make('ai_style')
                                    ->label('Стиль')
                                    ->options([
                                        'информационный' => 'Информационный',
                                        'продающий' => 'Продающий',
                                        'развлекательный' => 'Развлекательный',
                                        'экспертный' => 'Экспертный',
                                    ])
                                    ->default('информационный'),
                                Forms\Components\Select::make('ai_length')
                                    ->label('Длина')
                                    ->options([
                                        'короткий' => 'Короткий',
                                        'средний' => 'Средний',
                                        'длинный' => 'Длинный',
                                    ])
                                    ->default('средний'),
                            ]),
                        
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('generate')
                                ->label('🤖 Сгенерировать текст')
                                ->icon('heroicon-o-sparkles')
                                ->color('success')
                                ->action(function ($livewire, $get, $set) {
                                    $topic = $get('ai_topic');
                                    if (empty($topic)) {
                                        Notification::make()
                                            ->title('Введите тему поста')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    $service = app(AiGenerationService::class);
                                    $user = auth()->user();
                                    
                                    $generatedText = $service->generatePost([
                                        'topic' => $topic,
                                        'style' => $get('ai_style'),
                                        'length' => $get('ai_length'),
                                    ], $user);
                                    
                                    $set('ai_generated_text', $generatedText);
                                    
                                    Notification::make()
                                        ->title('✅ Текст сгенерирован!')
                                        ->body('Скопируйте текст и вставьте в редактор ниже.')
                                        ->success()
                                        ->send();
                                }),
                        ]),
                        
                        Forms\Components\Textarea::make('ai_generated_text')
                            ->label('Сгенерированный текст')
                            ->helperText('Нажмите Ctrl+C чтобы скопировать, затем вставьте в редактор выше')
                            ->rows(6)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                
                Forms\Components\RichEditor::make('content')
                    ->label('Текст поста')
                    ->required()
                    ->columnSpanFull(),
                
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'ready' => 'Готов к публикации',
                        'published' => 'Опубликован',
                        'scheduled' => 'Запланирован',
                        'error' => 'Ошибка',
                    ])
                    ->default('draft'),
                
                Forms\Components\Select::make('platforms')
                    ->label('Площадки для публикации')
                    ->relationship('platforms', 'name')
                    ->multiple()
                    ->preload(),
                
                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Запланированная публикация'),
                
                Forms\Components\FileUpload::make('media')
                    ->label('Изображения')
                    ->multiple()
                    ->image()
                    ->directory('posts'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'draft' => 'gray',
                        'ready' => 'info',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        'error' => 'danger',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Автор'),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Запланирован')
                    ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Опубликован')
                    ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Черновик',
                        'ready' => 'Готов',
                        'published' => 'Опубликован',
                        'scheduled' => 'Запланирован',
                        'error' => 'Ошибка',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Action::make('publish')
                    ->label('Опубликовать')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Post $record, PublicationService $publicationService) {
                        $platforms = $record->platforms()->where('is_active', true)->get();
                        
                        if ($platforms->isEmpty()) {
                            Notification::make()
                                ->title('Нет выбранных площадок')
                                ->body('Сначала выберите площадки для публикации в форме редактирования поста')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        $results = $publicationService->publishToMultiplePlatforms($record, $platforms->pluck('id')->toArray());
                        
                        $successCount = collect($results)->where('success', true)->count();
                        $failCount = collect($results)->where('success', false)->count();
                        
                        if ($failCount > 0) {
                            $errors = collect($results)
                                ->filter(fn($r) => !$r['success'])
                                ->map(fn($r, $platformId) => $platforms->find($platformId)?->name . ': ' . ($r['message'] ?? 'Неизвестная ошибка'))
                                ->implode("\n");
                            
                            Notification::make()
                                ->title("Опубликовано частично")
                                ->body("✅ Успешно: {$successCount}\n❌ Ошибок: {$failCount}\n\nОшибки:\n{$errors}")
                                ->warning()
                                ->persistent()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('✅ Пост успешно опубликован!')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn(Post $record) => $record->status !== 'published'),
                
                Action::make('schedule')
                    ->label('Запланировать')
                    ->icon('heroicon-o-calendar')
                    ->color('info')
                    ->form([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Дата и время публикации')
                            ->required()
                            ->minDate(now()),
                    ])
                    ->action(function (Post $record, array $data) {
                        $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at']);
                        
                        $record->update([
                            'scheduled_at' => $scheduledAt,
                            'status' => 'scheduled',
                        ]);
                        
                        $platformIds = $record->platforms()->pluck('platforms.id')->toArray();
                        
                        if (!empty($platformIds)) {
                            $delay = $scheduledAt->diffInSeconds(now());
                            
                            if ($delay > 0) {
                                PublishPostJob::dispatch($record, $platformIds)->delay($delay);
                            } else {
                                PublishPostJob::dispatch($record, $platformIds);
                            }
                        }
                        
                        Notification::make()
                            ->title('✅ Пост запланирован!')
                            ->body('Публикация состоится ' . $scheduledAt->format('d.m.Y H:i'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Post $record) => $record->status !== 'published' && $record->status !== 'scheduled'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}