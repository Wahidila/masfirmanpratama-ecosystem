@props([
    'product',
    'related' => [],
])

@php
    /** @var array $product */
    /** @var array $related */
    $title = $product['title'] ?? 'Buku';
    $subtitle = $product['subtitle'] ?? '';
    $tagline = $product['tagline'] ?? null;
    $price = (int) ($product['price'] ?? 0);
    $originalPrice = $product['original_price'] ?? null;
    $hasDiscount = $originalPrice && (int) $originalPrice > $price;
    $discountPercent = $hasDiscount ? (int) round((((int) $originalPrice - $price) / (int) $originalPrice) * 100) : 0;
    $saveAmount = $hasDiscount ? (int) $originalPrice - $price : 0;
    $formattedPrice = 'Rp' . number_format($price, 0, ',', '.');
    $formattedOriginal = $originalPrice ? 'Rp' . number_format((int) $originalPrice, 0, ',', '.') : null;
    $image = $product['image'] ?? null;
    $imageAlt = $product['image_alt'] ?? ('Cover buku ' . $title);
    $badge = $product['badge'] ?? null;
    $badgeIcon = $product['badge_icon'] ?? 'star';
    $categoryLabel = $product['category_label'] ?? 'Buku';

    // Deskripsi → paragraf. String tunggal dipecah per baris kosong supaya tak jadi
    // "tembok teks" (produk dari DB umumnya 1 string panjang).
    $description = $product['description'] ?? [];
    if (is_string($description)) {
        $paras = preg_split('/\R{2,}/', trim($description), -1, PREG_SPLIT_NO_EMPTY);
        $description = $paras !== false && count($paras) > 0 ? $paras : [$description];
    }

    $specs = $product['specs'] ?? [];
    $previewPages = $product['preview_pages'] ?? [];

    // Stok — hanya untuk scarcity JUJUR (tampil saat benar-benar menipis).
    $stock = $product['stock'] ?? null;
    $lowStockThreshold = 5;
    $isLowStock = is_numeric($stock) && (int) $stock > 0 && (int) $stock <= $lowStockThreshold;
    $isOutOfStock = is_numeric($stock) && (int) $stock <= 0;
    $availability = $isOutOfStock ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock';

    // Alpine x-data payload — escape via Js::from supaya aman dari quote/HTML.
    $alpineProduct = [
        'slug' => $product['slug'] ?? '',
        'title' => $title,
        'price' => $price,
        'image' => $image,
        'category_label' => $categoryLabel,
        'is_shippable' => true, // buku fisik — butuh pengiriman
    ];
@endphp

<x-layouts.store
    :title="$title . ' | Buku Firman Pratama'"
    :description="\Illuminate\Support\Str::limit($subtitle, 160)"
    :ogImage="$image"
    ogType="product"
    bodyClass="pb-24 lg:pb-0"
