<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <!-- Animated Background -->
        <div class="min-h-screen relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <!-- Background Elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-r from-blue-400 to-purple-400 rounded-full opacity-10 animate-pulse"></div>
                <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-r from-pink-400 to-indigo-400 rounded-full opacity-10 animate-pulse delay-1000"></div>
                <div class="absolute top-1/3 left-1/4 w-32 h-32 bg-gradient-to-r from-cyan-300 to-blue-300 rounded-full opacity-15 animate-pulse delay-500"></div>
            </div>

            <div class="relative z-10 min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
                <div class="mb-8">
                    <a href="/" class="group block">
                        <div class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent group-hover:scale-110 transition-transform duration-300">
                            📚 Book Digest
                        </div>
                    </a>
                </div>

                <div class="w-full sm:max-w-md mt-6 px-8 py-8 bg-white/80 backdrop-blur-md shadow-2xl shadow-indigo-500/10 overflow-hidden rounded-3xl border border-white/20 hover:shadow-3xl hover:shadow-indigo-500/20 transition-all duration-500">
                    {{ $slot }}
                </div>

                <!-- Decorative Elements -->
                <div class="absolute top-20 right-20 w-4 h-4 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-full opacity-60 animate-bounce"></div>
                <div class="absolute bottom-32 left-16 w-3 h-3 bg-gradient-to-r from-green-400 to-cyan-400 rounded-full opacity-50 animate-bounce delay-300"></div>
                <div class="absolute top-1/4 right-1/4 w-2 h-2 bg-gradient-to-r from-pink-400 to-purple-400 rounded-full opacity-40 animate-bounce delay-700"></div>
            </div>
        </div>
    </body>
</html>
