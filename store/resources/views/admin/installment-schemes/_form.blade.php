@props(['scheme', 'courses', 'action', 'method' => 'POST'])

@php
    $dpDefault = (float) old('dp_pct', $scheme->dp_pct ?? 30);
    $nDefault = (int) old('n_installments', $scheme->n_installments ?? 3);
    $intervalDefault = (int) old('interval_days', $scheme->interval_days ?? 30);
    $courseDefault = old('course_id', $scheme->course_id);
@endphp

<form method="POST" action="{{ $action }}"
      x-data="{
        dp: {{ $dpDefault }},
        n: {{ $nDefault }},
        interval: {{ $intervalDefault }},
        sample: 4500000,
        preset(dp, n, interval) { this.dp = dp; this.n = n; this.interval = interval; },
        fmt(v) { return 'Rp ' + Math.max(0, Math.round(v)).toLocaleString('id-ID'); },
        get isLunas() { return parseInt(this.n) <= 1 && Number(this.dp) >= 100; },
        get schedule() {
            const total = Math.max(0, Number(this.sample) || 0);
            const dpAmt = Math.ceil(total * (Number(this.dp) / 100));
            const remaining = Math.max(0, total - dpAmt);
            const n = Math.max(1, parseInt(this.n) || 1);
            const per = Math.ceil(remaining / n);
            const rows = [{ label: 'DP (bayar pertama)', amount: Math.max(0, dpAmt) }];
            for (let i = 1; i <= n; i++) {
                const amt = (i === n) ? remaining - per * (n - 1) : per;
                rows.push({ label: 'Cicilan ke-' + i, amount: Math.max(0, amt) });
            }
            return rows;
        }
      }"
      class="grid gap-6 lg:grid-cols-3">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    {{-- ── Kolom kiri: field ─────────────────────────────────── --}}
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card>
            <div class="space-y-5">
                {{-- Nama --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Skema <span class="text-error-500">*</span>
                    </label>
                    <input id="name" type="text" name="name"
                           value="{{ old('name', $scheme->name) }}"
                           required maxlength="120"
                           placeholder="Mis. 3x Cicilan · 12x Cicilan Kelas Platinum"
                           class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                    @error('name')
                        <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Scope: global (semua kelas) vs 1 kelas --}}
                <div>
                    <label for="course_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Berlaku untuk
                    </label>
                    <select id="course_id" name="course_id"
                            class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">🌐 Semua Kelas (global)</option>
                        @foreach ($courses as $course)
                            <option value="{{ $course->id }}"
                                @selected((int) $courseDefault === $course->id)>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <strong>Semua Kelas</strong>: skema muncul di checkout semua kelas.
                        <strong>Kelas tertentu</strong>: hanya muncul untuk kelas itu (mis. 12x khusus Platinum).
                        Cicilan tidak berlaku untuk produk/buku.
                    </p>
                    @error('course_id')
                        <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Preset cepat --}}
                <div>
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">Preset cepat</span>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" @click="preset(100, 1, 0)"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Lunas</button>
                        <button type="button" @click="preset(30, 3, 30)"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">DP 30% · 3x</button>
                        <button type="button" @click="preset(20, 6, 30)"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">DP 20% · 6x</button>
                        <button type="button" @click="preset(15, 12, 30)"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">DP 15% · 12x</button>
                    </div>
                </div>

                {{-- Numeric fields --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label for="dp_pct" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            DP (%) <span class="text-error-500">*</span>
                        </label>
                        <input id="dp_pct" type="number" name="dp_pct" x-model.number="dp"
                               min="0" max="100" step="0.01" required
                               class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">100 = lunas · 30 = DP 30%</p>
                        @error('dp_pct')
                            <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="n_installments" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Jml Cicilan <span class="text-error-500">*</span>
                        </label>
                        <input id="n_installments" type="number" name="n_installments" x-model.number="n"
                               min="1" max="36" step="1" required
                               class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jumlah cicilan <em>setelah</em> DP</p>
                        @error('n_installments')
                            <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="interval_days" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Interval (hari) <span class="text-error-500">*</span>
                        </label>
                        <input id="interval_days" type="number" name="interval_days" x-model.number="interval"
                               min="0" max="365" step="1" required
                               class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Jarak antar cicilan (30 = bulanan)</p>
                        @error('interval_days')
                            <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Active toggle --}}
                <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                    <input id="active" type="checkbox" name="active" value="1"
                           @checked(old('active', $scheme->active ?? true))
                           class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    <label for="active" class="text-sm text-gray-700 dark:text-gray-300">
                        Aktif — muncul di dropdown checkout kelas
                    </label>
                </div>
            </div>
        </x-admin.card>
    </div>

    {{-- ── Kolom kanan: pratinjau jadwal (live) ──────────────── --}}
    <div class="lg:col-span-1">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Pratinjau Jadwal</h3>
                <span class="inline-flex items-center rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400"
                      x-text="isLunas ? 'Lunas' : (n + '× cicilan')"></span>
            </div>

            <div class="mt-4">
                <label for="sample" class="block text-xs text-gray-500 dark:text-gray-400">Simulasi dengan harga kelas</label>
                <input id="sample" type="number" x-model.number="sample" min="0" step="100000"
                       class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>

            <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                <template x-for="(row, i) in schedule" :key="i">
                    <li class="flex items-center justify-between py-2.5 text-sm">
                        <span class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/[0.06] dark:text-gray-300"
                                  x-text="i === 0 ? 'DP' : i"></span>
                            <span x-text="row.label"></span>
                        </span>
                        <span class="font-semibold text-gray-800 dark:text-white/90" x-text="fmt(row.amount)"></span>
                    </li>
                </template>
            </ul>

            <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3 text-sm dark:border-gray-800">
                <span class="text-gray-500 dark:text-gray-400">Total</span>
                <span class="font-bold text-gray-800 dark:text-white/90" x-text="fmt(sample)"></span>
            </div>

            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                Pratinjau saja. Nominal aktual dihitung dari harga kelas saat checkout.
            </p>
        </div>
    </div>

    {{-- ── Actions ───────────────────────────────────────────── --}}
    <div class="flex items-center justify-end gap-3 lg:col-span-3">
        <a href="{{ route('admin.installment-schemes.index') }}"
           class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90">Batal</a>
        <x-admin.button type="submit">
            {{ $scheme->exists ? 'Simpan Perubahan' : 'Buat Skema' }}
        </x-admin.button>
    </div>
</form>
