@php
    use App\Models\InstallmentScheme;

    /**
     * Installment schemes (M2 — task t_8446fbd4): DB-backed.
     * Active global schemes only on cart-level checkout (no specific product).
     * Format ke FE: {name, n, dp_pct} — kompat dengan Alpine component existing.
     *
     * Fallback: kalau tabel installment_schemes belum ada (mis. test legacy
     * tanpa RefreshDatabase) atau empty (fresh install tanpa seeder), pakai
     * config sebagai safety net biar checkout tidak blank.
     */
    try {
        $dbSchemes = InstallmentScheme::query()
            ->active()
            ->forProduct(null)
            ->orderBy('n_installments')
            ->get(['name', 'n_installments', 'dp_pct'])
            ->map(fn ($s) => [
                'name' => $s->name,
                'n' => $s->n_installments,
                'dp_pct' => (int) $s->dp_pct,
            ])
            ->all();
    } catch (\Throwable) {
        $dbSchemes = [];
    }

    /** @var array<int, array{name: string, n: int, dp_pct: int}> $installmentSchemes */
    $installmentSchemes = ! empty($dbSchemes)
        ? $dbSchemes
        : config('store.installment_schemes', []);
    /** @var array<int, array{code: string, label: string, price: int}> $shippingMethods */
    $shippingMethods = config('store.shipping_methods', []);
    /** @var array<int, string> $provinces */
    $provinces = config('store.provinces', []);
    /** @var array<int, string> $cities */
    $cities = config('store.cities', []);
@endphp

