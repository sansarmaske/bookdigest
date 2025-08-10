<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📖 {{ __('Add a New Book') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('books.store') }}">
                        @csrf
                        
                        <div class="mb-4 relative">
                            <x-input-label for="title" :value="__('Book Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus autocomplete="off" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            
                            <!-- Autocomplete suggestions dropdown -->
                            <div id="suggestions-dropdown" class="absolute z-10 w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg mt-1 hidden max-h-60 overflow-y-auto">
                                <!-- Suggestions will be populated here -->
                            </div>
                            
                            <!-- Loading indicator -->
                            <div id="loading-indicator" class="absolute right-3 top-9 hidden">
                                <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="author" :value="__('Author')" />
                            <x-text-input id="author" class="block mt-1 w-full" type="text" name="author" :value="old('author')" required autocomplete="author" />
                            <x-input-error :messages="$errors->get('author')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="genre" :value="__('Genre')" />
                            <x-text-input id="genre" class="block mt-1 w-full" type="text" name="genre" :value="old('genre')" placeholder="e.g., Fiction, Non-fiction, Biography" />
                            <x-input-error :messages="$errors->get('genre')" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <x-input-label for="publication_year" :value="__('Publication Year')" />
                            <x-text-input id="publication_year" class="block mt-1 w-full" type="number" name="publication_year" :value="old('publication_year')" min="1800" :max="date('Y')" />
                            <x-input-error :messages="$errors->get('publication_year')" class="mt-2" />
                        </div>

                        <div class="mb-6">
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="4" 
                                      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                      placeholder="Brief description of the book (helps generate better quotes)">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="{{ route('books.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-500 focus:bg-gray-700 dark:focus:bg-gray-500 active:bg-gray-900 dark:active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <x-primary-button>
                                {{ __('Add Book') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">💡 Tips for better quotes:</h3>
                    <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>Include a detailed description - this helps generate more relevant quotes</li>
                        <li>Make sure the book title and author are spelled correctly</li>
                        <li>The more information you provide, the better the AI can understand your book</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        class BookAutocomplete {
            constructor() {
                this.config = {
                    minTitleLength: 3,
                    debounceDelay: 300,
                    apiUrl: '{{ route("books.autocomplete") }}'
                };
                
                this.elements = this.getElements();
                this.state = { searchTimeout: null, currentSuggestions: [], isLoading: false };
                
                if (this.elements.titleInput) {
                    this.bindEvents();
                }
            }

            getElements() {
                return {
                    titleInput: document.getElementById('title'),
                    authorInput: document.getElementById('author'),
                    genreInput: document.getElementById('genre'),
                    publicationYearInput: document.getElementById('publication_year'),
                    descriptionInput: document.getElementById('description'),
                    suggestionsDropdown: document.getElementById('suggestions-dropdown'),
                    loadingIndicator: document.getElementById('loading-indicator')
                };
            }

            bindEvents() {
                this.elements.titleInput.addEventListener('input', (e) => {
                    this.handleTitleInput(e.target.value.trim());
                });

                this.elements.titleInput.addEventListener('keydown', (e) => {
                    this.handleKeyNavigation(e);
                });

                document.addEventListener('click', (e) => {
                    this.handleOutsideClick(e);
                });
            }

            handleTitleInput(query) {
                clearTimeout(this.state.searchTimeout);
                
                if (query.length < this.config.minTitleLength) {
                    this.hideSuggestions();
                    return;
                }
                
                this.state.searchTimeout = setTimeout(() => {
                    this.searchBooks(query);
                }, this.config.debounceDelay);
            }

            async searchBooks(query) {
                if (this.state.isLoading) return;
                
                this.showLoading();
                
                try {
                    const url = `${this.config.apiUrl}?title=${encodeURIComponent(query)}`;
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        }
                    });

                    const data = await response.json();
                    this.hideLoading();
                    
                    if (response.ok && data.success && Array.isArray(data.suggestions)) {
                        this.displaySuggestions(data.suggestions);
                    } else {
                        this.hideSuggestions();
                    }
                } catch (error) {
                    console.error('Autocomplete error:', error);
                    this.hideLoading();
                    this.hideSuggestions();
                }
            }

            displaySuggestions(suggestions) {
                this.state.currentSuggestions = suggestions;
                this.elements.suggestionsDropdown.innerHTML = '';
                
                if (suggestions.length === 0) {
                    this.hideSuggestions();
                    return;
                }
                
                suggestions.forEach((book, index) => {
                    const item = this.createSuggestionElement(book, index);
                    this.elements.suggestionsDropdown.appendChild(item);
                });
                
                this.showSuggestions();
            }

            createSuggestionElement(book, index) {
                const item = document.createElement('div');
                item.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0 suggestion-item';
                item.setAttribute('data-index', index);
                
                item.innerHTML = `
                    <div class="font-semibold text-gray-900 dark:text-gray-100">${this.escapeHtml(book.title)}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">by ${this.escapeHtml(book.author)} (${book.publication_year || 'Unknown'})</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${this.escapeHtml(book.genre || 'Unknown genre')}</div>
                `;
                
                item.addEventListener('click', () => this.selectBook(book));
                return item;
            }

            selectBook(book) {
                this.fillFormFields(book);
                this.hideSuggestions();
                this.focusNextEmptyField();
            }

            fillFormFields(book) {
                const fields = [
                    [this.elements.titleInput, book.title],
                    [this.elements.authorInput, book.author],
                    [this.elements.genreInput, book.genre || ''],
                    [this.elements.publicationYearInput, book.publication_year || ''],
                    [this.elements.descriptionInput, book.description || '']
                ];

                fields.forEach(([element, value]) => {
                    if (element && value) {
                        element.value = value;
                        element.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });
            }

            focusNextEmptyField() {
                const fields = [this.elements.genreInput, this.elements.publicationYearInput, this.elements.descriptionInput];
                for (const field of fields) {
                    if (field && !field.value.trim()) {
                        field.focus();
                        return;
                    }
                }
            }

            handleKeyNavigation(event) {
                if (this.isSuggestionsHidden()) return;
                
                const suggestions = this.elements.suggestionsDropdown.querySelectorAll('.suggestion-item');
                const currentIndex = Array.from(suggestions).findIndex(s => s.classList.contains('bg-gray-100'));

                switch (event.key) {
                    case 'ArrowDown':
                        event.preventDefault();
                        this.highlightSuggestion(suggestions, currentIndex < suggestions.length - 1 ? currentIndex + 1 : 0);
                        break;
                    case 'ArrowUp':
                        event.preventDefault();
                        this.highlightSuggestion(suggestions, currentIndex > 0 ? currentIndex - 1 : suggestions.length - 1);
                        break;
                    case 'Enter':
                        event.preventDefault();
                        if (currentIndex >= 0 && this.state.currentSuggestions[currentIndex]) {
                            this.selectBook(this.state.currentSuggestions[currentIndex]);
                        }
                        break;
                    case 'Escape':
                        this.hideSuggestions();
                        break;
                }
            }

            highlightSuggestion(suggestions, index) {
                suggestions.forEach((suggestion, i) => {
                    suggestion.classList.toggle('bg-gray-100', i === index);
                    suggestion.classList.toggle('dark:bg-gray-600', i === index);
                });
            }

            handleOutsideClick(event) {
                const isInsideTitleInput = this.elements.titleInput?.contains(event.target);
                const isInsideDropdown = this.elements.suggestionsDropdown?.contains(event.target);
                
                if (!isInsideTitleInput && !isInsideDropdown) {
                    this.hideSuggestions();
                }
            }

            showLoading() {
                this.state.isLoading = true;
                this.elements.loadingIndicator?.classList.remove('hidden');
            }

            hideLoading() {
                this.state.isLoading = false;
                this.elements.loadingIndicator?.classList.add('hidden');
            }

            showSuggestions() {
                this.elements.suggestionsDropdown?.classList.remove('hidden');
            }

            hideSuggestions() {
                this.elements.suggestionsDropdown?.classList.add('hidden');
                this.state.currentSuggestions = [];
            }

            isSuggestionsHidden() {
                return this.elements.suggestionsDropdown?.classList.contains('hidden') !== false;
            }

            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            new BookAutocomplete();
        });
    </script>
</x-app-layout>