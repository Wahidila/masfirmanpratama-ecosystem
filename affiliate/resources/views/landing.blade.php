@extends('layouts.app')

@php
    $alumni = $types->firstWhere('slug', 'alumni');
    $nonAlumni = $types->firstWhere('slug', 'non-alumni');
    $alumniRate = $alumni->default_commission_rate ?? 15;
    $nonRate = $nonAlumni->default_commission_rate ?? 10;
    $fmtRate = fn ($r) => rtrim(rtrim(number_format($r, 2), '0'), '.');

    $pillars = [
        ['trending-up', 'Komisi nyata', 'Sampai '.$fmtRate($alumniRate).'% per transaksi — dari produk yang memang layak direkomendasikan.'],
        ['package-open', 'Tools lengkap', 'Materi marketing siap pakai, link referral, dan dashboard performa real-time.'],
        ['shield-check', 'Bayaran transparan', 'Komisi ke-track otomatis, cooling adil 7 hari, withdrawal ke bank & e-wallet.'],
    ];
    $steps = [
        ['user-plus', 'Daftar Gratis', 'Pilih tipe affiliator (Alumni atau Non-Alumni). Tanpa biaya, langsung dapat link.'],
        ['share-2', 'Bagikan Link', 'Sebar link referral unik-mu ke grup, sosial media, atau jaringan pribadi.'],
        ['banknote', 'Panen Komisi', 'Setiap pembelian lewat link-mu menghasilkan komisi otomatis. Tarik kapan saja.'],
    ];
    $benefits = [
        ['coins', 'Komisi hingga '.$fmtRate($alumniRate).'%', 'Rate tertinggi di kelasnya untuk setiap kelas & buku yang terjual.'],
        ['folder-open', 'Materi siap pakai', 'Banner, caption, dan aset promosi tinggal unduh — tanpa bikin dari nol.'],
        ['layout-dashboard', 'Dashboard real-time', 'Klik, order, dan komisi ke-track otomatis. Selalu tahu performamu.'],
        ['hourglass', 'Cooling adil 7 hari', 'Komisi terkonfirmasi lalu langsung tersedia untuk ditarik.'],
        ['wallet', 'Withdrawal mudah', 'Cairkan ke rekening bank atau e-wallet. Proses cepat & transparan.'],
        ['trophy', 'Event & leaderboard', 'Ikut tantangan gamifikasi, naik peringkat, raih reward tambahan.'],
    ];
    $testimonials = [
        ['Sari Wulandari', 'Alumni AMC', 'SW', 'Cukup share link di grup alumni, bulan pertama sudah cair Rp 3,2 juta. Materinya tinggal pakai, nggak ribet sama sekali.'],
        ['Rian Pratama', 'Content Creator', 'RP', 'Dashboard-nya jelas, komisi ke-track otomatis, withdrawal ke e-wallet cair cepat. Ini program affiliate paling rapi yang pernah saya ikut.'],
        ['Dewi Anggraini', 'Non-Alumni', 'DA', 'Nggak harus jadi alumni buat ikut. Aku mulai dari nol, sekarang jadi income sampingan yang stabil tiap bulan.'],
    ];
    $faqs = [
        ['Apa itu Program Affiliate MasFirmanPratama.com?', 'Program resmi untuk menyebarkan kelas & buku Mind Power (Alpha Mind Control). Kamu dapat komisi dari setiap transaksi lewat link referral-mu.'],
        ['Siapa yang bisa gabung?', 'Siapa saja — Alumni AMC maupun Non-Alumni. Rate komisi menyesuaikan tipe affiliator-mu.'],
        ['Berapa komisi yang didapat?', 'Mulai '.$fmtRate($nonRate).'% hingga '.$fmtRate($alumniRate).'% per transaksi, tergantung tipe affiliator dan jenis produk.'],
        ['Kapan komisi bisa dicairkan?', 'Setiap komisi melewati masa cooling 7 hari untuk memastikan transaksi valid, lalu statusnya menjadi "tersedia" dan siap ditarik.'],
        ['Ke mana komisi ditransfer?', 'Ke rekening bank atau e-wallet yang kamu daftarkan di profil. Ajukan withdrawal langsung dari dashboard.'],
        ['Apakah ada biaya untuk gabung?', 'Tidak ada. Pendaftaran 100% gratis dan kamu langsung dapat link referral setelah akun aktif.'],
    ];
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,500;1,600&display=swap" rel="stylesheet">
<style>
    .font-display { font-family: 'Playfair Display', Georgia, serif; }

    .soft-grid {
        background-image: linear-gradient(to right, rgba(15,23,42,.04) 1px, transparent 1px),
                          linear-gradient(to bottom, rgba(15,23,42,.04) 1px, transparent 1px);
        background-size: 44px 44px;
        -webkit-mask-image: radial-gradient(ellipse 65% 55% at 50% 40%, #000 30%, transparent 80%);
        mask-image: radial-gradient(ellipse 65% 55% at 50% 40%, #000 30%, transparent 80%);
    }

    @keyframes floaty { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
    .floaty { animation: floaty 6s ease-in-out infinite; }
    .floaty-slow { animation: floaty 8s ease-in-out 1.2s infinite; }

    .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
    .reveal.in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) {
        .reveal { opacity: 1; transform: none; transition: none; }
        .floaty, .floaty-slow { animation: none; }
    }
    input[type=range].calc-range { -webkit-appearance: none; appearance: none; height: 6px; border-radius: 9999px; background: #e2e8f0; }
    input[type=range].calc-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 22px; height: 22px; border-radius: 9999px; background: #4f46e5; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(79,70,229,.4); cursor: pointer; }
    input[type=range].calc-range::-moz-range-thumb { width: 22px; height: 22px; border-radius: 9999px; background: #4f46e5; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(79,70,229,.4); cursor: pointer; }
</style>
@endpush

@section('body')

{{-- ══════════ FLOATING NAVBAR ══════════ --}}
<nav x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 20"
     class="fixed top-4 inset-x-4 z-50 mx-auto max-w-6xl rounded-2xl transition-all duration-300"
     :class="scrolled ? 'bg-white/85 backdrop-blur-xl border border-slate-200/70 shadow-lg shadow-slate-900/5' : 'bg-white/60 backdrop-blur-md border border-white/40'">
    <div class="px-4 sm:px-5">
        <div class="h-14 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary-600 text-white shadow-lg shadow-primary-500/30">
                    <i data-lucide="git-fork" class="w-5 h-5"></i>
                </span>
                <span class="font-bold text-lg tracking-tight text-slate-900">MFP<span class="text-primary-600">Affiliate</span></span>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}" class="px-3 sm:px-4 py-2 text-sm font-medium text-slate-600 hover:text-primary-600 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-lg">Masuk</a>
                <a href="{{ route('register') }}" class="ripple inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-xl text-sm font-semibold bg-accent-500 text-white hover:bg-accent-600 shadow-lg shadow-accent-500/30 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2">
                    Daftar <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- ══════════ HERO ══════════ --}}
<header class="relative overflow-hidden bg-gradient-to-b from-primary-50/70 via-white to-white pt-28 pb-16 lg:pt-36 lg:pb-24">
    <div class="absolute inset-0 soft-grid" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-24 -left-24 w-[32rem] h-[32rem] rounded-full bg-primary-200/50 blur-3xl"></div>
        <div class="absolute top-10 -right-24 w-[30rem] h-[30rem] rounded-full bg-secondary-200/40 blur-3xl"></div>
    </div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
            {{-- copy --}}
            <div class="text-center lg:text-left">
                <span class="reveal inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-slate-200/80 shadow-sm text-slate-600 text-xs sm:text-sm font-medium">
                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-secondary-500"></span></span>
                    Affiliate Program · MasFirmanPratama.com
                </span>

                <h1 class="reveal mt-6 text-4xl sm:text-5xl lg:text-6xl font-bold text-slate-900 leading-[1.08] tracking-tight">
                    <span class="font-display font-semibold">Bagikan ilmu Mind Power,</span>
                    <span class="font-display italic font-semibold text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-secondary-600"> panen komisinya.</span>
                </h1>

                <p class="reveal mt-6 text-lg text-slate-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                    Promosikan kelas &amp; buku Alpha Mind Control, dapatkan komisi hingga
                    <span class="font-semibold text-slate-900">{{ $fmtRate($alumniRate) }}%</span> per transaksi —
                    lengkap dengan materi marketing, dashboard real-time, dan withdrawal cepat.
                </p>

                <div class="reveal mt-8 flex flex-col sm:flex-row items-center lg:items-start justify-center lg:justify-start gap-3.5">
                    <a href="{{ route('register') }}" class="ripple group inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-accent-500 text-white font-semibold hover:bg-accent-600 shadow-lg shadow-accent-500/30 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 w-full sm:w-auto">
                        Gabung Gratis Sekarang <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i>
                    </a>
                    <a href="#kalkulator" class="inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-xl bg-white text-slate-700 font-medium border border-slate-200 hover:border-slate-300 hover:bg-slate-50 shadow-sm transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 w-full sm:w-auto">
                        <i data-lucide="calculator" class="w-5 h-5 text-primary-600"></i> Hitung potensimu
                    </a>
                </div>

                {{-- inline social proof --}}
                <div class="reveal mt-9 flex flex-col sm:flex-row items-center lg:items-start gap-4 sm:gap-5">
                    <div class="flex -space-x-3">
                        @foreach (['bg-primary-500','bg-secondary-500','bg-accent-500','bg-rose-400'] as $i => $c)
                            <span class="flex items-center justify-center w-10 h-10 rounded-full {{ $c }} text-white text-xs font-bold ring-2 ring-white">{{ ['SW','RP','DA','+' ][$i] }}</span>
                        @endforeach
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="flex items-center justify-center sm:justify-start gap-0.5 text-accent-400">
                            @for ($s = 0; $s < 5; $s++)<i data-lucide="star" class="w-4 h-4 fill-current"></i>@endfor
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Dipercaya komunitas affiliator MasFirmanPratama</p>
                    </div>
                </div>
            </div>

            {{-- credibility visual --}}
            <div class="reveal relative hidden lg:block h-[26rem]" aria-hidden="true">
                {{-- main earnings card --}}
                <div class="floaty absolute top-6 right-2 w-72 rounded-3xl bg-white border border-slate-100 shadow-2xl shadow-slate-900/10 p-6">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-500">Komisi bulan ini</span>
                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-secondary-600 bg-secondary-50 px-2 py-0.5 rounded-full"><i data-lucide="trending-up" class="w-3.5 h-3.5"></i> +24%</span>
                    </div>
                    <p class="mt-2 font-display text-4xl font-semibold text-slate-900">Rp 4.850.000</p>
                    <div class="mt-5 flex items-end gap-1.5 h-16">
                        @foreach ([40,55,48,70,62,85,100] as $h)
                            <span class="flex-1 rounded-t-md bg-gradient-to-t from-primary-200 to-primary-500" style="height: {{ $h }}%"></span>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-primary-50 text-primary-700 font-medium"><i data-lucide="sparkles" class="w-3.5 h-3.5"></i> Alumni · {{ $fmtRate($alumniRate) }}%</span>
                    </div>
                </div>
                {{-- testimonial snippet --}}
                <div class="floaty-slow absolute bottom-4 left-0 w-64 rounded-2xl bg-white border border-slate-100 shadow-xl shadow-slate-900/10 p-5">
                    <div class="flex items-center gap-0.5 text-accent-400 mb-2">@for ($s = 0; $s < 5; $s++)<i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>@endfor</div>
                    <p class="text-sm text-slate-700 font-medium leading-snug">“Bulan pertama sudah cair Rp 3,2 juta.”</p>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="flex items-center justify-center w-7 h-7 rounded-full bg-primary-600 text-white text-[10px] font-bold">SW</span>
                        <span class="text-xs text-slate-500">Sari · Alumni AMC</span>
                    </div>
                </div>
                {{-- payout toast --}}
                <div class="floaty absolute top-0 left-8 inline-flex items-center gap-2.5 rounded-xl bg-slate-900 text-white shadow-xl px-4 py-3">
                    <span class="flex items-center justify-center w-7 h-7 rounded-full bg-secondary-500"><i data-lucide="check" class="w-4 h-4"></i></span>
                    <div class="leading-tight"><p class="text-xs font-semibold">Withdrawal berhasil</p><p class="text-[11px] text-slate-400">Rp 750.000 · ke BCA</p></div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ══════════ CREDIBILITY STAT BAR ══════════ --}}
