<?php

namespace App\Services;

use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class QuoteService
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function generateDailyQuotesForUser(User $user): array
    {
        $userBooks = $user->books;
        
        if ($userBooks->isEmpty()) {
            return [
                'success' => false,
                'message' => 'User has no books in their reading list.'
            ];
        }

        // Select 1-3 random books from the user's collection
        $selectedBooks = $this->selectRandomBooks($userBooks);
        $quotes = [];

        foreach ($selectedBooks as $book) {
            $quoteResult = $this->geminiService->generateQuote(
                $book->title,
                $book->author,
                $book->description ?? ''
            );

            if ($quoteResult['success']) {
                $quotes[] = [
                    'book' => $book,
                    'quote_content' => $quoteResult['quote']
                ];
            } else {
                Log::warning('Failed to generate quote', [
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                    'error' => $quoteResult['error']
                ]);
            }
        }

        return [
            'success' => !empty($quotes),
            'quotes' => $quotes,
            'user' => $user
        ];
    }

    protected function selectRandomBooks(Collection $books, int $maxBooks = 3): Collection
    {
        $count = min($maxBooks, $books->count());
        return $books->random($count);
    }

    public function generateQuoteForSpecificBook(Book $book): array
    {
        return $this->geminiService->generateQuote(
            $book->title,
            $book->author,
            $book->description ?? ''
        );
    }
}