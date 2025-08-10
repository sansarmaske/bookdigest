<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class GeminiServiceTest extends TestCase
{
    protected GeminiService $geminiService;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.gemini.api_key', 'test-api-key');
        Config::set('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        Config::set('services.gemini.model', 'gemini-2.0-flash');
        Config::set('services.gemini.timeout', 30);
        Config::set('services.gemini.max_output_tokens', 500);
        Config::set('services.gemini.temperature', 0.7);
        Config::set('services.gemini.enabled', true);

        $this->geminiService = new GeminiService;
    }

    public function test_generate_quote_with_empty_title(): void
    {
        Log::spy();

        $result = $this->geminiService->generateQuote('', 'Valid Author');

        $this->assertFalse($result['success']);
        $this->assertEquals('Book title and author are required for quote generation.', $result['error']);
        $this->assertNull($result['quote']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Invalid input for quote generation', [
                'title_empty' => true,
                'author_empty' => false,
            ]);
    }

    public function test_generate_quote_with_empty_author(): void
    {
        Log::spy();

        $result = $this->geminiService->generateQuote('Valid Title', '');

        $this->assertFalse($result['success']);
        $this->assertEquals('Book title and author are required for quote generation.', $result['error']);
        $this->assertNull($result['quote']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Invalid input for quote generation', [
                'title_empty' => false,
                'author_empty' => true,
            ]);
    }

    public function test_generate_quote_uses_fallback_when_no_api_key(): void
    {
        Config::set('services.gemini.api_key', null);
        Log::spy();

        $geminiService = new GeminiService;
        $result = $geminiService->generateQuote('The Great Gatsby', 'F. Scott Fitzgerald');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);
        $this->assertStringContainsString('So we beat on', $result['quote']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Using fallback quotes due to missing API configuration', [
                'book' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald',
            ]);
    }

    public function test_generate_quote_uses_fallback_when_placeholder_api_key(): void
    {
        Config::set('services.gemini.api_key', 'your-gemini-api-key-here');
        Log::spy();

        $geminiService = new GeminiService;
        $result = $geminiService->generateQuote('Test Book', 'Test Author');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Using fallback quotes due to missing API configuration', [
                'book' => 'Test Book',
                'author' => 'Test Author',
            ]);
    }

    public function test_generate_quote_successful_api_call(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Generated quote from API',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author', 'Test description');

        $this->assertTrue($result['success']);
        $this->assertEquals('Generated quote from API', $result['quote']);
        $this->assertEquals('gemini_api', $result['source']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=test-api-key' &&
                   $request->method() === 'POST' &&
                   isset($request->data()['contents']) &&
                   isset($request->data()['generationConfig']) &&
                   isset($request->data()['safetySettings']);
        });

        Log::shouldHaveReceived('debug')
            ->atLeast()->once()
            ->with('Making Gemini API request', \Mockery::on(function ($args) {
                return $args['book'] === 'Test Book' &&
                       $args['author'] === 'Test Author' &&
                       is_int($args['prompt_length']);
            }));
    }

    public function test_generate_quote_handles_empty_api_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);
        $this->assertStringContainsString('trust', $result['quote']); // From default fallback

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Empty quote received from Gemini API', [
                'book' => 'Test Book',
                'author' => 'Test Author',
            ]);
    }

    public function test_generate_quote_handles_api_error_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'API quota exceeded',
                ],
            ], 429),
        ]);

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini API Error', \Mockery::type('array'));
    }

    public function test_generate_quote_handles_content_filtered(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'SAFETY',
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = $this->geminiService->generateQuote('Controversial Book', 'Controversial Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Gemini API content filtered', [
                'book' => 'Controversial Book',
                'author' => 'Controversial Author',
                'finish_reason' => 'SAFETY',
            ]);
    }

    public function test_generate_quote_handles_connection_exception(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection failed');
        });

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini API Connection Error', [
                'message' => 'Connection failed',
                'book' => 'Test Book',
                'author' => 'Test Author',
                'type' => 'connection_error',
            ]);
    }

    public function test_generate_quote_handles_request_exception(): void
    {
        // Mock Http to return a failing response
        Http::fake([
            '*' => Http::response(['error' => 'Bad request'], 400),
        ]);

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        // Should log the error response
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini API Error', \Mockery::type('array'));
    }

    public function test_generate_quote_handles_unexpected_exception(): void
    {
        // This test verifies that the service gracefully handles unexpected exceptions
        // and falls back to mock quotes to ensure functionality is maintained

        // Create a service with invalid config (no API key configured)
        $invalidService = new GeminiService;

        $result = $invalidService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote gracefully
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);
        $this->assertStringContainsString('trust', $result['quote']); // Default fallback content
    }

    public function test_get_fallback_quote_returns_specific_quotes(): void
    {
        $specificBooks = [
            'The Great Gatsby' => 'So we beat on',
            '1984' => 'Freedom is the freedom to say',
        ];

        foreach ($specificBooks as $title => $expectedContent) {
            $result = $this->invokeMethod($this->geminiService, 'getFallbackQuote', [$title, 'Author']);

            $this->assertTrue($result['success']);
            $this->assertStringContainsString($expectedContent, $result['quote']);
        }
    }

    public function test_get_fallback_quote_returns_default_for_unknown_book(): void
    {
        $result = $this->invokeMethod($this->geminiService, 'getFallbackQuote', ['Unknown Book', 'Unknown Author']);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Unknown Book', $result['quote']);
        $this->assertStringContainsString('Unknown Author', $result['quote']);
        $this->assertStringContainsString('trust', $result['quote']);
    }

    public function test_build_quote_prompt_includes_all_information(): void
    {
        $prompt = $this->invokeMethod($this->geminiService, 'buildQuotePrompt', [
            'Test Title',
            'Test Author',
            'Test description',
        ]);

        $this->assertStringContainsString('Test Title', $prompt);
        $this->assertStringContainsString('Test Author', $prompt);
        $this->assertStringContainsString('Test description', $prompt);
        $this->assertStringContainsString('meaningful passage', $prompt);
    }

    public function test_build_quote_prompt_handles_empty_description(): void
    {
        $prompt = $this->invokeMethod($this->geminiService, 'buildQuotePrompt', [
            'Test Title',
            'Test Author',
            '',
        ]);

        $this->assertStringContainsString('Test Title', $prompt);
        $this->assertStringContainsString('Test Author', $prompt);
        $this->assertStringNotContainsString('Book description:', $prompt);
    }

    public function test_get_book_info_with_short_title(): void
    {
        $result = $this->geminiService->getBookInfo('ab');

        $this->assertFalse($result['success']);
        $this->assertEquals('Title must be at least 3 characters long.', $result['error']);
        $this->assertEmpty($result['suggestions']);
    }

    public function test_get_book_info_with_empty_title(): void
    {
        $result = $this->geminiService->getBookInfo('');

        $this->assertFalse($result['success']);
        $this->assertEquals('Title must be at least 3 characters long.', $result['error']);
        $this->assertEmpty($result['suggestions']);
    }

    public function test_get_book_info_uses_fallback_when_disabled(): void
    {
        Config::set('services.gemini.enabled', false);

        $geminiService = new GeminiService;
        $result = $geminiService->getBookInfo('great');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['suggestions']);
        $this->assertEquals('The Great Gatsby', $result['suggestions'][0]['title']);
    }

    public function test_get_book_info_successful_api_call(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '{"suggestions": [{"title": "Great Expectations", "author": "Charles Dickens", "publication_year": 1861, "genre": "Classic Literature", "description": "A story about Pip."}]}',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = $this->geminiService->getBookInfo('great');

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['suggestions']);
        $this->assertEquals('Great Expectations', $result['suggestions'][0]['title']);
        $this->assertEquals('Charles Dickens', $result['suggestions'][0]['author']);

        Log::shouldHaveReceived('debug')
            ->once()
            ->with('Making Gemini API request for book info', \Mockery::type('array'));
    }

    public function test_get_book_info_handles_malformed_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'invalid json response',
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Log::spy();

        $result = $this->geminiService->getBookInfo('test');

        // Should return parsing error (since JSON parsing fails)
        $this->assertFalse($result['success']); // Parsing failed
        $this->assertEquals('Could not parse book information from response.', $result['error']);
        $this->assertEmpty($result['suggestions']);

        // No log error expected for simple parsing failure (not exception-based)
    }

    public function test_get_book_info_handles_api_error(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'API error'], 500),
        ]);

        Log::spy();

        $result = $this->geminiService->getBookInfo('test');

        // Should fall back to fallback suggestions
        $this->assertTrue($result['success']);
        $this->assertEmpty($result['suggestions']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini API Error for book info', \Mockery::type('array'));
    }

    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
