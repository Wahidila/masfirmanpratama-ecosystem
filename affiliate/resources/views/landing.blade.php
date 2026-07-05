@extends('layouts.app')

@section('body')
<div class="min-h-screen">
    {{-- Hero Section --}}
    <header class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-primary-900 to-slate-900">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-20 left-10 w-72 h-72 bg-primary-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-20 right-10 w-72 h-72 bg-secondary-500 rounded-full mix-blend-multiply filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <nav class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between">
            <span class="text-xl font-bold text-white">MFP <span class="text-secondary-400">Affiliate</span></span>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-slate-300 hover:text-white transition">Masuk</a>
                <a href="{{ route('register') }}" class="text-sm px-4 py-2 bg-primary-600 text-white rounded-full hover:bg-primary-500 transition">Daftar</a>
            </div>
        </nav>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32 text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight">
                Hasilkan Komisi dari<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-secondary-400 to-primary-400">Setiap Referral</span>
            </h1>
            <p class="mt-6 text-lg text-slate-300 max-w-2xl mx-auto">
                Bergabunglah dengan program affiliate MasFirmanPratama.com. Promosikan produk kelas & buku Mind Power, dapatkan komisi hingga 15% per transaksi.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="px-8 py-3.5 bg-primary-600 text-white font-semibold rounded-full hover:bg-primary-500 transition shadow-lg shadow-primary-500/30">
                    Gabung Sekarang — Gratis
                </a>
                <a href="#cara-kerja" class="px-8 py-3.5 border border-slate-600 text-slate-300 font-medium rounded-full hover:bg-slate-800 transition">
                    Cara Kerjanya
                </a>
            </div>

            @if($stats['total_affiliators'] > 0)
            <div class="mt-16 flex items-center justify-center gap-8 text-center">
                <div>
                    <p class="text-2xl font-bold text-white">{{ number_format($stats['total_affiliators']) }}</p>
                    <p class="text-sm text-slate-400">Affiliator Aktif</p>
                </div>
                <div class="w-px h-10 bg-slate-700"></div>
                <div>
                    <p class="text-2xl font-bold text-white">Rp {{ number_format($stats['total_commissions_paid'], 0, ',', '.') }}</p>
                    <p class="text-sm text-slate-400">Total Komisi Dibayar</p>
                </div>
            </div>
            @endif
        </div>
    </header>

    {{-- Cara Kerja --}}
    <section id="cara-kerja" class="py-20 lg:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-800">Cara Kerja</h2>
                <p class="mt-3 text-slate-500">3 langkah mudah untuk mulai menghasilkan</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-primary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="user-plus" class="w-8 h-8 text-primary-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">1. Daftar Gratis</h3>
                    <p class="text-slate-500 text-sm">Pilih tipe affiliator sesuai profil Anda (Alumni atau Non-Alumni)</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-secondary-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="share-2" class="w-8 h-8 text-secondary-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">2. Bagikan Link</h3>
                    <p class="text-slate-500 text-sm">Dapatkan link referral unik dan bagikan ke jaringan Anda</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-accent-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="banknote" class="w-8 h-8 text-accent-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-800 mb-2">3. Dapatkan Komisi</h3>
                    <p class="text-slate-500 text-sm">Setiap pembelian melalui link Anda menghasilkan komisi otomatis</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Tipe Affiliator --}}
    <section class="py-20 lg:py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-slate-800">Pilih Tipe Affiliator</h2>
                <p class="mt-3 text-slate-500">Sesuaikan dengan profil dan keuntungan yang Anda inginkan</p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                @foreach($types as $type)
                <div class="bg-white rounded-2xl border border-slate-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                            <i data-lucide="star" class="w-5 h-5 text-primary-600"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-800">{{ $type->name }}</h3>
                    </div>
                    <p class="text-slate-500 text-sm mb-4">{{ $type->description }}</p>
                    <div class="mb-4">
                        <span class="text-2xl font-bold text-primary-600">{{ $type->default_commission_rate }}%</span>
                        <span class="text-sm text-slate-400"> komisi</span>
                    </div>
                    @if($type->benefits)
                    <ul class="space-y-2 mb-6">
                        @foreach($type->benefits as $benefit)
                        <li class="flex items-center gap-2 text-sm text-slate-600">
                            <i data-lucide="check" class="w-4 h-4 text-secondary-500"></i>
                            {{ $benefit }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    <a href="{{ route('register') }}?type={{ $type->id }}"
                       class="block text-center py-2.5 border border-primary-200 text-primary-600 font-medium rounded-xl hover:bg-primary-50 transition">
                        Pilih {{ $type->name }}
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-20 lg:py-24 bg-gradient-to-r from-primary-600 to-primary-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Siap Mulai Menghasilkan?</h2>
            <p class="text-primary-100 mb-8">Daftar sekarang dan dapatkan link referral pertama Anda dalam hitungan menit.</p>
            <a href="{{ route('register') }}" class="inline-block px-8 py-3.5 bg-white text-primary-700 font-semibold rounded-full hover:bg-primary-50 transition shadow-lg">
                Daftar Gratis Sekarang
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 bg-slate-900 text-center">
        <p class="text-sm text-slate-400">&copy; {{ date('Y') }} MasFirmanPratama.com — Program Affiliate Mind Power & Life Mastery</p>
    </footer>
</div>
@endsection