>
    {{-- Structured data: Book schema --}}
    <x-slot name="head">
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Book',
            'name' => $title,
            'author' => [
                '@type' => 'Person',
                'name' => $specs['penulis'] ?? 'Firman Pratama',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $specs['penerbit'] ?? 'Wahana Sejati',
            ],
            'inLanguage' => 'id',
            'description' => $subtitle,
            'image' => $image ? asset($image) : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'IDR',
                'availability' => $availability,
                'url' => url()->current(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    </x-slot>

    <section class="pt-10 pb-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative overflow-hidden">

        {{-- Breadcrumbs --}}
        <nav
            aria-label="Breadcrumb"
            class="flex flex-wrap text-sm text-slate-500 mb-8 items-center gap-2 relative z-10 w-fit bg-white/50 px-4 py-2 rounded-full border border-slate-100 backdrop-blur-sm"
        >
            <a
                href="{{ route('home') }}"
                class="hover:text-primary-600 transition-colors font-medium flex items-center gap-1"
            >
                <i data-lucide="home" class="w-4 h-4"></i>
                Beranda
            </a>
            <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
            <a
                href="{{ route('products.index') }}"
                class="hover:text-primary-600 transition-colors font-medium"
            >
                Karya Buku
            </a>
            <i data-lucide="chevron-right" class="w-4 h-4" aria-hidden="true"></i>
            <span class="text-primary-600 font-bold line-clamp-1">{{ $title }}</span>
        </nav>

        {{-- Decorative gradient blobs --}}
        <div
            class="pointer-events-none absolute -top-24 -right-24 w-96 h-96 bg-gradient-to-tr from-accent-500/20 to-primary-500/20 rounded-full blur-3xl mix-blend-multiply"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute top-1/2 -left-24 w-80 h-80 bg-gradient-to-tr from-primary-500/15 to-secondary-500/15 rounded-full blur-3xl mix-blend-multiply"
            aria-hidden="true"
        ></div>

        {{-- ═══════════ HERO: cover (kiri) + panel beli (kanan) ═══════════ --}}
        <div
            x-data="bookDetailPage({{ \Illuminate\Support\Js::from($alpineProduct) }})"
            class="grid grid-cols-1 lg:grid-cols-12 gap-12 relative z-10"
        >

            {{-- LEFT: Cover + preview gallery --}}
            <div class="lg:col-span-5 relative">
                <div class="lg:sticky lg:top-28 flex flex-col gap-6">

                    {{-- Hero cover --}}
                    <div class="group bg-slate-100/50 p-8 sm:p-12 rounded-[2.5rem] border border-slate-100 flex items-center justify-center shadow-inner overflow-hidden img-zoom-container relative">
                        <div
                            class="absolute inset-0 bg-gradient-to-tr from-accent-500/20 to-primary-500/20 mix-blend-multiply blur-2xl"
                            aria-hidden="true"
                        ></div>

                        @if ($image)
                            <img
                                src="{{ asset($image) }}"
                                alt="{{ $imageAlt }}"
                                width="600"
                                height="800"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                                class="w-full h-auto max-h-[500px] object-contain img-zoom motion-safe:group-hover:scale-105 transition-transform duration-500 drop-shadow-2xl relative z-10"
                            >
                        @else
                            <div class="w-full aspect-[3/4] max-h-[500px] flex flex-col items-center justify-center text-slate-300 relative z-10">
                                <i data-lucide="book" class="w-24 h-24 mb-3"></i>
                                <span class="text-sm font-semibold uppercase tracking-wider">Cover Coming Soon</span>
                            </div>
                        @endif
                    </div>

                    {{-- Preview pages gallery --}}
                    @if (! empty($previewPages))
                        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
                            <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <i data-lucide="images" class="w-4 h-4 text-primary-500"></i>
                                Preview Halaman
                            </h3>

                            <div
                                x-data="{ lightbox: false, current: 0, pages: @js(collect($previewPages)->map(fn($p) => asset($p))->values()->all()) }"
                                @keydown.escape.window="lightbox = false"
                                @keydown.arrow-right.window="if(lightbox) current = (current + 1) % pages.length"
                                @keydown.arrow-left.window="if(lightbox) current = (current - 1 + pages.length) % pages.length"
                            >
                                <div class="grid grid-cols-3 gap-3">
                                    @foreach ($previewPages as $i => $page)
                                        <button
                                            type="button"
                                            @click="current = {{ $i }}; lightbox = true"
                                            class="aspect-[3/4] rounded-xl overflow-hidden border border-slate-100 hover:border-primary-300 transition-colors img-zoom-container group focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                            aria-label="Lihat preview halaman {{ $i + 1 }}"
                                        >
                                            <img
                                                src="{{ asset($page) }}"
                                                alt="Preview halaman {{ $i + 1 }}"
                                                loading="lazy"
                                                onerror="this.closest('button').style.display='none'"
                                                class="w-full h-full object-cover img-zoom bg-slate-50"
                                            >
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Lightbox modal --}}
                                <div
                                    x-show="lightbox"
                                    x-cloak
                                    x-transition.opacity.duration.200ms
                                    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
                                    @click.self="lightbox = false"
                                    role="dialog"
                                    aria-modal="true"
                                    aria-label="Gallery preview halaman buku"
                                >
                                    <button
                                        type="button"
                                        @click="lightbox = false"
                                        class="absolute top-4 right-4 z-10 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 transition"
                                        aria-label="Tutup gallery"
                                    >
                                        <i data-lucide="x" class="w-5 h-5"></i>
                                    </button>

                                    <button
                                        type="button"
                                        @click="current = (current - 1 + pages.length) % pages.length"
                                        class="absolute left-4 z-10 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 transition"
                                        aria-label="Halaman sebelumnya"
                                    >
                                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                                    </button>

                                    <img
                                        :src="pages[current]"
                                        :alt="'Preview halaman ' + (current + 1)"
                                        class="max-h-[85vh] max-w-[90vw] object-contain rounded-xl shadow-2xl"
                                    >

                                    <button
                                        type="button"
                                        @click="current = (current + 1) % pages.length"
                                        class="absolute right-4 z-10 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 text-white hover:bg-white/20 transition"
                                        aria-label="Halaman berikutnya"
                                    >
                                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                    </button>

                                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white/80 text-sm font-semibold bg-black/40 px-3 py-1 rounded-full">
                                        <span x-text="current + 1"></span> / <span x-text="pages.length"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Judul + panel beli (lengket saat scroll) --}}
            <div class="lg:col-span-7">
                <div class="lg:sticky lg:top-28 flex flex-col gap-6">

                    {{-- Title --}}
                    <div>
                        @if ($badge)
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold uppercase tracking-wider mb-4 border border-amber-100 shadow-sm">
                                <i data-lucide="{{ $badgeIcon }}" class="w-4 h-4"></i>
                                {{ $badge }}
                            </div>
                        @endif

                        <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                            {{ $title }}
                        </h1>

                        @if ($tagline)
                            <p class="text-primary-600 font-bold text-lg mb-3">{{ $tagline }}</p>
                        @endif

                        @if ($subtitle)
                            <p class="text-lg text-slate-600 leading-relaxed font-medium">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </div>

                    {{-- Checkout interactive card --}}
                    <div class="bg-white p-6 md:p-8 rounded-[2.5rem] border border-slate-100 shadow-2xl shadow-slate-200/50 relative overflow-hidden">
                        <div
                            class="absolute top-0 right-0 w-32 h-32 bg-primary-50 rounded-bl-full -z-0 mix-blend-multiply opacity-50"
                            aria-hidden="true"
                        ></div>

                        {{-- Price --}}
                        <div class="mb-5 relative z-10">
                            <p class="text-slate-500 font-medium mb-1 flex items-center gap-2">
                                Harga Spesial
                                @if ($hasDiscount)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 text-xs font-extrabold border border-rose-100">
                                        -{{ $discountPercent }}%
                                    </span>
                                @endif
                            </p>
                            <div class="flex items-end gap-3 flex-wrap">
                                <div class="text-4xl lg:text-5xl font-extrabold text-slate-900 leading-none">
                                    {{ $formattedPrice }}
                                </div>
                                @if ($formattedOriginal)
                                    <span class="text-slate-500 line-through font-medium text-lg mb-1">
                                        {{ $formattedOriginal }}
                                    </span>
                                @endif
                            </div>
                            @if ($hasDiscount)
                                <p class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-full px-3 py-1">
                                    <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                                    Hemat Rp{{ number_format($saveAmount, 0, ',', '.') }}
                                </p>
                            @endif
                        </div>

                        {{-- Scarcity (hanya saat stok benar-benar menipis) --}}
                        @if ($isLowStock)
                            <p class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 relative z-10">
                                <i data-lucide="flame" class="w-4 h-4"></i>
                                Stok tinggal {{ (int) $stock }} — segera pesan
                            </p>
                        @elseif ($isOutOfStock)
                            <p class="mb-4 inline-flex items-center gap-1.5 text-sm font-bold text-slate-600 bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 relative z-10">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                Stok habis
                            </p>
                        @endif

                        {{-- Quantity selector --}}
                        <div class="mb-5 flex items-center gap-4 relative z-10">
                            <span class="text-sm font-semibold text-slate-700">Jumlah</span>
                            <div class="inline-flex items-stretch rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                                <button
                                    type="button"
                                    @click="decreaseQty()"
                                    class="inline-flex h-11 w-11 items-center justify-center text-slate-600 hover:bg-slate-50 hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                    aria-label="Kurangi jumlah"
                                >
                                    <i data-lucide="minus" class="w-4 h-4"></i>
                                </button>
                                <input
                                    type="number"
                                    min="1"
                                    inputmode="numeric"
                                    x-model.number="qty"
                                    @input="qty = Math.max(1, parseInt($event.target.value, 10) || 1)"
                                    class="h-11 w-14 text-center border-x border-slate-200 font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-inset bg-white"
                                    aria-label="Jumlah"
                                >
                                <button
                                    type="button"
                                    @click="increaseQty()"
                                    class="inline-flex h-11 w-11 items-center justify-center text-slate-600 hover:bg-slate-50 hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                    aria-label="Tambah jumlah"
                                >
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        {{-- CTA: Beli Sekarang (primary) + Tambah ke Keranjang (secondary) --}}
                        @unless ($isOutOfStock)
                            <a
                                href="{{ route('checkout.index') }}"
                                @click="addToCart({ silent: true })"
                                class="ripple block w-full text-center bg-primary-600 hover:bg-primary-700 text-white rounded-2xl py-4 font-extrabold text-lg transition-all shadow-[0_10px_30px_-5px_rgba(79,70,229,0.4)] hover:shadow-[0_15px_30px_-5px_rgba(79,70,229,0.5)] transform hover:-translate-y-1 mb-3 relative z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <i data-lucide="zap" class="w-5 h-5"></i>
                                    Beli Sekarang
                                </span>
                            </a>

                            <button
                                type="button"
                                @click="addToCart()"
                                class="block w-full text-center bg-white border border-slate-200 hover:border-primary-300 hover:text-primary-600 text-slate-700 rounded-2xl py-3 font-semibold transition-all shadow-sm mb-4 relative z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                data-testid="add-to-cart"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <i data-lucide="shopping-cart" class="w-4 h-4"></i>
                                    <span x-text="justAdded ? 'Ditambahkan ke keranjang ✓' : 'Tambah ke Keranjang'"></span>
                                </span>
                            </button>
                        @else
                            <div class="block w-full text-center bg-slate-100 text-slate-400 rounded-2xl py-4 font-extrabold text-lg mb-4 relative z-10 cursor-not-allowed">
                                Stok Habis
                            </div>
                        @endunless

                        <div class="flex gap-4 relative z-10 mb-5">
                            <button
                                type="button"
                                @click="toggleFavorite()"
                                :class="isFavorite ? 'text-rose-600 border-rose-200 bg-rose-50' : 'text-slate-700 border-slate-200 bg-white hover:bg-slate-50'"
                                class="w-full rounded-xl py-3 font-semibold transition-all shadow-sm flex items-center justify-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                :aria-pressed="isFavorite ? 'true' : 'false'"
                                aria-label="Tambahkan ke favorit"
                            >
                                <i data-lucide="heart" class="w-4 h-4"></i>
                                <span x-text="isFavorite ? 'Favorit ✓' : 'Favorit'"></span>
                            </button>
                            <button
                                type="button"
                                @click="shareProduct()"
                                class="w-full bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl py-3 font-semibold transition-all shadow-sm flex items-center justify-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                aria-label="Bagikan produk"
                            >
                                <i data-lucide="share-2" class="w-4 h-4"></i> Bagikan
                            </button>
                        </div>

                        {{-- Trust strip (hanya klaim yang benar) --}}
                        <ul class="space-y-2 border-t border-slate-100 pt-4 relative z-10">
                            <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                <i data-lucide="truck" class="w-4 h-4 text-primary-500 shrink-0"></i>
                                Dikirim via kurir (JNE/J&amp;T) — ongkir dihitung saat checkout
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                <i data-lucide="shield-check" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                                Transfer aman — tinggal upload bukti bayar
                            </li>
                            <li class="flex items-center gap-2.5 text-sm text-slate-600">
                                <i data-lucide="badge-check" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                                Dijamin buku asli &amp; dikemas rapi
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════ DETAIL: deskripsi + spesifikasi (lebar penuh) ═══════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-12 relative z-10">
            {{-- Description (multi-paragraph) --}}
            @if (! empty($description))
                <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4 flex items-center gap-3">
                        <i data-lucide="book-open" class="w-6 h-6 text-primary-500"></i>
                        Tentang Buku Ini
                    </h2>
                    <div class="space-y-4 text-slate-600 leading-relaxed">
                        @foreach ($description as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Specifications --}}
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
                <h2 class="text-2xl font-bold text-slate-900 mb-6 border-b border-slate-100 pb-4 flex items-center gap-3">
                    <i data-lucide="info" class="w-6 h-6 text-primary-500"></i>
                    Spesifikasi Buku
                </h2>
                <dl class="divide-y divide-slate-50 text-sm">
                    @forelse ($specs as $label => $value)
                        <div class="flex gap-4 py-2.5">
                            <dt class="w-32 shrink-0 text-slate-500 capitalize">{{ str_replace('_', ' ', $label) }}</dt>
                            <dd class="flex-1 font-bold text-slate-800">{{ $value }}</dd>
                        </div>
                    @empty
                        <div class="text-slate-500 italic py-2">Spesifikasi belum tersedia.</div>
                    @endforelse
                </dl>
            </div>
        </div>

        {{-- Related books --}}
        @if (! empty($related))
            <div class="mt-24 relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
                    <div>
                        <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-2">Buku & Karya Terkait</p>
                        <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 leading-tight">
                            Karya Lain dari <span class="text-gradient">Firman Pratama</span>
                        </h2>
                    </div>
                    <a
                        href="{{ route('products.index') }}"
                        class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 font-semibold"
                    >
                        Lihat semua karya
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($related as $rel)
                        <x-product-card
                            :title="$rel['title']"
                            :price="$rel['price']"
                            :originalPrice="$rel['original_price'] ?? null"
                            :image="! empty($rel['image']) ? asset($rel['image']) : null"
                            :imageAlt="$rel['image_alt'] ?? ('Cover ' . $rel['title'])"
                            :category="$rel['category_label'] ?? null"
                            categoryVariant="category"
                            :badge="$rel['badge'] ?? null"
                            :href="route('products.show', $rel['slug'])"
                        />
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    {{-- ─── Sticky CTA bar (mobile only) ──────────────────────────────── --}}
    <div
        x-data="{
            visible: false,
            justAdded: false,
            _t: null,
            product: {{ \Illuminate\Support\Js::from($alpineProduct) }},
            addToCartQuick(redirect = false) {
                const store = this.$store && this.$store.cart;
                if (store && typeof store.add === 'function') {
                    store.add(this.product, 1);
                }
                window.dispatchEvent(new CustomEvent('cart:add', {
                    detail: { product: this.product, qty: 1 },
                }));
                if (redirect) {
                    window.location.href = '{{ route('checkout.index') }}';
                    return;
                }
                this.justAdded = true;
                clearTimeout(this._t);
                this._t = setTimeout(() => { this.justAdded = false; }, 1800);
            },
        }"
        x-init="window.addEventListener('scroll', () => { const n = window.scrollY > 400; if (n !== visible) visible = n; }, { passive: true })"
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-cloak
        class="lg:hidden fixed inset-x-0 bottom-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 shadow-[0_-10px_30px_-15px_rgba(15,23,42,0.2)]"
        role="region"
        aria-label="Pesan buku"
    >
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <p class="text-[11px] text-slate-500 font-medium leading-none mb-1">Harga</p>
                <div class="flex items-baseline gap-2 flex-wrap">
                    <span class="text-lg font-extrabold text-slate-900 leading-none">{{ $formattedPrice }}</span>
                    @if ($hasDiscount)
                        <span class="text-xs text-slate-500 line-through">{{ $formattedOriginal }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full bg-rose-50 text-rose-600 text-[10px] font-extrabold border border-rose-100">-{{ $discountPercent }}%</span>
                    @endif
                </div>
            </div>
            <button
                type="button"
                @click="addToCartQuick(false)"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-primary-300 hover:text-primary-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                :aria-label="justAdded ? 'Ditambahkan ke keranjang' : 'Tambah ke keranjang'"
            >
                <i :data-lucide="justAdded ? 'check' : 'shopping-cart'" class="w-5 h-5"></i>
            </button>
            <button
                type="button"
                @click="addToCartQuick(true)"
                class="ripple inline-flex min-h-[44px] shrink-0 items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white rounded-full px-5 py-3 font-bold text-sm shadow-lg shadow-primary-500/30 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
            >
                Beli Sekarang
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </button>
        </div>
    </div>

    {{-- Page-scoped Alpine component --}}
    <x-slot name="scripts">
        <script>
            window.bookDetailPage = function (product) {
                return {
                    qty: 1,
                    justAdded: false,
                    isFavorite: false,
                    _resetTimer: null,

                    init() {
                        try {
                            const favs = JSON.parse(localStorage.getItem('mfp:favorites') || '[]');
                            this.isFavorite = Array.isArray(favs) && favs.includes(product.slug);
                        } catch (e) { /* ignore */ }
                    },

                    increaseQty() {
                        this.qty = (parseInt(this.qty, 10) || 1) + 1;
                    },

                    decreaseQty() {
                        this.qty = Math.max(1, (parseInt(this.qty, 10) || 1) - 1);
                    },

                    toggleFavorite() {
                        let favs = [];
                        try { favs = JSON.parse(localStorage.getItem('mfp:favorites') || '[]'); } catch (e) { favs = []; }
                        if (!Array.isArray(favs)) favs = [];
                        if (favs.includes(product.slug)) {
                            favs = favs.filter(s => s !== product.slug);
                            this.isFavorite = false;
                        } else {
                            favs.push(product.slug);
                            this.isFavorite = true;
                        }
                        try { localStorage.setItem('mfp:favorites', JSON.stringify(favs)); } catch (e) { /* ignore */ }
                    },

                    addToCart({ silent = false } = {}) {
                        const store = this.$store && this.$store.cart;
                        if (store && typeof store.add === 'function') {
                            store.add(product, this.qty);
                        }
                        window.dispatchEvent(new CustomEvent('cart:add', {
                            detail: { product: product, qty: this.qty },
                        }));

                        if (silent) return;

                        this.justAdded = true;
                        clearTimeout(this._resetTimer);
                        this._resetTimer = setTimeout(() => {
                            this.justAdded = false;
                        }, 1800);
                    },

                    async shareProduct() {
                        const url = window.location.href;
                        const title = product.title;
                        if (navigator.share) {
                            try {
                                await navigator.share({ title, url });
                                return;
                            } catch (e) {
                                // user cancel — fall through ke clipboard
                            }
                        }
                        try {
                            await navigator.clipboard.writeText(url);
                            alert('Link produk disalin ke clipboard.');
                        } catch (e) {
                            window.prompt('Salin link produk:', url);
                        }
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
