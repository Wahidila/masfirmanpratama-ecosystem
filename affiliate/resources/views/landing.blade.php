@extends('layouts.app')

@php
    $alumni = $types->firstWhere('slug', 'alumni');
    $nonAlumni = $types->firstWhere('slug', 'non-alumni');
    $alumniRate = $alumni->default_commission_rate ?? 15;
    $nonRate = $nonAlumni->default_commission_rate ?? 10;

    $benefits = [
        ['coins', 'Komisi hingga '.rtrim(rtrim(number_format($alumniRate, 2), '0'), '.').'%', 'Rate tertinggi di kelasnya untuk setiap kelas & buku yang terjual lewat link-mu.'],
        ['folder-open', 'Materi marketing siap pakai', 'Banner, caption, dan aset promosi tinggal unduh — tanpa perlu bikin dari nol.'],
        ['layout-dashboard', 'Dashboard real-time', 'Klik, order, dan komisi ke-track otomatis. Selalu tahu performa link-mu.'],
        ['calendar-check', 'Cooling adil 7 hari', 'Komisi terkonfirmasi setelah masa cooling, lalu langsung tersedia untuk ditarik.'],
        ['wallet', 'Withdrawal mudah', 'Cairkan ke rekening bank atau e-wallet favoritmu. Proses cepat & transparan.'],
        ['trophy', 'Event & leaderboard', 'Ikut tantangan gamifikasi, naik peringkat, dan raih reward tambahan.'],
    ];

    $steps = [
        ['user-plus', 'Daftar Gratis', 'Pilih tipe affiliator (Alumni atau Non-Alumni). Tanpa biaya, langsung punya link.'],
        ['share-2', 'Bagikan Link', 'Sebar link referral unik-mu ke grup, sosmed, atau jaringan pribadi.'],
        ['banknote', 'Panen Komisi', 'Setiap pembelian lewat link-mu menghasilkan komisi otomatis. Tarik kapan saja.'],
    ];

    $testimonials = [
        ['Sari Wulandari', 'Alumni AMC', 'S', 'Cukup share link di grup alumni, bulan pertama sudah cair Rp 3,2 juta. Materinya tinggal pakai, nggak ribet.'],
        ['Rian Pratama', 'Content Creator', 'R', 'Dashboard-nya jelas, komisi ke-track otomatis, dan withdrawal ke e-wallet cair cepat. Recommended.'],
        ['Dewi Anggraini', 'Non-Alumni', 'D', 'Nggak harus alumni buat ikut. Aku mulai dari nol, sekarang jadi income sampingan yang stabil tiap bulan.'],
    ];

    $faqs = [
        ['Apa itu Program Affiliate MasFirmanPratama.com?', 'Program resmi untuk membantu menyebarkan kelas & buku Mind Power (Alpha Mind Control). Kamu dapat komisi dari setiap transaksi lewat link referral-mu.'],
        ['Siapa yang bisa gabung?', 'Siapa saja — baik Alumni AMC maupun Non-Alumni. Rate komisi menyesuaikan tipe affiliator-mu.'],
        ['Berapa komisi yang didapat?', 'Mulai dari '.rtrim(rtrim(number_format($nonRate, 2), '0'), '.').'% hingga '.rtrim(rtrim(number_format($alumniRate, 2), '0'), '.').'% per transaksi, tergantung tipe affiliator dan jenis produk.'],
        ['Kapan komisi bisa dicairkan?', 'Setiap komisi melewati masa cooling 7 hari untuk memastikan transaksi valid, lalu statusnya menjadi "tersedia" dan siap ditarik.'],
        ['Ke mana komisi ditransfer?', 'Ke rekening bank atau e-wallet yang kamu daftarkan di profil. Kamu tinggal ajukan withdrawal dari dashboard.'],
        ['Apakah ada biaya untuk gabung?', 'Tidak ada. Pendaftaran 100% gratis dan kamu langsung mendapat link referral setelah akun aktif.'],
    ];
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700;9..144,900&display=swap" rel="stylesheet">
<style>
    .font-display { font-family: 'Fraunces', Georgia, 'Times New Roman', serif; font-optical-sizing: auto; }

    /* Aurora drift on the dark hero */
    @keyframes auroraDrift {
        0%, 100% { transform: translate3d(0,0,0) scale(1); }
        50% { transform: translate3d(4%, -3%, 0) scale(1.12); }
    }
    .aurora { animation: auroraDrift 16s ease-in-out infinite; }
    .aurora-2 { animation: auroraDrift 20s ease-in-out infinite reverse; }
    .aurora-3 { animation: auroraDrift 24s ease-in-out infinite; }

    /* Faint dotted grid with radial fade */
    .hero-grid {
        background-image: radial-gradient(circle at center, rgba(255,255,255,.07) 1px, transparent 1.4px);
        background-size: 30px 30px;
        -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 40%, transparent 100%);
        mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 40%, transparent 100%);
    }

    /* Grain overlay */
    .grain::after {
        content: '';
        position: absolute; inset: 0; pointer-events: none; z-index: 1;
        opacity: .5; mix-blend-mode: overlay;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.4'/%3E%3C/svg%3E");
    }

    /* Scroll reveal */
    .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s cubic-bezier(.16,1,.3,1), transform .7s cubic-bezier(.16,1,.3,1); }
    .reveal.in { opacity: 1; transform: none; }
    @media (prefers-reduced-motion: reduce) { .reveal { opacity: 1; transform: none; transition: none; } .aurora, .aurora-2, .aurora-3 { animation: none; } }

    /* Trust marquee */
    @keyframes marquee { to { transform: translateX(-50%); } }
    .marquee-track { animation: marquee 32s linear infinite; }

    /* Range slider */
    input[type=range].calc-range { -webkit-appearance: none; appearance: none; height: 6px; border-radius: 9999px; background: #e2e8f0; }
    input[type=range].calc-range::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 22px; height: 22px; border-radius: 9999px; background: #4f46e5; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(79,70,229,.4); cursor: pointer; }
    input[type=range].calc-range::-moz-range-thumb { width: 22px; height: 22px; border-radius: 9999px; background: #4f46e5; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(79,70,229,.4); cursor: pointer; }
</style>
@endpush

@section('body')

{{-- ══════════ NAVBAR ══════════ --}}
<nav x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 24"
     class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
     :class="scrolled ? 'bg-white/85 backdrop-blur-xl border-b border-slate-100 shadow-sm' : 'bg-transparent'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <span class="flex items-center justify-center w-9 h-9 rounded-xl bg-primary-600 text-white shadow-lg shadow-primary-500/30">
                    <i data-lucide="git-fork" class="w-5 h-5"></i>
                </span>
                <span class="font-bold text-lg tracking-tight transition-colors" :class="scrolled ? 'text-slate-900' : 'text-white'">
                    MFP<span class="text-primary-400" :class="scrolled ? 'text-primary-600' : 'text-primary-400'">Affiliate</span>
                </span>
            </a>
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}"
                   class="px-3 sm:px-4 py-2 text-sm font-medium transition-colors"
                   :class="scrolled ? 'text-slate-600 hover:text-primary-600' : 'text-slate-200 hover:text-white'">Masuk</a>
                <a href="{{ route('register') }}"
                   class="ripple inline-flex items-center gap-1.5 px-4 sm:px-5 py-2.5 rounded-full text-sm font-semibold bg-primary-600 text-white hover:bg-primary-500 shadow-lg shadow-primary-500/30 transition">
                    Daftar <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

{{-- ══════════ HERO (dark cosmic) ══════════ --}}
<header class="relative overflow-hidden bg-slate-950 grain pt-32 pb-24 lg:pt-40 lg:pb-32">
    {{-- aurora field --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="aurora absolute -top-40 -left-24 w-[42rem] h-[42rem] rounded-full bg-primary-600/30 blur-[130px]"></div>
        <div class="aurora-2 absolute -top-10 right-[-6rem] w-[36rem] h-[36rem] rounded-full bg-secondary-500/25 blur-[130px]"></div>
        <div class="aurora-3 absolute bottom-[-14rem] left-1/3 w-[38rem] h-[38rem] rounded-full bg-accent-500/20 blur-[130px]"></div>
    </div>
    <div class="absolute inset-0 hero-grid" aria-hidden="true"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-b from-transparent to-slate-950" aria-hidden="true"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto text-center">
            <span class="reveal inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-slate-200 text-xs sm:text-sm font-medium backdrop-blur-sm">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-secondary-400"></span>
                </span>
                Affiliate Program · MasFirmanPratama.com
            </span>

            <h1 class="reveal font-display mt-7 text-4xl sm:text-6xl lg:text-[4.75rem] font-semibold leading-[1.05] text-white tracking-tight">
                Bagikan Ilmu Mind Power,
                <span class="block mt-2 bg-gradient-to-r from-accent-300 via-white to-secondary-300 bg-clip-text text-transparent italic">panen komisinya.</span>
            </h1>

            <p class="reveal mt-7 text-lg text-slate-300 max-w-2xl mx-auto leading-relaxed">
                Promosikan kelas &amp; buku Alpha Mind Control, dapatkan komisi hingga
                <span class="font-semibold text-white">{{ rtrim(rtrim(number_format($alumniRate, 2), '0'), '.') }}%</span>
                per transaksi — lengkap dengan materi marketing, dashboard real-time, dan withdrawal cepat.
            </p>

            <div class="reveal mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="ripple group inline-flex items-center gap-2 px-8 py-4 rounded-full bg-white text-slate-900 font-semibold text-base hover:bg-slate-100 shadow-xl shadow-black/20 transition">
                    Gabung Gratis Sekarang
                    <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i>
                </a>
                <a href="#kalkulator"
                   class="inline-flex items-center gap-2 px-8 py-4 rounded-full border border-white/20 text-white font-medium text-base hover:bg-white/5 transition backdrop-blur-sm">
                    <i data-lucide="calculator" class="w-5 h-5"></i> Hitung Potensimu
                </a>
            </div>

            {{-- social proof --}}
            <div class="reveal mt-12 flex flex-col sm:flex-row items-center justify-center gap-5 sm:gap-8">
                <div class="flex items-center gap-3">
                    <div class="flex -space-x-3">
                        @foreach (['bg-primary-400','bg-secondary-400','bg-accent-400','bg-rose-400'] as $c)
                            <span class="w-9 h-9 rounded-full {{ $c }} ring-2 ring-slate-950"></span>
                        @endforeach
                    </div>
                    <p class="text-sm text-slate-300 text-left">Bergabung bersama komunitas<br class="hidden sm:block"> affiliator MasFirmanPratama</p>
                </div>
                @if ($stats['total_affiliators'] > 0 || $stats['total_commissions_paid'] > 0)
                    <div class="hidden sm:block w-px h-10 bg-white/15"></div>
                    <div class="flex items-center gap-6">
                        @if ($stats['total_affiliators'] > 0)
                            <div class="text-left">
                                <p class="text-2xl font-bold text-white" data-count="{{ (int) $stats['total_affiliators'] }}" data-format="int">0</p>
                                <p class="text-xs text-slate-400">Affiliator aktif</p>
                            </div>
                        @endif
                        @if ($stats['total_commissions_paid'] > 0)
                            <div class="text-left">
                                <p class="text-2xl font-bold text-white" data-count="{{ (int) $stats['total_commissions_paid'] }}" data-format="currency">Rp 0</p>
                                <p class="text-xs text-slate-400">Komisi dibayar</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>

{{-- ══════════ TRUST MARQUEE ══════════ --}}
<section class="bg-slate-900 border-y border-white/5 py-4 overflow-hidden">
    <div class="flex whitespace-nowrap">
        <div class="marquee-track flex shrink-0 items-center">
            @for ($i = 0; $i < 2; $i++)
                @foreach (['Tanpa biaya gabung', 'Cooling adil 7 hari', 'Withdrawal bank & e-wallet', 'Materi marketing gratis', 'Dashboard real-time', 'Komisi hingga '.rtrim(rtrim(number_format($alumniRate, 2), '0'), '.').'%'] as $item)
                    <span class="inline-flex items-center gap-2.5 px-6 text-sm text-slate-400">
                        <i data-lucide="sparkle" class="w-4 h-4 text-accent-400"></i> {{ $item }}
                    </span>
                @endforeach
            @endfor
        </div>
    </div>
</section>

{{-- ══════════ CARA KERJA ══════════ --}}
<section id="cara-kerja" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-16">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Cara Kerja</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Tiga langkah, mulai menghasilkan</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6 lg:gap-8 relative">
            @foreach ($steps as $idx => [$icon, $title, $desc])
                <div class="reveal relative">
                    <div class="h-full rounded-3xl border border-slate-100 bg-white p-8 shadow-sm hover-lift">
                        <div class="flex items-center justify-between mb-6">
                            <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-primary-50 text-primary-600">
                                <i data-lucide="{{ $icon }}" class="w-7 h-7"></i>
                            </span>
                            <span class="font-display text-5xl font-semibold text-slate-100">0{{ $idx + 1 }}</span>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ KALKULATOR PENGHASILAN ══════════ --}}
<section id="kalkulator" class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kalkulator</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Berapa yang bisa kamu hasilkan?</h2>
            <p class="mt-3 text-slate-600">Geser dan lihat estimasi komisimu. Ini simulasi — potensimu bisa lebih.</p>
        </div>

        <div class="reveal" x-data="{
            referrals: 10,
            aov: 500000,
            rate: {{ $alumniRate }},
            tier: 'alumni',
            setTier(t, r) { this.tier = t; this.rate = r; },
            get monthly() { return this.referrals * this.aov * this.rate / 100; },
            get yearly() { return this.monthly * 12; },
            fmt(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }
        }">
            <div class="grid lg:grid-cols-5 gap-6 items-stretch">
                {{-- controls --}}
                <div class="lg:col-span-3 rounded-3xl bg-white border border-slate-100 shadow-sm p-6 sm:p-8">
                    {{-- tier toggle --}}
                    <div class="mb-8">
                        <p class="text-sm font-medium text-slate-700 mb-3">Tipe affiliator</p>
                        <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl">
                            <button type="button" @click="setTier('alumni', {{ $alumniRate }})"
                                    class="py-2.5 rounded-xl text-sm font-semibold transition"
                                    :class="tier === 'alumni' ? 'bg-white text-primary-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                Alumni · {{ rtrim(rtrim(number_format($alumniRate, 2), '0'), '.') }}%
                            </button>
                            <button type="button" @click="setTier('non', {{ $nonRate }})"
                                    class="py-2.5 rounded-xl text-sm font-semibold transition"
                                    :class="tier === 'non' ? 'bg-white text-primary-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                Non-Alumni · {{ rtrim(rtrim(number_format($nonRate, 2), '0'), '.') }}%
                            </button>
                        </div>
                    </div>

                    {{-- referrals slider --}}
                    <div class="mb-8">
                        <div class="flex items-baseline justify-between mb-3">
                            <label for="calc-referrals" class="text-sm font-medium text-slate-700">Pembelian per bulan</label>
                            <span class="font-display text-2xl font-semibold text-slate-900"><span x-text="referrals"></span>×</span>
                        </div>
                        <input id="calc-referrals" type="range" min="1" max="50" x-model.number="referrals" class="calc-range w-full accent-primary-600">
                        <div class="flex justify-between text-xs text-slate-400 mt-2"><span>1</span><span>50</span></div>
                    </div>

                    {{-- AOV slider --}}
                    <div>
                        <div class="flex items-baseline justify-between mb-3">
                            <label for="calc-aov" class="text-sm font-medium text-slate-700">Rata-rata nilai order</label>
                            <span class="font-display text-2xl font-semibold text-slate-900" x-text="fmt(aov)"></span>
                        </div>
                        <input id="calc-aov" type="range" min="100000" max="5000000" step="50000" x-model.number="aov" class="calc-range w-full accent-primary-600">
                        <div class="flex justify-between text-xs text-slate-400 mt-2"><span>Rp 100rb</span><span>Rp 5jt</span></div>
                    </div>
                </div>

                {{-- result --}}
                <div class="lg:col-span-2 relative overflow-hidden rounded-3xl bg-slate-950 grain p-6 sm:p-8 flex flex-col justify-center text-center">
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                        <div class="aurora absolute -top-16 -right-10 w-64 h-64 rounded-full bg-primary-600/40 blur-3xl"></div>
                        <div class="aurora-2 absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-secondary-500/30 blur-3xl"></div>
                    </div>
                    <div class="relative z-10">
                        <p class="text-sm text-slate-300">Estimasi komisi per bulan</p>
                        <p class="font-display text-4xl sm:text-5xl font-semibold text-white mt-2 leading-tight break-words" x-text="fmt(monthly)"></p>
                        <div class="mt-6 pt-6 border-t border-white/10">
                            <p class="text-sm text-slate-300">Setahun bisa mencapai</p>
                            <p class="font-display text-2xl font-semibold text-secondary-300 mt-1 break-words" x-text="fmt(yearly)"></p>
                        </div>
                        <a href="{{ route('register') }}" class="ripple mt-8 inline-flex items-center justify-center gap-2 w-full py-3.5 rounded-full bg-white text-slate-900 font-semibold hover:bg-slate-100 transition">
                            Mulai hasilkan ini <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ══════════ TIPE AFFILIATOR ══════════ --}}
