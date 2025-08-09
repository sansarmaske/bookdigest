<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Services\QuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    protected $quoteService;

    public function __construct(QuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    public function index()
    {
        $user = Auth::user();
        $userBooks = $user->books()->with('users')->paginate(10);
        
        return view('books.index', compact('userBooks'));
    }

    public function create()
    {
        return view('books.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'description' => 'nullable|string',
            'publication_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'genre' => 'nullable|string|max:100',
        ]);

        $book = Book::firstOrCreate(
            [
                'title' => $validated['title'],
                'author' => $validated['author']
            ],
            $validated
        );

        $user = Auth::user();
        if (!$user->books()->where('book_id', $book->id)->exists()) {
            $user->books()->attach($book, ['read_at' => now()]);
        }

        return redirect()->route('books.index')
            ->with('success', 'Book added to your reading list!');
    }

    public function destroy(Book $book)
    {
        Auth::user()->books()->detach($book);
        
        return redirect()->route('books.index')
            ->with('success', 'Book removed from your reading list!');
    }

    public function generateQuote(Book $book)
    {
        $result = $this->quoteService->generateQuoteForSpecificBook($book);
        
        if ($result['success']) {
            return response()->json([
                'success' => true,
                'quote' => $result['quote']
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Unknown error'
        ], 500);
    }
}
