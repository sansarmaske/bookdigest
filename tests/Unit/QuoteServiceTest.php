<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class QuoteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuoteService $quoteService;

    protected GeminiService $mockGeminiService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockGeminiService = Mockery::mock(GeminiService::class);
        $this->quoteService = new QuoteService($this->mockGeminiService);
        $this->user = User::factory()->create();
    }

    public function test_generate_daily_quotes_for_user_with_no_books(): void
    {
        $result = $this->quoteService->generateDailyQuotesForUser($this->user);

        $this->assertFalse($result['success']);
        $this->assertEquals('User has no books in their reading list.', $result['message']);
        $this->assertEmpty($result['quotes']);
        $this->assertEquals($this->user->id, $result['user']->id);
    }

    public function test_generate_daily_quotes_for_user_with_books(): void
    {
        $books = Book::factory()->count(5)->create();
        foreach ($books as $book) {
            $this->user->addBook($book);
        }

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->times(5) // Now includes all books by default
            ->andReturn([
                'success' => true,
                'quote' => 'Test quote content',
            ]);

        $result = $this->quoteService->generateDailyQuotesForUser($this->user);

        $this->assertTrue($result['success']);
        $this->assertCount(5, $result['quotes']);
        $this->assertEquals($this->user->id, $result['user']->id);
        $this->assertStringContainsString('Successfully generated 5 quote(s)', $result['message']);
    }

    public function test_generate_daily_quotes_respects_max_books_parameter(): void
    {
        $books = Book::factory()->count(5)->create();
        foreach ($books as $book) {
            $this->user->addBook($book);
        }

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->times(2) // Custom max books
            ->andReturn([
                'success' => true,
                'quote' => 'Test quote content',
            ]);

        $result = $this->quoteService->generateDailyQuotesForUser($this->user, 2);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['quotes']);
    }

    public function test_generate_daily_quotes_handles_partial_failures(): void
    {
        $books = Book::factory()->count(3)->create();
        foreach ($books as $book) {
            $this->user->addBook($book);
        }

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->times(3)
            ->andReturnUsing(function () {
                static $callCount = 0;
                $callCount++;

                if ($callCount <= 2) {
                    return ['success' => true, 'quote' => 'Success quote'];
                } else {
                    return ['success' => false, 'error' => 'API failure'];
                }
            });

        $result = $this->quoteService->generateDailyQuotesForUser($this->user);

        $this->assertTrue($result['success']); // Still success because some quotes generated
        $this->assertCount(2, $result['quotes']);
        $this->assertCount(1, $result['failed_books']);
        $this->assertStringContainsString('Generated 2 quote(s) successfully, failed for 1 book(s)', $result['message']);
    }

    public function test_generate_daily_quotes_handles_all_failures(): void
    {
        Log::spy();

        $books = Book::factory()->count(2)->create();
        foreach ($books as $book) {
            $this->user->addBook($book);
        }

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->times(2)
            ->andReturn([
                'success' => false,
                'error' => 'API failure',
            ]);

        $result = $this->quoteService->generateDailyQuotesForUser($this->user);

        $this->assertFalse($result['success']);
        $this->assertEmpty($result['quotes']);
        $this->assertCount(2, $result['failed_books']);
        $this->assertStringContainsString('Failed to generate quotes for all 2 selected book(s)', $result['message']);
    }

    public function test_generate_daily_quotes_logs_completion(): void
    {
        Log::spy();

        $book = Book::factory()->create();
        $this->user->addBook($book);

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->once()
            ->andReturn([
                'success' => true,
                'quote' => 'Test quote',
            ]);

        $this->quoteService->generateDailyQuotesForUser($this->user);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Daily quotes generation completed', Mockery::type('array'));
    }

    public function test_generate_quote_for_specific_book_success(): void
    {
        $book = Book::factory()->create();

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->once()
            ->with($book->title, $book->author, $book->description ?? '')
            ->andReturn([
                'success' => true,
                'quote' => 'Specific quote content',
            ]);

        $result = $this->quoteService->generateQuoteForSpecificBook($book);

        $this->assertTrue($result['success']);
        $this->assertEquals('Specific quote content', $result['quote']);
    }

    public function test_generate_quote_for_specific_book_with_invalid_book(): void
    {
        $invalidBook = new Book;

        $result = $this->quoteService->generateQuoteForSpecificBook($invalidBook);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid book data: title and author are required.', $result['error']);
        $this->assertNull($result['quote']);
    }

    public function test_generate_quote_for_specific_book_handles_service_exception(): void
    {
        Log::spy();

        $book = Book::factory()->create();

        $this->mockGeminiService->shouldReceive('generateQuote')
            ->once()
            ->andThrow(new \Exception('Service exception'));

        $result = $this->quoteService->generateQuoteForSpecificBook($book);

        $this->assertFalse($result['success']);
        $this->assertEquals('Failed to generate quote due to system error.', $result['error']);
        $this->assertNull($result['quote']);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Quote generation failed for specific book', Mockery::type('array'));
    }

    public function test_select_random_books_with_empty_collection(): void
    {
        $books = collect([]);

        $result = $this->invokeMethod($this->quoteService, 'selectRandomBooks', [$books, 3]);

        $this->assertTrue($result->isEmpty());
    }

    public function test_select_random_books_with_fewer_books_than_max(): void
    {
        $books = Book::factory()->count(2)->make();

        $result = $this->invokeMethod($this->quoteService, 'selectRandomBooks', [$books, 5]);

        $this->assertCount(2, $result);
    }

    public function test_select_random_books_with_more_books_than_max(): void
    {
        $books = Book::factory()->count(10)->make();

        $result = $this->invokeMethod($this->quoteService, 'selectRandomBooks', [$books, 3]);

        $this->assertCount(3, $result);
    }

    public function test_select_random_books_with_null_max_returns_all_books(): void
    {
        $books = Book::factory()->count(10)->make();

        $result = $this->invokeMethod($this->quoteService, 'selectRandomBooks', [$books, null]);

        $this->assertCount(10, $result);
    }

    public function test_validate_book_with_valid_book(): void
    {
        $book = Book::factory()->make([
            'title' => 'Valid Title',
            'author' => 'Valid Author',
        ]);

        $result = $this->quoteService->validateBook($book);

        $this->assertTrue($result);
    }

    public function test_validate_book_with_invalid_book(): void
    {
        $invalidBooks = [
            Book::factory()->make(['title' => '', 'author' => 'Valid Author']),
            Book::factory()->make(['title' => 'Valid Title', 'author' => '']),
            Book::factory()->make(['title' => str_repeat('a', 256), 'author' => 'Valid Author']),
            Book::factory()->make(['title' => 'Valid Title', 'author' => str_repeat('b', 256)]),
        ];

        foreach ($invalidBooks as $book) {
            $result = $this->quoteService->validateBook($book);
            $this->assertFalse($result);
        }
    }

    public function test_build_result_message_variations(): void
    {
        $testCases = [
            [0, 0, 'No books were processed.'],
            [3, 0, 'Successfully generated 3 quote(s).'],
            [0, 2, 'Failed to generate quotes for all 2 selected book(s).'],
            [2, 1, 'Generated 2 quote(s) successfully, failed for 1 book(s).'],
        ];

        foreach ($testCases as [$success, $failure, $expected]) {
            $result = $this->invokeMethod($this->quoteService, 'buildResultMessage', [$success, $failure]);
            $this->assertEquals($expected, $result);
        }
    }

    protected function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
