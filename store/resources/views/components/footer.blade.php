@props([
    'brandText' => null,
    'brandAccent' => null,
    'tagline' => null,
    'address' => null,
    'phone' => null,
    'email' => null,
    'socials' => null,
    'sitemap' => null,
])

@php
    // Konten footer dinamis dari Settings (tab Settings → Footer di admin), dengan
    // fallback ke default. Props masih bisa override per-pemakaian bila perlu.
    $footer = \App\Services\Settings::getFooter();

    $brandText ??= $footer['brand_text'];
    $brandAccent ??= $footer['brand_accent'];
    $tagline ??= $footer['tagline'];
    $address ??= $footer['address'];
    $phone ??= $footer['phone'];
    $email ??= $footer['email'];

    $socialLinks = $socials ?? $footer['socials'];

    // Link disimpan sebagai list datar {group, label, href}; dikelompokkan per
    // `group` (urutan mengikuti kemunculan pertama) menjadi kolom sitemap.
    $sitemapLinks = $sitemap ?? collect($footer['links'])
        ->groupBy(fn ($link) => $link['group'] ?? 'Lainnya')
        ->map(fn ($rows) => $rows->map(fn ($r) => [
            'label' => $r['label'] ?? '',
            'href' => $r['href'] ?? '#',
        ])->values()->all())
        ->all();

    $legalLinks = $footer['legal'];
    $copyright = str_replace('{year}', (string) now()->year, (string) $footer['copyright']);

    // Logo footer dinamis (tab Settings → Logo); fallback ikon+teks brand.
    $footerLogo = \App\Services\Settings::getBranding()['footer_logo_url'];
@endphp

<footer
    {{ $attributes->merge([
        'class' => 'bg-slate-950 text-slate-300 pt-16 pb-10 border-t border-slate-800 mt-20',
    ]) }}
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            {{-- Brand --}}
            <div>
                <div class="flex items-center gap-2 mb-6">
                    @if ($footerLogo)
                        <img src="{{ $footerLogo }}" alt="{{ $brandText }} {{ $brandAccent }}" class="h-9 w-auto max-w-[200px] object-contain">
                    @else
                        <span class="w-9 h-9 bg-primary-600 rounded-lg flex items-center justify-center text-white">
                            <i data-lucide="brain-circuit" class="w-5 h-5"></i>
                        </span>
                        <span class="font-bold text-xl text-white">
                            {{ $brandText }}<span class="text-primary-500">{{ $brandAccent }}</span>
                        </span>
                    @endif
                </div>
                <p class="text-sm mb-6 text-slate-300 leading-relaxed">{{ $tagline }}</p>

                <div class="flex gap-3">
                    @foreach ($socialLinks as $social)
                        <a
                            href="{{ $social['href'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ $social['label'] ?? ucfirst($social['icon']) }}"
                            class="w-10 h-10 rounded-full bg-slate-800 hover:bg-primary-600 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
                        >
                            <i data-lucide="{{ $social['icon'] }}" class="w-5 h-5"></i>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Sitemap groups --}}
            @foreach ($sitemapLinks as $heading => $items)
                <div>
                    <h3 class="text-white font-bold mb-6">{{ $heading }}</h3>
                    <ul class="space-y-3 text-sm">
                        @foreach ($items as $item)
                            <li>
                                <a
                                    href="{{ $item['href'] }}"
                                    class="hover:text-primary-400 transition-colors"
                                >
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            {{-- Contact --}}
            <div>
                <h3 class="text-white font-bold mb-6">Pusat Layanan</h3>
                <ul class="space-y-4 text-sm">
                    <li class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-primary-500 flex-shrink-0 mt-0.5"></i>
                        <span>{{ $address }}</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="phone" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
                        <a href="tel:{{ preg_replace('/\D+/', '', $phone) }}" class="hover:text-white transition-colors">{{ $phone }}</a>
                    </li>
                    <li class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-5 h-5 text-primary-500 flex-shrink-0"></i>
                        <a href="mailto:{{ $email }}" class="hover:text-white transition-colors break-all">{{ $email }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
            <p>{{ $copyright }}</p>
            @if (! empty($legalLinks))
                <div class="flex gap-6">
                    @foreach ($legalLinks as $legal)
                        <a href="{{ $legal['href'] ?? '#' }}" class="hover:text-white transition-colors">{{ $legal['label'] ?? '' }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</footer>
