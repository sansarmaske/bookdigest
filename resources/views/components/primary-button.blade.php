<button {{ $attributes->merge(['type' => 'submit', 'class' => 'group relative inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 border border-transparent rounded-xl font-semibold text-sm text-white uppercase tracking-wider hover:shadow-lg hover:shadow-indigo-500/25 focus:shadow-lg focus:shadow-indigo-500/25 active:scale-95 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-300 hover:scale-105 transform overflow-hidden']) }}>
    <span class="relative z-10">{{ $slot }}</span>
    <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <div class="absolute inset-0 bg-white/20 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left"></div>
</button>