<section class="relative z-10 -mt-6 pb-4">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal grid grid-cols-2 md:grid-cols-4 gap-px rounded-3xl bg-slate-200/70 overflow-hidden border border-slate-200/70 shadow-sm">
            @php
                $statCards = [
                    ['value' => $fmtRate($alumniRate).'%', 'label' => 'Komisi hingga', 'count' => null],
                    ['value' => '7', 'label' => 'Hari masa cooling', 'count' => 7],
                    ['value' => 'Gratis', 'label' => 'Biaya gabung', 'count' => null],
                    ['value' => $stats['total_affiliators'] > 0 ? (int) $stats['total_affiliators'] : '2', 'label' => 'Affiliator aktif', 'count' => $stats['total_affiliators'] > 0 ? (int) $stats['total_affiliators'] : null],
                ];
            @endphp
            @foreach ($statCards as $sc)
                <div class="bg-white px-5 py-6 text-center">
                    <p class="font-display text-3xl sm:text-4xl font-semibold text-slate-900"
                       @if ($sc['count'] !== null) data-count="{{ $sc['count'] }}" data-format="int" @endif>{{ $sc['value'] }}</p>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500">{{ $sc['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ PROBLEM → SOLUTION ══════════ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal max-w-2xl mx-auto text-center mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kenapa Ini Untukmu</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 leading-tight">Punya pengaruh, tapi belum menghasilkan darinya?</h2>
            <p class="mt-4 text-slate-600">Kamu sudah merekomendasikan hal-hal baik ke orang sekitarmu. Sekarang saatnya rekomendasi itu berbuah komisi — dengan sistem yang rapi dan transparan.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($pillars as [$icon, $title, $desc])
                <div class="reveal rounded-3xl border border-slate-100 bg-slate-50/60 p-8 hover:bg-white hover:shadow-lg hover:shadow-slate-900/5 transition-all duration-200">
                    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-white border border-slate-100 text-primary-600 shadow-sm mb-5"><i data-lucide="{{ $icon }}" class="w-7 h-7"></i></span>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ CARA KERJA ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-16">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Cara Kerja</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Tiga langkah, mulai menghasilkan</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8">
            @foreach ($steps as $idx => [$icon, $title, $desc])
                <div class="reveal rounded-3xl border border-slate-100 bg-white p-8 shadow-sm hover-lift">
                    <div class="flex items-center justify-between mb-6">
                        <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-50 text-primary-600"><i data-lucide="{{ $icon }}" class="w-7 h-7"></i></span>
                        <span class="font-display text-5xl font-semibold text-slate-100">0{{ $idx + 1 }}</span>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ KALKULATOR ══════════ --}}
