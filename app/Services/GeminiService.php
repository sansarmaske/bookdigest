<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class GeminiService extends BaseAIService
{
    private const SAFETY_THRESHOLD = 'BLOCK_MEDIUM_AND_ABOVE';

    protected int $maxOutputTokens;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url');
        $this->model = config('services.gemini.model');
        $this->timeout = config('services.gemini.timeout', 30);
        $this->maxOutputTokens = config('services.gemini.max_output_tokens', 1000);
        $this->maxTokens = $this->maxOutputTokens;
        $this->temperature = config('services.gemini.temperature', 0.7);
        $this->enabled = config('services.gemini.enabled', true);
    }

    protected function makeContentRequest(string $prompt, string $type): array
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => $this->temperature,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => $this->maxOutputTokens,
                    'stopSequences' => [],
                ],
                'safetySettings' => $this->getSafetySettings(),
            ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $content = trim($data['candidates'][0]['content']['parts'][0]['text']);

                if (! empty($content)) {
                    $result = [
                        'success' => true,
                        'source' => 'gemini_api',
                    ];

                    if ($type === 'quote') {
                        $result['quote'] = $content;
                    } else {
                        $result['content'] = $content;
                    }

                    return $result;
                }
            }

            if (isset($data['candidates'][0]['finishReason'])) {
                Log::warning('Gemini API content filtered', [
                    'type' => $type,
                    'finish_reason' => $data['candidates'][0]['finishReason'],
                ]);
            }
        }

        Log::error('Gemini API Error', [
            'type' => $type,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception("Gemini API request failed for {$type}");
    }

    protected function makeBookInfoRequest(string $prompt): string
    {
        $response = Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => self::BOOK_INFO_TEMPERATURE,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => self::BOOK_INFO_MAX_TOKENS,
                    'stopSequences' => [],
                ],
                'safetySettings' => $this->getSafetySettings(),
            ]);

        if ($response->successful()) {
            $data = $response->json();

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
        }

        Log::error('Gemini API Error for book info', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception('Gemini API request failed for book info');
    }

    private function getSafetySettings(): array
    {
        return [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => self::SAFETY_THRESHOLD],
        ];
    }

    public function isAvailable(): bool
    {
        return $this->enabled &&
               $this->apiKey &&
               $this->apiKey !== 'your-gemini-api-key-here';
    }

    public function getProviderName(): string
    {
        return 'gemini';
    }
}
