@extends('layouts.dashboard')

@section('content')
<x-page-header title="Produk" subtitle="Produk yang bisa Anda promosikan. Pilih satu untuk langsung membuat link referral." />

@if (empty($products))
    <x-card>
        <div class="flex items-center gap-3 text-slate-500">
            <i data-lucide="package-x" class="h-5 w-5 shrink-0"></i>
            <p class="text-sm">Katalog produk belum tersedia saat ini. Silakan coba lagi beberapa saat lagi.</p>
        </div>
    </x-card>
@else
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($products as $p)
            @php $rate = $rates[$p['type']] ?? null; @endphp
            <x-card class="flex flex-col">
                <div class="flex items-start gap-3">
                    @if (! empty($p['image_url']))
                        <img src="{{ $p['image_url'] }}" alt="" loading="lazy"
                             class="h-16 w-16 shrink-0 rounded-lg bg-slate-100 object-cover" />
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-500">
                            <i data-lucide="{{ $p['type'] === 'course' ? 'graduation-cap' : 'book' }}" class="h-6 w-6"></i>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">
                            {{ $p['type'] === 'course' ? 'Kelas' : 'Buku' }}
                        </span>
                        <h3 class="mt-1 font-semibold leading-snug text-slate-800">{{ $p['title'] }}</h3>
                        <p class="text-sm text-slate-500">Rp {{ number_format($p['price'], 0, ',', '.') }}</p>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                    <div>
                        <p class="text-[11px] uppercase tracking-wide text-slate-400">Komisi Anda</p>
                        <p class="text-sm font-bold text-secondary-600">
                            {{ $rate !== null ? rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.').'%' : '—' }}
                        </p>
                    </div>
                    <x-button :href="route('referrals.create', ['target_url' => $p['url'], 'label' => $p['title']])" icon="link" size="sm">
                        Buat Link
                    </x-button>
                </div>

                <a href="{{ $p['url'] }}" target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center gap-1 text-xs text-slate-400 transition hover:text-primary-600">
                    Lihat di store <i data-lucide="external-link" class="h-3 w-3"></i>
                </a>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
