<?php

namespace App\Services;

use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiGenerationService
{
    protected $iamToken;
    protected $folderId;
    
    public function __construct()
    {
        $this->iamToken = env('YANDEX_IAM_TOKEN');
        $this->folderId = env('YANDEX_FOLDER_ID');
    }
    
    public function generatePost(array $params, User $user): string
    {
        $prompt = $this->buildPrompt($params);
        
        try {
            $url = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';
            
            $body = [
                'modelUri' => 'gpt://' . $this->folderId . '/yandexgpt-lite',
                'completionOptions' => [
                    'stream' => false,
                    'temperature' => 0.7,
                    'maxTokens' => 2000,
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'text' => $this->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'text' => $prompt
                    ]
                ],
            ];
            
            $response = Http::withOptions(['verify' => false])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->iamToken,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, $body);
            
            if (!$response->successful()) {
                throw new \Exception('API Error: ' . $response->body());
            }
            
            $generatedText = $response->json()['result']['alternatives'][0]['message']['text'] ?? 'Ошибка';
            
            AiGeneration::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'generated_text' => $generatedText,
                'settings' => ['provider' => 'yandexgpt-iam']
            ]);
            
            return $generatedText;
            
        } catch (\Exception $e) {
            Log::error('YandexGPT error: ' . $e->getMessage());
            return "❌ Ошибка: " . $e->getMessage();
        }
    }
    
    public function generateShortPost(string $topic, User $user): string
    {
        return $this->generatePost([
            'topic' => $topic,
            'length' => 'короткий, до 500 символов',
            'style' => 'лаконичный',
        ], $user);
    }
    
    protected function getSystemPrompt(): string
    {
        return "Ты SMM-копирайтер. Пиши посты для Telegram и VK. Стиль: живой, с эмодзи.";
    }
    
    protected function buildPrompt(array $params): string
    {
        $prompt = "Напиши пост для соцсетей";
        if (!empty($params['topic'])) $prompt .= " на тему: {$params['topic']}";
        if (!empty($params['length'])) $prompt .= ". Длина: {$params['length']}";
        if (!empty($params['style'])) $prompt .= ". Стиль: {$params['style']}";
        return $prompt;
    }
}