<section id="kalkulator" class="py-20 lg:py-28 bg-white border-t border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kalkulator</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Berapa yang bisa kamu hasilkan?</h2>
            <p class="mt-3 text-slate-600">Geser dan lihat estimasi komisimu. Ini simulasi — potensimu bisa lebih.</p>
        </div>
        <div class="reveal" x-data="{
            referrals: 10, aov: 500000, rate: {{ $alumniRate }}, tier: 'alumni',
            setTier(t, r) { this.tier = t; this.rate = r; },
            get monthly() { return this.referrals * this.aov * this.rate / 100; },
            get yearly() { return this.monthly * 12; },
            fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
        }">
            <div class="grid lg:grid-cols-5 gap-6 items-stretch">
                <div class="lg:col-span-3 rounded-3xl bg-slate-50 border border-slate-100 p-6 sm:p-8">
                    <div class="mb-8">
                        <p class="text-sm font-medium text-slate-700 mb-3">Tipe affiliator</p>
                        <div class="grid grid-cols-2 gap-2 p-1 bg-white rounded-2xl border border-slate-100">
                            <button type="button" @click="setTier('alumni', {{ $alumniRate }})" class="py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer" :class="tier === 'alumni' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'">Alumni · {{ $fmtRate($alumniRate) }}%</button>
                            <button type="button" @click="setTier('non', {{ $nonRate }})" class="py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer" :class="tier === 'non' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700'">Non-Alumni · {{ $fmtRate($nonRate) }}%</button>
                        </div>
                    </div>
                    <div class="mb-8">
                        <div class="flex items-baseline justify-between mb-3">
                            <label for="calc-referrals" class="text-sm font-medium text-slate-700">Pembelian per bulan</label>
                            <span class="font-display text-2xl font-semibold text-slate-900"><span x-text="referrals"></span>×</span>
                        </div>
                        <input id="calc-referrals" type="range" min="1" max="50" x-model.number="referrals" class="calc-range w-full accent-primary-600">
                        <div class="flex justify-between text-xs text-slate-400 mt-2"><span>1</span><span>50</span></div>
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between mb-3">
                            <label for="calc-aov" class="text-sm font-medium text-slate-700">Rata-rata nilai order</label>
                            <span class="font-display text-2xl font-semibold text-slate-900" x-text="fmt(aov)"></span>
                        </div>
                        <input id="calc-aov" type="range" min="100000" max="5000000" step="50000" x-model.number="aov" class="calc-range w-full accent-primary-600">
                        <div class="flex justify-between text-xs text-slate-400 mt-2"><span>Rp 100rb</span><span>Rp 5jt</span></div>
                    </div>
                </div>
                <div class="lg:col-span-2 relative overflow-hidden rounded-3xl bg-primary-600 p-6 sm:p-8 flex flex-col justify-center text-center">
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true"><div class="absolute -top-16 -right-10 w-64 h-64 rounded-full bg-white/10 blur-2xl"></div><div class="absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-secondary-400/30 blur-2xl"></div></div>
                    <div class="relative z-10">
                        <p class="text-sm text-primary-100">Estimasi komisi per bulan</p>
                        <p class="font-display text-4xl sm:text-5xl font-semibold text-white mt-2 leading-tight break-words" x-text="fmt(monthly)"></p>
                        <div class="mt-6 pt-6 border-t border-white/15">
                            <p class="text-sm text-primary-100">Setahun bisa mencapai</p>
                            <p class="font-display text-2xl font-semibold text-accent-300 mt-1 break-words" x-text="fmt(yearly)"></p>
                        </div>
                        <a href="{{ route('register') }}" class="ripple mt-8 inline-flex items-center justify-center gap-2 w-full py-3.5 rounded-xl bg-accent-500 text-white font-semibold hover:bg-accent-600 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Mulai hasilkan ini <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════ TIPE AFFILIATOR ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Pilih Jalurmu</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Satu program, dua keuntungan</h2>
            <p class="mt-3 text-slate-600">Alumni atau bukan, semua bisa menghasilkan. Rate menyesuaikan profilmu.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            @foreach ($types as $type)
                @php $isAlumni = $type->slug === 'alumni'; @endphp
                <div class="reveal relative flex flex-col rounded-3xl p-8 shadow-sm hover-lift {{ $isAlumni ? 'bg-primary-600 text-white ring-1 ring-primary-500' : 'bg-white border border-slate-200 text-slate-900' }}">
                    @if ($isAlumni)
                        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl" aria-hidden="true"><div class="absolute -top-16 -right-12 w-52 h-52 rounded-full bg-white/10 blur-2xl"></div></div>
                        <span class="relative z-10 self-start inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-accent-500 text-white text-xs font-semibold mb-5"><i data-lucide="crown" class="w-3.5 h-3.5"></i> Paling menguntungkan</span>
                    @endif
                    <div class="relative z-10 flex items-center gap-3 mb-4 {{ $isAlumni ? '' : 'mt-1' }}">
                        <span class="flex items-center justify-center w-11 h-11 rounded-xl {{ $isAlumni ? 'bg-white/15 text-accent-300' : 'bg-primary-100 text-primary-600' }}"><i data-lucide="{{ $isAlumni ? 'sparkles' : 'star' }}" class="w-5 h-5"></i></span>
                        <h3 class="text-xl font-semibold">{{ $type->name }}</h3>
                    </div>
                    <p class="relative z-10 text-sm mb-6 {{ $isAlumni ? 'text-primary-100' : 'text-slate-500' }}">{{ $type->description }}</p>
                    <div class="relative z-10 mb-6"><span class="font-display text-5xl font-semibold {{ $isAlumni ? 'text-white' : 'text-primary-600' }}">{{ $fmtRate($type->default_commission_rate) }}%</span><span class="text-sm {{ $isAlumni ? 'text-primary-200' : 'text-slate-400' }}"> komisi</span></div>
                    @if ($type->benefits)
                        <ul class="relative z-10 space-y-3 mb-8 flex-1">
                            @foreach ($type->benefits as $benefit)
                                <li class="flex items-start gap-2.5 text-sm {{ $isAlumni ? 'text-primary-50' : 'text-slate-600' }}"><i data-lucide="check" class="w-4 h-4 mt-0.5 shrink-0 {{ $isAlumni ? 'text-accent-300' : 'text-secondary-500' }}"></i><span>{{ $benefit }}</span></li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('register') }}?type={{ $type->id }}" class="ripple relative z-10 mt-auto inline-flex items-center justify-center gap-2 w-full py-3.5 rounded-xl font-semibold transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 {{ $isAlumni ? 'bg-white text-primary-700 hover:bg-slate-100 focus-visible:ring-white' : 'bg-primary-600 text-white hover:bg-primary-700 focus-visible:ring-primary-500' }}">Pilih {{ $type->name }} <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ TESTIMONI (social proof centerpiece) ══════════ --}}
