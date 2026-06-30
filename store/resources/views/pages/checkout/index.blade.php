@php
    /** @var array<int, array{code: string, label: string, price: int}> $shippingMethods */
    $shippingMethods = config('store.shipping_methods', []);
    /** @var array<int, string> $provinces */
    $provinces = config('store.provinces', []);
    /** @var array<int, string> $cities */
    $cities = config('store.cities', []);
@endphp

<x-layouts.store
    title="Checkout — Firman Pratama"
    description="Selesaikan pesanan kamu. Isi data pengiriman, lalu lanjut ke upload bukti bayar."
    bodyClass="relative pb-32 lg:pb-0"
>
    {{-- Decorative blobs (consistent dengan cart + product detail) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 -top-20 h-80 w-80 rounded-full bg-primary-200/70 blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-secondary-200/70 blur-3xl animate-blob"></div>
    </div>

    {{--
        Config sengaja di-emit ke <script type="application/json"> supaya
        JSON tetap clean (tanpa unicode-escape attribute). Alpine component
        membaca lewat window.__checkoutConfig.
    --}}
    <script type="application/json" id="checkout-config">@json([
        'shippingMethods' => $shippingMethods,
    ])</script>

    <section
        id="checkoutPage"
        class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
        x-data="checkoutPage()"
        x-init="
            // Hydrate server-side validation errors into Alpine
            const serverErrors = @json($errors->messages());
            if (Object.keys(serverErrors).length > 0) {
                errors = serverErrors;
                touched = { __all: true };
                // Hydrate form from old input
                form.customer_name = '{{ old('customer_name', '') }}';
                form.customer_email = '{{ old('customer_email', '') }}';
                form.customer_phone = '{{ old('customer_phone', '') }}';
                form.address_line = '{{ old('address_line', '') }}';
                form.address_city = '{{ old('address_city', '') }}';
                form.address_province = '{{ old('address_province', '') }}';
                form.address_postal = '{{ old('address_postal', '') }}';
                form.shipping_method = '{{ old('shipping_method', '') }}';
            }
            $nextTick(() => window.lucide && window.lucide.createIcons());
        "
    >
        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Checkout Flow</p>
        <h1 class="mt-3 text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
            Checkout Pembelian
        </h1>
        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-slate-600">
            Isi data pelanggan dan alamat pengiriman dengan benar, lalu lanjut ke upload bukti bayar.
        </p>

        {{-- ========================================================== --}}
        {{-- Checkout state container — reserve min-height to prevent CLS --}}
        {{-- (empty/form toggle + cart items hydrate post-Alpine boot)    --}}
        {{-- M2-hardening H1-new: parent reservation, mirror cart M4      --}}
        {{-- ========================================================== --}}
        <div class="mt-10 min-h-[1100px] lg:min-h-[760px]">

        {{-- ========================================================== --}}
        {{-- Empty cart guard                                             --}}
        {{-- ========================================================== --}}
        <div
            x-show="$store.cart.isEmpty"
            x-cloak
            class="glass rounded-3xl border border-white/60 p-10 text-center sm:p-16"
        >
            <div class="mx-auto inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                <i data-lucide="shopping-cart" class="h-10 w-10"></i>
            </div>
            <h2 class="mt-6 text-2xl font-bold text-slate-900 sm:text-3xl">Keranjang masih kosong</h2>
            <p class="mt-3 max-w-xl mx-auto text-base text-slate-600">
                Tambahkan minimal satu produk ke keranjang sebelum checkout.
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <x-button :href="route('products.index')" variant="primary" icon="arrow-right">
                    Lihat Produk
                </x-button>
                <a
                    href="{{ route('cart.index') }}"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Kembali ke Keranjang
                </a>
            </div>
        </div>

        {{-- ========================================================== --}}
        {{-- Checkout form                                                --}}
        {{-- ========================================================== --}}
        <form
            id="checkoutForm"
            method="POST"
            action="{{ route('checkout.store') }}"
            x-show="! $store.cart.isEmpty"
            x-cloak
            @submit.prevent="submit"
            novalidate
            class="mt-8 grid gap-8 lg:grid-cols-12"
        >
            @csrf

            @if($errors->any())
            <div class="lg:col-span-12 rounded-2xl border border-rose-200 bg-rose-50 p-5">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-rose-800">Periksa kembali data berikut</h3>
                        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-rose-700">
                            @foreach($errors->all() as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                        @if($errors->has('cart_total') || $errors->has('cart_json'))
                        <p class="mt-3 text-sm font-semibold text-rose-800">Silakan refresh halaman dan coba lagi.</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- ─── LEFT COLUMN: form sections ─────────────────────── --}}
            <div class="space-y-6 lg:col-span-7">

                {{-- ┌── 1. Data pelanggan ──────────────────────────────┐ --}}
                <section
                    id="customerForm"
                    class="panel-card glass hover-lift rounded-3xl border border-white/60 p-6 sm:p-8"
                >
                    <header class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                            <i data-lucide="user" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="text-2xl font-bold leading-tight text-slate-900">Data Pelanggan</h2>
                            <p class="mt-1 text-sm text-slate-500">Dipakai untuk invoice dan komunikasi setelah pembayaran.</p>
                        </div>
                    </header>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Nama Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="customer_name"
                                name="customer_name"
                                type="text"
                                autocomplete="name"
                                x-model="form.customer_name"
                                @blur="touch('customer_name')"
                                :class="errorClasses('customer_name')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                placeholder="Nama sesuai KTP"
                                required
                            >
                            <p x-text="errors.customer_name || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.customer_name ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>

                        <div>
                            <label for="customer_email" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Email <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="customer_email"
                                name="customer_email"
                                type="email"
                                autocomplete="email"
                                inputmode="email"
                                x-model="form.customer_email"
                                @blur="touch('customer_email')"
                                :class="errorClasses('customer_email')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                placeholder="nama@email.com"
                                required
                            >
                            <p x-text="errors.customer_email || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.customer_email ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Nomor WhatsApp <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="customer_phone"
                                name="customer_phone"
                                type="tel"
                                autocomplete="tel"
                                inputmode="tel"
                                x-model="form.customer_phone"
                                @blur="touch('customer_phone')"
                                :class="errorClasses('customer_phone')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                placeholder="08xxxxxxxxxx"
                                required
                            >
                            <p x-text="errors.customer_phone || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.customer_phone ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>
                    </div>
                </section>

                {{-- ┌── 2. Alamat pengiriman ───────────────────────────┐ --}}
                <section
                    id="shippingForm"
                    class="panel-card glass hover-lift rounded-3xl border border-white/60 p-6 sm:p-8"
                >
                    <header class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                            <i data-lucide="map-pin" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="text-2xl font-bold leading-tight text-slate-900">Alamat Pengiriman</h2>
                            <p class="mt-1 text-sm text-slate-500">Untuk pengiriman buku fisik. Produk kelas tetap perlu data ini sebagai backup.</p>
                        </div>
                    </header>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="address_line" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Alamat Lengkap <span class="text-rose-500">*</span>
                            </label>
                            <textarea
                                id="address_line"
                                name="address_line"
                                rows="3"
                                autocomplete="street-address"
                                x-model="form.address_line"
                                @blur="touch('address_line')"
                                :class="errorClasses('address_line')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                placeholder="Jalan, nomor rumah, RT/RW, kelurahan, kecamatan"
                                required
                            ></textarea>
                            <p x-text="errors.address_line || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.address_line ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>

                        {{-- Autocomplete tujuan (Agenwebsite /shipping/data).
                            Mengembalikan canonical {province, city, district} yang
                            dipahami API tarif. Hidden inputs di-bind ke x-model
                            agar form submit native tetap mengirim ketiganya. --}}
                        <div class="sm:col-span-2 relative">
                            <label for="dest_search" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Cari Kota / Kecamatan <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="dest_search"
                                type="text"
                                x-model="destSearch"
                                @input.debounce.300ms="fetchDestinations()"
                                @focus="destOpen = destResults.length > 0"
                                @keydown.escape.prevent="destOpen = false"
                                :class="errorClasses('address_city')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                placeholder="Ketik nama kota atau kecamatan (min. 3 huruf)"
                                autocomplete="off"
                            >
                            <div
                                x-show="destOpen && (destLoading || destResults.length > 0 || destNoResults)"
                                @click.outside="destOpen = false"
                                x-cloak
                                class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                            >
                                <template x-if="destLoading">
                                    <div class="px-4 py-3 text-sm text-slate-500">Mencari...</div>
                                </template>
                                <template x-if="!destLoading && destNoResults">
                                    <div class="px-4 py-3 text-sm text-slate-500">Tidak ada hasil. Coba kata kunci lain.</div>
                                </template>
                                <template x-for="(d, i) in destResults" :key="i">
                                    <button
                                        type="button"
                                        @click="pickDestination(d)"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-primary-50"
                                    >
                                        <p class="font-medium text-slate-900" x-text="d.district + ', ' + d.city"></p>
                                        <p class="text-xs text-slate-500" x-text="d.province"></p>
                                    </button>
                                </template>
                            </div>

                            {{-- Hidden inputs untuk form submit native --}}
                            <input type="hidden" name="address_city" id="address_city" x-model="form.address_city">
                            <input type="hidden" name="address_province" id="address_province" x-model="form.address_province">
                            <input type="hidden" name="address_district" id="address_district" x-model="form.address_district">

                            <template x-if="form.address_city">
                                <p class="mt-2 text-xs text-slate-600">
                                    Terpilih:
                                    <span class="font-medium text-slate-900" x-text="form.address_district + ', ' + form.address_city + ', ' + form.address_province"></span>
                                </p>
                            </template>

                            <p x-text="errors.address_city || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.address_city ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>

                        <div>
                            <label for="address_postal" class="mb-1.5 block text-sm font-semibold text-slate-700">Kode Pos</label>
                            <input
                                id="address_postal"
                                name="address_postal"
                                type="text"
                                autocomplete="postal-code"
                                inputmode="numeric"
                                x-model="form.address_postal"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200"
                                placeholder="60111"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">Opsional \u2014 bantu kurir, tapi tidak wajib.</p>
                        </div>

                        {{-- Ongkir method --}}
                        <div class="sm:col-span-2" x-show="requiresShipping">
                            <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Metode Pengiriman <span class="text-rose-500">*</span>
                            </label>

                            {{-- Loading spinner --}}
                            <template x-if="loadingRates">
                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">
                                    <svg class="h-4 w-4 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <span>Mengecek tarif pengiriman...</span>
                                </div>
                            </template>

                            {{-- No rates available --}}
                            <template x-if="!loadingRates && shippingRates.length === 0 && rateError">
                                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                                    <span x-text="rateError"></span>
                                </div>
                            </template>

                            {{-- Empty — address not filled --}}
                            <template x-if="!loadingRates && shippingRates.length === 0 && !rateError && form.address_city && form.address_province && form.address_postal">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                    Tidak ada tarif tersedia untuk alamat ini.
                                </div>
                            </template>

                            <template x-if="!loadingRates && shippingRates.length === 0 && !rateError && (!form.address_city || !form.address_province || !form.address_postal)">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                                    Masukkan alamat lengkap untuk cek ongkir.
                                </div>
                            </template>

                            {{-- Dynamic radio list --}}
                            <template x-if="!loadingRates && shippingRates.length > 0">
                                <div class="space-y-2">
                                    <template x-for="rate in shippingRates" :key="rate.service">
                                        <label
                                            class="relative flex cursor-pointer items-center gap-3 rounded-xl border-2 bg-white p-4 transition"
                                            :class="form.shipping_method === rate.service ? 'border-primary-500 ring-2 ring-primary-200' : 'border-slate-200 hover:border-primary-300'"
                                        >
                                            <input
                                                type="radio"
                                                name="shipping_method"
                                                :value="rate.service"
                                                x-model="form.shipping_method"
                                                @change="touch('shipping_method')"
                                                class="sr-only"
                                            >
                                            <span
                                                class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                                :class="form.shipping_method === rate.service ? 'border-primary-600 bg-primary-600' : 'border-slate-300 bg-white'"
                                            >
                                                <span x-show="form.shipping_method === rate.service" class="block h-2 w-2 rounded-full bg-white"></span>
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-slate-900 truncate" x-text="rate.label"></p>
                                                <p class="text-xs text-slate-500" x-text="'Estimasi: ' + (rate.etd || 'TBD')"></p>
                                            </div>
                                            <p class="shrink-0 text-sm font-bold text-primary-600" x-text="format(rate.price)"></p>
                                        </label>
                                    </template>
                                </div>
                            </template>

                            {{-- Hidden select for backward compat (keeps id for tests) --}}
                            <select id="shipping_method" name="shipping_method" x-model="form.shipping_method" class="hidden" aria-hidden="true">
                                <option value="">Pilih</option>
                            </select>

                            <p x-text="errors.shipping_method || '\u00A0'" class="mt-1.5 min-h-[1.25rem] text-xs font-medium text-rose-600" :class="errors.shipping_method ? 'opacity-100' : 'opacity-0'" aria-live="polite"></p>
                        </div>
                    </div>
                </section>

                {{-- Pembayaran selalu lunas (hidden) --}}
                <input type="hidden" name="payment_type" value="lunas">
            </div>

            {{-- ─── RIGHT COLUMN: summary ──────────────────────────── --}}
            <aside class="lg:col-span-5">
                {{-- Desktop summary (sidebar sticky) --}}
                <div class="hidden lg:block">
                    <div class="panel-card glass hover-lift sticky top-28 rounded-3xl border border-white/60 p-6 sm:p-8">
                        <h2 class="text-2xl font-bold leading-tight text-slate-900">Ringkasan Pesanan</h2>

                        {{-- Items list — min-h reserved (M2-hardening H1-new): sessionStorage hydration --}}
                        <ul class="mt-5 max-h-72 min-h-[120px] space-y-3 overflow-y-auto pr-1">
                            <template x-for="item in $store.cart.items" :key="item.slug">
                                <li class="flex items-start gap-3 rounded-xl bg-white/60 p-3">
                                    <div class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                        <template x-if="item.image">
                                            <img :src="item.image" :alt="item.name" class="h-full w-full object-cover" loading="lazy">
                                        </template>
                                        <template x-if="! item.image">
                                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                <i data-lucide="image" class="h-5 w-5"></i>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900 line-clamp-2" x-text="item.name"></p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            <span x-text="item.qty"></span> &times; <span x-text="format(item.price)"></span>
                                        </p>
                                    </div>
                                    <p class="shrink-0 text-sm font-bold text-slate-900" x-text="format(item.price * item.qty)"></p>
                                </li>
                            </template>
                        </ul>

                        {{-- Totals --}}
                        <dl class="mt-5 space-y-3 border-t border-slate-100 pt-5 text-base">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Subtotal</dt>
                                <dd class="font-semibold text-slate-900" x-text="format($store.cart.subtotal)"></dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-600">Ongkir</dt>
                                <dd>
                                    <template x-if="shippingPrice > 0">
                                        <span class="font-semibold text-slate-900" x-text="format(shippingPrice)"></span>
                                    </template>
                                    <template x-if="shippingPrice === 0">
                                        <span class="italic text-slate-500">Pilih metode</span>
                                    </template>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-lg">
                                <dt class="font-bold text-slate-900">Total</dt>
                                <dd class="font-extrabold text-primary-600" x-text="format(grandTotal)"></dd>
                            </div>
                        </dl>

                        <button
                            type="submit"
                            id="payNowBtn"
                            class="ripple mt-6 inline-flex w-full items-center justify-center gap-2 rounded-full bg-primary-600 px-6 py-3.5 text-base font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="submitting"
                        >
                            <span x-show="! submitting">Proses Pembayaran</span>
                            <span x-show="submitting" x-cloak>Memproses...</span>
                            <i data-lucide="arrow-right" class="h-5 w-5"></i>
                        </button>

                        <p class="mt-4 flex items-start gap-2 text-xs leading-relaxed text-slate-500">
                            <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-secondary-600"></i>
                            Setelah submit, kamu akan diarahkan ke halaman upload bukti transfer.
                        </p>
                    </div>
                </div>

                {{-- Mobile summary (fixed-bottom) --}}
                <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden">
                    <div
                        x-data="{ expanded: false }"
                        class="mx-auto w-full max-w-7xl px-4 py-3"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <button
                                type="button"
                                @click="expanded = ! expanded"
                                class="flex flex-1 items-center justify-between gap-2 text-left"
                                :aria-expanded="expanded"
                            >
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                                        Total
                                    </p>
                                    <p class="text-xl font-extrabold text-primary-600" x-text="format(grandTotal)"></p>
                                </div>
                                <i data-lucide="chevron-up" class="h-5 w-5 text-slate-500 transition" :class="expanded ? 'rotate-180' : ''"></i>
                            </button>
                            <button
                                type="submit"
                                class="ripple inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-primary-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="submitting"
                            >
                                <span x-show="! submitting">Bayar</span>
                                <span x-show="submitting" x-cloak>...</span>
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </button>
                        </div>

                        <div
                            x-show="expanded"
                            x-cloak
                            x-transition
                            class="mt-3 space-y-2 border-t border-slate-100 pt-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Subtotal</span>
                                <span class="font-semibold text-slate-900" x-text="format($store.cart.subtotal)"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600">Ongkir</span>
                                <span class="font-semibold text-slate-900" x-text="shippingPrice > 0 ? format(shippingPrice) : '—'"></span>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-100 pt-2 font-bold">
                                <span class="text-slate-900">Total Order</span>
                                <span class="text-primary-600" x-text="format(grandTotal)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Hidden cart payload — backend (M2) will read this to persist order_items.
                 M1: stub mendukung audit POST tanpa simpan DB. --}}
            <input type="hidden" name="cart_json" :value="JSON.stringify($store.cart.items)">
            <input type="hidden" name="cart_total" :value="grandTotal">
        </form>

        </div>{{-- /M2-hardening H1-new: checkout state container reservation --}}
    </section>

    {{-- ────────────────────────────────────────────────────────── --}}
    {{-- Alpine page component                                       --}}
    {{-- ────────────────────────────────────────────────────────── --}}
    <x-slot name="scripts">
        <script>
            window.checkoutPage = function () {
                // Read server-injected config from JSON island (set in Blade).
                let cfg = { shippingMethods: [] };
                try {
                    const el = document.getElementById('checkout-config');
                    if (el && el.textContent.trim()) {
                        cfg = JSON.parse(el.textContent);
                    }
                } catch (e) {
                    // eslint-disable-next-line no-console
                    console.warn('[checkout] failed to parse config:', e);
                }

                return {
                    // Static config (server-injected)
                    shippingMethods: Array.isArray(cfg.shippingMethods) ? cfg.shippingMethods : [],

                    // Form state
                    form: {
                        customer_name: '',
                        customer_email: '',
                        customer_phone: '',
                        address_line: '',
                        address_city: '',
                        address_province: '',
                        address_district: '',
                        address_postal: '',
                        shipping_method: '',
                    },
                    errors: {},
                    touched: {},
                    submitting: false,

                    // Shipping rate fetching (dynamic API)
                    shippingRates: [],
                    loadingRates: false,
                    rateError: '',

                    // Destination autocomplete (Agenwebsite /shipping/data)
                    destSearch: '',
                    destResults: [],
                    destLoading: false,
                    destOpen: false,
                    destNoResults: false,

                    // ── Computed ────────────────────────────────────────
                    get requiresShipping() {
                        const items = this.$store.cart && this.$store.cart.items ? this.$store.cart.items : [];
                        // Cart requires shipping if ANY item has is_shippable !== false (default true)
                        return items.some((i) => i.is_shippable !== false);
                    },

                    get cartSubtotal() {
                        return this.$store.cart && this.$store.cart.subtotal
                            ? Number(this.$store.cart.subtotal) || 0
                            : 0;
                    },

                    get shippingPrice() {
                        if (this.shippingRates.length > 0) {
                            const r = this.shippingRates.find((x) => x.service === this.form.shipping_method);
                            if (r) return Number(r.price) || 0;
                        }
                        const m = this.shippingMethods.find((x) => x.code === this.form.shipping_method);
                        return m ? Number(m.price) || 0 : 0;
                    },

                    get grandTotal() {
                        return this.cartSubtotal + this.shippingPrice;
                    },

                    // ── Validation ──────────────────────────────────────
                    touch(field) {
                        this.touched[field] = true;
                        this.validate();
                    },

                    validate() {
                        const e = {};

                        // Always-required fields
                        if (! this.form.customer_name || this.form.customer_name.trim().length < 2) {
                            e.customer_name = 'Nama lengkap wajib diisi (min. 2 karakter).';
                        }
                        const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (! this.form.customer_email || ! emailRe.test(this.form.customer_email)) {
                            e.customer_email = 'Format email tidak valid.';
                        }
                        const phoneRe = /^(\+?62|0)8[1-9][0-9]{6,11}$/;
                        const phoneClean = (this.form.customer_phone || '').replace(/[\s-]/g, '');
                        if (! phoneClean || ! phoneRe.test(phoneClean)) {
                            e.customer_phone = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';
                        }
                        if (! this.form.address_line || this.form.address_line.trim().length < 10) {
                            e.address_line = 'Alamat lengkap wajib diisi (min. 10 karakter).';
                        }
                        if (! this.form.address_city) e.address_city = 'Kota wajib dipilih.';
                        if (! this.form.address_province) e.address_province = 'Provinsi wajib dipilih.';
                        if (this.requiresShipping && ! this.form.shipping_method) e.shipping_method = 'Metode pengiriman wajib dipilih.';

                        // Only surface errors for fields user has touched, OR all on submit.
                        const filtered = {};
                        for (const k of Object.keys(e)) {
                            if (this.touched[k] || this.touched.__all) filtered[k] = e[k];
                        }
                        this.errors = filtered;
                        return Object.keys(e).length === 0;
                    },

                    errorClasses(field) {
                        return this.errors[field]
                            ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'
                            : 'border-slate-200 focus:border-primary-500 focus:ring-primary-200';
                    },

                    // ── Submit ──────────────────────────────────────────
                    submit() {
                        // Force-validate every field on submit.
                        this.touched = {
                            customer_name: true, customer_email: true, customer_phone: true,
                            address_line: true, address_city: true, address_province: true,
                            shipping_method: true,
                            __all: true,
                        };
                        if (! this.validate()) {
                            // Scroll ke field error pertama supaya user langsung lihat masalahnya.
                            this.$nextTick(() => {
                                const firstField = Object.keys(this.errors)[0];
                                if (firstField) {
                                    const el = document.getElementById(firstField);
                                    if (el) {
                                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                        el.focus({ preventScroll: true });
                                    }
                                }
                            });
                            return;
                        }
                        this.submitting = true;
                        // Native submit (controller stub akan redirect ke /checkout/success/{order}).
                        document.getElementById('checkoutForm').submit();
                    },

                    // ── Helpers ─────────────────────────────────────────
                    format(amount) {
                        const n = Number(amount) || 0;
                        return 'Rp ' + n.toLocaleString('id-ID');
                    },

                    _addMonths(date, months) {
                        const d = new Date(date.getTime());
                        const targetMonth = d.getMonth() + months;
                        d.setMonth(targetMonth);
                        // Handle month-overflow (e.g. Jan 31 + 1 month → Mar 3 default; clamp to last day of target month).
                        if (d.getMonth() !== ((targetMonth % 12) + 12) % 12) {
                            d.setDate(0);
                        }
                        return d;
                    },

                    _isoDate(d) {
                        const yyyy = d.getFullYear();
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        const dd = String(d.getDate()).padStart(2, '0');
                        return yyyy + '-' + mm + '-' + dd;
                    },

                    _formatDate(d) {
                        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
                    },

                    // ── Destination autocomplete ────────────────────────
                    fetchDestinations() {
                        const q = (this.destSearch || '').trim();
                        if (q.length < 3) {
                            this.destResults = [];
                            this.destOpen = false;
                            this.destNoResults = false;
                            return;
                        }
                        this.destLoading = true;
                        this.destOpen = true;
                        this.destNoResults = false;
                        fetch('/shipping/destinations?q=' + encodeURIComponent(q), {
                            headers: { 'Accept': 'application/json' },
                        })
                            .then((r) => r.json())
                            .then((data) => {
                                this.destResults = Array.isArray(data.results) ? data.results : [];
                                this.destNoResults = this.destResults.length === 0;
                            })
                            .catch(() => {
                                this.destResults = [];
                                this.destNoResults = true;
                            })
                            .finally(() => { this.destLoading = false; });
                    },

                    pickDestination(d) {
                        this.form.address_province = d.province || '';
                        this.form.address_city = d.city || '';
                        this.form.address_district = d.district || '';
                        this.destSearch = (d.district ? d.district + ', ' : '') + d.city + ', ' + d.province;
                        this.destOpen = false;
                        this.touched.address_city = true;
                        this.touched.address_province = true;
                        this.validate();
                        // Trigger rate refresh immediately on selection.
                        this.fetchRates();
                    },

                    // ── Dynamic shipping rates ──────────────────────────
                    fetchRates() {
                        // Zipcode optional (relaxed) — city+province dari autocomplete
                        // cukup buat API tarif.
                        if (! this.form.address_city || ! this.form.address_province) {
                            this.shippingRates = [];
                            return;
                        }
                        this.loadingRates = true;
                        this.rateError = '';
                        const csrfToken = document.querySelector('input[name=_token]')
                            ? document.querySelector('input[name=_token]').value
                            : '';
                        fetch('/shipping/rates', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({
                                city: this.form.address_city,
                                province: this.form.address_province,
                                district: this.form.address_district,
                                zipcode: this.form.address_postal,
                                cart_json: JSON.stringify(this.$store.cart.items.map((i) => ({ slug: i.slug, qty: i.qty }))),
                            }),
                        })
                            .then((r) => r.json())
                            .then((data) => {
                                if (data && data.error) {
                                    this.shippingRates = [];
                                    this.rateError = data.error;
                                } else {
                                    this.shippingRates = Array.isArray(data.rates) ? data.rates : [];
                                    this.rateError = '';
                                }
                            })
                            .catch(() => {
                                this.shippingRates = [];
                                this.rateError = 'Gagal memuat tarif';
                            })
                            .finally(() => { this.loadingRates = false; });
                    },

                    init() {
                        // Rate refresh hanya saat triple (province+city+district) berubah,
                        // bukan tiap keystroke postal — postal sekarang opsional.
                        this.$watch('form.address_postal', () => {
                            clearTimeout(this._rateTimer);
                            this._rateTimer = setTimeout(() => this.fetchRates(), 500);
                        });
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
