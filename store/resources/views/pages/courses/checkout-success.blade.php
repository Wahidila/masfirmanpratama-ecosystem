@php
    /** @var \App\Models\Course $course */
    /** @var \App\Models\Order $order */
    /** @var string $paymentType */ // 'lunas' | 'cicilan'
    /** @var int $totalTransfer */
    /** @var array<int, array{label?: string, due_label?: string, amount?: int}> $schedule */
    /** @var string $uploadUrl */
    /** @var string $trackUrl */
    /** @var array<int, array{bank: string, number: string, holder: string, logo_color?: string}> $bankAccounts */
    /** @var array{number: string, label: string} $waAdmin */

    $bankAccounts = $bankAccounts ?? \App\Services\Settings::getBankAccounts();
    $waAdmin = $waAdmin ?? \App\Services\Settings::getWaAdmin();

    $isInstallment = $paymentType === 'cicilan';
    $waText = rawurlencode("Halo Admin, saya baru daftar kelas {$course->title} (order {$order->order_number}). Mau konfirmasi pembayaran.");
    $waLink = "https://wa.me/{$waAdmin['number']}?text={$waText}";
    $imageUrl = $course->image_path ? asset($course->image_path) : null;
@endphp

<x-layouts.store
    title="Pendaftaran Berhasil — {{ $course->title }}"
    description="Pendaftaran kelas berhasil. Selesaikan pembayaran lalu upload bukti untuk diverifikasi tim kami."
    bodyClass="relative"