<section class="py-20 lg:py-28 bg-white border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kata Mereka</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Sudah dibuktikan affiliator kami</h2>
            <div class="mt-4 inline-flex items-center gap-2 text-sm text-slate-500">
                <span class="flex items-center gap-0.5 text-accent-400">@for ($s = 0; $s < 5; $s++)<i data-lucide="star" class="w-4 h-4 fill-current"></i>@endfor</span>
                <span>Cerita nyata dari komunitas affiliator</span>
            </div>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($testimonials as $idx => [$name, $role, $initial, $quote])
                <figure class="reveal relative rounded-3xl bg-slate-50 border border-slate-100 p-8 {{ $idx === 1 ? 'md:-translate-y-4 md:shadow-lg md:shadow-slate-900/5' : '' }}">
                    <div class="flex items-center gap-0.5 text-accent-400 mb-4">@for ($s = 0; $s < 5; $s++)<i data-lucide="star" class="w-4 h-4 fill-current"></i>@endfor</div>
                    <blockquote class="text-slate-700 leading-relaxed font-medium mb-6">“{{ $quote }}”</blockquote>
                    <figcaption class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-11 h-11 rounded-full bg-primary-600 text-white font-semibold text-sm">{{ $initial }}</span>
                        <div><p class="font-semibold text-slate-900 text-sm">{{ $name }}</p><p class="text-xs text-primary-600 font-medium">{{ $role }}</p></div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ BENEFIT ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kenapa Gabung</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Semua yang kamu butuhkan untuk sukses</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($benefits as [$icon, $title, $desc])
                <div class="reveal group rounded-2xl bg-white border border-slate-100 p-6 shadow-sm hover-lift">
                    <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 text-primary-600 mb-5 transition-colors group-hover:bg-primary-600 group-hover:text-white"><i data-lucide="{{ $icon }}" class="w-6 h-6"></i></span>
                    <h3 class="text-base font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ FAQ ══════════ --}}
