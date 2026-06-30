@php
    /** @var string $order */
    /** @var string $paymentType */ // 'lunas' | 'cicilan'
    /** @var int $cartTotal */
    /** @var int $totalTransfer */
    /** @var array<int, array{label?: string, note?: string, due_label?: string, amount?: int}> $schedule */
    /** @var string $uploadUrl — signed URL ke /upload/{order} */
    /** @var string $trackUrl — signed URL ke /track/{order} */
    /** @var array<int, array{bank: string, number: string, holder: string, logo_color?: string}> $bankAccounts */
    /** @var array{number: string, label: string} $waAdmin */

    // Fallback resolve bila view dipakai tanpa controller (defensif).
    $bankAccounts = $bankAccounts ?? \App\Services\Settings::getBankAccounts();
    $waAdmin = $waAdmin ?? \App\Services\Settings::getWaAdmin();

    $isInstallment = $paymentType === 'cicilan';
    $waText = rawurlencode("Halo Admin, saya baru saja checkout order {$order}. Mau konfirmasi pembayaran.");
    $waLink = "https://wa.me/{$waAdmin['number']}?text={$waText}";

    // Color palette per bank logo (Tailwind class names dipakai sebagai
    // string statis supaya purge tetap bisa pickup).
    $logoPalette = [
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'emerald' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    ];
@endphp

<x-layouts.store
    title="Order Berhasil — Firman Pratama"
    description="Terima kasih, pesananmu sudah masuk. Lanjut transfer ke rekening tujuan, lalu upload bukti bayar untuk diverifikasi tim kami."
    bodyClass="relative"
