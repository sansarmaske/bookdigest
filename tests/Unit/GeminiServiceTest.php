<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

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
        
        $this->geminiService = new GeminiService();
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
                'author_empty' => false
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
                'author_empty' => true
            ]);
    }

    public function test_generate_quote_uses_fallback_when_no_api_key(): void
    {
        Config::set('services.gemini.api_key', null);
        Log::spy();

        $geminiService = new GeminiService();
        $result = $geminiService->generateQuote('The Great Gatsby', 'F. Scott Fitzgerald');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);
        $this->assertStringContainsString('So we beat on', $result['quote']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Using fallback quotes due to missing API configuration', [
                'book' => 'The Great Gatsby',
                'author' => 'F. Scott Fitzgerald'
            ]);
    }

    public function test_generate_quote_uses_fallback_when_placeholder_api_key(): void
    {
        Config::set('services.gemini.api_key', 'your-gemini-api-key-here');
        Log::spy();

        $geminiService = new GeminiService();
        $result = $geminiService->generateQuote('Test Book', 'Test Author');

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Using fallback quotes due to missing API configuration', [
                'book' => 'Test Book',
                'author' => 'Test Author'
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
                                    'text' => 'Generated quote from API'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
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
            ->once()
            ->with('Making Gemini API request', [
                'book' => 'Test Book',
                'author' => 'Test Author',
                'prompt_length' => \Mockery::type('integer')
            ]);
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
                                    'text' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
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
                'author' => 'Test Author'
            ]);
    }

    public function test_generate_quote_handles_api_error_response(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'message' => 'API quota exceeded'
                ]
            ], 429)
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
                        'finishReason' => 'SAFETY'
                    ]
                ]
            ], 200)
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
                'finish_reason' => 'SAFETY'
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
                'type' => 'connection_error'
            ]);
    }

    public function test_generate_quote_handles_request_exception(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'Bad request'], 400)
        ]);

        // Force a RequestException by making the response fail
        Http::fake(function () {
            $response = Http::response(['error' => 'Bad request'], 400);
            throw new \Illuminate\Http\Client\RequestException($response);
        });

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini API Request Error', \Mockery::type('array'));
    }

    public function test_generate_quote_handles_unexpected_exception(): void
    {
        Http::fake(function () {
            throw new \RuntimeException('Unexpected error');
        });

        Log::spy();

        $result = $this->geminiService->generateQuote('Test Book', 'Test Author');

        // Should fall back to mock quote
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['quote']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Gemini Service Unexpected Exception', [
                'message' => 'Unexpected error',
                'book' => 'Test Book',
                'author' => 'Test Author',
                'type' => 'unexpected_error',
                'trace' => \Mockery::type('string')
            ]);
    }

    public function test_get_fallback_quote_returns_specific_quotes(): void
    {
        $specificBooks = [
            'The Great Gatsby' => 'So we beat on',
            '1984' => 'Freedom is the freedom to say'
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
            'Test description'
        ]);

        $this->assertStringContainsString('Test Title', $prompt);
        $this->assertStringContainsString('Test Author', $prompt);
        $this->assertStringContainsString('Test description', $prompt);
        $this->assertStringContainsString('meaningful  passage', $prompt);
    }

    public function test_build_quote_prompt_handles_empty_description(): void
    {
        $prompt = $this->invokeMethod($this->geminiService, 'buildQuotePrompt', [
            'Test Title',
            'Test Author',
            ''
        ]);

        $this->assertStringContainsString('Test Title', $prompt);
        $this->assertStringContainsString('Test Author', $prompt);
        $this->assertStringNotContainsString('Book description:', $prompt);
    }

    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}