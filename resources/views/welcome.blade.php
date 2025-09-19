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
                            },
                            animation: {
                                'fade-in': 'fadeIn 1s ease-out',
                                'fade-in-up': 'fadeInUp 1s ease-out',
                                'gradient': 'gradient 6s ease infinite',
                                'float': 'float 6s ease-in-out infinite',
                                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                            },
                            keyframes: {
                                fadeIn: {
                                    '0%': { opacity: '0' },
                                    '100%': { opacity: '1' }
                                },
                                fadeInUp: {
                                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                                    '100%': { opacity: '1', transform: 'translateY(0)' }
                                },
                                gradient: {
                                    '0%, 100%': { backgroundPosition: '0% 50%' },
                                    '50%': { backgroundPosition: '100% 50%' }
                                },
                                float: {
                                    '0%, 100%': { transform: 'translateY(0px)' },
                                    '50%': { transform: 'translateY(-10px)' }
                                }
                            }
                        }
                    }
                }
            </script>
        @endif
    </head>
    <body class="bg-gradient-to-br from-indigo-50 via-white to-cyan-50 text-gray-900 font-sans antialiased min-h-screen">
        <!-- Animated Background Elements -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-r from-blue-400 to-purple-400 rounded-full opacity-20 animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-r from-pink-400 to-red-400 rounded-full opacity-15 animate-pulse delay-1000"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-gradient-to-r from-green-400 to-blue-400 rounded-full opacity-10 animate-pulse delay-500"></div>
        </div>

        <!-- Header -->
        <header class="absolute top-0 left-0 right-0 z-10 backdrop-blur-md bg-white/80 border-b border-white/20">
            <nav class="flex items-center justify-between p-6 max-w-7xl mx-auto">
                <div class="text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent animate-pulse">
                    📚 Book Digest
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-gray-900 transition-all duration-300 hover:scale-105">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 transition-all duration-300 hover:scale-105 relative group">
                                Log in
                                <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-indigo-600 to-purple-600 group-hover:w-full transition-all duration-300"></span>
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-xl hover:shadow-lg hover:shadow-indigo-500/25 transition-all duration-300 hover:scale-105 transform">
                                    Get Started
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="min-h-screen flex items-center justify-center px-6 relative">
            <div class="text-center max-w-4xl mx-auto relative z-10">
                <!-- Floating Elements -->
                <div class="absolute -top-20 -left-10 w-8 h-8 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full opacity-60 animate-bounce"></div>
                <div class="absolute -top-16 right-10 w-6 h-6 bg-gradient-to-r from-green-400 to-blue-400 rounded-full opacity-50 animate-bounce delay-300"></div>
                <div class="absolute top-10 -right-16 w-4 h-4 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full opacity-40 animate-bounce delay-700"></div>

                <h1 class="text-6xl md:text-7xl font-bold mb-8 leading-tight animate-fade-in">
                    <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 bg-clip-text text-transparent animate-gradient">
                        AI-powered insights
                    </span>
                    <br>
                    from your
                    <span class="relative">
                        <span class="bg-gradient-to-r from-cyan-500 to-blue-500 bg-clip-text text-transparent">
                            library
                        </span>
                        <svg class="absolute -bottom-2 left-0 w-full h-4 text-cyan-400 opacity-30" viewBox="0 0 200 12" fill="none">
                            <path d="M2 6C50 3 150 9 198 6" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </span>
                </h1>

                <p class="text-xl md:text-2xl text-gray-600 mb-10 leading-relaxed animate-fade-in-up opacity-90">
                    Transform your reading list into
                    <span class="font-semibold text-indigo-600">personalized daily digests</span>.
                    Our AI analyzes the books you've read and delivers
                    <span class="font-semibold text-purple-600">key insights</span>
                    straight to your inbox.
                </p>

                <div class="flex flex-col sm:flex-row gap-6 justify-center animate-fade-in-up delay-300">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="group relative bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-10 py-4 rounded-2xl font-semibold text-lg hover:shadow-2xl hover:shadow-indigo-500/25 transition-all duration-500 hover:scale-110 transform overflow-hidden">
                            <span class="relative z-10">Get Started Free</span>
                            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <div class="absolute inset-0 bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                        </a>
                    @endif
                    <button onclick="openModal()" class="group relative backdrop-blur-sm bg-white/70 border-2 border-white/50 text-gray-700 px-10 py-4 rounded-2xl font-semibold text-lg hover:bg-white/90 hover:shadow-xl hover:shadow-gray-500/20 transition-all duration-300 hover:scale-105 transform">
                        <span class="flex items-center justify-center gap-2">
                            <span>See Example</span>
                            <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                    </button>
                </div>

                <!-- Stats or Social Proof -->
                <div class="mt-16 flex flex-wrap justify-center gap-8 text-sm text-gray-500 animate-fade-in-up delay-500">
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        <span>AI-powered insights</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse delay-200"></div>
                        <span>Daily personalized digests</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-2 h-2 bg-purple-400 rounded-full animate-pulse delay-400"></div>
                        <span>Connect insights across books</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- Features Section -->
        <section class="py-32 relative overflow-hidden">
            <!-- Gradient Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100"></div>

            <!-- Animated shapes -->
            <div class="absolute top-20 left-10 w-32 h-32 bg-gradient-to-r from-blue-200 to-purple-200 rounded-full opacity-20 animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-24 h-24 bg-gradient-to-r from-pink-200 to-red-200 rounded-full opacity-25 animate-pulse delay-1000"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-6">
                <div class="text-center mb-20">
                    <h2 class="text-5xl md:text-6xl font-bold mb-6 bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent">
                        How it works
                    </h2>
                    <p class="text-2xl text-gray-600 font-medium">Smart, personalized, delivered daily</p>
                    <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 mx-auto mt-6 rounded-full"></div>
                </div>

                <div class="grid md:grid-cols-3 gap-12">
                    <!-- Feature 1 -->
                    <div class="group text-center relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-3xl opacity-0 group-hover:opacity-100 transition-all duration-500 transform group-hover:scale-105 blur-xl"></div>
                        <div class="relative p-8 backdrop-blur-sm bg-white/80 rounded-3xl border border-white/50 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-gray-800">Your Reading List</h3>
                            <p class="text-gray-600 text-lg leading-relaxed">Add books you've read and we'll analyze them for key insights and themes</p>
                            <div class="mt-6 px-4 py-2 bg-blue-50 rounded-full text-blue-600 text-sm font-medium inline-block">
                                Step 1
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="group text-center relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-purple-100 to-pink-100 rounded-3xl opacity-0 group-hover:opacity-100 transition-all duration-500 transform group-hover:scale-105 blur-xl"></div>
                        <div class="relative p-8 backdrop-blur-sm bg-white/80 rounded-3xl border border-white/50 hover:shadow-2xl hover:shadow-purple-500/10 transition-all duration-500 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-gray-800">AI-Generated Digests</h3>
                            <p class="text-gray-600 text-lg leading-relaxed">Our AI creates personalized summaries connecting insights across your books</p>
                            <div class="mt-6 px-4 py-2 bg-purple-50 rounded-full text-purple-600 text-sm font-medium inline-block">
                                Step 2
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="group text-center relative">
                        <div class="absolute inset-0 bg-gradient-to-br from-green-100 to-emerald-100 rounded-3xl opacity-0 group-hover:opacity-100 transition-all duration-500 transform group-hover:scale-105 blur-xl"></div>
                        <div class="relative p-8 backdrop-blur-sm bg-white/80 rounded-3xl border border-white/50 hover:shadow-2xl hover:shadow-green-500/10 transition-all duration-500 group-hover:transform group-hover:-translate-y-2">
                            <div class="w-20 h-20 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-4 text-gray-800">Daily Emails</h3>
                            <p class="text-gray-600 text-lg leading-relaxed">Receive thoughtful digests in your inbox to reinforce and expand your knowledge</p>
                            <div class="mt-6 px-4 py-2 bg-green-50 rounded-full text-green-600 text-sm font-medium inline-block">
                                Step 3
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Lines -->
                <div class="hidden md:flex absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-4xl">
                    <div class="flex justify-between w-full px-16">
                        <svg class="w-32 h-8 text-indigo-300" fill="none" viewBox="0 0 128 32">
                            <path d="M0 16h128" stroke="currentColor" stroke-width="2" stroke-dasharray="8,4" opacity="0.6"/>
                            <path d="M120 12l8 4-8 4" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                        <svg class="w-32 h-8 text-indigo-300" fill="none" viewBox="0 0 128 32">
                            <path d="M0 16h128" stroke="currentColor" stroke-width="2" stroke-dasharray="8,4" opacity="0.6"/>
                            <path d="M120 12l8 4-8 4" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                </div>
            </div>
        </section>

        <!-- Example Digest Modal -->
        <div id="exampleModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
            <div class="bg-white/95 backdrop-blur-md rounded-3xl max-w-3xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-white/20" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <header class="flex justify-between items-center mb-6 p-8 pb-0">
                    <div>
                        <h3 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Daily Digest Example</h3>
                        <p class="text-gray-600 mt-2">See what personalized insights look like</p>
                    </div>
                    <button onclick="closeModal()" class="group w-10 h-10 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-red-100 hover:to-red-200 rounded-full flex items-center justify-center text-gray-500 hover:text-red-500 transition-all duration-300 hover:scale-110 transform" aria-label="Close modal">
                        <svg class="w-5 h-5 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </header>

                <!-- Email Preview with Modern Styling -->
                <div class="mx-8 mb-8 relative">
                    <!-- Mock Browser Frame -->
                    <div class="bg-gradient-to-r from-gray-100 to-gray-200 p-4 rounded-t-2xl border-b">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                            <div class="ml-4 bg-white rounded-full px-4 py-1 text-xs text-gray-600">
                                📧 digest@bookdigest.com
                            </div>
                        </div>
                        <div class="space-y-1 text-sm text-gray-600">
                            <div class="flex"><span class="font-medium">From:</span><span class="ml-2">Book Digest &lt;digest@bookdigest.com&gt;</span></div>
                            <div class="flex"><span class="font-medium">Subject:</span><span class="ml-2">Your Daily Digest - March 15, 2024</span></div>
                        </div>
                    </div>

                    <!-- Email Content -->
                    <div class="bg-gradient-to-br from-white to-gray-50 p-8 rounded-b-2xl shadow-inner border border-gray-200">
                        <div class="space-y-8">
                            <!-- Header -->
                            <header class="text-center relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-100 to-purple-100 rounded-3xl opacity-50 blur-xl"></div>
                                <div class="relative z-10 p-6">
                                    <h2 class="text-4xl font-bold mb-3 bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">📚 Your Daily Book Digest</h2>
                                    <p class="text-xl text-gray-600">March 15, 2024</p>
                                    <div class="w-20 h-1 bg-gradient-to-r from-indigo-500 to-purple-500 mx-auto mt-4 rounded-full"></div>
                                </div>
                            </header>

                            <!-- Greeting -->
                            <div class="text-center mb-8">
                                <p class="text-xl text-gray-700 mb-6">Good morning, Sarah! ☀️</p>
                                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-6 rounded-2xl border border-indigo-100">
                                    <p class="text-lg italic text-gray-700">"A reader lives a thousand lives before he dies. The man who never reads lives only one."</p>
                                    <p class="text-sm text-gray-500 mt-2">— George R.R. Martin</p>
                                </div>
                            </div>

                            <!-- Today's Snippets -->
                            <div class="group relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-2xl opacity-0 group-hover:opacity-100 transition-all duration-500 blur-xl"></div>
                                <div class="relative bg-white/80 backdrop-blur-sm p-6 rounded-2xl border-l-4 border-blue-500 shadow-lg hover:shadow-xl transition-all duration-300 hover:transform hover:-translate-y-1">
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center text-2xl">📖</div>
                                        <h3 class="text-2xl font-bold text-gray-800">Today's Snippets</h3>
                                    </div>

                                    <!-- Snippet 1 -->
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <div class="bg-blue-50 rounded-lg p-4 mb-3">
                                            <p class="text-sm font-medium text-blue-700">From <span class="font-semibold italic">"Atomic Habits"</span> by James Clear</p>
                                        </div>
                                        <p class="text-gray-700 text-lg leading-relaxed">"You do not rise to the level of your goals. You fall to the level of your systems. Your goal is your desired outcome. Your system is the collection of daily habits that will get you there."</p>
                                    </div>

                                    <!-- Snippet 2 -->
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <div class="bg-purple-50 rounded-lg p-4 mb-3">
                                            <p class="text-sm font-medium text-purple-700">From <span class="font-semibold italic">"Mindset"</span> by Carol Dweck</p>
                                        </div>
                                        <p class="text-gray-700 text-lg leading-relaxed">"Becoming is better than being. The fixed mindset does not allow people the luxury of becoming. They have to already be."</p>
                                    </div>

                                    <!-- Snippet 3 -->
                                    <div>
                                        <div class="bg-green-50 rounded-lg p-4 mb-3">
                                            <p class="text-sm font-medium text-green-700">From <span class="font-semibold italic">"Deep Work"</span> by Cal Newport</p>
                                        </div>
                                        <p class="text-gray-700 text-lg leading-relaxed">"Human beings, it seems, are at their best when immersed deeply in something challenging."</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Inspirational Message -->
                            <div class="text-center mt-8">
                                <div class="bg-gradient-to-r from-cyan-50 to-blue-50 p-6 rounded-2xl border border-cyan-100">
                                    <p class="text-lg italic text-gray-700">Let these words inspire your day and fuel your passion for reading! 📖✨</p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <footer class="text-center pt-6 border-t border-gray-200 space-y-3">
                                <p class="text-gray-600">This digest was generated with love by your personal book quote system.</p>
                                <p class="text-2xl">Happy reading! 📚</p>
                            </footer>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Modal CTA -->
                <div class="p-8 text-center relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-b-3xl"></div>
                    <div class="relative z-10">
                        <p class="text-xl text-gray-700 mb-6 font-medium">Get personalized digests like this every day!</p>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="group relative inline-flex items-center justify-center px-12 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold text-lg rounded-2xl hover:shadow-2xl hover:shadow-indigo-500/25 transition-all duration-500 hover:scale-110 transform overflow-hidden">
                                <span class="relative z-10 flex items-center gap-3">
                                    <span>Start Your Journey</span>
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                    </svg>
                                </span>
                                <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                <div class="absolute inset-0 bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
                            </a>
                        @endif
                        <p class="text-sm text-gray-500 mt-4">✨ Free to start • No credit card required</p>
                    </div>
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
