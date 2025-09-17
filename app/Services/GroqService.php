<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Contracts\AIServiceInterface;

class GroqService implements AIServiceInterface
{
    private const MIN_TITLE_LENGTH = 3;

    private const BOOK_INFO_TEMPERATURE = 0.3;

    private const BOOK_INFO_MAX_TOKENS = 1000;

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

    protected ?string $apiKey;

    protected string $baseUrl;

    protected string $model;

    protected int $timeout;

    protected int $maxTokens;

    protected float $temperature;

    protected bool $enabled;

    public function generateQuote(string $bookTitle, string $author, ?string $description = ''): array
    {
        if (empty($bookTitle) || empty($author)) {
            Log::warning('Invalid input for quote generation', [
                'title_empty' => empty($bookTitle),
                'author_empty' => empty($author),
            ]);

            return [
                'success' => false,
                'error' => 'Book title and author are required for quote generation.',
                'quote' => null,
            ];
        }

        if (! $this->isAvailable()) {
            Log::info('Using fallback quotes due to missing API configuration', [
                'book' => $bookTitle,
                'author' => $author,
            ]);

            return $this->getFallbackQuote($bookTitle, $author);
        }

        try {
            $prompt = $this->buildQuotePrompt($bookTitle, $author, $description);
            $response = $this->makeApiRequest($prompt);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $quote = trim($data['choices'][0]['message']['content']);

                    if (empty($quote)) {
                        Log::warning('Empty quote received from Groq API', [
                            'book' => $bookTitle,
                            'author' => $author,
                        ]);

                        return $this->getFallbackQuote($bookTitle, $author);
                    }

                    return [
                        'success' => true,
                        'quote' => $quote,
                        'source' => 'groq_api',
                    ];
                }
            }

            Log::error('Groq API Error', [
                'book' => $bookTitle,
                'author' => $author,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->getFallbackQuote($bookTitle, $author);

        } catch (\Exception $e) {
            Log::error('Groq Service Exception', [
                'message' => $e->getMessage(),
                'book' => $bookTitle,
                'author' => $author,
                'type' => 'unexpected_error',
            ]);

            return $this->getFallbackQuote($bookTitle, $author);
        }
    }

    public function getBookInfo(string $partialTitle): array
    {
        if (! $this->isValidTitleInput($partialTitle)) {
            return $this->createErrorResponse('Title must be at least '.self::MIN_TITLE_LENGTH.' characters long.');
        }

        if (! $this->isAvailable()) {
            return $this->getFallbackBookSuggestions($partialTitle);
        }

        try {
            $prompt = $this->buildBookInfoPrompt($partialTitle);
            $response = $this->makeApiRequest($prompt, self::BOOK_INFO_TEMPERATURE, self::BOOK_INFO_MAX_TOKENS);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $responseText = trim($data['choices'][0]['message']['content']);

                    return $this->parseBookInfoResponse($responseText);
                }
            }

            Log::error('Groq API Error for book info', [
                'partial_title' => $partialTitle,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->getFallbackBookSuggestions($partialTitle);

        } catch (\Exception $e) {
            Log::error('Groq Service Exception for book info', [
                'message' => $e->getMessage(),
                'partial_title' => $partialTitle,
                'type' => 'unexpected_error',
            ]);

            return $this->getFallbackBookSuggestions($partialTitle);
        }
    }

    public function generateTodaysSnippet(string $bookTitle, string $author, ?string $description = ''): array
    {
        if (empty($bookTitle) || empty($author)) {
            return $this->createErrorResponse('Book title and author are required.');
        }

        if (! $this->isAvailable()) {
            return $this->getFallbackSnippet($bookTitle, $author);
        }

        try {
            $prompt = $this->buildSnippetPrompt($bookTitle, $author, $description);
            $response = $this->makeApiRequest($prompt);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $content = trim($data['choices'][0]['message']['content']);

                    if (! empty($content)) {
                        return [
                            'success' => true,
                            'content' => $content,
                            'source' => 'groq_api',
                        ];
                    }
                }
            }

