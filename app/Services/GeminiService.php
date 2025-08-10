<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $baseUrl;
    protected $model;
    protected $timeout;
    protected $maxOutputTokens;
    protected $temperature;
    protected $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = config('services.gemini.base_url');
        $this->model = config('services.gemini.model');
        $this->timeout = config('services.gemini.timeout');
        $this->maxOutputTokens = config('services.gemini.max_output_tokens');
        $this->temperature = config('services.gemini.temperature');
        $this->enabled = config('services.gemini.enabled');
    }

    public function generateQuote(string $bookTitle, string $author, ?string $description = ''): array
    {
        if (empty($bookTitle) || empty($author)) {
            Log::warning('Invalid input for quote generation', [
                'title_empty' => empty($bookTitle),
                'author_empty' => empty($author)
            ]);
            
            return [
                'success' => false,
                'error' => 'Book title and author are required for quote generation.',
                'quote' => null
            ];
        }

        // Use fallback if service is disabled or no API key is configured
        if (!$this->enabled || !$this->apiKey || $this->apiKey === 'your-gemini-api-key-here') {
            Log::info('Using fallback quotes due to missing API configuration', [
                'book' => $bookTitle,
                'author' => $author
            ]);
            return $this->getFallbackQuote($bookTitle, $author);
        }

        try {
            $prompt = $this->buildQuotePrompt($bookTitle, $author, $description);
            
            Log::debug('Making Gemini API request', [
                'book' => $bookTitle,
                'author' => $author,
                'prompt_length' => strlen($prompt)
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
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
                        'temperature' => $this->temperature,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => $this->maxOutputTokens,
                        'stopSequences' => []
                    ],
                    'safetySettings' => [
                        [
                            'category' => 'HARM_CATEGORY_HARASSMENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_HATE_SPEECH',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ],
                        [
                            'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                            'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::debug('Gemini API response received', [
                    'book' => $bookTitle,
                    'response_status' => $response->status(),
                    'has_candidates' => isset($data['candidates'])
                ]);

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $quote = trim($data['candidates'][0]['content']['parts'][0]['text']);
                    
                    if (empty($quote)) {
                        Log::warning('Empty quote received from Gemini API', [
                            'book' => $bookTitle,
                            'author' => $author
                        ]);
                        return $this->getFallbackQuote($bookTitle, $author);
                    }
                    
                    return [
                        'success' => true,
                        'quote' => $quote,
                        'source' => 'gemini_api'
                    ];
                }

                if (isset($data['candidates'][0]['finishReason'])) {
                    Log::warning('Gemini API content filtered', [
                        'book' => $bookTitle,
                        'author' => $author,
                        'finish_reason' => $data['candidates'][0]['finishReason']
                    ]);
                }
            }

            Log::error('Gemini API Error', [
                'book' => $bookTitle,
                'author' => $author,
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            // Fallback to mock quote if API fails
            return $this->getFallbackQuote($bookTitle, $author);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Error', [
                'message' => $e->getMessage(),
                'book' => $bookTitle,
                'author' => $author,
                'type' => 'connection_error'
            ]);

            return $this->getFallbackQuote($bookTitle, $author);
            
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Gemini API Request Error', [
                'message' => $e->getMessage(),
                'book' => $bookTitle,
                'author' => $author,
                'type' => 'request_error',
                'response_body' => $e->response?->body()
            ]);

            return $this->getFallbackQuote($bookTitle, $author);
            
        } catch (\Exception $e) {
            Log::error('Gemini Service Unexpected Exception', [
                'message' => $e->getMessage(),
                'book' => $bookTitle,
                'author' => $author,
                'type' => 'unexpected_error',
                'trace' => $e->getTraceAsString()
            ]);

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
