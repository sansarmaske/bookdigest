<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class QuoteService
{
    public function __construct(
        protected GeminiService $geminiService
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
            $result = $this->geminiService->generateQuote(
                $book->title,
                $book->author,
                $book->description ?? ''
            );

            if ($result['success']) {
                Log::debug('Quote generated successfully', [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'quote_length' => strlen($result['quote'] ?? ''),
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
     * Generate all 4 sections for the enhanced daily digest
     */
    protected function generateDigestSections(Collection $books, User $user): array
    {
        $sections = [];

        try {
            // 1. Today's Snippet - random book
            $snippetBook = $books->random();
            $snippetResult = $this->geminiService->generateTodaysSnippet(
                $snippetBook->title,
                $snippetBook->author,
                $snippetBook->description ?? ''
            );

            if ($snippetResult['success']) {
                $sections['todaysSnippet'] = [
                    'book' => $snippetBook,
                    'quote_content' => $snippetResult['content'],
                ];
            }

            // 2. Cross-book Connection - use multiple books
            if ($books->count() >= 2) {
                $connectionBooks = $books->take(3)->map(function ($book) {
                    return [
                        'title' => $book->title,
                        'author' => $book->author,
                    ];
                })->toArray();

                $connectionResult = $this->geminiService->generateCrossBookConnection($connectionBooks);

                if ($connectionResult['success']) {
                    $sections['crossBookConnection'] = [
                        'connection' => $connectionResult['content'],
                        'books' => $books->take(3)->pluck('title')->implode(', '),
                    ];
                }
            }

            // 3. Quote to Ponder - different random book
            $ponderBook = $books->count() > 1 ? 
                $books->except($snippetBook->id)->random() : 
                $snippetBook;

            $ponderResult = $this->geminiService->generateQuoteToPonder(
                $ponderBook->title,
                $ponderBook->author,
                $ponderBook->description ?? ''
            );

            if ($ponderResult['success']) {
                $sections['quoteToPonder'] = [
                    'book' => $ponderBook,
                    'quote_content' => $ponderResult['content'],
                ];
            }

            // 4. Today's Reflection - based on all books
            $reflectionBooks = $books->map(function ($book) {
                return [
                    'title' => $book->title,
                    'author' => $book->author,
                ];
            })->toArray();

            $reflectionResult = $this->geminiService->generateTodaysReflection($reflectionBooks);

            if ($reflectionResult['success']) {
                $sections['todaysReflection'] = [
                    'reflection' => $reflectionResult['content'],
                ];
            }

            Log::info('Digest sections generated', [
                'user_id' => $user->id,
                'sections_count' => count($sections),
                'sections' => array_keys($sections),
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
