@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'block w-full px-4 py-3 text-gray-900 placeholder-gray-500 bg-white/70 backdrop-blur-sm border-2 border-gray-200 rounded-xl shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:bg-white transition-all duration-300 hover:border-gray-300 hover:shadow-md']) }}>
