@extends('layouts.dashboard')

@section('content')
<x-page-header title="Buat Link Referral" subtitle="Pilih produk yang ingin dipromosikan, atau masukkan URL custom." />

<div class="max-w-lg">
    <x-card>
        <form method="POST" action="{{ route('referrals.store') }}" class="space-y-4"
              x-data="referralPicker(@js($products), @js(old('target_url', $prefillUrl ?? '')))">
            @csrf
            <x-form.group label="Label" name="label" hint="Untuk membantu Anda membedakan tiap link.">
                <x-form.input name="label" value="{{ old('label', $prefillLabel ?? '') }}" placeholder="Contoh: Instagram Bio, WhatsApp Group" />
            </x-form.group>

            <div>
                <span class="mb-1.5 block text-sm font-medium text-slate-700">Tujuan link</span>
                <div class="flex gap-5 text-sm text-slate-700">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="radio" value="product" x-model="mode" class="text-primary-600 focus:ring-primary-500"> Pilih produk
                    </label>
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="radio" value="custom" x-model="mode" class="text-primary-600 focus:ring-primary-500"> URL custom
                    </label>
                </div>
            </div>

            {{-- Pilih produk --}}
            <div x-show="mode === 'product'" class="space-y-1.5">
                <select x-model="target"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 shadow-sm transition focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                    <option value="">— Pilih produk —</option>
                    <template x-for="p in products" :key="p.url">
                        <option :value="p.url" x-text="p.label + ' — Rp ' + formatPrice(p.price)"></option>
                    </template>
                </select>
                <p x-show="products.length === 0" class="text-xs text-amber-600">Katalog produk belum tersedia. Gunakan URL custom.</p>
            </div>

            {{-- URL custom --}}
            <div x-show="mode === 'custom'" class="space-y-1.5" x-cloak>
                <input type="url" x-model="target" placeholder="https://masfirmanpratama.com/produk/..."
                       class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 shadow-sm transition focus:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500/20" />
                <p class="text-xs text-slate-400">Kosongkan untuk mengarah ke halaman utama store.</p>
            </div>

            {{-- Nilai final yang dikirim ke server --}}
            <input type="hidden" name="target_url" :value="target">

            <div x-show="target" x-cloak class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                Link akan mengarah ke: <span class="font-medium text-slate-700 break-all" x-text="target"></span>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <x-button type="submit" icon="plus">Buat Link</x-button>
                <x-button :href="route('referrals.index')" variant="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>

<script>
    function referralPicker(products, initialTarget) {
        products = products || [];
        initialTarget = initialTarget || '';
        return {
            products,
            target: initialTarget,
            mode: (! initialTarget)
                ? (products.length ? 'product' : 'custom')
                : (products.some(p => p.url === initialTarget) ? 'product' : 'custom'),
            formatPrice(v) {
                return new Intl.NumberFormat('id-ID').format(v || 0);
            },
        };
    }
</script>
@endsection
