@props([
    'product',
    'related' => [],
])

@php
    /** @var array $product */
    $title = $product['title'] ?? 'Kelas';
    $subtitle = $product['subtitle'] ?? '';
    $price = (int) ($product['price'] ?? 0);
    $originalPrice = $product['original_price'] ?? null;
    $hasDiscount = $originalPrice && (int) $originalPrice > $price;
    $discountPercent = $hasDiscount ? (int) round((((int) $originalPrice - $price) / (int) $originalPrice) * 100) : 0;
    $formattedPrice = 'Rp' . number_format($price, 0, ',', '.');
    $formattedOriginal = $originalPrice ? 'Rp' . number_format((int) $originalPrice, 0, ',', '.') : null;
    $image = $product['image'] ?? null;
    $imageAlt = $product['image_alt'] ?? $title;
    $badge = $product['badge'] ?? null;
    $badgeIcon = $product['badge_icon'] ?? 'sparkles';
    $categoryLabel = $product['category_label'] ?? 'Kelas';
    $rating = $product['rating'] ?? '4.9/5';
    $studentCount = $product['student_count'] ?? '1000+';
    $tagline = $product['tagline'] ?? null;
    $description = $product['description'] ?? [];
    $syllabus = $product['syllabus'] ?? [];
    $schedule = $product['schedule'] ?? [];
    $benefits = $product['benefits'] ?? [];
    $testimonials = $product['testimonials'] ?? [];
    $ctaLabel = $product['cta_label'] ?? 'Daftar Sekarang';
    $ctaHref = $product['cta_href'] ?? route('checkout.index');
    $alpineProduct = [
        'slug' => $product['slug'] ?? '',
        'name' => $title,
        'title' => $title,
        'price' => $price,
        'image' => $image,
        'category' => $categoryLabel ?? 'Kelas',
        'is_shippable' => false,
    ];
    $installmentAvailable = $product['installment_available'] ?? false;
@endphp

<x-layouts.store
    :title="$title . ' | Firman Pratama'"
    :description="\Illuminate\Support\Str::limit($subtitle, 160)"
    :ogImage="$image"
    ogType="product"
    bodyClass="pb-24 lg:pb-0"
