<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    // Constants for configuration
    private const MIN_TITLE_LENGTH = 3;
    private const BOOK_INFO_TEMPERATURE = 0.3;
    private const BOOK_INFO_MAX_TOKENS = 1000;
    private const SAFETY_THRESHOLD = 'BLOCK_MEDIUM_AND_ABOVE';
    
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

    public function getBookInfo(string $partialTitle): array
    {
        if (!$this->isValidTitleInput($partialTitle)) {
            return $this->createErrorResponse('Title must be at least ' . self::MIN_TITLE_LENGTH . ' characters long.');
        }

        if (!$this->isServiceEnabled()) {
            return $this->getFallbackBookSuggestions($partialTitle);
        }

        try {
            return $this->fetchBookInfoFromApi($partialTitle);
        } catch (\Exception $e) {
            $this->logBookInfoError($e, $partialTitle);
            return $this->getFallbackBookSuggestions($partialTitle);
        }
    }

    private function isValidTitleInput(string $partialTitle): bool
    {
        return !empty($partialTitle) && strlen(trim($partialTitle)) >= self::MIN_TITLE_LENGTH;
    }

    private function isServiceEnabled(): bool
    {
        return $this->enabled && 
               $this->apiKey && 
               $this->apiKey !== 'your-gemini-api-key-here';
    }

    private function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'suggestions' => []
        ];
    }

    private function fetchBookInfoFromApi(string $partialTitle): array
    {
        $prompt = $this->buildBookInfoPrompt($partialTitle);
        
        Log::debug('Making Gemini API request for book info', [
            'partial_title' => $partialTitle,
            'prompt_length' => strlen($prompt)
        ]);

        $response = $this->makeApiRequest($prompt, $this->getBookInfoConfig());

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $responseText = trim($data['candidates'][0]['content']['parts'][0]['text']);
                return $this->parseBookInfoResponse($responseText);
            }
        }

        Log::error('Gemini API Error for book info', [
            'partial_title' => $partialTitle,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return $this->getFallbackBookSuggestions($partialTitle);
    }

    private function getBookInfoConfig(): array
    {
        return [
            'temperature' => self::BOOK_INFO_TEMPERATURE,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => self::BOOK_INFO_MAX_TOKENS,
            'stopSequences' => []
        ];
    }

    private function getSafetySettings(): array
    {
        return [
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => self::SAFETY_THRESHOLD],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => self::SAFETY_THRESHOLD]
        ];
    }

    private function makeApiRequest(string $prompt, array $generationConfig): \Illuminate\Http\Client\Response
    {
        return Http::timeout($this->timeout)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => $generationConfig,
                'safetySettings' => $this->getSafetySettings()
            ]);
    }

    private function logBookInfoError(\Exception $e, string $partialTitle): void
    {
        Log::error('Gemini Service Exception for book info', [
            'message' => $e->getMessage(),
            'partial_title' => $partialTitle,
            'type' => 'unexpected_error'
        ]);
    }

    protected function buildBookInfoPrompt(string $partialTitle): string
    {
        return "Based on the partial book title: '{$partialTitle}', please provide up to 3 book suggestions that match this title. For each book, provide ONLY the following information in this exact JSON format:

{
  \"suggestions\": [
    {
      \"title\": \"Full book title\",
      \"author\": \"Author name\",
      \"publication_year\": year,
      \"genre\": \"Genre\",
      \"description\": \"Brief description (2-3 sentences)\"
    }
  ]
}

Only include well-known, published books. Provide accurate information only.";
    }

    protected function parseBookInfoResponse(string $responseText): array
    {
        try {
            // Try to extract JSON from the response
            $jsonStart = strpos($responseText, '{');
            $jsonEnd = strrpos($responseText, '}');
            
            if ($jsonStart !== false && $jsonEnd !== false) {
                $jsonText = substr($responseText, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($jsonText, true);
                
                if (isset($parsed['suggestions']) && is_array($parsed['suggestions'])) {
                    return [
                        'success' => true,
                        'suggestions' => $parsed['suggestions']
                    ];
                }
            }
            
            return [
                'success' => false,
                'error' => 'Could not parse book information from response.',
                'suggestions' => []
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to parse book info response', [
                'error' => $e->getMessage(),
                'response_text' => $responseText
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to parse book information.',
                'suggestions' => []
            ];
        }
    }

    protected function getFallbackBookSuggestions(string $partialTitle): array
    {
        $fallbackSuggestions = [
            'great' => [
                [
                    'title' => 'The Great Gatsby',
                    'author' => 'F. Scott Fitzgerald',
                    'publication_year' => 1925,
                    'genre' => 'Fiction',
                    'description' => 'A classic American novel about the Jazz Age and the American Dream. The story follows Nick Carraway as he observes the tragic story of Jay Gatsby.'
                ]
            ],
            '1984' => [
                [
                    'title' => '1984',
                    'author' => 'George Orwell',
                    'publication_year' => 1949,
                    'genre' => 'Dystopian Fiction',
                    'description' => 'A dystopian social science fiction novel about totalitarian control. The story follows Winston Smith as he struggles against the oppressive regime of Big Brother.'
                ]
            ],
            'pride' => [
                [
                    'title' => 'Pride and Prejudice',
                    'author' => 'Jane Austen',
                    'publication_year' => 1813,
                    'genre' => 'Romance',
                    'description' => 'A romantic novel that follows Elizabeth Bennet as she deals with issues of manners, upbringing, morality, education, and marriage.'
                ]
            ]
        ];

        $partialTitleLower = strtolower($partialTitle);
        
        foreach ($fallbackSuggestions as $key => $suggestions) {
            if (strpos($partialTitleLower, $key) !== false) {
                return [
                    'success' => true,
                    'suggestions' => $suggestions
                ];
            }
        }

        return [
            'success' => true,
            'suggestions' => []
        ];
    }
}
