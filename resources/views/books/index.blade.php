<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">
                    📚 {{ __('My Reading List') }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">Manage your personal library</p>
            </div>
            <a href="{{ route('books.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Book
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if($userBooks->count() > 0)
                        <div class="space-y-4">
                            @foreach($userBooks as $book)
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $book->title }}</h3>

                                            <div class="flex items-center gap-4 mb-3">
                                                <div class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $book->author }}</span>
                                                </div>

                                                @if($book->genre)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                                        {{ $book->genre }}
                                                    </span>
                                                @endif

                                                @if($book->publication_year)
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                                        {{ $book->publication_year }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($book->description)
                                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">{{ Str::limit($book->description, 150) }}</p>
                                            @endif

                                            <!-- Quote Display Area -->
                                            <div id="quote-{{ $book->id }}" class="mt-4 hidden">
                                                <div class="bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-400 p-4 rounded">
                                                    <div class="flex items-start gap-2 mb-2">
                                                        <svg class="w-4 h-4 text-indigo-600 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                        </svg>
                                                        <h4 class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Random Quote</h4>
                                                    </div>
                                                    <div class="quote-content text-gray-700 dark:text-gray-300 text-sm italic pl-6"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 ml-4">
                                            <button class="generate-quote-btn inline-flex items-center gap-2 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded transition-colors duration-200"
                                                    data-book-id="{{ $book->id }}"
                                                    data-book-title="{{ $book->title }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                                Quote
                                            </button>

                                            <form method="POST" action="{{ route('books.destroy', $book) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center p-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors duration-200"
                                                        onclick="return confirm('Are you sure you want to remove this book from your list?')"
                                                        title="Remove book">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
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
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-2">No books yet</h3>
                            <p class="text-gray-600 dark:text-gray-400 mb-6">Add books to start receiving daily quote digests</p>
                            <a href="{{ route('books.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
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
                    this.textContent = 'Random Quote';
                });
            });
        });

    });
    </script>
</x-app-layout>
