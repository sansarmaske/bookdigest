<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📚 {{ __('My Reading List') }}
            </h2>
            <a href="{{ route('books.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add New Book
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($userBooks->count() > 0)
                        <div class="space-y-6">
                            @foreach($userBooks as $book)
                                <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="text-xl font-semibold text-gray-800 mb-2">{{ $book->title }}</h3>
                                            <p class="text-gray-600 mb-1"><strong>Author:</strong> {{ $book->author }}</p>
                                            
                                            @if($book->genre)
                                                <p class="text-gray-600 mb-1"><strong>Genre:</strong> {{ $book->genre }}</p>
                                            @endif
                                            
                                            @if($book->publication_year)
                                                <p class="text-gray-600 mb-1"><strong>Year:</strong> {{ $book->publication_year }}</p>
                                            @endif
                                            
                                            @if($book->description)
                                                <p class="text-gray-700 mt-3">{{ Str::limit($book->description, 200) }}</p>
                                            @endif

                                            <div id="quote-{{ $book->id }}" class="mt-4 hidden">
                                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                                                    <div class="quote-content text-gray-700"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ml-6 flex flex-col space-y-2">
                                            <button class="generate-quote-btn bg-green-500 hover:bg-green-700 text-gray-800 hover:text-gray-900 font-bold py-2 px-4 rounded" 
                                                    data-book-id="{{ $book->id }}" 
                                                    data-book-title="{{ $book->title }}">
                                                Generate Quote
                                            </button>
                                            
                                            
                                            <form method="POST" action="{{ route('books.destroy', $book) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full" 
                                                        onclick="return confirm('Are you sure you want to remove this book from your list?')">
                                                    Remove
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $userBooks->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-6xl mb-4">📚</div>
                            <h3 class="text-2xl font-semibold text-gray-800 mb-2">No books in your reading list yet!</h3>
                            <p class="text-gray-600 mb-6">Start by adding some books to receive daily quote digests.</p>
                            <a href="{{ route('books.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg">
                                Add Your First Book
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const generateButtons = document.querySelectorAll('.generate-quote-btn');
        
        generateButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-book-id');
                const bookTitle = this.getAttribute('data-book-title');
                const quoteSection = document.getElementById(`quote-${bookId}`);
                const quoteContent = quoteSection.querySelector('.quote-content');
                
                // Show loading state
                this.disabled = true;
                this.textContent = 'Generating...';
                quoteSection.classList.remove('hidden');
                quoteContent.innerHTML = '<div class="flex items-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Generating quote...</div>';
                
                // Make AJAX request
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                fetch(`/books/${bookId}/quote`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        quoteContent.innerHTML = data.quote.replace(/\n/g, '<br>');
                    } else {
                        quoteContent.innerHTML = '<div class="text-red-600">Error: ' + (data.error || data.message || 'Failed to generate quote') + '</div>';
                    }
                })
                .catch(error => {
                    quoteContent.innerHTML = '<div class="text-red-600">Error: ' + error.message + '</div>';
                })
                .finally(() => {
                    this.disabled = false;
                    this.textContent = 'Generate Quote';
                });
            });
        });
        
    });
    </script>
</x-app-layout>