<section class="py-20 lg:py-28 bg-white border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Pilih Jalurmu</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Satu program, dua keuntungan</h2>
            <p class="mt-3 text-slate-600">Alumni atau bukan, semua bisa menghasilkan. Rate menyesuaikan profilmu.</p>
        </div>
        <div class="grid md:grid-cols-2 gap-6 max-w-4xl mx-auto">
            @foreach ($types as $type)
                @php $isAlumni = $type->slug === 'alumni'; @endphp
                <div class="reveal relative flex flex-col rounded-3xl p-8 shadow-sm hover-lift
                    {{ $isAlumni ? 'bg-slate-950 grain text-white ring-1 ring-white/10' : 'bg-white border border-slate-200 text-slate-900' }}">
                    @if ($isAlumni)
                        <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-3xl" aria-hidden="true">
                            <div class="aurora absolute -top-20 -right-16 w-56 h-56 rounded-full bg-primary-600/40 blur-3xl"></div>
                            <div class="aurora-2 absolute -bottom-20 -left-16 w-56 h-56 rounded-full bg-accent-500/25 blur-3xl"></div>
                        </div>
                        <span class="relative z-10 self-start inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-accent-500/20 border border-accent-400/30 text-accent-200 text-xs font-semibold mb-5">
                            <i data-lucide="crown" class="w-3.5 h-3.5"></i> Paling menguntungkan
                        </span>
                    @endif
                    <div class="relative z-10 flex items-center gap-3 mb-4 {{ $isAlumni ? '' : 'mt-1' }}">
                        <span class="flex items-center justify-center w-11 h-11 rounded-xl {{ $isAlumni ? 'bg-white/10 text-accent-300' : 'bg-primary-100 text-primary-600' }}">
                            <i data-lucide="{{ $isAlumni ? 'sparkles' : 'star' }}" class="w-5 h-5"></i>
                        </span>
                        <h3 class="text-xl font-semibold">{{ $type->name }}</h3>
                    </div>
                    <p class="relative z-10 text-sm mb-6 {{ $isAlumni ? 'text-slate-300' : 'text-slate-500' }}">{{ $type->description }}</p>
                    <div class="relative z-10 mb-6">
                        <span class="font-display text-5xl font-semibold {{ $isAlumni ? 'text-white' : 'text-primary-600' }}">{{ rtrim(rtrim(number_format($type->default_commission_rate, 2), '0'), '.') }}%</span>
                        <span class="text-sm {{ $isAlumni ? 'text-slate-400' : 'text-slate-400' }}"> komisi</span>
                    </div>
                    @if ($type->benefits)
                        <ul class="relative z-10 space-y-3 mb-8 flex-1">
                            @foreach ($type->benefits as $benefit)
                                <li class="flex items-start gap-2.5 text-sm {{ $isAlumni ? 'text-slate-200' : 'text-slate-600' }}">
                                    <i data-lucide="check" class="w-4 h-4 mt-0.5 shrink-0 {{ $isAlumni ? 'text-secondary-400' : 'text-secondary-500' }}"></i>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <a href="{{ route('register') }}?type={{ $type->id }}"
                       class="ripple relative z-10 mt-auto inline-flex items-center justify-center gap-2 w-full py-3.5 rounded-full font-semibold transition
                       {{ $isAlumni ? 'bg-white text-slate-900 hover:bg-slate-100' : 'bg-primary-600 text-white hover:bg-primary-700' }}">
                        Pilih {{ $type->name }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ BENEFIT ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kenapa Gabung</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Semua yang kamu butuhkan untuk sukses</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($benefits as [$icon, $title, $desc])
                <div class="reveal group rounded-2xl bg-white border border-slate-100 p-6 shadow-sm hover-lift">
                    <span class="flex items-center justify-center w-12 h-12 rounded-xl bg-primary-50 text-primary-600 mb-5 transition-colors group-hover:bg-primary-600 group-hover:text-white">
                        <i data-lucide="{{ $icon }}" class="w-6 h-6"></i>
                    </span>
                    <h3 class="text-base font-semibold text-slate-900 mb-2">{{ $title }}</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ TESTIMONI ══════════ --}}
