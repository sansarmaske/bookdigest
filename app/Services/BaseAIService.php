<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Contracts\AIServiceInterface;

abstract class BaseAIService implements AIServiceInterface
{
    protected const MIN_TITLE_LENGTH = 3;

    protected const BOOK_INFO_TEMPERATURE = 0.3;

    protected const BOOK_INFO_MAX_TOKENS = 1000;

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

            return $this->makeContentRequest($prompt, 'quote');
        } catch (\Exception $e) {
            Log::error($this->getProviderName().' Service Exception for quote', [
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
            $response = $this->makeBookInfoRequest($prompt);

            return $this->parseBookInfoResponse($response);
        } catch (\Exception $e) {
            Log::error($this->getProviderName().' Service Exception for book info', [
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

            return $this->makeContentRequest($prompt, 'snippet');
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

            return $this->makeContentRequest($prompt, 'connection');
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

            return $this->makeContentRequest($prompt, 'quote_to_ponder');
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

            return $this->makeContentRequest($prompt, 'reflection');
        } catch (\Exception $e) {
            Log::error('Today\'s reflection generation failed', [
                'books_count' => count($books),
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackReflection($books);
        }
    }

    protected function buildQuotePrompt(string $bookTitle, string $author, ?string $description = ''): string
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
        $prompt .= "I need an ACTUAL, REAL passage from the published book '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "Please find and extract {$randomPassageType} and {$randomAnalysisAngle}.\n\n";

        $prompt .= "CRITICAL REQUIREMENTS - NO EXCEPTIONS:\n";
        $prompt .= "- You MUST provide an EXACT quote from the actual published book\n";
        $prompt .= "- DO NOT create, paraphrase, or generate new content\n";
        $prompt .= "- DO NOT make up quotes that sound like the author\n";
        $prompt .= "- If you cannot recall the exact text, return: 'Unable to locate exact passage'\n";
        $prompt .= "- Choose a DIFFERENT section of the book each time for variety\n";
        $prompt .= "- Focus on passages that showcase different aspects of the author's writing\n\n";

        $prompt .= 'Provide exactly one authentic passage (1 short paragraph maximum) with NO introductory text. ';
        $prompt .= "Simply provide the EXACT passage content as it appears in the published book.\n\n";

        $prompt .= "If you cannot provide an exact quote, respond with: 'Unable to locate exact passage from {$bookTitle} by {$author}.'";

        return $prompt;
    }

    protected function buildSnippetPrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $randomSeed = mt_rand(1, 1000000);

        $prompt = "Random seed: {$randomSeed}\n\n";
        $prompt .= "Extract an ACTUAL, REAL excerpt from the published book '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "CRITICAL REQUIREMENTS - NO EXCEPTIONS:\n";
        $prompt .= "- You MUST provide an EXACT excerpt from the actual published book\n";
        $prompt .= "- DO NOT create, paraphrase, or generate new content\n";
        $prompt .= "- DO NOT make up text that sounds like the author\n";
        $prompt .= "- If you cannot recall exact text, return: 'Unable to locate exact excerpt'\n";
        $prompt .= "- Choose a different, random section each time to avoid repetition\n";
        $prompt .= "- Keep it concise (2-3 sentences, maximum 1 short paragraph)\n";
        $prompt .= "- Select passages that showcase the author's unique voice and style\n";
        $prompt .= "- Provide ONLY the excerpt content, no introductory text\n\n";
        $prompt .= "If you cannot provide an exact excerpt, respond with: 'Unable to locate exact excerpt from {$bookTitle} by {$author}.'";

        return $prompt;
    }

    protected function buildConnectionPrompt(array $books): string
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

    protected function buildQuoteToPonderPrompt(string $bookTitle, string $author, ?string $description = ''): string
    {
        $randomSeed = mt_rand(1, 1000000);

        $prompt = "Random seed: {$randomSeed}\n\n";
        $prompt .= "Extract an ACTUAL, REAL quote from the published book '{$bookTitle}' by {$author}. ";

        if (! empty($description)) {
            $prompt .= "Book context: {$description}. ";
        }

        $prompt .= "CRITICAL REQUIREMENTS - NO EXCEPTIONS:\n";
        $prompt .= "- You MUST provide an EXACT quote from the actual published book\n";
        $prompt .= "- DO NOT create, paraphrase, or generate new content\n";
        $prompt .= "- DO NOT make up quotes that sound like the author\n";
        $prompt .= "- If you cannot recall exact text, return: 'Unable to locate exact quote'\n";
        $prompt .= "- Choose a different quote each time to ensure variety\n";
        $prompt .= "- Select quotes that are philosophically rich or emotionally resonant\n";
        $prompt .= "- Focus on quotes that make readers pause and think\n";
        $prompt .= "- Provide ONLY the quote text, no context or explanation\n\n";
        $prompt .= "If you cannot provide an exact quote, respond with: 'Unable to locate exact quote from {$bookTitle} by {$author}.'";

        return $prompt;
    }

    protected function buildReflectionPrompt(array $books): string
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

    protected function isValidTitleInput(string $partialTitle): bool
    {
        return ! empty($partialTitle) && strlen(trim($partialTitle)) >= self::MIN_TITLE_LENGTH;
    }

    protected function createErrorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'suggestions' => [],
        ];
    }

    protected function parseBookInfoResponse(string $responseText): array
    {
        try {
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
            ],
        ];

        $quote = $fallbackQuotes[$bookTitle] ?? $fallbackQuotes['default'];

        return [
            'success' => true,
            'quote' => $quote['quote'],
        ];
    }

    protected function getFallbackSnippet(string $bookTitle, string $author): array
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

    protected function getFallbackConnection(array $books): array
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

    protected function getFallbackQuoteToPonder(string $bookTitle, string $author): array
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

    protected function getFallbackReflection(array $books): array
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

    protected function getFallbackBookSuggestions(string $partialTitle): array
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

    abstract protected function makeContentRequest(string $prompt, string $type): array;

    abstract protected function makeBookInfoRequest(string $prompt): string;
}
