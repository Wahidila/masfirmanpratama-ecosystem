@php
    /** @var string $orderNumber */
    /** @var string $paymentType */ // 'lunas' | 'cicilan' (dari ?type=)
    /** @var int $totalTransfer */    // nominal yang harus dibayar (dari ?total=)
    /** @var int $totalPayments */    // jumlah total pembayaran (dari ?n=, hanya cicilan; default 1 untuk lunas)
    /** @var int $defaultSequence */  // pre-select index 0..n-1 (dari ?seq=, default 0)

    $isInstallment = $paymentType === 'cicilan' && $totalPayments > 1;

    /** @var array{number: string, label: string} $waAdmin */
    $waAdmin = \App\Services\Settings::getWaAdmin();
    $waText = rawurlencode("Halo Admin, saya baru saja upload bukti bayar untuk order {$orderNumber}. Mohon dicek.");
    $waLink = "https://wa.me/{$waAdmin['number']}?text={$waText}";

    // Opsi dropdown cicilan + nominal per-sequence disiapkan oleh UploadController.
    // Untuk order nyata state-aware (tandai lunas/menunggu/ditolak + disable yang
    // tak bisa diupload); untuk stub M1 count-based. Default fallback [] biar aman.
    $installmentOptions = $installmentOptions ?? [];
    $installmentAmounts = $installmentAmounts ?? [];

    $successFlash = session('upload.success');
@endphp

{{--
    TODO (M2 — KRITIS KEAMANAN):
    Route /upload/{order_number} ini WAJIB di-token-protect sebelum production.
    Pilihan implementasi:
      - Laravel signed URL (`URL::temporarySignedRoute('upload.show', ttl, [...])`)
        dikirim ke customer via WA setelah checkout. Default 7 hari, refreshable
        via "Kirim ulang link upload" di admin panel.
      - JWT singkat (HMAC-SHA256, exp 7 hari) di-encode ke query / path.
    Tanpa proteksi, attacker bisa enumerate order_number (format MFP-YYYYMMDD-XXXXXX
    cuma 6 hex char terakhir → 16 juta kombinasi, brute-forceable) dan menimpa
    bukti bayar order orang lain. Spawn task FE+BE bareng saat M2 admin landing.
--}}

<x-layouts.store
    title="Upload Bukti Bayar — {{ $orderNumber }}"
    description="Upload bukti transfer untuk pesanan {{ $orderNumber }} agar tim admin Firman Pratama bisa verifikasi pembayaran kamu."
    bodyClass="relative"