<section class="py-20 lg:py-28 bg-white border-t border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">FAQ</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900">Masih ada pertanyaan?</h2>
        </div>
        <div class="reveal space-y-3" x-data="{ open: 0 }">
            @foreach ($faqs as $i => [$q, $a])
                <div class="rounded-2xl bg-slate-50 border border-slate-100 overflow-hidden">
                    <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}" class="w-full flex items-center justify-between gap-4 text-left px-6 py-5 cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500 rounded-2xl" :aria-expanded="open === {{ $i }}">
                        <span class="font-semibold text-slate-900">{{ $q }}</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-300" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak><p class="px-6 pb-5 text-sm text-slate-500 leading-relaxed">{{ $a }}</p></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ CTA FINAL ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-primary-600 px-8 py-16 md:px-16 md:py-20 text-center">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true"><div class="absolute -top-24 -right-24 w-[28rem] h-[28rem] rounded-full bg-white/10 blur-3xl"></div><div class="absolute -bottom-24 -left-24 w-[28rem] h-[28rem] rounded-full bg-accent-500/25 blur-3xl"></div></div>
            <div class="relative z-10">
                <h2 class="font-display text-3xl md:text-5xl font-semibold text-white leading-tight">Ilmumu berharga.<br><span class="italic text-accent-300">Sekarang bisa berbuah komisi.</span></h2>
                <p class="mt-5 text-primary-100 max-w-xl mx-auto">Daftar gratis, dapatkan link referral pertamamu dalam hitungan menit.</p>
                <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="ripple group inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-accent-500 text-white font-semibold hover:bg-accent-600 shadow-xl shadow-accent-900/20 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Daftar Gratis Sekarang <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i></a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl border border-white/25 text-white font-medium hover:bg-white/10 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">Sudah punya akun? Masuk</a>
                </div>
            </div>
        </div>
    </div>