            throw new \Exception('Groq API request failed for snippet');
        } catch (\Exception $e) {
            Log::error('Today\'s snippet generation failed', [
                'book' => $bookTitle,
                'author' => $author,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackSnippet($bookTitle, $author);
        }
    }

    public function generateCrossBookConnection(array $books): array
    {
        if (count($books) < 2) {
            return $this->createErrorResponse('At least 2 books are required for cross-book connections.');
        }

        if (! $this->isAvailable()) {
            return $this->getFallbackConnection($books);
        }

        try {
            $prompt = $this->buildConnectionPrompt($books);
            $response = $this->makeApiRequest($prompt);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $content = trim($data['choices'][0]['message']['content']);

                    if (! empty($content)) {
                        return [
                            'success' => true,
                            'content' => $content,
                            'source' => 'groq_api',
                        ];
                    }
                }
            }

            throw new \Exception('Groq API request failed for connection');
        } catch (\Exception $e) {
            Log::error('Cross-book connection generation failed', [
                'books_count' => count($books),
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackConnection($books);
        }
    }

    public function generateQuoteToPonder(string $bookTitle, string $author, ?string $description = ''): array
    {
        if (empty($bookTitle) || empty($author)) {
            return $this->createErrorResponse('Book title and author are required.');
        }

        if (! $this->isAvailable()) {
            return $this->getFallbackQuoteToPonder($bookTitle, $author);
        }

        try {
            $prompt = $this->buildQuoteToPonderPrompt($bookTitle, $author, $description);
            $response = $this->makeApiRequest($prompt);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $content = trim($data['choices'][0]['message']['content']);

                    if (! empty($content)) {
                        return [
                            'success' => true,
                            'content' => $content,
                            'source' => 'groq_api',
                        ];
                    }
                }
            }

            throw new \Exception('Groq API request failed for quote to ponder');
        } catch (\Exception $e) {
            Log::error('Quote to ponder generation failed', [
                'book' => $bookTitle,
                'author' => $author,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackQuoteToPonder($bookTitle, $author);
        }
    }

    public function generateTodaysReflection(array $books): array
    {
        if (empty($books)) {
            return $this->createErrorResponse('At least one book is required for reflection.');
        }

        if (! $this->isAvailable()) {
            return $this->getFallbackReflection($books);
        }

        try {
            $prompt = $this->buildReflectionPrompt($books);
            $response = $this->makeApiRequest($prompt);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['choices'][0]['message']['content'])) {
                    $content = trim($data['choices'][0]['message']['content']);

                    if (! empty($content)) {
                        return [
                            'success' => true,
                            'content' => $content,
                            'source' => 'groq_api',
                        ];
                    }
                }
            }

            throw new \Exception('Groq API request failed for reflection');
        } catch (\Exception $e) {
            Log::error('Today\'s reflection generation failed', [
                'books_count' => count($books),
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackReflection($books);
        }
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

    private function buildQuotePrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $passageTypes = [
            'a thought-provoking philosophical passage',
            'a pivotal character development moment',
            'a beautifully descriptive scene',
            'an emotionally powerful dialogue',
            'a passage that reveals the book\'s central theme',
            'an intriguing plot turning point',
            'a memorable character interaction',
            'a passage with rich symbolism or metaphor',
            'a moment of internal conflict or revelation',
            'a striking opening or closing passage from a chapter',
        ];

        $analysisAngles = [
            'focus on the literary techniques used',
            'examine the character psychology',
            'explore the cultural or historical context',
            'analyze the symbolism and deeper meaning',
            'discuss the emotional impact',
            'consider the philosophical implications',
            'highlight the unique writing style',
            'examine the social commentary',
            'discuss how it connects to the overall narrative',
            'analyze the use of language and imagery',
        ];

        $randomPassageType = $passageTypes[array_rand($passageTypes)];
        $randomAnalysisAngle = $analysisAngles[array_rand($analysisAngles)];
        $randomSeed = mt_rand(1, 1000000);

        $prompt = "Random seed: {$randomSeed}\n\n";
        $prompt .= "I need a book digest passage from '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "Please select {$randomPassageType} and {$randomAnalysisAngle}.\n\n";

        $prompt .= "IMPORTANT VARIETY REQUIREMENTS:\n";
        $prompt .= "- Choose a DIFFERENT section of the book each time\n";
        $prompt .= "- Vary your selection strategy (beginning, middle, end, or thematically significant moments)\n";
        $prompt .= "- For lesser-known books, draw from your training knowledge creatively but accurately\n";
        $prompt .= "- Avoid repeating the same passages or themes from previous requests\n";
        $prompt .= "- Focus on passages that showcase different aspects of the author's writing\n\n";

        $prompt .= "Provide exactly one meaningful passage (1 short paragraph maximum) with NO introductory text like 'Here's a passage' or 'From the book'. ";
        $prompt .= "Simply provide the passage content directly, ensuring it represents the book authentically and offers genuine literary insight.\n\n";

        $prompt .= "The passage should be concise yet impactful to give readers a real taste of the author's voice and the book's essence.";

        return $prompt;
    }

    private function buildSnippetPrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $randomSeed = mt_rand(1, 1000000);

        $prompt = "Random seed: {$randomSeed}\n\n";
        $prompt .= "Generate a compelling paragraph-long excerpt from '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "Requirements:\n";
        $prompt .= "- Choose a different, random section each time to avoid repetition\n";
        $prompt .= "- Keep it concise (2-3 sentences, maximum 1 short paragraph)\n";
        $prompt .= "- Select passages that showcase the author's unique voice and style\n";
        $prompt .= "- Focus on memorable, impactful moments from the book\n";
        $prompt .= "- Provide ONLY the excerpt content, no introductory text\n\n";
        $prompt .= "The excerpt should be engaging and representative of the book's essence.";

        return $prompt;
    }

    private function buildConnectionPrompt(array $books): string
    {
        $booksList = '';
        foreach ($books as $book) {
            $booksList .= "- '{$book['title']}' by {$book['author']}\n";
        }

        $prompt = "Generate an insightful connection between these books from the user's reading list:\n";
        $prompt .= $booksList;
        $prompt .= "\nRequirements:\n";
        $prompt .= "- Find a meaningful thematic, philosophical, or stylistic connection\n";
        $prompt .= "- Make it thought-provoking and intellectually engaging\n";
        $prompt .= "- Keep it concise but substantive (2-3 sentences)\n";
        $prompt .= "- Focus on how the books complement or contrast with each other\n";
        $prompt .= "- Provide only the connection insight, no introductory text\n";

        return $prompt;
    }

    private function buildQuoteToPonderPrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $randomSeed = mt_rand(1, 1000000);

        $prompt = "Random seed: {$randomSeed}\n\n";
        $prompt .= "Select a profound, thought-provoking quote from '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "Requirements:\n";
        $prompt .= "- Choose a different quote each time to ensure variety\n";
        $prompt .= "- Select quotes that are philosophically rich or emotionally resonant\n";
        $prompt .= "- Focus on quotes that make readers pause and think\n";
        $prompt .= "- Provide ONLY the quote text, no context or explanation\n";
        $prompt .= "- Ensure the quote is authentic to the book and author's voice\n";

        return $prompt;
    }

    private function buildReflectionPrompt(array $books): string
    {
        $booksList = '';
        foreach ($books as $book) {
            $booksList .= "- '{$book['title']}' by {$book['author']}\n";
        }

        $prompt = "Based on these books from the user's reading list:\n";
        $prompt .= $booksList;
        $prompt .= "\nGenerate a thoughtful reflection question or insight for today. Requirements:\n";
        $prompt .= "- Create a question or prompt that encourages deep thinking\n";
        $prompt .= "- Draw from themes, ideas, or lessons found in these books\n";
        $prompt .= "- Make it personally applicable and actionable\n";
        $prompt .= "- Keep it concise but meaningful (1-2 sentences)\n";
        $prompt .= "- Focus on personal growth, wisdom, or practical application\n";
        $prompt .= "- Provide only the reflection content, no introductory text\n";

        return $prompt;
    }

    private function buildBookInfoPrompt(string $partialTitle): string
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

    // Validation and helper methods
    private function isValidTitleInput(string $partialTitle): bool
    {
        return ! empty($partialTitle) && strlen(trim($partialTitle)) >= self::MIN_TITLE_LENGTH;
    }

    private function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'suggestions' => [],
        ];
    }

    private function parseBookInfoResponse(string $responseText): array
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
                        'suggestions' => $parsed['suggestions'],
                    ];
                }
            }

            return [
                'success' => false,
                'error' => 'Could not parse book information from response.',
                'suggestions' => [],
            ];

        } catch (\Exception $e) {
            Log::error('Failed to parse book info response', [
                'error' => $e->getMessage(),
                'response_text' => $responseText,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse book information.',
                'suggestions' => [],
            ];
        }
    }

    // Fallback methods (same as GeminiService for consistency)
    private function getFallbackQuote(string $bookTitle, string $author): array
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
            ],
        ];

        $quote = $fallbackQuotes[$bookTitle] ?? $fallbackQuotes['default'];

        return [
            'success' => true,
            'quote' => $quote['quote'],
        ];
    }

    private function getFallbackSnippet(string $bookTitle, string $author): array
    {
        $fallbackSnippets = [
            'The Great Gatsby' => 'In his blue gardens men and girls came and went like moths among the whisperings and the champagne and the stars. At high tide in the afternoon I watched his guests diving from the tower of his raft, or taking the sun on the hot sand of his beach while his two motor-boats slit the waters of the Sound, drawing aquaplanes over cataracts of foam.',
            '1984' => 'It was a bright cold day in April, and the clocks were striking thirteen. Winston Smith, his chin nuzzled into his breast in an effort to escape the vile wind, slipped quickly through the glass doors of Victory Mansions, though not quickly enough to prevent a swirl of gritty dust from entering along with him.',
            'default' => "From the pages of \"{$bookTitle}\" by {$author}, this passage captures the essence of the human experience, weaving together themes of growth, challenge, and discovery that resonate across time and culture.",
        ];

        $snippet = $fallbackSnippets[$bookTitle] ?? $fallbackSnippets['default'];

        return [
            'success' => true,
            'content' => $snippet,
            'source' => 'fallback',
        ];
    }

    private function getFallbackConnection(array $books): array
    {
        $book1 = $books[0]['title'] ?? 'Unknown';
        $book2 = $books[1]['title'] ?? 'Unknown';

        $connection = "Both \"{$book1}\" and \"{$book2}\" explore the fundamental human experience of growth through challenge. While their contexts differ, both works remind us that transformation often comes through facing what initially seems impossible.";

        return [
            'success' => true,
            'content' => $connection,
            'source' => 'fallback',
        ];
    }

    private function getFallbackQuoteToPonder(string $bookTitle, string $author): array
    {
        $fallbackQuotes = [
            'The Great Gatsby' => 'So we beat on, boats against the current, borne back ceaselessly into the past.',
            '1984' => 'Freedom is the freedom to say that two plus two make four. If that is granted, all else follows.',
            'default' => 'The only way to make sense out of change is to plunge into it, move with it, and join the dance.',
        ];

        $quote = $fallbackQuotes[$bookTitle] ?? $fallbackQuotes['default'];

        return [
            'success' => true,
            'content' => $quote,
            'source' => 'fallback',
        ];
    }

    private function getFallbackReflection(array $books): array
    {
        $reflections = [
            'What small action can you take today that aligns with the wisdom you\'ve gained from your reading?',
            'How might the challenges faced by characters in your books inform your approach to current obstacles?',
            'Which insight from your recent reading deserves deeper contemplation and practical application?',
        ];

        $reflection = $reflections[array_rand($reflections)];

        return [
            'success' => true,
            'content' => $reflection,
            'source' => 'fallback',
        ];
    }

    private function getFallbackBookSuggestions(string $partialTitle): array
    {
        $fallbackSuggestions = [
            'great' => [
                [
                    'title' => 'The Great Gatsby',
                    'author' => 'F. Scott Fitzgerald',
                    'publication_year' => 1925,
                    'genre' => 'Fiction',
                    'description' => 'A classic American novel about the Jazz Age and the American Dream. The story follows Nick Carraway as he observes the tragic story of Jay Gatsby.',
                ],
            ],
            '1984' => [
                [
                    'title' => '1984',
                    'author' => 'George Orwell',
                    'publication_year' => 1949,
                    'genre' => 'Dystopian Fiction',
                    'description' => 'A dystopian social science fiction novel about totalitarian control. The story follows Winston Smith as he struggles against the oppressive regime of Big Brother.',
                ],
            ],
            'pride' => [
                [
                    'title' => 'Pride and Prejudice',
                    'author' => 'Jane Austen',
                    'publication_year' => 1813,
                    'genre' => 'Romance',
                    'description' => 'A romantic novel that follows Elizabeth Bennet as she deals with issues of manners, upbringing, morality, education, and marriage.',
                ],
            ],
        ];

        $partialTitleLower = strtolower($partialTitle);

        foreach ($fallbackSuggestions as $key => $suggestions) {
            if (strpos($partialTitleLower, $key) !== false) {
                return [
                    'success' => true,
                    'suggestions' => $suggestions,
                ];
            }
        }

        return [
            'success' => true,
            'suggestions' => [],
        ];
    }
}
