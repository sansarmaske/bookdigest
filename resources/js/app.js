import './bootstrap';

import Alpine from 'alpinejs';

Alpine.store('darkMode', {
    isDark: localStorage.getItem('theme') === 'dark' || 
           (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
    
    init() {
        this.updateTheme();
    },
    
    toggle() {
        this.isDark = !this.isDark;
        this.updateTheme();
    },
    
    updateTheme() {
        if (this.isDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    }
});

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('alpine:init', () => {
    Alpine.store('darkMode').init();
});
