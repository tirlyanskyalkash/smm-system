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
    protected $oauthToken;
    
    public function __construct()
    {
        $this->oauthToken = env('YANDEX_GPT_OAUTH_TOKEN');
        $this->folderId = env('YANDEX_FOLDER_ID');
        $this->iamToken = env('YANDEX_IAM_TOKEN');
    }
    
    protected function refreshToken(): void
    {
        try {
            $response = Http::post('https://iam.api.cloud.yandex.net/iam/v1/tokens', [
                'yandexPassportOauthToken' => $this->oauthToken,
            ]);
            
            if ($response->successful()) {
                $this->iamToken = $response->json()['iamToken'];
                Log::info('Yandex IAM token refreshed');
            } else {
                throw new \Exception('Failed to refresh token: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function generatePost(array $params, User $user): string
    {
        $prompt = $this->buildPrompt($params);
        
        try {
            $response = $this->callYandexGPT($prompt, $params['temperature'] ?? 0.7);
            $generatedText = $response['result']['alternatives'][0]['message']['text'] ?? 'Ошибка';
            
            AiGeneration::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'generated_text' => $generatedText,
                'settings' => ['provider' => 'yandexgpt']
            ]);
            
            return $generatedText;
            
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), 'expired')) {
                $this->refreshToken();
                return $this->generatePost($params, $user);
            }
            
            Log::error('YandexGPT error: ' . $e->getMessage());
            return "❌ Ошибка: " . $e->getMessage();
        }
    }
    
    protected function callYandexGPT(string $prompt, float $temperature = 0.7): array
    {
        $url = 'https://llm.api.cloud.yandex.net/foundationModels/v1/completion';
        
        $body = [
            'modelUri' => 'gpt://' . $this->folderId . '/yandexgpt-lite',
            'completionOptions' => [
                'stream' => false,
                'temperature' => $temperature,
                'maxTokens' => 2000,
            ],
            'messages' => [
                ['role' => 'system', 'text' => $this->getSystemPrompt()],
                ['role' => 'user', 'text' => $prompt]
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
        
        return $response->json();
    }
    
    public function generateShortPost(string $topic, User $user): string
    {
        return $this->generatePost([
            'topic' => $topic,
            'length' => 'короткий, до 500 символов',
            'style' => 'лаконичный, живой',
        ], $user);
    }
    
    public function generatePostFromTopic(string $topic, User $user): string
    {
        return $this->generatePost([
            'topic' => $topic,
            'length' => 'средний, 500-1000 символов',
            'style' => 'информационный, с эмодзи',
        ], $user);
    }
    
    /**
     * Генерирует случайную тему для поста
     */
    public function generateRandomTopic(User $user): string
    {
        $prompt = "Придумай одну интересную, актуальную и цепляющую тему для поста в социальных сетях (Telegram, VK). Тема должна быть короткой (не более 10 слов), понятной и вызывать интерес. Без лишних слов, просто тема. Не используй кавычки. Примеры: 'Как найти первых клиентов', '5 ошибок в SMM', 'Почему посты не набирают охваты'.";
        
        try {
            $response = $this->callYandexGPT($prompt, 0.9);
            $topic = $response['result']['alternatives'][0]['message']['text'] ?? 'Советы по продвижению бизнеса в социальных сетях';
            
            // Очищаем тему от лишних символов
            $topic = trim($topic);
            $topic = str_replace(['"', "'", '`'], '', $topic);
            
            AiGeneration::create([
                'user_id' => $user->id,
                'prompt' => $prompt,
                'generated_text' => $topic,
                'settings' => ['provider' => 'yandexgpt', 'type' => 'random_topic']
            ]);
            
            return $topic;
            
        } catch (\Exception $e) {
            Log::error('Random topic generation error: ' . $e->getMessage());
            return 'Советы по продвижению бизнеса в социальных сетях';
        }
    }
    
    protected function getSystemPrompt(): string
    {
        return "Ты профессиональный SMM-копирайтер. Пишешь посты для Telegram и VK. "
              . "Стиль: живой, цепляющий, с эмодзи. Используй переносы строк между абзацами. "
              . "Не используй шаблонные фразы. Пиши естественно, как человек.";
    }
    
    protected function buildPrompt(array $params): string
    {
        $prompt = "Напиши пост для социальных сетей";
        if (!empty($params['topic'])) $prompt .= " на тему: {$params['topic']}";
        if (!empty($params['length'])) $prompt .= ". Длина: {$params['length']}";
        if (!empty($params['style'])) $prompt .= ". Стиль: {$params['style']}";
        if (!empty($params['cta'])) $prompt .= ". Призыв к действию: {$params['cta']}";
        return $prompt;
    }
}