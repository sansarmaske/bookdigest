<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book Digest</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

        <!-- Styles -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            fontFamily: {
                                sans: ['Inter', 'sans-serif'],
                            }
                        }
                    }
                }
            </script>
        @endif
    </head>
    <body class="bg-white text-gray-900 font-sans antialiased">
        <!-- Header -->
        <header class="absolute top-0 left-0 right-0 z-10">
            <nav class="flex items-center justify-between p-6 max-w-7xl mx-auto">
                <div class="text-xl font-bold">Book Digest</div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="min-h-screen flex items-center justify-center px-6">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                    AI-powered insights from
                    <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        your library
                    </span>
                </h1>

                <p class="text-xl text-gray-600 mb-8 leading-relaxed">
                    Transform your reading list into personalized daily digests. Our AI analyzes
                    the books you've read and delivers key insights straight to your inbox.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-black text-white px-8 py-3 rounded-lg font-medium hover:bg-gray-800 transition-colors">
                            Get Started
                        </a>
                    @endif
                    <button onclick="openModal()" class="border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                        See Example
                    </button>
                </div>
            </div>
        </main>

        <!-- Features Section -->
        <section class="py-20 bg-gray-50">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold mb-4">How it works</h2>
                    <p class="text-gray-600 text-lg">Smart, personalized, delivered daily</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Your Reading List</h3>
                        <p class="text-gray-600">Add books you've read and we'll analyze them for key insights and themes</p>
                    </div>

                    <div class="text-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">AI-Generated Digests</h3>
                        <p class="text-gray-600">Our AI creates personalized summaries connecting insights across your books</p>
                    </div>

                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Daily Emails</h3>
                        <p class="text-gray-600">Receive thoughtful digests in your inbox to reinforce and expand your knowledge</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Example Digest Modal -->
        <div id="exampleModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <header class="flex justify-between items-center mb-4 p-6 pb-0">
                    <h3 class="text-xl font-bold">Daily Digest Example</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl transition-colors cursor-pointer" aria-label="Close modal">&times;</button>
                </header>
                
                <!-- Email Preview -->
                <div class="bg-gray-50 mx-6 p-6 rounded-lg border">
                    <div class="space-y-2 mb-4">
                        <div class="text-sm text-gray-500">From: Book Digest &lt;digest@bookdigest.com&gt;</div>
                        <div class="text-sm text-gray-500">Subject: Your Daily Digest - March 15, 2024</div>
                    </div>
                    <hr class="my-4">
                    
                    <div class="space-y-6">
                        <header class="text-center">
                            <h2 class="text-2xl font-bold mb-2">📚 Your Daily Digest</h2>
                            <p class="text-gray-600">Connecting insights from your reading journey</p>
                        </header>
                        
                        <div class="bg-white p-4 rounded border-l-4 border-blue-500">
                            <h3 class="font-semibold text-lg mb-2">🧠 Today's Insight</h3>
                            <p class="text-gray-700 mb-3">The connection between <strong>Atomic Habits</strong> and <strong>Mindset</strong> reveals a powerful truth about personal growth: small, consistent actions compound when backed by the right mental framework.</p>
                            <p class="text-sm text-gray-600">From your books: Atomic Habits by James Clear, Mindset by Carol Dweck</p>
                        </div>
                        
                        <div class="bg-white p-4 rounded border-l-4 border-green-500">
                            <h3 class="font-semibold text-lg mb-2">💡 Cross-Book Connection</h3>
                            <p class="text-gray-700 mb-3">Both <strong>Deep Work</strong> and <strong>Flow</strong> emphasize the importance of undivided attention. While Newport focuses on productivity, Csikszentmihalyi reveals how this same focus creates fulfillment.</p>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p>• Deep Work: "Focus on what matters most"</p>
                                <p>• Flow: "Complete absorption in activity"</p>
                            </div>
                        </div>
                        
                        <div class="bg-white p-4 rounded border-l-4 border-purple-500">
                            <h3 class="font-semibold text-lg mb-2">📖 Quote to Ponder</h3>
                            <blockquote class="italic text-gray-700 mb-2">The cave you fear to enter holds the treasure you seek.</blockquote>
                            <p class="text-sm text-gray-600">From: The Hero with a Thousand Faces by Joseph Campbell</p>
                        </div>
                        
                        <div class="bg-blue-50 p-4 rounded">
                            <h3 class="font-semibold text-lg mb-2">🎯 Today's Reflection</h3>
                            <p class="text-gray-700">How can you apply the principle of "marginal gains" from your reading to one area of your life today?</p>
                        </div>
                        
                        <footer class="text-center pt-4 border-t text-sm text-gray-500 space-y-2">
                            <p>This digest was generated from 12 books in your library</p>
                            <p>Happy reading! 📚</p>
                        </footer>
                    </div>
                </div>
                
                <!-- Modal CTA -->
                <div class="p-6 text-center">
                    <p class="text-gray-600 mb-4">Get personalized digests like this every day!</p>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="bg-black text-white px-6 py-2 rounded-lg font-medium hover:bg-gray-800 transition-colors inline-block">
                            Start Your Free Trial
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t py-8">
            <div class="max-w-7xl mx-auto px-6 text-center">
                <p class="text-gray-600">&copy; {{ date('Y') }} Book Digest. Made with ❤️ for book lovers.</p>
            </div>
        </footer>


        <script>
            class ModalManager {
                constructor(modalId) {
                    this.modal = document.getElementById(modalId);
                    this.initializeEventListeners();
                }
                
                open() {
                    this.modal.classList.remove('hidden');
                    this.modal.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }
                
                close() {
                    this.modal.classList.add('hidden');
                    this.modal.classList.remove('flex');
                    document.body.style.overflow = 'auto';
                }
                
                initializeEventListeners() {
                    // Close on outside click
                    this.modal.addEventListener('click', (e) => {
                        if (e.target === this.modal) {
                            this.close();
                        }
                    });
                    
                    // Close on Escape key
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                            this.close();
                        }
                    });
                }
            }
            
            // Initialize modal
            const exampleModal = new ModalManager('exampleModal');
            
            // Global functions for onclick handlers
            function openModal() {
                exampleModal.open();
            }
            
            function closeModal() {
                exampleModal.close();
            }
        </script>
    </body>
</html>