</section>

<x-marketing.footer />
@endsection

@push('scripts')
<script>
    (function () {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const reveals = document.querySelectorAll('.reveal');
        if (reduce || !('IntersectionObserver' in window)) {
            reveals.forEach(el => el.classList.add('in'));
        } else {
            const io = new IntersectionObserver((es) => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }), { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            reveals.forEach(el => io.observe(el));
        }
        const fmt = (n, kind) => kind === 'currency' ? 'Rp ' + Math.round(n).toLocaleString('id-ID') : Math.round(n).toLocaleString('id-ID');
        const run = (el) => {
            const target = parseInt(el.dataset.count, 10) || 0, kind = el.dataset.format || 'int';
            if (reduce) { el.textContent = fmt(target, kind); return; }
            const dur = 1400, start = performance.now();
            const tick = (now) => { const p = Math.min((now - start) / dur, 1); el.textContent = fmt(target * (1 - Math.pow(1 - p, 3)), kind); if (p < 1) requestAnimationFrame(tick); };
            requestAnimationFrame(tick);
        };
        const counters = document.querySelectorAll('[data-count]');
        if ('IntersectionObserver' in window) {
            const cio = new IntersectionObserver((es) => es.forEach(e => { if (e.isIntersecting) { run(e.target); cio.unobserve(e.target); } }), { threshold: 0.5 });
            counters.forEach(el => cio.observe(el));
        } else { counters.forEach(run); }
    })();
</script>
@endpush