>
    {{-- Decorative blobs (consistent dengan halaman checkout flow) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 -top-20 h-80 w-80 rounded-full bg-primary-200/70 blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-secondary-200/70 blur-3xl animate-blob"></div>
    </div>

    <section
        id="checkoutSuccessPage"
        class="mx-auto w-full max-w-4xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
        x-data="checkoutSuccessPage({
            orderNumber: @js($order),
            totalTransfer: @js($totalTransfer),
            paymentType: @js($paymentType),
            clearCart: @js($clearCart ?? false),
        })"
    >
        {{-- ====================================================== --}}
        {{-- Hero / confirmation                                     --}}
        {{-- ====================================================== --}}
        <header class="text-center">
            <span
                class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-secondary-50 text-secondary-600 ring-1 ring-secondary-200"
                aria-hidden="true"
            >
                <i data-lucide="badge-check" class="h-8 w-8"></i>
            </span>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Checkout Flow</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
                Order berhasil dibuat
            </h1>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                Terima kasih sudah memesan. Selesaikan pembayaran via transfer manual ke salah satu rekening di bawah, lalu upload bukti bayar untuk diverifikasi tim kami.
            </p>
        </header>

        {{-- ====================================================== --}}
        {{-- Order number card + copy-to-clipboard                   --}}
        {{-- ====================================================== --}}
        <section
            id="orderNumberCard"
            class="panel-card glass hover-lift mt-10 rounded-3xl border border-white/60 p-6 text-center sm:p-8"
            aria-labelledby="orderNumberLabel"
        >
            <p id="orderNumberLabel" class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
                Nomor Pesanan
            </p>
            <p
                class="mt-3 break-all font-mono text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl"
                data-testid="order-number"
            >
                {{ $order }}
            </p>
            <p class="mt-3 text-sm text-slate-500">
                Simpan nomor ini. Dipakai untuk upload bukti bayar dan tracking pesanan.
            </p>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                <button
                    type="button"
                    @click="copyOrderNumber()"
                    :disabled="copied"
                    class="ripple inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-90"
                    :aria-label="copied ? 'Nomor pesanan tersalin' : 'Salin nomor pesanan'"
                >
                    <i :data-lucide="copied ? 'check' : 'copy'" class="h-4 w-4"></i>
                    <span x-text="copied ? 'Tersalin!' : 'Salin nomor pesanan'"></span>
                </button>
                <a
                    href="{{ $trackUrl }}"
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                >
                    <i data-lucide="package-search" class="h-4 w-4"></i>
                    Track order
                </a>
            </div>
        </section>

        {{-- ====================================================== --}}
        {{-- Total to transfer                                       --}}
        {{-- ====================================================== --}}
        <section
            id="totalTransferCard"
            class="panel-card glass hover-lift mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
            aria-labelledby="totalTransferLabel"
        >
            <header class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <i data-lucide="wallet" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <p id="totalTransferLabel" class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                        @if ($isInstallment)
                            Total Transfer (Down Payment)
                        @else
                            Total Transfer (Lunas)
                        @endif
                    </p>
                    <p
                        class="mt-1 text-3xl font-extrabold leading-tight text-primary-600 sm:text-4xl"
                        data-testid="total-transfer"
                    >
                        Rp {{ number_format($totalTransfer, 0, ',', '.') }}
                    </p>

                    @if ($isInstallment)
                        <p class="mt-2 text-sm text-slate-600">
                            Pembayaran cicilan: total order
                            <span class="font-semibold text-slate-900">Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>.
                            Sisa pembayaran sesuai jadwal di bawah, reminder otomatis via WhatsApp H-3.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-slate-600">
                            Bayar sekali penuh sesuai nominal di atas. Order langsung diproses setelah bukti diverifikasi tim.
                        </p>
                    @endif
                </div>
            </header>

            @if ($isInstallment && count($schedule) > 0)
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50/60">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-white/60 px-4 py-3">
                        <p class="text-sm font-bold text-slate-900">Jadwal Pembayaran</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700">
                            {{ count($schedule) }}x pembayaran
                        </span>
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
                                @foreach ($schedule as $i => $row)
                                    <tr @class(['bg-primary-50/40' => $i === 0])>
                                        <td class="px-4 py-3">
                                            <p class="font-semibold text-slate-900">{{ $row['label'] ?? 'Pembayaran' }}</p>
                                            @if (! empty($row['note']))
                                                <p class="text-xs text-slate-500">{{ $row['note'] }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">{{ $row['due_label'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">
                                            Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        {{-- ====================================================== --}}
        {{-- Bank accounts                                           --}}
        {{-- ====================================================== --}}
        <section
            id="bankAccountsCard"
            class="panel-card glass hover-lift mt-6 rounded-3xl border border-white/60 p-6 sm:p-8"
            aria-labelledby="bankAccountsLabel"
        >
            <header class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                    <i data-lucide="landmark" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 id="bankAccountsLabel" class="text-2xl font-bold leading-tight text-slate-900">Transfer ke salah satu rekening</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Pilih bank yang paling nyaman, transfer sesuai nominal di atas, lalu upload bukti bayar.
                    </p>
                </div>
            </header>

            <ul class="mt-6 grid gap-4 sm:grid-cols-2" role="list">
                @foreach ($bankAccounts as $idx => $bank)
                    @php
                        $colorKey = $bank['logo_color'] ?? 'indigo';
                        $logoClass = $logoPalette[$colorKey] ?? $logoPalette['indigo'];
                    @endphp
                    <li
                        class="rounded-2xl border border-slate-100 bg-white/90 p-5 transition hover:border-primary-200 hover:shadow-md"
                        data-testid="bank-account"
                    >
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 w-16 items-center justify-center rounded-xl text-xs font-extrabold uppercase tracking-wider ring-1 {{ $logoClass }}">
                                {{ $bank['bank'] }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Bank {{ $bank['bank'] }}</p>
                                <p class="text-sm font-semibold text-slate-900">a.n. {{ $bank['holder'] }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-3">
                            <p
                                class="font-mono text-base font-bold tracking-wider text-slate-900 sm:text-lg"
                                :data-bank-number="@js($bank['number'])"
                            >
                                {{ $bank['number'] }}
                            </p>
                            <button
                                type="button"
                                @click="copyBank({{ $idx }}, @js(preg_replace('/[^0-9]/', '', $bank['number'])))"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                                :aria-label="bankCopied === {{ $idx }} ? 'Nomor rekening tersalin' : 'Salin nomor rekening {{ $bank['bank'] }}'"
                            >
                                <i :data-lucide="bankCopied === {{ $idx }} ? 'check' : 'copy'" class="h-3.5 w-3.5"></i>
                                <span x-text="bankCopied === {{ $idx }} ? 'Tersalin' : 'Salin'"></span>
                            </button>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-3.5 text-sm text-amber-800">
                <p class="flex items-start gap-2 leading-relaxed">
                    <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <span>
                        Transfer sesuai nominal <strong>persis</strong> sampai 3 digit terakhir supaya verifikasi otomatis lebih cepat.
                        Order kedaluwarsa dalam 24 jam jika belum ada bukti bayar.
                    </span>
                </p>
            </div>
        </section>

        {{-- ====================================================== --}}
        {{-- Primary CTAs                                            --}}
        {{-- ====================================================== --}}
        <section class="mt-8 grid gap-4 sm:grid-cols-2">
            <a
                href="{{ $uploadUrl }}"
                class="ripple inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300"
                data-testid="cta-upload"
            >
                <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                Upload bukti bayar sekarang
            </a>
            <a
                href="{{ $trackUrl }}"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-6 py-4 text-base font-bold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                data-testid="cta-track"
            >
                <i data-lucide="package-search" class="h-5 w-5"></i>
                Track order
            </a>
        </section>

        {{-- ====================================================== --}}
        {{-- WA admin note                                           --}}
        {{-- ====================================================== --}}
        <aside
            id="waAdminNote"
            class="mt-8 flex flex-col items-start gap-4 rounded-3xl border border-secondary-200 bg-secondary-50/70 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6"
        >
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-100 text-secondary-700">
                    <i data-lucide="message-circle-more" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-bold text-secondary-900">Butuh konfirmasi via WhatsApp?</p>
                    <p class="mt-0.5 text-sm text-secondary-800">
                        Hubungi {{ $waAdmin['label'] }} jika kamu butuh bantuan atau ingin konfirmasi setelah transfer.
                    </p>
                </div>
            </div>
            <a
                href="{{ $waLink }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex shrink-0 items-center gap-2 rounded-full bg-secondary-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-secondary-500/30 transition hover:-translate-y-0.5 hover:bg-secondary-700"
            >
                <i data-lucide="message-circle" class="h-4 w-4"></i>
                Chat admin di WhatsApp
            </a>
        </aside>

        <p class="mt-10 text-center text-sm text-slate-500">
            Mau lihat-lihat dulu?
            <a href="{{ route('products.index') }}" class="font-semibold text-primary-600 hover:underline">Kembali ke katalog produk</a>.
        </p>
    </section>

    {{-- ────────────────────────────────────────────────────────── --}}
    {{-- Alpine page component                                       --}}
    {{-- ────────────────────────────────────────────────────────── --}}
    <x-slot name="scripts">
        <script>
            window.checkoutSuccessPage = function (cfg) {
                return {
                    // Static config
                    orderNumber: cfg.orderNumber || '',
                    totalTransfer: Number(cfg.totalTransfer) || 0,
                    paymentType: cfg.paymentType || 'lunas',
                    clearCart: cfg.clearCart === true,

                    // UI state
                    copied: false,
                    bankCopied: null, // index of copied bank, null if none
                    _copyTimer: null,
                    _bankTimer: null,

                    init() {
                        // Reset cart tepat setelah checkout (sinyal one-time dari server).
                        // Order sudah masuk DB → item cart "dikonsumsi" jadi order. Tidak
                        // jalan saat refresh/share-link (clearCart=false), jadi cart yang
                        // baru diisi user tidak ikut terhapus.
                        if (this.clearCart && this.$store && this.$store.cart) {
                            this.$store.cart.clear();
                        }

                        // Re-run lucide setelah icon-nama berubah dari `copy` → `check`.
                        this.$watch('copied', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                        this.$watch('bankCopied', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                    },

                    async copyOrderNumber() {
                        await this._copyToClipboard(this.orderNumber);
                        this.copied = true;
                        clearTimeout(this._copyTimer);
                        this._copyTimer = setTimeout(() => { this.copied = false; }, 2000);
                    },

                    async copyBank(idx, digits) {
                        await this._copyToClipboard(String(digits || ''));
                        this.bankCopied = idx;
                        clearTimeout(this._bankTimer);
                        this._bankTimer = setTimeout(() => { this.bankCopied = null; }, 2000);
                    },

                    async _copyToClipboard(value) {
                        const text = String(value || '');
                        if (!text) return;
                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(text);
                                return;
                            }
                        } catch (e) {
                            // fall through ke fallback execCommand
                        }
                        // Fallback: textarea + execCommand (browser lama / non-HTTPS preview)
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.setAttribute('readonly', '');
                        ta.style.position = 'absolute';
                        ta.style.left = '-9999px';
                        document.body.appendChild(ta);
                        ta.select();
                        try {
                            document.execCommand('copy');
                        } catch (e) {
                            // last resort: prompt
                            window.prompt('Salin manual:', text);
                        } finally {
                            document.body.removeChild(ta);
                        }
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
