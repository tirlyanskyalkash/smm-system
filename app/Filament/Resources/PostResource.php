<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use App\Models\Platform;
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

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return true; }
    public static function canEdit($record): bool { return true; }
    public static function canDelete($record): bool { return true; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('user_id')->default(fn() => Auth::id()),
                
                // Выбор площадки
                Forms\Components\Select::make('platform_id')
                    ->label('Площадка для публикации')
                    ->options(Platform::where('is_active', true)->pluck('name', 'id'))
                    ->required()
                    ->live()
                    ->reactive(),
                
                // Чекбокс автоматического выбора темы
                Forms\Components\Checkbox::make('auto_topic')
                    ->label('🤖 Автоматический выбор темы')
                    ->helperText('ИИ сам придумает тему поста')
                    ->live()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if ($state) {
                            $set('topic', null);
                            $set('content', null);
                            $set('generated_content', null);
                        }
                    }),
                
                // Поле темы (активно только если чекбокс выключен)
                Forms\Components\TextInput::make('topic')
                    ->label('Тема поста')
                    ->placeholder('Например: Как продвигать Telegram-канал')
                    ->disabled(fn($get) => $get('auto_topic') == true)
                    ->live(),
                
                // Кнопка генерации
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('generate')
                        ->label('🤖 Сгенерировать пост')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->action(function ($livewire, $get, $set) {
                            $service = app(AiGenerationService::class);
                            $user = auth()->user();
                            
                            // Если автоматический выбор темы
                            if ($get('auto_topic')) {
                                // Генерируем случайную тему через AI
                                $randomTopic = $service->generateRandomTopic($user);
                                $set('topic', $randomTopic);
                                
                                // Генерируем пост на эту тему
                                $generatedText = $service->generatePostFromTopic($randomTopic, $user);
                            } else {
                                $topic = $get('topic');
                                if (empty($topic)) {
                                    Notification::make()
                                        ->title('Введите тему поста или включите автоматический выбор')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                $generatedText = $service->generatePostFromTopic($topic, $user);
                            }
                            
                            $set('generated_content', $generatedText);
                            $set('content', $generatedText);
                            
                            Notification::make()
                                ->title('✅ Пост сгенерирован!')
                                ->body('Вы можете отредактировать текст перед публикацией.')
                                ->success()
                                ->send();
                        }),
                ]),
                
                // Поле текста поста
                Forms\Components\RichEditor::make('content')
                    ->label('Текст поста')
                    ->required()
                    ->columnSpanFull(),
                
                // Кнопки действий
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('save_draft')
                                ->label('💾 Черновик')
                                ->icon('heroicon-o-document')
                                ->color('gray')
                                ->action(function ($livewire, $get, $set, $record) {
                                    $platformId = $get('platform_id');
                                    if (!$platformId) {
                                        Notification::make()
                                            ->title('Выберите площадку')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    $data = [
                                        'content' => $get('content'),
                                        'topic' => $get('topic'),
                                        'status' => 'draft',
                                        'user_id' => auth()->id(),
                                    ];
                                    
                                    if ($record) {
                                        $post = $record;
                                        $post->update($data);
                                        $post->platforms()->sync([$platformId]);
                                    } else {
                                        $post = Post::create($data);
                                        $post->platforms()->attach($platformId);
                                    }
                                    
                                    Notification::make()
                                        ->title('✅ Пост сохранён в черновики!')
                                        ->success()
                                        ->send();
                                    
                                    redirect()->to('/admin/posts');
                                }),
                            
                            Forms\Components\Actions\Action::make('publish_now')
                                ->label('📤 Опубликовать')
                                ->icon('heroicon-o-paper-airplane')
                                ->color('success')
                                ->action(function ($livewire, $get, $set, $record, PublicationService $publicationService) {
                                    $platformId = $get('platform_id');
                                    if (!$platformId) {
                                        Notification::make()
                                            ->title('Выберите площадку')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    $platform = Platform::find($platformId);
                                    if (!$platform || !$platform->is_active) {
                                        Notification::make()
                                            ->title('Площадка не активна')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    $service = app(AiGenerationService::class);
                                    $user = auth()->user();
                                    
                                    // Если автоматический выбор темы и текст не сгенерирован
                                    $content = $get('content');
                                    $topic = $get('topic');
                                    
                                    if (empty($content) || empty($topic)) {
                                        if ($get('auto_topic')) {
                                            // Генерируем тему и пост
                                            $randomTopic = $service->generateRandomTopic($user);
                                            $generatedText = $service->generatePostFromTopic($randomTopic, $user);
                                            $content = $generatedText;
                                            $topic = $randomTopic;
                                        } else {
                                            Notification::make()
                                                ->title('Сначала сгенерируйте пост')
                                                ->warning()
                                                ->send();
                                            return;
                                        }
                                    }
                                    
                                    $data = [
                                        'content' => $content,
                                        'topic' => $topic,
                                        'status' => 'published',
                                        'user_id' => auth()->id(),
                                        'published_at' => now(),
                                    ];
                                    
                                    if ($record) {
                                        $post = $record;
                                        $post->update($data);
                                        $post->platforms()->sync([$platformId]);
                                    } else {
                                        $post = Post::create($data);
                                        $post->platforms()->attach($platformId);
                                    }
                                    
                                    $result = $publicationService->publishToPlatform($post, $platform);
                                    
                                    if ($result['success']) {
                                        Notification::make()
                                            ->title('✅ Пост опубликован!')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('❌ Ошибка публикации')
                                            ->body($result['message'])
                                            ->danger()
                                            ->send();
                                    }
                                    
                                    redirect()->to('/admin/posts');
                                }),
                            
                            Forms\Components\Actions\Action::make('schedule_post')
                                ->label('📅 Запланировать')
                                ->icon('heroicon-o-calendar')
                                ->color('info')
                                ->form([
                                    Forms\Components\DateTimePicker::make('scheduled_at')
                                        ->label('Дата и время публикации')
                                        ->required()
                                        ->minDate(now()),
                                ])
                                ->action(function ($livewire, $get, $set, $record, array $data) {
                                    $platformId = $get('platform_id');
                                    if (!$platformId) {
                                        Notification::make()
                                            ->title('Выберите площадку')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    
                                    $scheduledAt = \Carbon\Carbon::parse($data['scheduled_at']);
                                    
                                    $postData = [
                                        'content' => $get('content'),
                                        'topic' => $get('topic'),
                                        'status' => 'scheduled',
                                        'user_id' => auth()->id(),
                                        'scheduled_at' => $scheduledAt,
                                    ];
                                    
                                    if ($record) {
                                        $post = $record;
                                        $post->update($postData);
                                        $post->platforms()->sync([$platformId]);
                                    } else {
                                        $post = Post::create($postData);
                                        $post->platforms()->attach($platformId);
                                    }
                                    
                                    $delay = $scheduledAt->diffInSeconds(now());
                                    if ($delay > 0) {
                                        PublishPostJob::dispatch($post, [$platformId])->delay($delay);
                                    } else {
                                        PublishPostJob::dispatch($post, [$platformId]);
                                    }
                                    
                                    Notification::make()
                                        ->title('✅ Пост запланирован!')
                                        ->body('Публикация состоится ' . $scheduledAt->format('d.m.Y H:i'))
                                        ->success()
                                        ->send();
                                    
                                    redirect()->to('/admin/posts');
                                }),
                        ]),
                    ])
                    ->columnSpanFull(),
                
                Forms\Components\Hidden::make('generated_content'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('content')
                    ->label('Текст поста')
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
                Tables\Columns\TextColumn::make('platforms.name')
                    ->label('Площадка')
                    ->badge(),
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
                Tables\Filters\SelectFilter::make('platforms')
                    ->label('Площадка')
                    ->relationship('platforms', 'name'),
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
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        $results = $publicationService->publishToMultiplePlatforms($record, $platforms->pluck('id')->toArray());
                        
                        $successCount = collect($results)->where('success', true)->count();
                        $failCount = collect($results)->where('success', false)->count();
                        
                        if ($failCount > 0) {
                            Notification::make()
                                ->title("Опубликовано частично: ✅ {$successCount}, ❌ {$failCount}")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('✅ Пост опубликован!')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn(Post $record) => $record->status !== 'published'),
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