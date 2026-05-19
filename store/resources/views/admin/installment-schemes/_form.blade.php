@props(['scheme', 'products', 'action', 'method' => 'POST'])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-admin.card>
        <div class="space-y-5">
            {{-- Nama --}}
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">
                    Nama Skema <span class="text-rose-500">*</span>
                </label>
                <input id="name" type="text" name="name"
                       value="{{ old('name', $scheme->name) }}"
                       required maxlength="120"
                       placeholder="Mis. 3x Cicilan, Lunas, 12x Cicilan Kelas Reguler"
                       class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Scope: global vs per-product --}}
            <div>
                <label for="product_id" class="block text-sm font-medium text-slate-700">
                    Berlaku untuk
                </label>
                <select id="product_id" name="product_id"
                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                    <option value="">Global (semua produk)</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                            @selected((int) old('product_id', $scheme->product_id) === $product->id)>
                            {{ $product->title }} ({{ $product->slug }})
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">
                    Pilih "Global" supaya skema muncul di checkout semua produk.
                    Pilih produk spesifik kalau skema hanya berlaku untuk produk itu (mis. 12x untuk kelas reguler).
                </p>
                @error('product_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Numeric fields: 3-col grid --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label for="dp_pct" class="block text-sm font-medium text-slate-700">
                        DP (%) <span class="text-rose-500">*</span>
                    </label>
                    <input id="dp_pct" type="number" name="dp_pct"
                           value="{{ old('dp_pct', $scheme->dp_pct) }}"
                           min="0" max="100" step="0.01" required
                           class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                    <p class="mt-1 text-xs text-slate-500">100 = lunas, 30 = DP 30%</p>
                    @error('dp_pct')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="n_installments" class="block text-sm font-medium text-slate-700">
                        Jumlah Pembayaran <span class="text-rose-500">*</span>
                    </label>
                    <input id="n_installments" type="number" name="n_installments"
                           value="{{ old('n_installments', $scheme->n_installments) }}"
                           min="1" max="36" step="1" required
                           class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                    <p class="mt-1 text-xs text-slate-500">1 = lunas (DP saja), 3 = DP + 2 cicilan</p>
                    @error('n_installments')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="interval_days" class="block text-sm font-medium text-slate-700">
                        Interval (hari) <span class="text-rose-500">*</span>
                    </label>
                    <input id="interval_days" type="number" name="interval_days"
                           value="{{ old('interval_days', $scheme->interval_days) }}"
                           min="0" max="365" step="1" required
                           class="mt-1 block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                    <p class="mt-1 text-xs text-slate-500">Jarak antar cicilan dalam hari (default 30 = bulanan)</p>
                    @error('interval_days')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center gap-3">
                <input id="active" type="checkbox" name="active" value="1"
                       @checked(old('active', $scheme->active ?? true))
                       class="rounded border-slate-300 text-primary-600 focus:ring-primary-500/40">
                <label for="active" class="text-sm text-slate-700">
                    Aktif (muncul di dropdown checkout)
                </label>
            </div>
        </div>
    </x-admin.card>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.installment-schemes.index') }}"
           class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
        <button type="submit"
                class="inline-flex items-center rounded-xl bg-primary-600 px-5 py-2 text-sm font-medium text-white hover:bg-primary-500 transition">
            {{ $scheme->exists ? 'Simpan Perubahan' : 'Buat Skema' }}
        </button>
    </div>
</form>
