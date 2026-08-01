@props([
    'cartCount' => 0,
    'logoText' => 'Firman',
    'logoAccent' => 'Pratama',
    'ctaLabel' => 'Konsultasi',
    'ctaHref' => 'https://wa.me/6281230633464',
])

@php
    // Logo dinamis dari Settings (tab Settings → Logo di admin); fallback ikon+teks.
    $headerLogo = \App\Services\Settings::getBranding()['header_logo_url'];
@endphp

<nav
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 8)"
    :class="scrolled ? 'shadow-md' : ''"
    {{ $attributes->merge([
        'class' => 'fixed inset-x-0 top-0 z-50 glass border-b border-slate-100 transition-shadow duration-300',
    ]) }}
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            {{-- Logo --}}
            <a href="{{ url('/') }}" class="flex items-center gap-2 cursor-pointer">
                @if ($headerLogo)
                    <img src="{{ $headerLogo }}" alt="{{ $logoText }} {{ $logoAccent }}" class="h-10 w-auto max-w-[200px] object-contain">
                @else
                    <span class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-primary-500/30">
                        <i data-lucide="brain-circuit" class="w-6 h-6"></i>
                    </span>
                    <span class="font-bold text-xl sm:text-2xl tracking-tight text-slate-900">
                        {{ $logoText }}<span class="text-primary-600">{{ $logoAccent }}</span>
                    </span>
                @endif
            </a>

            {{-- Desktop nav --}}
            <div class="hidden md:flex items-center space-x-8">
                <a href="{{ url('/') }}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Beranda</a>
                <a href="{{ url('/produk') }}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Produk</a>
                <a href="{{ route('blog.index') }}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Blog</a>
                <a href="{{ route('pages.tentang') }}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Tentang</a>
                <a href="{{ route('pages.kontak') }}" class="text-slate-600 hover:text-primary-600 font-medium transition-colors">Kontak</a>

                <span class="h-6 w-px bg-slate-200" aria-hidden="true"></span>

                {{-- Cart icon --}}
                <a
                    href="{{ route('cart.index') }}"
                    class="relative inline-flex items-center justify-center w-11 h-11 rounded-full text-slate-600 hover:text-primary-600 hover:bg-primary-50 transition-colors"
                    aria-label="Keranjang belanja"
                >
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    {{-- Badge: Alpine-reactive ($store.cart.count) with SSR fallback for pre-hydration --}}
                    <span
                        x-cloak
                        x-show="$store.cart && $store.cart.count > 0"
                        x-text="$store.cart && $store.cart.count > 99 ? '99+' : ($store.cart ? $store.cart.count : 0)"
                        class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-accent-500 text-white text-[10px] font-bold ring-2 ring-white"
                    >{{ $cartCount > 99 ? '99+' : ($cartCount > 0 ? $cartCount : '') }}</span>
                </a>

                <x-button :href="$ctaHref" size="sm" icon="message-circle" iconPosition="left">
                    {{ $ctaLabel }}
                </x-button>
            </div>

            {{-- Mobile toggle --}}
            <div class="md:hidden flex items-center gap-1">
                <a
                    href="{{ route('cart.index') }}"
                    class="relative inline-flex items-center justify-center w-11 h-11 rounded-full text-slate-600 hover:text-primary-600 hover:bg-primary-50 transition-colors"
                    aria-label="Keranjang belanja"
                >
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    <span
                        x-cloak
                        x-show="$store.cart && $store.cart.count > 0"
                        x-text="$store.cart && $store.cart.count > 99 ? '99+' : ($store.cart ? $store.cart.count : 0)"
                        class="absolute top-1 right-1 inline-flex items-center justify-center min-w-[20px] h-5 px-1 rounded-full bg-accent-500 text-white text-[10px] font-bold ring-2 ring-white"
                    ></span>
                </a>
                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-controls="mobile-nav"
                    class="inline-flex items-center justify-center w-11 h-11 rounded-lg text-slate-600 hover:text-primary-600 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 transition-colors"
                    aria-label="Buka menu navigasi"
                >
                    <i data-lucide="menu" class="w-6 h-6" x-show="!open"></i>
                    <i data-lucide="x" class="w-6 h-6" x-show="open" x-cloak></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div
        id="mobile-nav"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="md:hidden bg-white border-t border-slate-100 shadow-xl origin-top"
        @click.outside="open = false"
        @keydown.escape.window="open = false"
    >
        <div class="px-4 pt-3 pb-6 space-y-1">
            <a href="{{ url('/') }}" class="flex items-center min-h-[44px] px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Beranda</a>
            <a href="{{ url('/produk') }}" class="flex items-center min-h-[44px] px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Produk</a>
            <a href="{{ route('blog.index') }}" class="flex items-center min-h-[44px] px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Blog</a>
            <a href="{{ route('pages.tentang') }}" class="flex items-center min-h-[44px] px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Tentang</a>
            <a href="{{ route('pages.kontak') }}" class="flex items-center min-h-[44px] px-3 py-3 rounded-md text-base font-medium text-slate-700 hover:text-primary-600 hover:bg-primary-50 transition-colors">Kontak</a>

            <div class="pt-2">
                <x-button :href="$ctaHref" size="md" icon="message-circle" iconPosition="left" class="w-full">
                    {{ $ctaLabel }}
                </x-button>
            </div>
        </div>
    </div>
</nav>
