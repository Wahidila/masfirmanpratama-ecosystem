@extends('layouts.app')

@section('body')
<x-marketing.navbar />

<main>
    {{-- Hero --}}
    <header class="relative overflow-hidden bg-slate-50 pt-28 pb-20 lg:pt-36 lg:pb-28">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -top-16 -left-16 w-96 h-96 bg-primary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-10 -right-16 w-96 h-96 bg-secondary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-200"></div>
            <div class="absolute -bottom-24 left-1/3 w-96 h-96 bg-accent-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-400"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 border border-primary-100 text-primary-700 text-sm font-medium mb-6">
                <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
                Affiliate Program MasFirmanPratama.com
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight">
                Hasilkan Komisi dari
                <span class="text-gradient block mt-2 pb-2">Setiap Referral</span>
            </h1>
            <p class="mt-6 text-lg text-slate-600 max-w-2xl mx-auto">
                Promosikan produk kelas &amp; buku Mind Power MasFirmanPratama.com, dapatkan komisi hingga <span class="font-semibold text-slate-800">15%</span> per transaksi.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <x-button :href="route('register')" size="lg" icon="arrow-right" iconPosition="right">Gabung Sekarang — Gratis</x-button>
                <x-button href="#cara-kerja" variant="outline" size="lg" icon="play">Cara Kerjanya</x-button>
            </div>

            @if ($stats['total_affiliators'] > 0)
                <div class="mt-16 inline-flex items-center gap-8 px-8 py-5 rounded-2xl glass shadow-sm">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_affiliators']) }}</p>
                        <p class="text-sm text-slate-500">Affiliator Aktif</p>
                    </div>
                    <div class="w-px h-10 bg-slate-200"></div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-900">Rp {{ number_format($stats['total_commissions_paid'], 0, ',', '.') }}</p>
                        <p class="text-sm text-slate-500">Total Komisi Dibayar</p>
                    </div>
                </div>
            @endif
        </div>
    </header>

    {{-- Cara Kerja --}}
    <section id="cara-kerja" class="py-20 lg:py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Cara Kerja</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">3 langkah mudah mulai menghasilkan</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach ([
                    ['user-plus', 'primary', '1. Daftar Gratis', 'Pilih tipe affiliator sesuai profil Anda (Alumni atau Non-Alumni).'],
                    ['share-2', 'secondary', '2. Bagikan Link', 'Dapatkan link referral unik dan bagikan ke jaringan Anda.'],
                    ['banknote', 'accent', '3. Dapatkan Komisi', 'Setiap pembelian melalui link Anda menghasilkan komisi otomatis.'],
                ] as [$icon, $tone, $title, $desc])
                    <div class="text-center p-6 rounded-2xl border border-slate-100 bg-white hover-lift">
                        <div class="w-16 h-16 bg-{{ $tone }}-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="{{ $icon }}" class="w-8 h-8 text-{{ $tone }}-600"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                        <p class="text-slate-500 text-sm">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Tipe Affiliator --}}
    <section class="py-20 lg:py-24 bg-slate-50 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Tipe Affiliator</p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900">Pilih yang sesuai profil Anda</h2>
                <p class="mt-3 text-slate-600">Setiap tipe punya rate komisi dan benefit tersendiri.</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                @foreach ($types as $type)
                    <div class="flex flex-col bg-white rounded-2xl border border-slate-100 p-6 shadow-sm hover-lift">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="w-11 h-11 bg-primary-100 rounded-xl flex items-center justify-center text-primary-600">
                                <i data-lucide="star" class="w-5 h-5"></i>
                            </span>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $type->name }}</h3>
                        </div>
                        <p class="text-slate-500 text-sm mb-4">{{ $type->description }}</p>
                        <div class="mb-4">
                            <span class="text-3xl font-extrabold text-primary-600">{{ $type->default_commission_rate }}%</span>
                            <span class="text-sm text-slate-400"> komisi</span>
                        </div>
                        @if ($type->benefits)
                            <ul class="space-y-2 mb-6 flex-1">
                                @foreach ($type->benefits as $benefit)
                                    <li class="flex items-center gap-2 text-sm text-slate-600">
                                        <i data-lucide="check" class="w-4 h-4 text-secondary-500 shrink-0"></i>
                                        {{ $benefit }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <x-button :href="route('register').'?type='.$type->id" variant="outline" class="w-full mt-auto">Pilih {{ $type->name }}</x-button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-24 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative overflow-hidden rounded-[2rem] bg-primary-900 px-8 py-14 md:px-16 md:py-16 text-center shadow-xl">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-600 rounded-full mix-blend-multiply filter blur-3xl opacity-50"></div>
                    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-accent-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
                </div>
                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-4">Siap Mulai Menghasilkan?</h2>
                    <p class="text-primary-100 mb-8 max-w-xl mx-auto">Daftar sekarang dan dapatkan link referral pertama Anda dalam hitungan menit.</p>
                    <x-button :href="route('register')" variant="accent" size="lg" icon="arrow-right" iconPosition="right">Daftar Gratis Sekarang</x-button>
                </div>
            </div>
        </div>
    </section>
</main>

<x-marketing.footer />
@endsection
