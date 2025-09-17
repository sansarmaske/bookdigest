<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GroqService extends BaseAIService
{
    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->baseUrl = config('services.groq.base_url', 'https://api.groq.com/openai/v1');
        $this->model = config('services.groq.model', 'llama-3.3-70b-versatile');
        $this->timeout = config('services.groq.timeout', 30);
        $this->maxTokens = config('services.groq.max_tokens', 1000);
        $this->temperature = config('services.groq.temperature', 0.7);
        $this->enabled = config('services.groq.enabled', true);
    }

    protected function makeContentRequest(string $prompt, string $type): array
    {
        $response = $this->makeApiRequest($prompt);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['choices'][0]['message']['content'])) {
                $content = trim($data['choices'][0]['message']['content']);

                if (! empty($content)) {
                    $result = [
                        'success' => true,
                        'source' => 'groq_api',
                    ];

                    if ($type === 'quote') {
                        $result['quote'] = $content;
                    } else {
                        $result['content'] = $content;
                    }

                    return $result;
                }
            }
        }

        Log::error('Groq API Error', [
            'type' => $type,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception("Groq API request failed for {$type}");
    }

    protected function makeBookInfoRequest(string $prompt): string
    {
        $response = $this->makeApiRequest($prompt, self::BOOK_INFO_TEMPERATURE, self::BOOK_INFO_MAX_TOKENS);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }
        }

        Log::error('Groq API Error for book info', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception('Groq API request failed for book info');
    }

    private function makeApiRequest(string $prompt, ?float $temperature = null, ?int $maxTokens = null): \Illuminate\Http\Client\Response
    {
        return Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $temperature ?? $this->temperature,
                'max_tokens' => $maxTokens ?? $this->maxTokens,
                'top_p' => 0.95,
                'stream' => false,
            ]);
    }

    public function isAvailable(): bool
    {
        return $this->enabled &&
               $this->apiKey &&
               $this->apiKey !== 'your-groq-api-key-here';
    }

    public function getProviderName(): string
    {
        return 'groq';
    }
}
