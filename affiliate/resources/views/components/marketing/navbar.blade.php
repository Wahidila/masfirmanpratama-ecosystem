<nav x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 8"
     class="fixed inset-x-0 top-0 z-50 glass border-b border-slate-100 transition-shadow"
     :class="scrolled ? 'shadow-sm' : ''">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center">
                <x-brand-mark size="md" />
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}" class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-600 hover:text-primary-600 transition">Masuk</a>
                <x-button :href="route('register')" size="sm" icon="arrow-right" iconPosition="right">Daftar</x-button>
            </div>
        </div>
    </div>
</nav>