>
    {{-- Page-specific structured data --}}
    <x-slot name="head">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $title,
            'description' => $subtitle,
            'provider' => [
                '@type' => 'Organization',
                'name' => 'Firman Pratama — Alpha Mind Control',
                'sameAs' => 'https://masfirmanpratama.com',
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'IDR',
                'availability' => 'https://schema.org/InStock',
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot>

    <section class="relative overflow-hidden">
        {{-- Background blobs --}}
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply opacity-20 blur-3xl animate-blob"></div>
            <div class="absolute bottom-32 left-10 w-80 h-80 bg-secondary-200 rounded-full mix-blend-multiply opacity-20 blur-3xl animate-blob animation-delay-200"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-20">

            {{-- Breadcrumbs --}}
            <nav
                aria-label="Breadcrumb"
                class="flex flex-wrap text-sm text-slate-500 mb-8 items-center gap-2 w-fit bg-white/60 px-4 py-2 rounded-full border border-slate-100 backdrop-blur-sm"
            >
                <a href="{{ route('home') }}" class="hover:text-primary-600 transition-colors font-medium flex items-center gap-1">
                    <i data-lucide="home" class="w-4 h-4"></i> Beranda
                </a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <a href="{{ route('products.index') }}" class="hover:text-primary-600 transition-colors font-medium">Program AMC</a>
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
                <span class="text-primary-600 font-bold truncate max-w-[200px] sm:max-w-none">{{ $categoryLabel }}</span>
            </nav>

            {{-- Grid parent reservation (M2-hardening H2-new): main content + sticky aside --}}
            {{-- Mobile stack vertical (hero + tabs + price card), desktop 8/4 split --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 min-h-[1800px] lg:min-h-[1200px]">

                {{-- ─── LEFT: Konten utama ───────────────────────────────── --}}
                <div class="lg:col-span-8 space-y-10">

                    {{-- Hero --}}
                    <header>
                        @if ($badge)
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-bold uppercase tracking-wider mb-4 border border-primary-100 shadow-sm">
                                <i data-lucide="{{ $badgeIcon }}" class="w-4 h-4"></i> {{ $badge }}
                            </div>
                        @endif

                        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-6 leading-tight">
                            {{ $title }}
                        </h1>

                        @if ($subtitle)
                            <p class="text-lg md:text-xl text-slate-600 leading-relaxed mb-6 font-medium">
                                {{ $subtitle }}
                            </p>
                        @endif

                        <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-sm text-slate-600 border-t border-b border-slate-200/60 py-4">
                            <div class="flex items-center gap-2 font-medium bg-slate-100/50 px-3 py-1.5 rounded-lg border border-slate-100">
                                <i data-lucide="users" class="w-5 h-5 text-primary-500"></i>
                                {{ $studentCount }} Peserta Terdaftar
                            </div>
                            <div class="flex items-center gap-2 font-medium bg-amber-50/50 px-3 py-1.5 rounded-lg border border-amber-100/50 text-amber-800">
                                <i data-lucide="star" class="w-5 h-5 text-accent-500"></i>
                                {{ $rating }} Rating Kepuasan
                            </div>
                            <div class="flex items-center gap-2 font-medium bg-secondary-50/50 px-3 py-1.5 rounded-lg border border-secondary-100/50 text-secondary-800">
                                <i data-lucide="clock" class="w-5 h-5 text-secondary-500"></i>
                                Fleksibel & Terjadwal
                            </div>
                        </div>
                    </header>

                    {{-- Media cover --}}
                    @if ($image)
                        <figure class="relative w-full aspect-[16/9] rounded-[2rem] overflow-hidden shadow-lg border border-slate-100 group">
                            <img
                                src="{{ asset($image) }}"
                                alt="{{ $imageAlt }}"
                                width="1280"
                                height="720"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 filter brightness-95"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                            >
                            @if ($tagline)
                                <figcaption class="absolute bottom-6 left-6 right-6">
                                    <div class="glass-dark p-4 rounded-xl inline-block max-w-md">
                                        <p class="text-white font-medium text-sm">"{{ $tagline }}"</p>
                                    </div>
                                </figcaption>
                            @endif
                        </figure>
                    @endif

                    {{-- Tabs (Alpine) --}}
                    <div
                        x-data="{
                            tab: 'deskripsi',
                            tabs: [
                                { id: 'deskripsi', label: 'Deskripsi', icon: 'file-text' },
                                { id: 'materi',    label: 'Materi',    icon: 'list-checks' },
                                { id: 'jadwal',    label: 'Jadwal',    icon: 'calendar' },
                                { id: 'benefit',   label: 'Benefit',   icon: 'gift' },
                                { id: 'testimoni', label: 'Testimoni', icon: 'message-square-quote' },
                            ],
                        }"
                        class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden"
                    >
                        {{-- Tab nav --}}
                        <div
                            role="tablist"
                            aria-label="Detail kelas"
                            class="flex overflow-x-auto border-b border-slate-100 bg-slate-50/50 px-2 sm:px-4"
                        >
                            <template x-for="t in tabs" :key="t.id">
                                <button
                                    type="button"
                                    role="tab"
                                    :id="'tab-' + t.id"
                                    :aria-selected="tab === t.id"
                                    :aria-controls="'panel-' + t.id"
                                    :tabindex="tab === t.id ? 0 : -1"
                                    @click="tab = t.id"
                                    @keydown.right.prevent="
                                        const i = tabs.findIndex(x => x.id === tab);
                                        tab = tabs[(i + 1) % tabs.length].id;
                                        $nextTick(() => $root.querySelector('#tab-' + tab)?.focus());
                                    "
                                    @keydown.left.prevent="
                                        const i = tabs.findIndex(x => x.id === tab);
                                        tab = tabs[(i - 1 + tabs.length) % tabs.length].id;
                                        $nextTick(() => $root.querySelector('#tab-' + tab)?.focus());
                                    "
                                    :class="tab === t.id
                                        ? 'text-primary-700 border-primary-600 bg-white'
                                        : 'text-slate-500 border-transparent hover:text-primary-600 hover:bg-white/50'"
                                    class="shrink-0 inline-flex items-center gap-2 px-4 sm:px-5 py-4 text-sm font-semibold border-b-2 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                >
                                    <i :data-lucide="t.icon" class="w-4 h-4"></i>
                                    <span x-text="t.label"></span>
                                </button>
                            </template>
                        </div>

                        {{-- Tab panels --}}
                        <div class="p-6 sm:p-8 md:p-10">

                            {{-- Deskripsi --}}
                            <div
                                role="tabpanel"
                                id="panel-deskripsi"
                                aria-labelledby="tab-deskripsi"
                                x-show="tab === 'deskripsi'"
                                x-transition.opacity.duration.200ms
                                class="prose prose-slate max-w-none"
                            >
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 border-b-2 border-primary-100 inline-block pb-2">
                                    Tentang Kelas Ini
                                </h2>
                                @forelse ($description as $paragraph)
                                    <p class="text-slate-600 leading-relaxed mb-4 text-base">{{ $paragraph }}</p>
                                @empty
                                    <p class="text-slate-500 italic">Deskripsi belum tersedia.</p>
                                @endforelse
                            </div>

                            {{-- Materi --}}
                            <div
                                role="tabpanel"
                                id="panel-materi"
                                aria-labelledby="tab-materi"
                                x-show="tab === 'materi'"
                                x-transition.opacity.duration.200ms
                                x-cloak
                            >
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 border-b-2 border-primary-100 inline-block pb-2">
                                    Materi Apa Saja Yang Dibahas?
                                </h2>

                                @if (count($syllabus) > 0)
                                    <ol class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 list-none">
                                        @foreach ($syllabus as $i => $point)
                                            <li class="flex items-start gap-4 group">
                                                <div class="w-8 h-8 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center shrink-0 border border-primary-100 group-hover:bg-primary-600 group-hover:text-white transition-colors text-xs font-bold">
                                                    {{ $i + 1 }}
                                                </div>
                                                <p class="text-slate-700 font-medium leading-relaxed pt-1">{{ $point }}</p>
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="text-slate-500 italic">Daftar materi belum tersedia.</p>
                                @endif
                            </div>

                            {{-- Jadwal --}}
                            <div
                                role="tabpanel"
                                id="panel-jadwal"
                                aria-labelledby="tab-jadwal"
                                x-show="tab === 'jadwal'"
                                x-transition.opacity.duration.200ms
                                x-cloak
                            >
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 border-b-2 border-primary-100 inline-block pb-2">
                                    Jadwal & Format
                                </h2>

                                @if (count($schedule) > 0)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach ($schedule as $slot)
                                            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 hover:border-primary-200 hover:bg-primary-50/30 transition-colors">
                                                <div class="flex items-start gap-3">
                                                    <div class="w-10 h-10 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center shrink-0">
                                                        <i data-lucide="calendar-clock" class="w-5 h-5"></i>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-slate-900 mb-1">{{ $slot['title'] }}</h3>
                                                        <p class="text-sm text-slate-600 leading-relaxed">{{ $slot['detail'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-6 bg-amber-50 border border-amber-100 rounded-2xl p-4 text-sm text-amber-800 font-medium flex items-start gap-3">
                                        <i data-lucide="info" class="w-5 h-5 shrink-0 mt-0.5"></i>
                                        <span>Konfirmasi jadwal pasti via WhatsApp admin sebelum mendaftar — kuota dan tanggal dapat berubah.</span>
                                    </div>
                                @else
                                    <p class="text-slate-500 italic">Jadwal belum tersedia.</p>
                                @endif
                            </div>

                            {{-- Benefit --}}
                            <div
                                role="tabpanel"
                                id="panel-benefit"
                                aria-labelledby="tab-benefit"
                                x-show="tab === 'benefit'"
                                x-transition.opacity.duration.200ms
                                x-cloak
                            >
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 border-b-2 border-primary-100 inline-block pb-2">
                                    Yang Anda Dapatkan
                                </h2>

                                @if (count($benefits) > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        @foreach ($benefits as $benefit)
                                            <div class="flex items-start gap-4 p-5 bg-slate-50 rounded-2xl border border-slate-100 hover:border-primary-200 transition-colors">
                                                <div class="w-12 h-12 rounded-xl bg-primary-100 text-primary-600 flex items-center justify-center shrink-0">
                                                    <i data-lucide="{{ $benefit['icon'] ?? 'check' }}" class="w-6 h-6"></i>
                                                </div>
                                                <div>
                                                    <h3 class="font-bold text-slate-900 mb-1">{{ $benefit['title'] }}</h3>
                                                    <p class="text-sm text-slate-600 leading-relaxed">{{ $benefit['desc'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-slate-500 italic">Daftar benefit belum tersedia.</p>
                                @endif
                            </div>

                            {{-- Testimoni --}}
                            <div
                                role="tabpanel"
                                id="panel-testimoni"
                                aria-labelledby="tab-testimoni"
                                x-show="tab === 'testimoni'"
                                x-transition.opacity.duration.200ms
                                x-cloak
                            >
                                <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mb-6 border-b-2 border-primary-100 inline-block pb-2">
                                    Apa Kata Alumni
                                </h2>

                                @if (count($testimonials) > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        @foreach ($testimonials as $t)
                                            <figure class="p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:border-primary-200 transition-colors">
                                                <i data-lucide="quote" class="w-8 h-8 text-primary-300 mb-3"></i>
                                                <blockquote class="text-slate-700 leading-relaxed font-medium mb-4">
                                                    "{{ $t['quote'] }}"
                                                </blockquote>
                                                <figcaption class="flex items-center gap-3">
                                                    <div class="w-10 h-10 rounded-full bg-primary-600 text-white flex items-center justify-center font-bold">
                                                        {{ \Illuminate\Support\Str::substr($t['name'], 0, 1) }}
                                                    </div>
                                                    <div>
                                                        <div class="font-bold text-slate-900 text-sm">{{ $t['name'] }}</div>
                                                        <div class="text-xs text-slate-500">{{ $t['role'] }}</div>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-slate-500 italic">Testimoni belum tersedia.</p>
                                @endif
                            </div>

                        </div>
                    </div>

                    {{-- Related products --}}
                    @if (count($related) > 0)
                        <section aria-labelledby="related-heading">
                            <div class="flex items-end justify-between mb-6">
                                <h2 id="related-heading" class="text-2xl md:text-3xl font-bold text-slate-900">
                                    Lengkapi <span class="text-gradient">Pembelajaran Anda</span>
                                </h2>
                                <a href="{{ route('products.index') }}" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-700">
                                    Lihat semua
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </a>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach ($related as $r)
                                    <x-product-card
                                        :title="$r['title']"
                                        :category="$r['category_label'] ?? 'Buku'"
                                        :categoryVariant="($r['type'] ?? 'buku') === 'kelas' ? 'category' : 'info'"
                                        :price="$r['price']"
                                        :originalPrice="$r['original_price'] ?? null"
                                        :image="isset($r['image']) ? asset($r['image']) : null"
                                        :imageAlt="$r['image_alt'] ?? $r['title']"
                                        :href="($r['type'] ?? 'buku') === 'kelas' ? route('courses.show', ['slug' => $r['slug']]) : route('products.show', ['slug' => $r['slug']])"
                                    />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- ─── RIGHT: Sticky checkout panel (desktop) ───────────── --}}
                <aside class="lg:col-span-4 mt-2 lg:mt-0" x-data="{
                    product: {{ \Illuminate\Support\Js::from($alpineProduct) }},
                    addToCartAndCheckout() {
                        const store = this.$store && this.$store.cart;
                        if (store && typeof store.add === 'function') {
                            store.add(this.product, 1);
                        }
                        window.location.href = '{{ route("checkout.index") }}';
                    }
                }">
                    <div class="lg:sticky lg:top-28 bg-white p-7 sm:p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50 hover-lift">

                        <div class="mb-6 flex justify-between items-start gap-3">
                            <div>
                                <p class="text-slate-500 font-medium mb-1 text-sm">Investasi Kelas</p>
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-3xl sm:text-4xl font-extrabold text-slate-900 leading-none">{{ $formattedPrice }}</span>
                                    @if ($hasDiscount)
                                        <span class="text-base text-slate-500 line-through font-medium">{{ $formattedOriginal }}</span>
                                    @endif
                                </div>
                                @if ($hasDiscount)
                                    <span class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2 py-1 rounded-full">
                                        <i data-lucide="tag" class="w-3 h-3"></i>
                                        Hemat {{ $discountPercent }}%
                                    </span>
                                @endif
                            </div>
                            @if ($installmentAvailable)
                                <div class="bg-secondary-50 text-secondary-700 px-3 py-1 rounded-full text-xs font-bold border border-secondary-100 shrink-0">
                                    Cicilan Tersedia
                                </div>
                            @endif
                        </div>

                        <hr class="border-slate-100 mb-6">

                        <h3 class="font-bold text-slate-900 mb-4 text-base">Fasilitas Spesial:</h3>
                        <ul class="space-y-3 mb-8 text-sm text-slate-600">
                            @foreach (array_slice($benefits, 0, 5) as $benefit)
                                <li class="flex items-start gap-3">
                                    <div class="w-6 h-6 rounded-full bg-secondary-50 flex items-center justify-center shrink-0 text-secondary-600 mt-0.5">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    </div>
                                    <span class="font-medium leading-relaxed pt-0.5">{{ $benefit['title'] }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <button
                            @click="addToCartAndCheckout()"
                            type="button"
                            class="ripple block w-full text-center bg-primary-600 hover:bg-primary-700 text-white rounded-2xl py-4 font-extrabold text-lg transition-all shadow-[0_10px_30px_-5px_rgba(79,70,229,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(79,70,229,0.5)] transform hover:-translate-y-1"
                        >
                            {{ $ctaLabel }}
                        </button>

                        <p class="text-xs text-slate-500 mt-4 text-center leading-relaxed font-medium">
                            100% metode logis dan ilmiah. Tidak membawa-bawa hal gaib/mistis.
                        </p>

                        <div class="mt-6 pt-6 border-t border-slate-100 flex items-center justify-center gap-4 text-xs text-slate-500">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-4 h-4 text-secondary-500"></i>
                                Garansi
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="lock" class="w-4 h-4 text-primary-500"></i>
                                Pembayaran Aman
                            </div>
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="message-circle" class="w-4 h-4 text-secondary-500"></i>
                                Support 24/7
                            </div>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </section>

    {{-- ─── Sticky CTA bar (mobile only) ──────────────────────────────── --}}
    <div
        x-data="{
            visible: false,
            product: {{ \Illuminate\Support\Js::from($alpineProduct) }},
            addToCartAndCheckout() {
                const store = this.$store && this.$store.cart;
                if (store && typeof store.add === 'function') {
                    store.add(this.product, 1);
                }
                window.location.href = '{{ route("checkout.index") }}';
            }
        }"
        x-init="window.addEventListener('scroll', () => visible = window.scrollY > 400)"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
        class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-[0_-10px_30px_-15px_rgba(15,23,42,0.2)]"
        role="region"
        aria-label="Daftar kelas"
    >
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[11px] text-slate-500 font-medium leading-none mb-1">Investasi</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-lg font-extrabold text-slate-900 leading-none">{{ $formattedPrice }}</span>
                    @if ($hasDiscount)
                        <span class="text-xs text-slate-500 line-through">{{ $formattedOriginal }}</span>
                    @endif
                </div>
            </div>
            <button
                @click="addToCartAndCheckout()"
                type="button"
                class="ripple shrink-0 inline-flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white rounded-full px-5 py-3 font-bold text-sm shadow-lg shadow-primary-500/30 transition-colors"
            >
                {{ $ctaLabel }}
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</x-layouts.store>