<section class="py-20 lg:py-28 bg-white border-t border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center max-w-2xl mx-auto mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">Kata Mereka</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Sudah dibuktikan affiliator kami</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-6">
            @foreach ($testimonials as $idx => [$name, $role, $initial, $quote])
                <figure class="reveal relative rounded-3xl bg-slate-50 border border-slate-100 p-8 {{ $idx === 1 ? 'md:-translate-y-4' : '' }}">
                    <i data-lucide="quote" class="absolute top-7 right-7 w-9 h-9 text-primary-100"></i>
                    <blockquote class="text-slate-700 leading-relaxed font-medium mb-6">“{{ $quote }}”</blockquote>
                    <figcaption class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-11 h-11 rounded-full bg-primary-600 text-white font-semibold">{{ $initial }}</span>
                        <div>
                            <p class="font-semibold text-slate-900 text-sm">{{ $name }}</p>
                            <p class="text-xs text-primary-600 font-medium">{{ $role }}</p>
                        </div>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ FAQ ══════════ --}}
<section class="py-20 lg:py-28 bg-slate-50 border-t border-slate-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal text-center mb-14">
            <p class="text-xs font-bold tracking-[0.2em] text-accent-600 uppercase mb-3">FAQ</p>
            <h2 class="font-display text-3xl md:text-5xl font-semibold text-slate-900 tracking-tight">Masih ada pertanyaan?</h2>
        </div>
        <div class="reveal space-y-3" x-data="{ open: 0 }">
            @foreach ($faqs as $i => [$q, $a])
                <div class="rounded-2xl bg-white border border-slate-100 overflow-hidden">
                    <button type="button" @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                            class="w-full flex items-center justify-between gap-4 text-left px-6 py-5"
                            :aria-expanded="open === {{ $i }}">
                        <span class="font-semibold text-slate-900">{{ $q }}</span>
                        <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-300" :class="open === {{ $i }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak>
                        <p class="px-6 pb-5 text-sm text-slate-500 leading-relaxed">{{ $a }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ══════════ CTA FINAL ══════════ --}}