>
    {{-- Decorative blobs --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 -top-20 h-80 w-80 rounded-full bg-primary-200/70 blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-secondary-200/70 blur-3xl animate-blob"></div>
    </div>

    <section
        class="mx-auto w-full max-w-3xl px-4 py-12 sm:px-6 lg:py-16"
        x-data="checkoutSuccessPage({ orderNumber: @js($order->order_number) })"
    >
        {{-- Hero --}}
        <header class="text-center">
            <span class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-secondary-50 text-secondary-600 ring-1 ring-secondary-200" aria-hidden="true">
                <i data-lucide="badge-check" class="h-8 w-8"></i>
            </span>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Pendaftaran Kelas</p>
            <h1 class="mt-3 text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl">Pendaftaran berhasil! 🎉</h1>
            <p class="mt-4 text-base leading-relaxed text-slate-600 sm:text-lg">
                Terima kasih sudah mendaftar. Selesaikan pembayaran ke salah satu rekening di bawah, lalu upload bukti bayar untuk diverifikasi tim kami. Detail juga dikirim ke WhatsApp kamu.
            </p>
        </header>

        {{-- Course chip --}}
        <div class="mt-8 flex items-center gap-4 rounded-2xl border border-slate-100 bg-white/90 p-4 shadow-sm">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $course->title }}"
                     class="h-14 w-14 rounded-xl object-cover shrink-0 ring-1 ring-slate-100"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="h-14 w-14 rounded-xl bg-gradient-to-br from-primary-500 to-blue-600 shrink-0 items-center justify-center text-white" style="display:none">
                    <i data-lucide="{{ $course->card_icon ?: 'graduation-cap' }}" class="h-6 w-6"></i>
                </div>
            @else
                <div class="h-14 w-14 rounded-xl bg-gradient-to-br from-primary-500 to-blue-600 shrink-0 flex items-center justify-center text-white">
                    <i data-lucide="{{ $course->card_icon ?: 'graduation-cap' }}" class="h-6 w-6"></i>
                </div>
            @endif
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kelas terdaftar</p>
                <h2 class="font-bold text-slate-900 text-sm leading-snug">{{ $course->title }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">a.n {{ $order->customer_name }} · {{ $order->phone }}</p>
            </div>
        </div>

        {{-- Order number + copy --}}
        <section class="mt-6 rounded-3xl border border-slate-100 bg-white p-6 text-center shadow-sm sm:p-7">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Nomor Pendaftaran</p>
            <p class="mt-3 break-all font-mono text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl" data-testid="order-number">
                {{ $order->order_number }}
            </p>
            <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                <button type="button" @click="copyOrderNumber()" :disabled="copied"
                        class="ripple inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 disabled:opacity-90">
                    <i :data-lucide="copied ? 'check' : 'copy'" class="h-4 w-4"></i>
                    <span x-text="copied ? 'Tersalin!' : 'Salin nomor'"></span>
                </button>
                <a href="{{ $trackUrl }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600">
                    <i data-lucide="package-search" class="h-4 w-4"></i>
                    Lacak status
                </a>
            </div>
        </section>

        {{-- Total transfer / DP --}}
        <section class="mt-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                    <i data-lucide="wallet" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-500">
                        {{ $isInstallment ? 'Transfer sekarang (DP)' : 'Transfer sekarang (Lunas)' }}
                    </p>
                    <p class="mt-1 text-3xl font-extrabold leading-tight text-primary-600 sm:text-4xl" data-testid="total-transfer">
                        Rp {{ number_format($totalTransfer, 0, ',', '.') }}
                    </p>
                    @if ($isInstallment)
                        <p class="mt-2 text-sm text-slate-600">
                            Total investasi <span class="font-semibold text-slate-900">Rp {{ number_format((int) $order->total, 0, ',', '.') }}</span> —
                            cukup bayar DP dulu sekarang, sisanya dicicil sesuai jadwal di bawah.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-slate-600">Bayar sekali penuh. Kelas langsung diproses setelah bukti diverifikasi.</p>
                    @endif
                </div>
            </div>

            @if ($isInstallment && count($schedule) > 0)
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/70 px-4 py-3">
                        <p class="text-sm font-bold text-slate-900">Jadwal pembayaran</p>
                        <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700">
                            {{ count($schedule) }}× transfer
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($schedule as $i => $row)
                                    <tr @class(['bg-primary-50/40' => $i === 0])>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center gap-2">
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold {{ $i === 0 ? 'bg-primary-100 text-primary-700' : 'bg-slate-200 text-slate-600' }}">{{ $i === 0 ? 'DP' : $i }}</span>
                                                <span class="font-semibold text-slate-900">{{ $row['label'] ?? 'Pembayaran' }}</span>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">{{ $row['due_label'] ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>

        {{-- Bank accounts --}}
        @if (count($bankAccounts) > 0)
            <section class="mt-6 rounded-3xl border border-slate-100 bg-white p-6 shadow-sm sm:p-7">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-50 text-secondary-600">
                        <i data-lucide="landmark" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold leading-tight text-slate-900">Transfer ke salah satu rekening</h2>
                        <p class="mt-1 text-sm text-slate-500">Pilih bank yang paling nyaman, transfer sesuai nominal di atas.</p>
                    </div>
                </div>

                <ul class="mt-5 grid gap-3 sm:grid-cols-2" role="list">
                    @foreach ($bankAccounts as $idx => $bank)
                        <li class="rounded-2xl border border-slate-100 bg-white p-4 transition hover:border-primary-200 hover:shadow-md" data-testid="bank-account">
                            <div class="flex items-center gap-3">
                                <x-bank-logo :bank="$bank" size="sm" />
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Bank {{ $bank['bank'] }}</p>
                                    <p class="text-sm font-semibold text-slate-900 truncate">a.n. {{ $bank['holder'] }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3.5 py-2.5">
                                <p class="font-mono text-base font-bold tracking-wider text-slate-900">{{ $bank['number'] }}</p>
                                <button type="button"
                                        @click="copyBank({{ $idx }}, @js(preg_replace('/[^0-9]/', '', $bank['number'])))"
                                        class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:border-primary-300 hover:text-primary-600">
                                    <i :data-lucide="bankCopied === {{ $idx }} ? 'check' : 'copy'" class="h-3.5 w-3.5"></i>
                                    <span x-text="bankCopied === {{ $idx }} ? 'Tersalin' : 'Salin'"></span>
                                </button>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <p class="flex items-start gap-2 leading-relaxed">
                        <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <span>Transfer nominal <strong>persis</strong> supaya verifikasi lebih cepat. Selesaikan dalam 1×24 jam.</span>
                    </p>
                </div>
            </section>
        @endif

        {{-- CTAs --}}
        <section class="mt-7 grid gap-3 sm:grid-cols-2">
            <a href="{{ $uploadUrl }}" data-testid="cta-upload"
               class="ripple inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-6 py-4 text-base font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700">
                <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                Upload bukti bayar
            </a>
            <a href="{{ $trackUrl }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl border-2 border-slate-200 bg-white px-6 py-4 text-base font-bold text-slate-700 transition hover:border-primary-300 hover:text-primary-600">
                <i data-lucide="package-search" class="h-5 w-5"></i>
                Lacak status
            </a>
        </section>

        {{-- WA admin --}}
        <aside class="mt-6 flex flex-col items-start gap-4 rounded-3xl border border-secondary-200 bg-secondary-50/70 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-secondary-100 text-secondary-700">
                    <i data-lucide="message-circle-more" class="h-5 w-5"></i>
                </span>
                <div>
                    <p class="text-sm font-bold text-secondary-900">Butuh bantuan?</p>
                    <p class="mt-0.5 text-sm text-secondary-800">Chat {{ $waAdmin['label'] }} untuk konfirmasi atau pertanyaan.</p>
                </div>
            </div>
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex shrink-0 items-center gap-2 rounded-full bg-secondary-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-secondary-500/30 transition hover:-translate-y-0.5 hover:bg-secondary-700">
                <i data-lucide="message-circle" class="h-4 w-4"></i>
                Chat admin
            </a>
        </aside>

        <p class="mt-8 text-center text-sm text-slate-500">
            <a href="{{ route('courses.show', $course->slug) }}" class="font-semibold text-primary-600 hover:underline">← Kembali ke halaman kelas</a>
        </p>
    </section>

    <x-slot name="scripts">
        <script>
            window.checkoutSuccessPage = function (cfg) {
                return {
                    orderNumber: cfg.orderNumber || '',
                    copied: false,
                    bankCopied: null,
                    _copyTimer: null,
                    _bankTimer: null,

                    init() {
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
                        } catch (e) { /* fall through */ }
                        const ta = document.createElement('textarea');
                        ta.value = text;
                        ta.setAttribute('readonly', '');
                        ta.style.position = 'absolute';
                        ta.style.left = '-9999px';
                        document.body.appendChild(ta);
                        ta.select();
                        try { document.execCommand('copy'); } catch (e) { window.prompt('Salin manual:', text); }
                        finally { document.body.removeChild(ta); }
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
