import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        init() {
            const saved = localStorage.getItem('theme');
            const sys = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            this.theme = saved || sys;
            this.apply();
        },
        theme: 'light',
        toggle() {
            this.theme = this.theme === 'light' ? 'dark' : 'light';
            localStorage.setItem('theme', this.theme);
            this.apply();
        },
        apply() {
            document.documentElement.classList.toggle('dark', this.theme === 'dark');
        }
    });

    Alpine.store('sidebar', {
        isExpanded: window.innerWidth >= 1280,
        isMobileOpen: false,
        isHovered: false,
        toggleExpanded() {
            this.isExpanded = !this.isExpanded;
            this.isMobileOpen = false;
        },
        toggleMobileOpen() {
            this.isMobileOpen = !this.isMobileOpen;
        },
        setMobileOpen(v) {
            this.isMobileOpen = v;
        },
        setHovered(v) {
            if (window.innerWidth >= 1280 && !this.isExpanded) {
                this.isHovered = v;
            }
        }
    });
});

Alpine.start();
