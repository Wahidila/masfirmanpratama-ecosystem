<x-layouts.store
    title="Daftar Kelas — {{ $course->title }}"
    description="Formulir pendaftaran kelas {{ $course->title }}."
>
@php
    $price = (int) $course->price;
    $hasDiscount = $course->original_price !== null && (float) $course->original_price > (float) $course->price;
    $discountPct = $hasDiscount ? (int) round((1 - (float) $course->price / (float) $course->original_price) * 100) : null;
    $features = collect($course->card_features ?? [])->map(fn ($f) => trim((string) $f))->filter()->values();
    $imageUrl = $course->image_path ? asset($course->image_path) : null;
    $tagline = $course->tagline ?: $course->subtitle;
@endphp

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white py-8 sm:py-12">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">

        {{-- Breadcrumb / back --}}
        <nav class="mb-6 flex items-center gap-1.5 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-primary-600">Beranda</a>
            <i data-lucide="chevron-right" class="h-4 w-4 text-slate-300"></i>
            <a href="{{ route('courses.show', $course->slug) }}" class="hover:text-primary-600 truncate max-w-[180px] sm:max-w-none">{{ $course->title }}</a>
            <i data-lucide="chevron-right" class="h-4 w-4 text-slate-300"></i>
            <span class="text-slate-700 font-medium">Pendaftaran</span>
        </nav>

        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Selesaikan Pendaftaran</h1>
            <p class="mt-1.5 text-slate-500 text-sm">Isi data pendaftar &amp; pilih metode pembayaran. Detail rekening dikirim ke WhatsApp kamu.</p>
        </div>

        <form
            method="POST"
            action="{{ route('courses.checkout.store', $course->slug) }}"
            x-data="courseCheckout({{ json_encode($schemes) }}, {{ $price }})"
            x-init="$watch('paymentType', v => { if (v === 'cicilan' && !selectedScheme && schemes.length) selectedScheme = schemes[0].id })"
            class="grid grid-cols-1 lg:grid-cols-[1fr_384px] gap-6 lg:gap-8 items-start"
        >
            @csrf

            {{-- ══════════════ LEFT: form ══════════════ --}}
            <div class="space-y-6 min-w-0">

                {{-- Data Pendaftar --}}
                <section class="rounded-2xl border border-slate-100 bg-white p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                            <i data-lucide="user-round" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="font-bold text-slate-900 text-base leading-tight">Data Pendaftar</h2>
                            <p class="text-xs text-slate-400">Pastikan nomor WhatsApp aktif</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required
                                   placeholder="Masukkan nama lengkap"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition @error('customer_name') border-rose-400 @enderror">
                            @error('customer_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-rose-500">*</span></label>
                                <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" required
                                       placeholder="email@contoh.com"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition @error('customer_email') border-rose-400 @enderror">
                                @error('customer_email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="customer_phone" class="block text-sm font-medium text-slate-700 mb-1.5">Nomor WhatsApp <span class="text-rose-500">*</span></label>
                                <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" required inputmode="numeric"
                                       placeholder="08xxxxxxxxxx"
                                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition @error('customer_phone') border-rose-400 @enderror">
                                @error('customer_phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="occupation" class="block text-sm font-medium text-slate-700 mb-1.5">Pekerjaan / Profesi <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <input type="text" id="occupation" name="occupation" value="{{ old('occupation') }}"
                                   placeholder="Mahasiswa, Karyawan, Wirausaha, dll"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition">
                        </div>

                        <div>
                            <label for="motivation" class="block text-sm font-medium text-slate-700 mb-1.5">Motivasi Mengikuti Kelas <span class="text-slate-400 font-normal">(opsional)</span></label>
                            <textarea id="motivation" name="motivation" rows="3"
                                      placeholder="Ceritakan singkat alasan kamu ingin ikut kelas ini..."
                                      class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 transition resize-none">{{ old('motivation') }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Metode Pembayaran --}}
                <section class="rounded-2xl border border-slate-100 bg-white p-6 sm:p-7 shadow-sm">
                    <div class="flex items-center gap-2.5 mb-5">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                            <i data-lucide="wallet" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="font-bold text-slate-900 text-base leading-tight">Metode Pembayaran</h2>
                            <p class="text-xs text-slate-400">Transfer bank manual, dikonfirmasi tim kami</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        {{-- Lunas --}}
                        <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                               :class="paymentType === 'lunas' ? 'border-primary-500 bg-primary-50/60' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="payment_type" value="lunas" x-model="paymentType" class="sr-only">
                            <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                  :class="paymentType === 'lunas' ? 'border-primary-600' : 'border-slate-300'">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary-600" x-show="paymentType === 'lunas'"></span>
                            </span>
                            <span class="flex-1">
                                <span class="font-semibold text-sm text-slate-900">Bayar Lunas</span>
                                <span class="block text-xs text-slate-500 mt-0.5">Sekali bayar, langsung beres.</span>
                            </span>
                            <span class="font-bold text-sm text-slate-900">{{ 'Rp ' . number_format($price, 0, ',', '.') }}</span>
                        </label>

                        @if ($schemes->count() > 0)
                            {{-- Cicilan --}}
                            <label class="flex items-center gap-3 p-4 rounded-xl border-2 cursor-pointer transition"
                                   :class="paymentType === 'cicilan' ? 'border-primary-500 bg-primary-50/60' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" name="payment_type" value="cicilan" x-model="paymentType" class="sr-only">
                                <span class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                                      :class="paymentType === 'cicilan' ? 'border-primary-600' : 'border-slate-300'">
                                    <span class="h-2.5 w-2.5 rounded-full bg-primary-600" x-show="paymentType === 'cicilan'"></span>
                                </span>
                                <span class="flex-1">
                                    <span class="font-semibold text-sm text-slate-900">Bayar Cicilan</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">DP dulu, sisanya dicicil — tanpa bunga.</span>
                                </span>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-600">0% bunga</span>
                            </label>

                            {{-- Pilihan skema cicilan --}}
                            <div x-show="paymentType === 'cicilan'" x-collapse class="space-y-2.5 pt-1">
                                <p class="text-xs font-medium text-slate-500 px-1">Pilih skema cicilan:</p>
                                @foreach ($schemes as $scheme)
                                    @php
                                        $dp = (int) ceil($price * (float) $scheme->dp_pct / 100);
                                        $isLunasScheme = $scheme->n_installments <= 1 && (float) $scheme->dp_pct >= 100;
                                    @endphp
                                    <label class="flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                           :class="selectedScheme == {{ $scheme->id }} ? 'border-primary-400 bg-primary-50/50 ring-1 ring-primary-200' : 'border-slate-200 hover:border-slate-300'">
                                        <input type="radio" name="installment_scheme_id" value="{{ $scheme->id }}" x-model="selectedScheme" class="sr-only">
                                        <span class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2"
                                              :class="selectedScheme == {{ $scheme->id }} ? 'border-primary-600' : 'border-slate-300'">
                                            <span class="h-2 w-2 rounded-full bg-primary-600" x-show="selectedScheme == {{ $scheme->id }}"></span>
                                        </span>
                                        <span class="flex-1 min-w-0">
                                            <span class="font-semibold text-sm text-slate-900">{{ $scheme->name }}</span>
                                            <span class="block text-xs text-slate-500 mt-0.5">
                                                @if ($isLunasScheme)
                                                    Bayar penuh di awal
                                                @else
                                                    DP {{ $scheme->dp_label }}% (Rp {{ number_format($dp, 0, ',', '.') }}) + {{ $scheme->n_installments }}× cicilan / {{ $scheme->interval_days }} hari
                                                @endif
                                            </span>
                                        </span>
                                    </label>
                                @endforeach

                                {{-- Timeline jadwal --}}
                                <div x-show="selectedScheme" x-cloak class="rounded-xl bg-slate-50 border border-slate-100 p-4 mt-1">
                                    <p class="text-xs font-semibold text-slate-700 mb-2.5 flex items-center gap-1.5">
                                        <i data-lucide="calendar-clock" class="h-4 w-4 text-primary-500"></i>
                                        Jadwal pembayaran
                                    </p>
                                    <ul class="space-y-0">
                                        <template x-for="(item, idx) in schedule" :key="idx">
                                            <li class="flex items-center justify-between gap-3 py-2 text-xs border-b border-slate-100 last:border-0">
                                                <span class="flex items-center gap-2 text-slate-600">
                                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold"
                                                          :class="idx === 0 ? 'bg-primary-100 text-primary-700' : 'bg-slate-200 text-slate-600'"
                                                          x-text="idx === 0 ? 'DP' : idx"></span>
                                                    <span x-text="item.label"></span>
                                                </span>
                                                <span class="font-semibold text-slate-900" x-text="fmt(item.amount)"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            </div>

            {{-- ══════════════ RIGHT: sticky order summary ══════════════ --}}
            <aside class="lg:sticky lg:top-24 space-y-4">
                <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
                    {{-- Course header --}}
                    <div class="p-5 flex gap-4">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $course->title }}"
                                 class="h-16 w-16 rounded-xl object-cover shrink-0 ring-1 ring-slate-100"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="h-16 w-16 rounded-xl bg-gradient-to-br from-primary-500 to-blue-600 shrink-0 items-center justify-center text-white" style="display:none">
                                <i data-lucide="{{ $course->card_icon ?: 'graduation-cap' }}" class="h-7 w-7"></i>
                            </div>
                        @else
                            <div class="h-16 w-16 rounded-xl bg-gradient-to-br from-primary-500 to-blue-600 shrink-0 flex items-center justify-center text-white">
                                <i data-lucide="{{ $course->card_icon ?: 'graduation-cap' }}" class="h-7 w-7"></i>
                            </div>
                        @endif
                        <div class="min-w-0">
                            @if ($course->badge)
                                <span class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-primary-600 mb-1">{{ $course->badge }}</span>
                            @endif
                            <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ $course->title }}</h3>
                            @if ($tagline)
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">{{ $tagline }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Price + discount --}}
                    <div class="px-5 pb-4">
                        <div class="flex items-baseline gap-2 flex-wrap">
                            <span class="text-2xl font-extrabold text-slate-900">{{ 'Rp ' . number_format($price, 0, ',', '.') }}</span>
                            @if ($hasDiscount)
                                <span class="text-sm text-slate-400 line-through">{{ 'Rp ' . number_format((int) $course->original_price, 0, ',', '.') }}</span>
                            @endif
                        </div>
                        @if ($hasDiscount)
                            <span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-600">
                                <i data-lucide="tag" class="h-3 w-3"></i>
                                Hemat {{ $discountPct }}%
                            </span>
                        @endif
                    </div>

                    {{-- Yang kamu dapat --}}
                    @if ($features->isNotEmpty())
                        <div class="border-t border-slate-100 px-5 py-4">
                            <p class="text-xs font-semibold text-slate-700 mb-2.5">Yang kamu dapat</p>
                            <ul class="space-y-2">
                                @foreach ($features->take(6) as $feature)
                                    <li class="flex items-start gap-2 text-xs text-slate-600">
                                        <i data-lucide="check" class="h-4 w-4 text-emerald-500 shrink-0 mt-px"></i>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Ringkasan bayar (dinamis) --}}
                    <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-4 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">Total investasi</span>
                            <span class="font-semibold text-slate-900">{{ 'Rp ' . number_format($price, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between" :class="paymentType === 'cicilan' && selectedScheme ? '' : 'hidden'">
                            <span class="text-sm text-slate-500">Sisa dicicil</span>
                            <span class="text-sm text-slate-600" x-text="nInstall + '× ' + fmt((price - dpAmount) / Math.max(1, nInstall))"></span>
                        </div>
                        <div class="flex items-center justify-between border-t border-slate-200 pt-2.5">
                            <span class="text-sm font-medium text-slate-700">
                                <span x-show="paymentType === 'cicilan' && selectedScheme">Bayar sekarang (DP)</span>
                                <span x-show="!(paymentType === 'cicilan' && selectedScheme)">Bayar sekarang</span>
                            </span>
                            <span class="text-xl font-extrabold text-primary-600" x-text="fmt(payNow)"></span>
                        </div>
                    </div>

                    {{-- CTA + trust --}}
                    <div class="px-5 pb-5 pt-1">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-primary-600/20 hover:bg-primary-700 active:scale-[0.99] transition">
                            <i data-lucide="shield-check" class="h-4 w-4"></i>
                            <span x-text="'Daftar &amp; Bayar ' + fmt(payNow)">Daftar &amp; Bayar</span>
                        </button>
                        <p class="mt-3 text-[11px] leading-relaxed text-slate-400 text-center">
                            Detail rekening dikirim ke WhatsApp setelah daftar. Belum ada dana keluar dari halaman ini.
                        </p>
                    </div>
                </div>

                {{-- Trust strip --}}
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl border border-slate-100 bg-white px-2 py-3">
                        <i data-lucide="badge-check" class="h-5 w-5 text-emerald-500 mx-auto"></i>
                        <p class="mt-1 text-[10px] leading-tight text-slate-500">Bergaransi</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-white px-2 py-3">
                        <i data-lucide="lock" class="h-5 w-5 text-primary-500 mx-auto"></i>
                        <p class="mt-1 text-[10px] leading-tight text-slate-500">Data aman</p>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-white px-2 py-3">
                        <i data-lucide="headset" class="h-5 w-5 text-blue-500 mx-auto"></i>
                        <p class="mt-1 text-[10px] leading-tight text-slate-500">Dibantu tim</p>
                    </div>
                </div>
            </aside>
        </form>
    </div>
</div>
</x-layouts.store>

<script>
function courseCheckout(schemes, price) {
    return {
        paymentType: 'lunas',
        selectedScheme: null,
        schemes: schemes,
        price: price,

        fmt(v) { return 'Rp ' + Math.max(0, Math.round(v)).toLocaleString('id-ID'); },
        scheme() { return this.schemes.find(s => s.id == this.selectedScheme) || null; },

        get dpAmount() {
            const s = this.scheme();
            return s ? Math.ceil(this.price * (parseFloat(s.dp_pct) / 100)) : 0;
        },
        get nInstall() {
            const s = this.scheme();
            return s ? s.n_installments : 0;
        },
        get payNow() {
            return (this.paymentType === 'cicilan' && this.scheme()) ? this.dpAmount : this.price;
        },
        get schedule() {
            const s = this.scheme();
            if (!s) return [];
            const dp = Math.ceil(this.price * (parseFloat(s.dp_pct) / 100));
            const remaining = this.price - dp;
            const n = Math.max(1, s.n_installments);
            const per = Math.ceil(remaining / n);
            const items = [{ label: 'Bayar sekarang', amount: dp }];
            for (let i = 1; i <= n; i++) {
                const amount = (i === n) ? remaining - (per * (n - 1)) : per;
                const days = s.interval_days * i;
                items.push({ label: 'Cicilan ke-' + i + ' · H+' + days, amount: Math.max(0, amount) });
            }
            return items;
        }
    };
}
</script>
