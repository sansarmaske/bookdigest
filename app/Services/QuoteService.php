<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Contracts\AIServiceInterface;

class QuoteService
{
    public function __construct(
        protected AIServiceInterface $aiService
    ) {}

    public function generateDailyQuotesForUser(User $user, ?int $maxBooks = null): array
    {
        try {
            $userBooks = $user->books()
                ->whereNotNull('title')
                ->whereNotNull('author')
                ->get();

            if ($userBooks->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'User has no books in their reading list.',
                    'quotes' => [],
                    'user' => $user,
                ];
            }

            $selectedBooks = $this->selectRandomBooks($userBooks, $maxBooks ?? $userBooks->count());
            $quotes = [];
            $failedBooks = [];

            foreach ($selectedBooks as $book) {
                $quoteResult = $this->generateQuoteForSpecificBook($book);

                if ($quoteResult['success']) {
                    $quotes[] = [
                        'book' => $book,
                        'quote_content' => $quoteResult['quote'],
                        'generated_at' => now(),
                    ];
                } else {
                    $failedBooks[] = [
                        'book' => $book,
                        'error' => $quoteResult['error'] ?? 'Unknown error',
                    ];

                    Log::warning('Failed to generate quote for daily digest', [
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'book_title' => $book->title,
                        'error' => $quoteResult['error'] ?? 'Unknown error',
                    ]);
                }
            }

            Log::info('Daily quotes generation completed', [
                'user_id' => $user->id,
                'books_selected' => $selectedBooks->count(),
                'quotes_generated' => count($quotes),
                'failed_books' => count($failedBooks),
            ]);

            // Generate the 4 new sections for the digest
            $digestSections = $this->generateDigestSections($selectedBooks, $user);

            return [
                'success' => ! empty($quotes) || ! empty($digestSections),
                'quotes' => $quotes,
                'digestSections' => $digestSections,
                'failed_books' => $failedBooks,
                'user' => $user,
                'message' => $this->buildResultMessage(count($quotes), count($failedBooks)),
            ];

        } catch (\Exception $e) {
            Log::error('Daily quotes generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to generate daily quotes due to system error.',
                'quotes' => [],
                'user' => $user,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function selectRandomBooks(Collection $books, ?int $maxBooks = null): Collection
    {
        if ($books->isEmpty()) {
            return collect([]);
        }

        // If no maxBooks specified, return all books
        if ($maxBooks === null) {
            return $books;
        }

        $count = min($maxBooks, max(1, $books->count()));

        if ($books->count() <= $count) {
            return $books;
        }

        return $books->random($count);
    }

    public function generateQuoteForSpecificBook(Book $book): array
    {
        if (! $book || empty($book->title) || empty($book->author)) {
            return [
                'success' => false,
                'error' => 'Invalid book data: title and author are required.',
                'quote' => null,
            ];
        }

        try {
            $result = $this->aiService->generateQuote(
                $book->title,
                $book->author,
                $book->description ?? ''
            );

            if ($result['success']) {
                Log::debug('Quote generated successfully', [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'quote_length' => strlen($result['quote'] ?? ''),
                    'provider' => $this->aiService->getProviderName(),
                    'source' => $result['source'] ?? 'unknown',
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Quote generation failed for specific book', [
                'book_id' => $book->id,
                'book_title' => $book->title,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate quote due to system error.',
                'quote' => null,
            ];
        }
    }

    protected function buildResultMessage(int $successCount, int $failureCount): string
    {
        if ($successCount === 0 && $failureCount === 0) {
            return 'No books were processed.';
        }

        if ($successCount > 0 && $failureCount === 0) {
            return "Successfully generated {$successCount} quote(s).";
        }

        if ($successCount === 0 && $failureCount > 0) {
            return "Failed to generate quotes for all {$failureCount} selected book(s).";
        }

        return "Generated {$successCount} quote(s) successfully, failed for {$failureCount} book(s).";
    }

    public function validateBook(Book $book): bool
    {
        return ! empty($book->title) &&
               ! empty($book->author) &&
               strlen($book->title) <= 255 &&
               strlen($book->author) <= 255;
    }

    /**
     * Generate today's snippet section with 5 random book quotes
     */
    protected function generateDigestSections(Collection $books, User $user): array
    {
        $sections = [];

        try {
            // Today's Snippet - 5 random book quotes
            $selectedBooks = $this->selectRandomBooks($books, 5);
            $snippetQuotes = [];

            foreach ($selectedBooks as $book) {
                $quoteResult = $this->aiService->generateQuote(
                    $book->title,
                    $book->author,
                    $book->description ?? ''
                );

                if ($quoteResult['success']) {
                    $snippetQuotes[] = [
                        'book' => $book,
                        'quote_content' => $quoteResult['quote'],
                    ];
                }
            }

            if (! empty($snippetQuotes)) {
                $sections['todaysSnippet'] = $snippetQuotes;
            }

            Log::info('Digest sections generated', [
                'user_id' => $user->id,
                'sections_count' => count($sections),
                'snippet_quotes_count' => count($snippetQuotes),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate digest sections', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'books_count' => $books->count(),
            ]);
        }

        return $sections;
    }
}