>
    {{-- Decorative blobs (consistent dengan halaman checkout flow) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -left-24 -top-20 h-80 w-80 rounded-full bg-primary-200/70 blur-3xl animate-blob"></div>
        <div class="absolute -bottom-24 -right-16 h-80 w-80 rounded-full bg-secondary-200/70 blur-3xl animate-blob"></div>
    </div>

    <section
        id="uploadProofPage"
        class="mx-auto w-full max-w-3xl px-4 py-14 sm:px-6 lg:px-8 lg:py-20"
        x-data="uploadProofPage({
            orderNumber: @js($orderNumber),
            paymentType: @js($paymentType),
            totalTransfer: @js($totalTransfer),
            totalPayments: @js($totalPayments),
            defaultSequence: @js($defaultSequence),
            installmentAmounts: @js($installmentAmounts),
            maxBytes: 2 * 1024 * 1024,
            acceptTypes: ['image/jpeg', 'image/png', 'image/webp'],
            initialSuccess: @js((bool) $successFlash),
        })"
        x-init="init()"
    >
        {{-- ====================================================== --}}
        {{-- Header: order context                                    --}}
        {{-- ====================================================== --}}
        <header class="text-center">
            <span
                class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 ring-1 ring-primary-200"
                aria-hidden="true"
            >
                <i data-lucide="upload-cloud" class="h-8 w-8"></i>
            </span>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-[0.2em] text-primary-600">Upload Bukti Bayar</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-tight text-slate-900 md:text-5xl">
                Kirim bukti transfer
            </h1>
            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                Setelah transfer manual, upload bukti pembayaran biar tim admin bisa verifikasi dan order kamu langsung diproses.
            </p>
        </header>

        {{-- ====================================================== --}}
        {{-- Order number + nominal yang harus dibayar               --}}
        {{-- ====================================================== --}}
        <section
            id="orderContextCard"
            class="panel-card glass hover-lift mt-10 grid gap-4 rounded-3xl border border-white/60 p-6 sm:grid-cols-2 sm:p-8"
            aria-label="Konteks pesanan"
        >
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Nomor Pesanan</p>
                <p
                    class="mt-2 break-all font-mono text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl"
                    data-testid="upload-order-number"
                >
                    {{ $orderNumber }}
                </p>
            </div>

            <div class="sm:text-right">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
                    @if ($isInstallment)
                        Nominal Pembayaran (DP / Cicilan)
                    @else
                        Total Transfer (Lunas)
                    @endif
                </p>
                <p
                    class="mt-2 text-2xl font-extrabold leading-tight text-primary-600 sm:text-3xl"
                    data-testid="upload-total-transfer"
                    x-text="currentAmountLabel"
                >
                    @if ($totalTransfer > 0)
                        Rp {{ number_format($totalTransfer, 0, ',', '.') }}
                    @else
                        —
                    @endif
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Pastikan nominal transfer sesuai sampai 3 digit terakhir.
                </p>
            </div>
        </section>

        {{-- ====================================================== --}}
        {{-- Success state (flash setelah POST sukses)                --}}
        {{-- ====================================================== --}}
        <section
            x-show="success"
            x-cloak
            x-transition.opacity
            class="mt-8 rounded-3xl border border-secondary-200 bg-secondary-50/70 p-6 sm:p-8"
            role="status"
            aria-live="polite"
            data-testid="upload-success-state"
        >
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-secondary-100 text-secondary-700">
                    <i data-lucide="badge-check" class="h-6 w-6"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-2xl font-bold text-secondary-900">Bukti diterima</h2>
                    <p class="mt-2 text-secondary-800">
                        Tim admin akan verifikasi pembayaran kamu via WhatsApp dalam 1×24 jam kerja. Kamu bakal dapat update status order setelah dicek.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-3">
                        <a
                            href="{{ $waLink }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-full bg-secondary-600 px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-secondary-500/30 transition hover:-translate-y-0.5 hover:bg-secondary-700"
                        >
                            <i data-lucide="message-circle" class="h-4 w-4"></i>
                            Konfirmasi via WhatsApp
                        </a>
                        <a
                            href="{{ $trackUrl ?? route('track.show', ['order_number' => $orderNumber]) }}"
                            class="inline-flex items-center gap-2 rounded-full border border-secondary-300 bg-white px-4 py-2.5 text-sm font-bold text-secondary-700 transition hover:border-secondary-500"
                        >
                            <i data-lucide="package-search" class="h-4 w-4"></i>
                            Track order
                        </a>
                        <button
                            type="button"
                            @click="resetForm()"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                        >
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload bukti lain
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ====================================================== --}}
        {{-- Form upload bukti                                        --}}
        {{-- ====================================================== --}}
        <form
            x-show="!success"
            x-cloak
            method="POST"
            action="{{ $uploadStoreUrl ?? route('upload.store', ['order_number' => $orderNumber]) }}"
            enctype="multipart/form-data"
            class="panel-card glass mt-8 rounded-3xl border border-white/60 p-6 sm:p-8"
            @submit="onSubmit($event)"
            novalidate
            data-testid="upload-form"
        >
            @csrf

            {{-- ────────────────────────────────────────────────── --}}
            {{-- Cicilan dropdown                                    --}}
            {{-- ────────────────────────────────────────────────── --}}
            <div class="space-y-2">
                <label for="installment_sequence" class="block text-sm font-bold text-slate-900">
                    Pembayaran ke-berapa?
                </label>

                @if ($isInstallment)
                    <select
                        id="installment_sequence"
                        name="installment_sequence"
                        x-model="form.sequence"
                        class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 shadow-sm transition focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200"
                        data-testid="installment-select"
                        required
                    >
                        @foreach ($installmentOptions as $opt)
                            <option value="{{ $opt['value'] }}" @disabled(! ($opt['uploadable'] ?? true)) @selected($opt['value'] === $defaultSequence)>
                                {{ $opt['label'] }}@if (! empty($opt['note'])) — {{ $opt['note'] }}@endif @if (! empty($opt['status_note'])) · {{ $opt['status_note'] }}@endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-500">
                        Terpilih otomatis ke pembayaran berikutnya yang belum lunas. Yang sudah lunas / sedang diverifikasi tidak bisa dipilih lagi.
                    </p>
                @else
                    {{-- Lunas — placeholder read-only supaya backend tetap dapat field konsisten. --}}
                    <select
                        id="installment_sequence"
                        name="installment_sequence"
                        class="block w-full cursor-not-allowed rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm"
                        data-testid="installment-select"
                        disabled
                    >
                        <option value="0" selected>Pembayaran Lunas (sekali bayar)</option>
                    </select>
                    {{-- Hidden input supaya $request->installment_sequence tetap terkirim untuk lunas. --}}
                    <input type="hidden" name="installment_sequence" value="0">
                    <p class="text-xs text-slate-500">
                        Order ini lunas (sekali bayar) — tidak ada pilihan cicilan.
                    </p>
                @endif
            </div>

            {{-- ────────────────────────────────────────────────── --}}
            {{-- File input + dropzone + preview                     --}}
            {{-- ────────────────────────────────────────────────── --}}
            <div class="mt-6 space-y-2">
                <label for="proof_file" class="block text-sm font-bold text-slate-900">
                    Bukti Transfer (foto / screenshot)
                </label>

                {{-- Dropzone --}}
                <label
                    for="proof_file"
                    class="group relative flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-white px-6 py-10 text-center transition"
                    :class="{
                        'border-primary-400 bg-primary-50/50': isDragging,
                        'border-rose-300 bg-rose-50/40': fileError,
                        'border-slate-200 hover:border-primary-300 hover:bg-primary-50/30': !isDragging && !fileError,
                    }"
                    @dragover.prevent="isDragging = true"
                    @dragleave.prevent="isDragging = false"
                    @drop.prevent="onDrop($event)"
                    data-testid="upload-dropzone"
                >
                    <input
                        type="file"
                        id="proof_file"
                        name="proof_file"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onFileChange($event)"
                        class="sr-only"
                        :required="!file"
                        x-ref="fileInput"
                    >

                    {{-- Empty state --}}
                    <template x-if="!file">
                        <div>
                            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 transition group-hover:bg-primary-100">
                                <i data-lucide="image-up" class="h-6 w-6"></i>
                            </span>
                            <p class="mt-3 text-sm font-bold text-slate-900">
                                Drop gambar di sini, atau klik untuk pilih
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                JPG, PNG, atau WebP. Maks. 2 MB.
                            </p>
                        </div>
                    </template>

                    {{-- Filled state — preview thumbnail --}}
                    <template x-if="file">
                        <div class="flex w-full flex-col items-center gap-4 sm:flex-row sm:items-start sm:text-left">
                            <img
                                :src="filePreview"
                                alt="Preview bukti transfer"
                                class="h-32 w-32 shrink-0 rounded-xl border border-slate-200 object-cover shadow-sm"
                                data-testid="upload-preview"
                            >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold text-slate-900" x-text="file.name"></p>
                                <p class="mt-1 text-xs text-slate-500">
                                    <span x-text="fileTypeLabel"></span> · <span x-text="formatBytes(file.size)"></span>
                                </p>
                                <button
                                    type="button"
                                    @click.prevent="clearFile()"
                                    class="mt-3 inline-flex min-h-[44px] items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:border-rose-300 hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                                >
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                    Ganti file
                                </button>
                            </div>
                        </div>
                    </template>
                </label>

                {{-- Inline error --}}
                <p
                    x-show="fileError"
                    x-text="fileError"
                    class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-rose-600"
                    role="alert"
                    data-testid="upload-file-error"
                ></p>

                {{-- Server-side validation error (laravel) --}}
                @error('proof_file')
                    <p class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-rose-600" role="alert">
                        <i data-lucide="alert-circle" class="h-4 w-4"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- ────────────────────────────────────────────────── --}}
            {{-- Catatan opsional                                    --}}
            {{-- ────────────────────────────────────────────────── --}}
            <div class="mt-6 space-y-2">
                <label for="note" class="block text-sm font-bold text-slate-900">
                    Catatan untuk admin <span class="font-normal text-slate-500">(opsional)</span>
                </label>
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    maxlength="500"
                    x-model="form.note"
                    placeholder="Misal: transfer dari rekening BCA atas nama Budi Santoso, jam 14:32"
                    class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-200"
                ></textarea>
                <p class="text-right text-xs text-slate-500">
                    <span x-text="form.note.length"></span>/500
                </p>
            </div>

            {{-- ────────────────────────────────────────────────── --}}
            {{-- Submit                                              --}}
            {{-- ────────────────────────────────────────────────── --}}
            <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <a
                    href="{{ route('checkout.success', ['order' => $orderNumber]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-600"
                >
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Lihat info rekening lagi
                </a>

                <button
                    type="submit"
                    :disabled="!canSubmit"
                    class="ripple inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-6 py-3 text-base font-bold text-white shadow-lg shadow-primary-500/30 transition hover:-translate-y-0.5 hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
                    data-testid="upload-submit"
                >
                    <template x-if="!submitting">
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                            Kirim bukti bayar
                        </span>
                    </template>
                    <template x-if="submitting">
                        <span class="inline-flex items-center gap-2">
                            <i data-lucide="loader-2" class="h-5 w-5 animate-spin"></i>
                            Mengirim...
                        </span>
                    </template>
                </button>
            </div>
        </form>

        {{-- ====================================================== --}}
        {{-- Footer info                                              --}}
        {{-- ====================================================== --}}
        <p class="mt-10 text-center text-sm text-slate-500">
            Belum sempat transfer?
            <a href="{{ route('checkout.success', ['order' => $orderNumber]) }}" class="font-semibold text-primary-600 hover:underline">Kembali ke info rekening</a>.
        </p>
    </section>

    {{-- ────────────────────────────────────────────────────────── --}}
    {{-- Alpine page component                                       --}}
    {{-- ────────────────────────────────────────────────────────── --}}
    <x-slot name="scripts">
        <script>
            window.uploadProofPage = function (cfg) {
                return {
                    // Static config
                    orderNumber: cfg.orderNumber || '',
                    paymentType: cfg.paymentType || 'lunas',
                    totalTransfer: Number(cfg.totalTransfer) || 0,
                    totalPayments: Number(cfg.totalPayments) || 1,
                    installmentAmounts: (cfg.installmentAmounts && typeof cfg.installmentAmounts === 'object') ? cfg.installmentAmounts : {},
                    maxBytes: Number(cfg.maxBytes) || (2 * 1024 * 1024),
                    acceptTypes: Array.isArray(cfg.acceptTypes) ? cfg.acceptTypes : ['image/jpeg', 'image/png', 'image/webp'],

                    // Form state
                    form: {
                        sequence: String(Number(cfg.defaultSequence) || 0),
                        note: '',
                    },
                    file: null,
                    filePreview: '',
                    fileError: '',
                    isDragging: false,
                    submitting: false,
                    success: !!cfg.initialSuccess,

                    // Computed
                    get canSubmit() {
                        return !!this.file && !this.fileError && !this.submitting;
                    },
                    // Nominal yang ditampilkan mengikuti pilihan cicilan (kalau ada
                    // data per-sequence); fallback ke totalTransfer server.
                    get currentAmountLabel() {
                        const seq = Number(this.form.sequence) || 0;
                        const amt = (this.installmentAmounts && this.installmentAmounts[seq] != null)
                            ? Number(this.installmentAmounts[seq])
                            : this.totalTransfer;
                        return amt > 0 ? ('Rp ' + amt.toLocaleString('id-ID')) : '—';
                    },
                    get fileTypeLabel() {
                        if (!this.file) return '';
                        const map = {
                            'image/jpeg': 'JPEG',
                            'image/png': 'PNG',
                            'image/webp': 'WebP',
                        };
                        return map[this.file.type] || this.file.type;
                    },

                    init() {
                        // Re-render lucide icons setelah Alpine swap template (file → preview).
                        this.$watch('file', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                        this.$watch('success', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));
                        this.$watch('fileError', () => this.$nextTick(() => window.lucide && window.lucide.createIcons()));

                        // Auto-hide success state setelah refresh (kalau user navigate balik
                        // dengan file lain). M2: hapus state ini setelah upload disimpan ke DB
                        // dan halaman jadi server-rendered DB-driven.
                    },

                    onFileChange(event) {
                        const file = event.target.files && event.target.files[0];
                        this.acceptFile(file || null);
                    },

                    onDrop(event) {
                        this.isDragging = false;
                        const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];
                        if (file) {
                            // Sync ke <input type=file> supaya form submit kirim file (tidak cuma object Alpine).
                            try {
                                const dt = new DataTransfer();
                                dt.items.add(file);
                                this.$refs.fileInput.files = dt.files;
                            } catch (e) {
                                // Browser lama tanpa DataTransfer → fallback: minta user klik dropzone manual.
                                this.fileError = 'Browser kamu tidak support drag-drop, silakan klik area upload untuk pilih file.';
                                return;
                            }
                            this.acceptFile(file);
                        }
                    },

                    acceptFile(file) {
                        this.fileError = '';
                        this.file = null;
                        this.filePreview = '';

                        if (!file) return;

                        // FE strict validation: type + size.
                        if (!this.acceptTypes.includes(file.type)) {
                            this.fileError = 'Format tidak didukung. Pakai JPG, PNG, atau WebP.';
                            this.clearInput();
                            return;
                        }
                        if (file.size > this.maxBytes) {
                            this.fileError = 'Ukuran file terlalu besar. Maksimal 2 MB.';
                            this.clearInput();
                            return;
                        }
                        if (file.size === 0) {
                            this.fileError = 'File kosong / corrupted. Coba upload ulang.';
                            this.clearInput();
                            return;
                        }

                        this.file = file;
                        // Render preview thumbnail dari blob URL.
                        try {
                            this.filePreview = URL.createObjectURL(file);
                        } catch (e) {
                            this.filePreview = '';
                        }
                    },

                    clearFile() {
                        if (this.filePreview) {
                            try { URL.revokeObjectURL(this.filePreview); } catch (e) { /* noop */ }
                        }
                        this.file = null;
                        this.filePreview = '';
                        this.fileError = '';
                        this.clearInput();
                    },

                    clearInput() {
                        if (this.$refs.fileInput) {
                            this.$refs.fileInput.value = '';
                        }
                    },

                    onSubmit(event) {
                        // Re-validate sebelum submit; kalau ada error FE batalkan.
                        if (!this.file) {
                            event.preventDefault();
                            this.fileError = 'Pilih file bukti transfer dulu sebelum mengirim.';
                            return;
                        }
                        if (this.fileError) {
                            event.preventDefault();
                            return;
                        }
                        this.submitting = true;
                        // Submit beneran ke POST /upload/{order_number}.
                    },

                    resetForm() {
                        this.success = false;
                        this.clearFile();
                        this.form.note = '';
                    },

                    formatBytes(bytes) {
                        const n = Number(bytes) || 0;
                        if (n >= 1048576) return (n / 1048576).toFixed(2) + ' MB';
                        if (n >= 1024) return (n / 1024).toFixed(1) + ' KB';
                        return n + ' B';
                    },
                };
            };
        </script>
    </x-slot>
</x-layouts.store>
