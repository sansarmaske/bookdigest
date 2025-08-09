<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url');
    }

    public function generateQuote(string $bookTitle, string $author, ?string $description = '')
    {
        // Use fallback only if no API key is set
        if (!$this->apiKey || $this->apiKey === 'your-gemini-api-key-here') {
            return $this->getFallbackQuote($bookTitle, $author);
        }

        try {
            $prompt = $this->buildQuotePrompt($bookTitle, $author, $description);

            $response = Http::timeout(30)
                ->post("{$this->baseUrl}/models/gemini-2.0-flash:generateContent?key={$this->apiKey}", [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 500,
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return [
                        'success' => true,
                        'quote' => trim($data['candidates'][0]['content']['parts'][0]['text']),
                    ];
                }
            }

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            // Fallback to mock quote if API fails
            return $this->getFallbackQuote($bookTitle, $author);
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception', [
                'message' => $e->getMessage(),
                'book' => $bookTitle,
                'author' => $author
            ]);

            // Fallback to mock quote if API fails
            return $this->getFallbackQuote($bookTitle, $author);
        }
    }

    protected function buildQuotePrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $prompt = "I have read this book. I am trying to digest the book .please find random passages from the book. The book is :'{$bookTitle}' by {$author}.";

        if (!empty($description)) {
            $prompt .= " Book description: {$description}.";
        }

        $prompt .= " Please provide:\n";
        $prompt .= "1. A meaningful  passage (1 paragraph)\n";
        $prompt .= "\nFormat your response as:\n";
        $prompt .= "\n Dont't say Okay, here's a random passage. Just print out passage";
        $prompt .= " the passage here\n";



        return $prompt;
    }

    protected function getFallbackQuote(string $bookTitle, string $author): array
    {
        $fallbackQuotes = [
            'The Great Gatsby' => [
                'quote' => "QUOTE: \"So we beat on, boats against the current, borne back ceaselessly into the past.\"\n\nCONTEXT: This powerful closing line from The Great Gatsby captures the novel's central theme about the impossibility of recapturing the past and the relentless march of time. It speaks to the human condition of struggling against forces beyond our control while being shaped by our history.",
            ],
            '1984' => [
                'quote' => "QUOTE: \"Freedom is the freedom to say that two plus two make four. If that is granted, all else follows.\"\n\nCONTEXT: This quote from Winston's diary represents the fundamental importance of truth and objective reality. In Orwell's dystopian world, even basic mathematical facts become acts of rebellion against totalitarian control.",
            ],
            'default' => [
                'quote' => "QUOTE: \"The best way to find out if you can trust somebody is to trust them.\"\n\nCONTEXT: This thought-provoking insight from \"{$bookTitle}\" by {$author} reminds us that trust is not just about others—it's about our willingness to be vulnerable and take meaningful risks in our relationships.",
            ]
        ];

        $quote = $fallbackQuotes[$bookTitle] ?? $fallbackQuotes['default'];

        return [
            'success' => true,
            'quote' => $quote['quote']
        ];
    }
}
