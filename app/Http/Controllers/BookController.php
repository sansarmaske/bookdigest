<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookAutocompleteRequest;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Services\GeminiService;
use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class BookController extends Controller
{
    public function __construct(
        protected QuoteService $quoteService,
        protected GeminiService $geminiService
    ) {}

    public function index(): View
    {
        $user = Auth::user();
        $userBooks = $user->books()
            ->with(['users' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('user_books.created_at', 'desc')
            ->paginate(10);

        return view('books.index', compact('userBooks'));
    }

    public function create(): View
    {
        return view('books.create');
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            $book = Book::firstOrCreate(
                [
                    'title' => $validated['title'],
                    'author' => $validated['author'],
                ],
                $validated
            );

            $user = Auth::user();

            if (! $user->books()->where('book_id', $book->id)->exists()) {
                $user->books()->attach($book, [
                    'read_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('Book added to user library', [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                ]);
            }

            DB::commit();

            return redirect()->route('books.index')
                ->with('success', 'Book added to your reading list successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add book to user library', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'book_data' => $request->validated(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add book to your reading list. Please try again.');
        }
    }

    public function destroy(Book $book): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->books()->where('book_id', $book->id)->exists()) {
            return redirect()->route('books.index')
                ->with('error', 'Book not found in your reading list.');
        }

        try {
            $user->books()->detach($book);

            Log::info('Book removed from user library', [
                'user_id' => $user->id,
                'book_id' => $book->id,
                'book_title' => $book->title,
            ]);

            return redirect()->route('books.index')
                ->with('success', 'Book removed from your reading list successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to remove book from user library', [
                'user_id' => $user->id,
                'book_id' => $book->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('books.index')
                ->with('error', 'Failed to remove book from your reading list. Please try again.');
        }
    }

    public function generateQuote(Book $book): JsonResponse
    {
        $user = Auth::user();

        if (! $user->books()->where('book_id', $book->id)->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'You can only generate quotes for books in your reading list.',
            ], 403);
        }

        try {
            $result = $this->quoteService->generateQuoteForSpecificBook($book);

            if ($result['success']) {
                Log::info('Quote generated successfully', [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'book_title' => $book->title,
                ]);

                return response()->json([
                    'success' => true,
                    'quote' => $result['quote'],
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to generate quote',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Quote generation failed', [
                'user_id' => $user->id,
                'book_id' => $book->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred while generating the quote.',
            ], 500);
        }
    }

    public function autocomplete(BookAutocompleteRequest $request): JsonResponse
    {
        try {
            $partialTitle = $request->validated()['title'];
            $result = $this->geminiService->getBookInfo($partialTitle);

            $this->logAutocompleteRequest($partialTitle, $result);

            return response()->json($result);

        } catch (\Exception $e) {
            $this->logAutocompleteError($e, $request->input('title'));

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch book suggestions.',
                'suggestions' => [],
            ], 500);
        }
    }

    private function logAutocompleteRequest(string $partialTitle, array $result): void
    {
        Log::info('Book autocomplete request', [
            'user_id' => Auth::id(),
            'partial_title' => $partialTitle,
            'success' => $result['success'],
            'suggestions_count' => count($result['suggestions'] ?? []),
        ]);
    }

    private function logAutocompleteError(\Exception $e, ?string $partialTitle): void
    {
        Log::error('Book autocomplete failed', [
            'user_id' => Auth::id(),
            'partial_title' => $partialTitle,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