<section class="py-20 lg:py-28 bg-white">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="reveal relative overflow-hidden rounded-[2.5rem] bg-slate-950 grain px-8 py-16 md:px-16 md:py-20 text-center">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="aurora absolute -top-24 -right-24 w-[30rem] h-[30rem] rounded-full bg-primary-600/35 blur-[120px]"></div>
                <div class="aurora-2 absolute -bottom-24 -left-24 w-[30rem] h-[30rem] rounded-full bg-accent-500/25 blur-[120px]"></div>
                <div class="aurora-3 absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[24rem] h-[24rem] rounded-full bg-secondary-500/20 blur-[120px]"></div>
            </div>
            <div class="relative z-10">
                <h2 class="font-display text-3xl md:text-5xl font-semibold text-white tracking-tight leading-tight">
                    Ilmumu berharga.<br><span class="italic bg-gradient-to-r from-accent-300 to-secondary-300 bg-clip-text text-transparent">Sekarang bisa berbuah komisi.</span>
                </h2>
                <p class="mt-5 text-slate-300 max-w-xl mx-auto">Daftar gratis, dapatkan link referral pertamamu dalam hitungan menit.</p>
                <div class="mt-9 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('register') }}" class="ripple group inline-flex items-center gap-2 px-8 py-4 rounded-full bg-white text-slate-900 font-semibold hover:bg-slate-100 shadow-xl shadow-black/20 transition">
                        Daftar Gratis Sekarang <i data-lucide="arrow-right" class="w-5 h-5 transition-transform group-hover:translate-x-1"></i>
                    </a>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-full border border-white/20 text-white font-medium hover:bg-white/5 transition">
                        Sudah punya akun? Masuk
                    </a>
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

        // Scroll reveal
        const reveals = document.querySelectorAll('.reveal');
        if (reduce || !('IntersectionObserver' in window)) {
            reveals.forEach(el => el.classList.add('in'));
        } else {
            const io = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
            reveals.forEach(el => io.observe(el));
        }

        // Animated counters
        const fmt = (n, kind) => kind === 'currency'
            ? 'Rp ' + Math.round(n).toLocaleString('id-ID')
            : Math.round(n).toLocaleString('id-ID');
        const counters = document.querySelectorAll('[data-count]');
        const runCounter = (el) => {
            const target = parseInt(el.dataset.count, 10) || 0;
            const kind = el.dataset.format || 'int';
            if (reduce) { el.textContent = fmt(target, kind); return; }
            const dur = 1500, start = performance.now();
            const tick = (now) => {
                const p = Math.min((now - start) / dur, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = fmt(target * eased, kind);
                if (p < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        if ('IntersectionObserver' in window) {
            const cio = new IntersectionObserver((entries) => {
                entries.forEach(e => { if (e.isIntersecting) { runCounter(e.target); cio.unobserve(e.target); } });
            }, { threshold: 0.5 });
            counters.forEach(el => cio.observe(el));
        } else {
            counters.forEach(runCounter);
        }
    })();
</script>
@endpush
