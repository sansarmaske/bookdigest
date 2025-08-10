<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_index_shows_user_books_only(): void
    {
        $userBook = Book::factory()->create(['title' => 'User Book']);
        $otherBook = Book::factory()->create(['title' => 'Other Book']);
        
        $this->user->addBook($userBook);
        $this->otherUser->addBook($otherBook);

        $response = $this->actingAs($this->user)->get('/books');

        $response->assertStatus(200);
        $response->assertSee('User Book');
        $response->assertDontSee('Other Book');
    }

    public function test_index_requires_authentication(): void
    {
        $response = $this->get('/books');
        
        $response->assertRedirect('/login');
    }

    public function test_create_shows_form(): void
    {
        $response = $this->actingAs($this->user)->get('/books/create');
        
        $response->assertStatus(200);
        $response->assertSee('title');
        $response->assertSee('author');
    }

    public function test_create_requires_authentication(): void
    {
        $response = $this->get('/books/create');
        
        $response->assertRedirect('/login');
    }

    public function test_store_creates_book_and_associates_with_user(): void
    {
        $bookData = [
            'title' => 'New Test Book',
            'author' => 'Test Author',
            'description' => 'A great test book',
            'publication_year' => 2020,
            'genre' => 'Fiction',
        ];

        $response = $this->actingAs($this->user)->post('/books', $bookData);

        $response->assertRedirect('/books');
        $response->assertSessionHas('success', 'Book added to your reading list successfully!');

        $this->assertDatabaseHas('books', [
            'title' => 'New Test Book',
            'author' => 'Test Author',
        ]);

        $book = Book::where('title', 'New Test Book')->first();
        $this->assertTrue($this->user->hasBook($book));
    }

    public function test_store_finds_existing_book_instead_of_creating_duplicate(): void
    {
        $existingBook = Book::factory()->create([
            'title' => 'Existing Book',
            'author' => 'Existing Author',
        ]);

        $bookData = [
            'title' => 'Existing Book',
            'author' => 'Existing Author',
            'description' => 'Different description',
            'genre' => 'Different genre',
        ];

        $response = $this->actingAs($this->user)->post('/books', $bookData);

        $response->assertRedirect('/books');

        // Should only have one book in database
        $this->assertEquals(1, Book::where('title', 'Existing Book')->count());
        $this->assertTrue($this->user->hasBook($existingBook));
    }

    public function test_store_trims_whitespace(): void
    {
        $bookData = [
            'title' => '  Trimmed Title  ',
            'author' => '  Trimmed Author  ',
            'description' => '  Trimmed Description  ',
            'genre' => '  Trimmed Genre  ',
        ];

        $this->actingAs($this->user)->post('/books', $bookData);

        $this->assertDatabaseHas('books', [
            'title' => 'Trimmed Title',
            'author' => 'Trimmed Author',
            'description' => 'Trimmed Description',
            'genre' => 'Trimmed Genre',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post('/books', []);

        $response->assertSessionHasErrors(['title', 'author']);
    }

    public function test_store_validates_field_lengths(): void
    {
        $response = $this->actingAs($this->user)->post('/books', [
            'title' => str_repeat('a', 256), // Too long
            'author' => str_repeat('b', 256), // Too long
            'description' => str_repeat('c', 2001), // Too long
            'genre' => str_repeat('d', 101), // Too long
        ]);

        $response->assertSessionHasErrors(['title', 'author', 'description', 'genre']);
    }

    public function test_store_validates_publication_year(): void
    {
        $response = $this->actingAs($this->user)->post('/books', [
            'title' => 'Valid Title',
            'author' => 'Valid Author',
            'publication_year' => 999, // Too early
        ]);

        $response->assertSessionHasErrors(['publication_year']);

        $response = $this->actingAs($this->user)->post('/books', [
            'title' => 'Valid Title',
            'author' => 'Valid Author',
            'publication_year' => date('Y') + 2, // Future year
        ]);

        $response->assertSessionHasErrors(['publication_year']);
    }

    public function test_store_requires_authentication(): void
    {
        $response = $this->post('/books', [
            'title' => 'Test Book',
            'author' => 'Test Author',
        ]);

        $response->assertRedirect('/login');
    }

    public function test_destroy_removes_book_from_user_list(): void
    {
        $book = Book::factory()->create();
        $this->user->addBook($book);

        $this->assertTrue($this->user->hasBook($book));

        $response = $this->actingAs($this->user)->delete("/books/{$book->id}");

        $response->assertRedirect('/books');
        $response->assertSessionHas('success', 'Book removed from your reading list successfully!');
        $this->assertFalse($this->user->hasBook($book));
    }

    public function test_destroy_returns_error_if_book_not_in_user_list(): void
    {
        $book = Book::factory()->create();

        $response = $this->actingAs($this->user)->delete("/books/{$book->id}");

        $response->assertRedirect('/books');
        $response->assertSessionHas('error', 'Book not found in your reading list.');
    }

    public function test_destroy_requires_authentication(): void
    {
        $book = Book::factory()->create();

        $response = $this->delete("/books/{$book->id}");

        $response->assertRedirect('/login');
    }

    public function test_generate_quote_success(): void
    {
        $book = Book::factory()->create();
        $this->user->addBook($book);

        $mockQuoteService = Mockery::mock(QuoteService::class);
        $mockQuoteService->shouldReceive('generateQuoteForSpecificBook')
            ->once()
            ->with(Mockery::on(function($arg) use ($book) {
                return $arg instanceof Book && $arg->id === $book->id;
            }))
            ->andReturn([
                'success' => true,
                'quote' => 'Test quote content'
            ]);

        // Mock GeminiService as well since BookController now depends on it
        $mockGeminiService = Mockery::mock(\App\Services\GeminiService::class);

        $this->app->instance(QuoteService::class, $mockQuoteService);
        $this->app->instance(\App\Services\GeminiService::class, $mockGeminiService);

        $response = $this->actingAs($this->user)
            ->postJson("/books/{$book->id}/quote");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'quote' => 'Test quote content'
        ]);
    }

    public function test_generate_quote_fails_for_book_not_in_user_list(): void
    {
        $book = Book::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/books/{$book->id}/quote");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error' => 'You can only generate quotes for books in your reading list.'
        ]);
    }

    public function test_generate_quote_handles_service_failure(): void
    {
        $book = Book::factory()->create();
        $this->user->addBook($book);

        $mockQuoteService = Mockery::mock(QuoteService::class);
        $mockQuoteService->shouldReceive('generateQuoteForSpecificBook')
            ->once()
            ->with(Mockery::on(function($arg) use ($book) {
                return $arg instanceof Book && $arg->id === $book->id;
            }))
            ->andReturn([
                'success' => false,
                'error' => 'API error'
            ]);

        // Mock GeminiService as well since BookController now depends on it
        $mockGeminiService = Mockery::mock(\App\Services\GeminiService::class);

        $this->app->instance(QuoteService::class, $mockQuoteService);
        $this->app->instance(\App\Services\GeminiService::class, $mockGeminiService);

        $response = $this->actingAs($this->user)
            ->postJson("/books/{$book->id}/quote");

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'error' => 'API error'
        ]);
    }

    public function test_generate_quote_requires_authentication(): void
    {
        $book = Book::factory()->create();

        $response = $this->postJson("/books/{$book->id}/quote");

        $response->assertStatus(401);
    }

    public function test_store_logs_successful_book_addition(): void
    {
        Log::spy();

        $bookData = [
            'title' => 'Log Test Book',
            'author' => 'Log Test Author',
        ];

        $this->actingAs($this->user)->post('/books', $bookData);

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Book added to user library', Mockery::type('array'));
    }

    public function test_destroy_logs_successful_book_removal(): void
    {
        Log::spy();

        $book = Book::factory()->create();
        $this->user->addBook($book);

        $this->actingAs($this->user)->delete("/books/{$book->id}");

        Log::shouldHaveReceived('info')
            ->once()
            ->with('Book removed from user library', Mockery::type('array'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}