<x-layouts.store
    title="Checkout — Firman Pratama"
    description="Selesaikan pesanan kamu. Pilih metode pembayaran lunas atau cicilan, isi data kirim, lalu lanjut ke upload bukti bayar."
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
        'schemes' => $installmentSchemes,
        'shippingMethods' => $shippingMethods,
    ])</script>

    <section
        id="checkoutPage"
        class="mx-auto w-full max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
        x-data="checkoutPage()"
        x-init="$nextTick(() => window.lucide && window.lucide.createIcons())"
    >
        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Checkout Flow</p>
        <h1 class="mt-3 text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
            Checkout Pembelian
        </h1>
        <p class="mt-4 max-w-3xl text-lg leading-relaxed text-slate-600">
            Isi data pelanggan dan alamat pengiriman dengan benar, pilih metode pembayaran, lalu lanjut ke upload bukti bayar.
        </p>

        {{-- ========================================================== --}}
        {{-- Empty cart guard                                             --}}
        {{-- ========================================================== --}}
        <div
            x-show="$store.cart.isEmpty"
            x-cloak
            class="mt-10 glass rounded-3xl border border-white/60 p-10 text-center sm:p-16"
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
                            <p x-show="errors.customer_name" x-cloak x-text="errors.customer_name" class="mt-1.5 text-xs font-medium text-rose-600"></p>
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
                            <p x-show="errors.customer_email" x-cloak x-text="errors.customer_email" class="mt-1.5 text-xs font-medium text-rose-600"></p>
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
                            <p x-show="errors.customer_phone" x-cloak x-text="errors.customer_phone" class="mt-1.5 text-xs font-medium text-rose-600"></p>
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
                            <p x-show="errors.address_line" x-cloak x-text="errors.address_line" class="mt-1.5 text-xs font-medium text-rose-600"></p>
                        </div>

                        <div>
                            <label for="address_city" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Kota <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="address_city"
                                name="address_city"
                                x-model="form.address_city"
                                @change="touch('address_city')"
                                :class="errorClasses('address_city')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                required
                            >
                                <option value="">Pilih kota</option>
                                @foreach ($cities as $city)
                                    <option value="{{ $city }}">{{ $city }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.address_city" x-cloak x-text="errors.address_city" class="mt-1.5 text-xs font-medium text-rose-600"></p>
                        </div>

                        <div>
                            <label for="address_province" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Provinsi <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="address_province"
                                name="address_province"
                                x-model="form.address_province"
                                @change="touch('address_province')"
                                :class="errorClasses('address_province')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                required
                            >
                                <option value="">Pilih provinsi</option>
                                @foreach ($provinces as $province)
                                    <option value="{{ $province }}">{{ $province }}</option>
                                @endforeach
                            </select>
                            <p x-show="errors.address_province" x-cloak x-text="errors.address_province" class="mt-1.5 text-xs font-medium text-rose-600"></p>
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
                        </div>

                        {{-- Ongkir method --}}
                        <div class="sm:col-span-2">
                            <label for="shipping_method" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Metode Pengiriman <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="shipping_method"
                                name="shipping_method"
                                x-model="form.shipping_method"
                                @change="touch('shipping_method')"
                                :class="errorClasses('shipping_method')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                                required
                            >
                                <option value="">Pilih metode pengiriman</option>
                                <template x-for="method in shippingMethods" :key="method.code">
                                    <option
                                        :value="method.code"
                                        x-text="method.label + ' — ' + format(method.price)"
                                    ></option>
                                </template>
                            </select>
                            <p x-show="errors.shipping_method" x-cloak x-text="errors.shipping_method" class="mt-1.5 text-xs font-medium text-rose-600"></p>
                            <p class="mt-2 text-xs text-slate-500">
                                <i data-lucide="info" class="mr-1 inline-block h-3.5 w-3.5 align-text-bottom"></i>
                                Tarif final + estimasi sampai akan dihitung otomatis lewat Agenwebsite.com pada milestone berikutnya.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- ┌── 3. Metode pembayaran ───────────────────────────┐ --}}
                <section
                    id="paymentMethod"
                    class="panel-card glass hover-lift rounded-3xl border border-white/60 p-6 sm:p-8"
                >
                    <header class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-accent-50 text-accent-600">
                            <i data-lucide="credit-card" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="text-2xl font-bold leading-tight text-slate-900">Metode Pembayaran</h2>
                            <p class="mt-1 text-sm text-slate-500">Pilih bayar lunas atau cicilan. Pembayaran via transfer manual + upload bukti.</p>
                        </div>
                    </header>

                    {{-- Lunas vs Cicilan radio --}}
                    <div class="mt-6 grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Metode pembayaran">
                        <label
                            class="relative cursor-pointer rounded-2xl border-2 bg-white p-4 transition"
                            :class="form.payment_type === 'lunas' ? 'border-primary-500 ring-2 ring-primary-200' : 'border-slate-200 hover:border-primary-300'"
                        >
                            <input
                                type="radio"
                                name="payment_type"
                                value="lunas"
                                x-model="form.payment_type"
                                class="sr-only"
                            >
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                    :class="form.payment_type === 'lunas' ? 'border-primary-600 bg-primary-600' : 'border-slate-300 bg-white'"
                                >
                                    <span
                                        x-show="form.payment_type === 'lunas'"
                                        x-cloak
                                        class="block h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </span>
                                <div>
                                    <p class="text-base font-bold text-slate-900">Lunas</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Bayar sekali penuh. Pesanan langsung diproses setelah bukti diverifikasi.</p>
                                </div>
                            </div>
                        </label>

                        <label
                            class="relative cursor-pointer rounded-2xl border-2 bg-white p-4 transition"
                            :class="form.payment_type === 'cicilan' ? 'border-primary-500 ring-2 ring-primary-200' : 'border-slate-200 hover:border-primary-300'"
                            x-show="schemes.length > 0"
                        >
                            <input
                                type="radio"
                                name="payment_type"
                                value="cicilan"
                                x-model="form.payment_type"
                                class="sr-only"
                            >
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition"
                                    :class="form.payment_type === 'cicilan' ? 'border-primary-600 bg-primary-600' : 'border-slate-300 bg-white'"
                                >
                                    <span
                                        x-show="form.payment_type === 'cicilan'"
                                        x-cloak
                                        class="block h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </span>
                                <div>
                                    <p class="text-base font-bold text-slate-900">Cicilan</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Bayar bertahap dengan DP. Skema fleksibel sesuai pilihan kamu.</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    {{-- Cicilan scheme picker + jadwal preview --}}
                    <div
                        x-show="form.payment_type === 'cicilan' && schemes.length > 0"
                        x-cloak
                        x-transition.opacity
                        class="mt-6 space-y-5 border-t border-slate-100 pt-6"
                    >
                        <div>
                            <label for="installment_scheme" class="mb-1.5 block text-sm font-semibold text-slate-700">
                                Skema Cicilan <span class="text-rose-500">*</span>
                            </label>
                            <select
                                id="installment_scheme"
                                name="installment_scheme"
                                x-model.number="form.installment_scheme"
                                @change="touch('installment_scheme')"
                                :class="errorClasses('installment_scheme')"
                                class="w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-900 transition focus:outline-none focus:ring-2"
                            >
                                <option :value="null">Pilih skema cicilan</option>
                                <template x-for="(scheme, idx) in schemes" :key="idx">
                                    <option
                                        :value="idx"
                                        x-text="scheme.name + ' — DP ' + scheme.dp_pct + '%'"
                                    ></option>
                                </template>
                            </select>
                            <p x-show="errors.installment_scheme" x-cloak x-text="errors.installment_scheme" class="mt-1.5 text-xs font-medium text-rose-600"></p>
                        </div>

                        {{-- Jadwal preview --}}
                        <div
                            x-show="schedule.length > 0"
                            x-cloak
                            data-testid="installment-schedule"
                            class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50/60"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 bg-white/60 px-4 py-3">
                                <p class="text-sm font-bold text-slate-900">Jadwal Pembayaran</p>
                                <span
                                    class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700"
                                    x-text="schedule.length + 'x pembayaran'"
                                ></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                                            <th class="px-4 py-2.5">Pembayaran</th>
                                            <th class="px-4 py-2.5">Jatuh Tempo</th>
                                            <th class="px-4 py-2.5 text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="(row, i) in schedule" :key="i">
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-slate-900" x-text="row.label"></p>
                                                    <p class="text-xs text-slate-500" x-text="row.note"></p>
                                                </td>
                                                <td class="px-4 py-3 text-slate-600" x-text="row.due_label"></td>
                                                <td class="px-4 py-3 text-right font-bold text-slate-900" x-text="format(row.amount)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                            <p class="border-t border-slate-100 bg-white/60 px-4 py-3 text-xs text-slate-500">
                                <i data-lucide="info" class="mr-1 inline-block h-3.5 w-3.5 align-text-bottom"></i>
                                Reminder otomatis via WhatsApp akan dikirim H-3 setiap jatuh tempo.
                            </p>
                        </div>
                    </div>

                    {{-- Hidden field: serialized schedule for backend (M2 will use this) --}}
                    <input type="hidden" name="schedule_json" :value="JSON.stringify(schedule)">
                </section>
            </div>

            {{-- ─── RIGHT COLUMN: summary ──────────────────────────── --}}
            <aside class="lg:col-span-5">
                {{-- Desktop summary (sidebar sticky) --}}
                <div class="hidden lg:block">
                    <div class="panel-card glass hover-lift sticky top-28 rounded-3xl border border-white/60 p-6 sm:p-8">
                        <h2 class="text-2xl font-bold leading-tight text-slate-900">Ringkasan Pesanan</h2>

                        {{-- Items list --}}
                        <ul class="mt-5 space-y-3 max-h-72 overflow-y-auto pr-1">
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
                            <div
                                x-show="form.payment_type === 'cicilan' && schedule.length > 0"
                                x-cloak
                                class="flex items-center justify-between rounded-xl bg-primary-50 px-3 py-2.5 text-sm"
                            >
                                <dt class="font-bold text-primary-700">Bayar sekarang (DP)</dt>
                                <dd class="font-extrabold text-primary-700" x-text="format(dpAmount)"></dd>
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
                                        Total <span x-text="form.payment_type === 'cicilan' ? '(DP)' : ''"></span>
                                    </p>
                                    <p class="text-xl font-extrabold text-primary-600" x-text="format(form.payment_type === 'cicilan' ? dpAmount : grandTotal)"></p>
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
    </section>

    {{-- ────────────────────────────────────────────────────────── --}}
    {{-- Alpine page component                                       --}}
    {{-- ────────────────────────────────────────────────────────── --}}
    <x-slot name="scripts">
        <script>
            window.checkoutPage = function () {
                // Read server-injected config from JSON island (set in Blade).
                let cfg = { schemes: [], shippingMethods: [] };
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
                    schemes: Array.isArray(cfg.schemes) ? cfg.schemes : [],
                    shippingMethods: Array.isArray(cfg.shippingMethods) ? cfg.shippingMethods : [],

                    // Form state
                    form: {
                        customer_name: '',
                        customer_email: '',
                        customer_phone: '',
                        address_line: '',
                        address_city: '',
                        address_province: '',
                        address_postal: '',
                        shipping_method: '',
                        payment_type: 'lunas',
                        installment_scheme: null,
                    },
                    errors: {},
                    touched: {},
                    submitting: false,

                    // ── Computed ────────────────────────────────────────
                    get cartSubtotal() {
                        return this.$store.cart && this.$store.cart.subtotal
                            ? Number(this.$store.cart.subtotal) || 0
                            : 0;
                    },

                    get shippingPrice() {
                        const m = this.shippingMethods.find((x) => x.code === this.form.shipping_method);
                        return m ? Number(m.price) || 0 : 0;
                    },

                    get grandTotal() {
                        return this.cartSubtotal + this.shippingPrice;
                    },

                    get selectedScheme() {
                        const idx = this.form.installment_scheme;
                        if (idx === null || idx === undefined || idx === '') return null;
                        return this.schemes[Number(idx)] || null;
                    },

                    get dpAmount() {
                        const s = this.selectedScheme;
                        if (!s) return 0;
                        return Math.round((this.grandTotal * Number(s.dp_pct || 0)) / 100);
                    },

                    /**
                     * Auto-generated installment schedule.
                     * Returns array of { label, note, due_label, due_at, amount }.
                     *
                     * - Row 0 = DP (jatuh tempo: hari ini).
                     * - Row 1..n-1 = sisa cicilan, dibagi rata, jatuh tempo +1, +2, ...
                     *   Rounding genap: cicilan terakhir menyerap selisih supaya
                     *   total persis sama dengan grandTotal.
                     */
                    get schedule() {
                        if (this.form.payment_type !== 'cicilan') return [];
                        const s = this.selectedScheme;
                        if (!s) return [];
                        const total = this.grandTotal;
                        if (total <= 0) return [];

                        const n = Math.max(2, Number(s.n) || 2);
                        const dp = this.dpAmount;
                        const remaining = Math.max(0, total - dp);
                        const installmentCount = n - 1;
                        const baseInstallment = Math.floor(remaining / installmentCount);
                        const lastInstallment = remaining - baseInstallment * (installmentCount - 1);

                        const today = new Date();
                        const out = [];

                        // DP
                        out.push({
                            label: 'Down Payment',
                            note: 'Bayar sekarang (' + Number(s.dp_pct) + '% dari total)',
                            due_at: this._isoDate(today),
                            due_label: 'Hari ini',
                            amount: dp,
                        });

                        // Cicilan ke-i
                        for (let i = 1; i <= installmentCount; i++) {
                            const due = this._addMonths(today, i);
                            const isLast = i === installmentCount;
                            out.push({
                                label: 'Cicilan ke-' + i + ' dari ' + installmentCount,
                                note: isLast ? 'Cicilan terakhir' : '',
                                due_at: this._isoDate(due),
                                due_label: this._formatDate(due),
                                amount: isLast ? lastInstallment : baseInstallment,
                            });
                        }

                        return out;
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
                        if (! this.form.shipping_method) e.shipping_method = 'Metode pengiriman wajib dipilih.';

                        if (this.form.payment_type === 'cicilan') {
                            const idx = this.form.installment_scheme;
                            if (idx === null || idx === undefined || idx === '' || ! this.schemes[Number(idx)]) {
                                e.installment_scheme = 'Pilih skema cicilan.';
                            }
                        }

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
                            shipping_method: true, installment_scheme: true,
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
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
