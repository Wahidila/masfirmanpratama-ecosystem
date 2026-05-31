@php
    $featuredBookTitle = \App\Models\Product::where('slug', 'alpha-telepathy')->value('title') ?? 'Buku Alpha Telepati';

    /* -----------------------------------------------------------------
     | Static data — di-port dari prototype/index.html.
     | M2 akan di-wire ke DB (products, testimonials, media-coverage).
     |---------------------------------------------------------------- */

    $benefits = [
        [
            'icon' => 'shield-check',
            'title' => 'Halal, Logis & Ilmiah',
            'body' => 'Metode AMC bisa dipertanggungjawabkan secara agama dan akal sehat — 100% bebas mistis, sudah dijalani ribuan alumni.',
            'color' => 'primary',
        ],
        [
            'icon' => 'zap',
            'title' => '80% Praktik Langsung',
            'body' => 'Bukan sekadar teori. Kelas didesain agar setiap peserta langsung praktik dan merasakan hasil di hari yang sama.',
            'color' => 'secondary',
        ],
        [
            'icon' => 'users',
            'title' => 'Komunitas Alumni Aktif',
            'body' => 'Akses grup Telegram alumni AMC, pertemuan rutin bulanan, dan support langsung dari Mas Firman & tim.',
            'color' => 'accent',
        ],
        [
            'icon' => 'sparkles',
            'title' => 'Garansi Perubahan Nyata',
            'body' => 'Jika dipraktikkan konsisten 30 hari, AMC terbukti membantu memperbaiki bisnis, finansial, hubungan, hingga kesehatan mental.',
            'color' => 'rose',
        ],
    ];

    $pricing = [
        [
            'name' => 'Kelas Reguler',
            'tagline' => 'Kelas Reguler Banyak Orang sesuai jadwal admin. Online via Zoom satu persatu sesuai antrian.',
            'price' => 'Rp 4.500.000',
            'priceNote' => '*Bisa dicicil sampai lunas.',
            'iconAccent' => 'video',
            'iconColor' => 'text-blue-600',
            'features' => [
                '20 Materi Alpha Mind Control',
                'Modul materi AMC',
                $featuredBookTitle,
                'Sertifikat & alat tulis (offline)',
                'Grup Telegram alumni + pertemuan bulanan',
                'Jadwal online sesuai antrian',
            ],
            'ctaLabel' => 'Daftar Reguler',
            'ctaHref' => 'https://wa.me/6281230633464?text=Saya%20mau%20daftar%20Kelas%20Reguler%20AMC',
            'highlight' => false,
            'dark' => false,
        ],
        [
            'name' => 'Kelas Privat',
            'tagline' => 'Materi sama dengan reguler tapi 1-on-1 offline. Materi spesifik sesuai masalah pribadi anda.',
            'price' => 'Rp 7.500.000',
            'priceNote' => '*Bisa dicicil sampai lunas.',
            'iconAccent' => 'mic',
            'iconColor' => 'text-accent-600',
            'features' => [
                '20 Materi AMC',
                'Modul materi AMC',
                $featuredBookTitle,
                'Sertifikat & alat tulis (notes)',
                'Grup Telegram alumni + pertemuan bulanan',
                'Jadwal lebih fleksibel & lebih cepat',
            ],
            'ctaLabel' => 'Daftar Privat',
            'ctaHref' => 'https://wa.me/6281230633464?text=Saya%20mau%20daftar%20Kelas%20Privat%20AMC',
            'highlight' => true,
            'badge' => 'Terlaris',
            'dark' => false,
        ],
        [
            'name' => 'Kelas Platinum',
            'tagline' => 'Program 3 hari 2 malam untuk membongkar semua penghambat diri & mempercepat transformasi.',
            'price' => 'Rp 22.500.000',
            'priceNote' => '*Bisa dicicil sampai lunas.',
            'iconAccent' => 'gem',
            'iconColor' => 'text-secondary-400',
            'features' => [
                'Materi advanced',
                'Hotel 3 hari 2 malam',
                'Makan 3x sehari',
                'Tugas terstruktur selama pelatihan',
                'Modul Platinum + alat tulis',
                'Durasi panjang untuk konsultasi privat',
            ],
            'ctaLabel' => 'Pilih Platinum',
            'ctaHref' => 'https://wa.me/6281230633464?text=Saya%20mau%20daftar%20Kelas%20Platinum%20AMC',
            'highlight' => false,
            'dark' => true,
        ],
    ];

    $testimonials = [
        [
            'quote' => 'Sangat bersyukur bertemu kelas ini. Dari AMC saya semakin menyadari bahwa hidup ini sungguh indah, enak, dan menyenangkan jika kita paham rumusnya.',
            'name' => 'Ria Handayani',
            'role' => 'Alumni Kelas Reguler',
            'initial' => 'R',
        ],
        [
            'quote' => 'Luar biasa! Setelah mempraktikkan isi kelasnya dengan bimbingan Mas Firman, saya yakin kita bisa mencapai apapun yang kita inginkan dengan kekuatan pikiran.',
            'name' => 'Fitria',
            'role' => 'Alumni Kelas Privat',
            'initial' => 'F',
        ],
        [
            'quote' => 'Rasional, logis, tanpa embel-embel sihir. AMC adalah ilmu yang sangat mind blowing bagi nalar saya, sangat aplikatif di dunia kerja.',
            'name' => 'Edi',
            'role' => 'Alumni AMC',
            'initial' => 'E',
        ],
    ];
@endphp

<x-layouts.store
    title="Firman Pratama — Pakar Pikiran No. 1 Indonesia | Mind Power & Life Mastery"
    description="Alpha Mind Control (AMC) adalah metode halal, logis & ilmiah untuk mengenali, mengontrol, dan memaksimalkan kekuatan pikiran. Bergabung bersama 2.500+ alumni."
    bodyClass="relative"
>
    {{-- ======================================================
       | 1. HERO — Full-width Slider/Carousel
       |====================================================== --}}
    <section
        x-data="{
            current: 0,
            total: 3,
            autoplay: true,
            autoplayInterval: 6000,
            timer: null,
            touchStartX: 0,
            touchEndX: 0,
            reducedMotion: false,
            init() {
                this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (this.autoplay && !this.reducedMotion) this.startAutoplay();
            },
            next() {
                this.current = (this.current + 1) % this.total;
                this.restartAutoplay();
            },
            prev() {
                this.current = (this.current - 1 + this.total) % this.total;
                this.restartAutoplay();
            },
            goTo(n) {
                this.current = n;
                this.restartAutoplay();
            },
            startAutoplay() {
                if (this.reducedMotion || this.timer) return;
                this.timer = setInterval(() => {
                    this.current = (this.current + 1) % this.total;
                }, this.autoplayInterval);
            },
            stopAutoplay() {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },
            restartAutoplay() {
                this.stopAutoplay();
                if (this.autoplay && !this.reducedMotion) this.startAutoplay();
            },
            handleTouchStart(e) {
                this.touchStartX = e.changedTouches[0].screenX;
            },
            handleTouchEnd(e) {
                this.touchEndX = e.changedTouches[0].screenX;
                const diff = this.touchStartX - this.touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) this.next();
                    else this.prev();
                }
            }
        }"
        x-cloak
        role="region"
        aria-label="Hero slider"
        aria-roledescription="carousel"
        tabindex="0"
        @keydown.left="prev()"
        @keydown.right="next()"
        @mouseenter="stopAutoplay()"
        @mouseleave="startAutoplay()"
        @focusin="stopAutoplay()"
        @focusout="startAutoplay()"
        @touchstart.passive="handleTouchStart"
        @touchend="handleTouchEnd"
        class="relative w-full pt-12 pb-20 lg:pt-24 lg:pb-32 overflow-hidden bg-slate-50 min-h-[600px] lg:min-h-[680px]"
    >
        {{-- Animated blob background (disabled when prefers-reduced-motion) --}}
        <template x-if="!reducedMotion">
            <div class="absolute inset-0 w-full h-full overflow-hidden z-0 pointer-events-none" aria-hidden="true">
                <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-primary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
                <div class="absolute top-[20%] right-[-10%] w-96 h-96 bg-secondary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-200"></div>
                <div class="absolute bottom-[-20%] left-[20%] w-96 h-96 bg-accent-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-400"></div>
            </div>
        </template>

        {{-- Slides track --}}
        <div
            class="relative z-10 flex w-full h-full"
            :class="!reducedMotion ? 'transition-transform duration-700 ease-in-out' : ''"
            :style="'transform: translateX(-' + (current * 100) + '%)'"
        >
            {{-- ======== SLIDE 1: Main Hero ======== --}}
            <div
                class="w-full flex-shrink-0 min-h-[600px] lg:min-h-[680px] flex items-center"
                role="group"
                aria-roledescription="slide"
                aria-label="Slide 1 dari 3"
                :aria-hidden="current !== 0"
            >
                <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                        <div class="text-center lg:text-left animate-fade-in-up">
                            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 border border-primary-100 text-primary-700 mb-6 font-medium text-sm">
                                <span class="flex h-2 w-2 rounded-full bg-primary-600 animate-pulse"></span>
                                Pakar Pikiran No. 1 di Indonesia
                            </div>

                            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight mb-6">
                                Kenali Kekuatan Pikiranmu,
                                <span class="text-gradient block mt-2 pb-2">Ubah Hidup Jadi Ajaib</span>
                            </h1>

                            <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                Alpha Mind Control (AMC) adalah metode yang teruji, halal, dan logis untuk mengenali, mengontrol, dan memaksimalkan pikiran demi mewujudkan semua impianmu. Mulai perubahanmu hari ini.
                            </p>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                                <x-button href="#kelas" size="md" icon="book-open" iconPosition="right" class="whitespace-nowrap">
                                    Pelajari Alpha Mind Control
                                </x-button>
                                <x-button href="#katalog" variant="outline" size="md" icon="library" iconPosition="left" class="whitespace-nowrap">
                                    Lihat Koleksi Buku
                                </x-button>
                            </div>

                            <div class="mt-10 flex items-center justify-center lg:justify-start gap-4">
                                <div class="flex -space-x-4">
                                    <div class="w-10 h-10 rounded-full border-2 border-white bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-sm">A</div>
                                    <div class="w-10 h-10 rounded-full border-2 border-white bg-secondary-100 flex items-center justify-center text-secondary-700 font-bold text-sm">B</div>
                                    <div class="w-10 h-10 rounded-full border-2 border-white bg-accent-100 flex items-center justify-center text-accent-600 font-bold text-sm">C</div>
                                    <div class="w-10 h-10 rounded-full border-2 border-white bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600">+2.5K</div>
                                </div>
                                <p class="text-sm font-medium text-slate-600">
                                    Bergabung bersama 2.550+ alumni AMC
                                </p>
                            </div>
                        </div>

                        <div class="relative lg:ml-10 mt-10 lg:mt-0 hidden lg:block">
                            <div class="absolute inset-0 bg-gradient-to-tr from-primary-100 to-secondary-100 rounded-full blur-3xl opacity-50" aria-hidden="true"></div>

                            <div class="relative w-full h-[500px] flex justify-center items-center">
                                <div class="relative w-80 h-96 group z-20 animate-float">
                                    <div class="absolute -inset-4 bg-gradient-to-tr from-primary-500/20 to-secondary-500/20 rounded-[2.5rem] blur-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500" aria-hidden="true"></div>

                                    <div class="relative h-full bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border-8 border-white/50 glass">
                                        <picture>
                                            <source srcset="{{ asset('assets/images/firman-foto.webp') }}" type="image/webp">
                                            <img
                                                src="{{ asset('assets/images/firman-foto.jpeg') }}"
                                                alt="Mas Firman Pratama — Pakar Kekuatan Pikiran"
                                                width="320"
                                                height="384"
                                                loading="eager"
                                                fetchpriority="high"
                                                decoding="async"
                                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                            >
                                        </picture>
                                        <div class="absolute bottom-0 left-0 right-0 p-6 bg-gradient-to-t from-slate-900/80 to-transparent">
                                            <h2 class="text-xl font-bold text-white mb-1">Mas Firman Pratama</h2>
                                            <p class="text-xs text-slate-200 font-medium tracking-wide">Pakar Kekuatan Pikiran</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="absolute -top-6 -right-12 bg-white p-4 rounded-xl shadow-xl glass z-30 animate-float-delayed flex items-center gap-3 border border-white/50">
                                    <div class="w-10 h-10 bg-accent-100 rounded-full flex items-center justify-center shrink-0">
                                        <i data-lucide="award" class="w-5 h-5 text-accent-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Expertise</p>
                                        <p class="text-sm font-bold text-slate-800">Mindset Mastery</p>
                                    </div>
                                </div>

                                <div class="absolute bottom-20 left-0 bg-white p-4 rounded-xl shadow-xl glass z-30 animate-float flex items-center gap-3 border border-white/50">
                                    <div class="w-10 h-10 bg-secondary-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="users" class="w-5 h-5 text-secondary-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-500 font-medium">Akses Eksklusif</p>
                                        <p class="text-sm font-bold text-slate-800">Grup Konsultasi</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======== SLIDE 2: Kelas AMC ======== --}}
            <div
                class="w-full flex-shrink-0 min-h-[600px] lg:min-h-[680px] flex items-center"
                role="group"
                aria-roledescription="slide"
                aria-label="Slide 2 dari 3"
                :aria-hidden="current !== 1"
            >
                <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                        <div class="text-center lg:text-left">
                            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight mb-6">
                                Ikut Kelas Langsung
                                <span class="text-gradient block mt-2 pb-2">Bersama Mas Firman</span>
                            </h2>

                            <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                Kelas reguler, privat, hingga platinum — bimbingan langsung dengan metode Alpha Mind Control yang telah mengubah ribuan hidup.
                            </p>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                                <x-button href="#kelas" size="lg" icon="calendar" iconPosition="right">
                                    Lihat Jadwal Kelas
                                </x-button>
                                <x-button href="#kelas" variant="outline" size="lg" icon="message-circle" iconPosition="left">
                                    Konsultasi Gratis
                                </x-button>
                            </div>
                        </div>

                        <div class="relative hidden lg:flex items-center justify-center">
                            <div class="relative w-full max-w-sm space-y-5">
                                <div class="glass rounded-2xl p-5 shadow-xl border border-white/50 flex items-center gap-4 hover-lift transition-all duration-300">
                                    <div class="w-12 h-12 rounded-xl bg-primary-100 flex items-center justify-center shrink-0">
                                        <i data-lucide="graduation-cap" class="w-6 h-6 text-primary-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">Bimbingan Langsung</h3>
                                        <p class="text-xs text-slate-500">Sesi intensif dengan Mas Firman</p>
                                    </div>
                                </div>

                                <div class="glass rounded-2xl p-5 shadow-xl border border-white/50 flex items-center gap-4 hover-lift transition-all duration-300 ml-6">
                                    <div class="w-12 h-12 rounded-xl bg-secondary-100 flex items-center justify-center shrink-0">
                                        <i data-lucide="users" class="w-6 h-6 text-secondary-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">Komunitas Alumni</h3>
                                        <p class="text-xs text-slate-500">Grup diskusi & pertemuan rutin</p>
                                    </div>
                                </div>

                                <div class="glass rounded-2xl p-5 shadow-xl border border-white/50 flex items-center gap-4 hover-lift transition-all duration-300 ml-12">
                                    <div class="w-12 h-12 rounded-xl bg-accent-100 flex items-center justify-center shrink-0">
                                        <i data-lucide="star" class="w-6 h-6 text-accent-600"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-slate-800">Metode Terbukti</h3>
                                        <p class="text-xs text-slate-500">Hasil nyata sejak 2016</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======== SLIDE 3: Buku/Karya ======== --}}
            <div
                class="w-full flex-shrink-0 min-h-[600px] lg:min-h-[680px] flex items-center"
                role="group"
                aria-roledescription="slide"
                aria-label="Slide 3 dari 3"
                :aria-hidden="current !== 2"
            >
                <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                        <div class="text-center lg:text-left">
                            <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight mb-6">
                                Pelajari Lewat Karya
                                <span class="text-gradient block mt-2 pb-2">Buku Bestseller</span>
                            </h2>

                            <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                                Koleksi buku bestseller Mas Firman — dari Alpha Telepathy hingga Formula AMC — panduan praktis menguasai kekuatan pikiran.
                            </p>

                            <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                                <x-button href="#katalog" size="lg" icon="library" iconPosition="right">
                                    Lihat Koleksi Buku
                                </x-button>
                            </div>
                        </div>

                        <div class="relative hidden lg:flex items-center justify-center h-[480px]">
                            <div class="relative w-64 h-[440px]">
                                <img
                                    src="{{ asset('assets/images/alpha-telepathy.webp') }}"
                                    alt="Buku Alpha Telepati — Karya Mas Firman Pratama"
                                    loading="lazy"
                                    class="absolute bottom-0 left-0 w-48 h-[360px] -rotate-[10deg] origin-bottom-left object-cover rounded-xl shadow-xl z-10 transition-all duration-500 hover:z-50 hover:scale-105 hover:rotate-0"
                                >
                                <img
                                    src="{{ asset('assets/images/10-keajaiban-pikiran.webp') }}"
                                    alt="Buku 10 Keajaiban Pikiran — Karya Mas Firman Pratama"
                                    loading="lazy"
                                    class="absolute bottom-3 left-4 w-48 h-[360px] -rotate-[4deg] origin-bottom-left object-cover rounded-xl shadow-xl z-20 transition-all duration-500 hover:z-50 hover:scale-105 hover:rotate-0"
                                >
                                <img
                                    src="{{ asset('assets/images/instan-hypnosis.webp') }}"
                                    alt="Buku Instan Hypnosis — Karya Mas Firman Pratama"
                                    loading="lazy"
                                    class="absolute bottom-6 left-8 w-48 h-[360px] rotate-[2deg] origin-bottom-left object-cover rounded-xl shadow-xl z-30 transition-all duration-500 hover:z-50 hover:scale-105 hover:rotate-0"
                                >
                                <img
                                    src="{{ asset('assets/images/formula-amc-firman-pratama.webp') }}"
                                    alt="Buku Formula AMC — Karya Mas Firman Pratama"
                                    loading="lazy"
                                    class="absolute bottom-9 left-12 w-48 h-[360px] rotate-[8deg] origin-bottom-left object-cover rounded-xl shadow-xl z-40 transition-all duration-500 hover:z-50 hover:scale-105 hover:rotate-0"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Prev/Next buttons --}}
        <button
            @click="prev()"
            aria-label="Slide sebelumnya"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 backdrop-blur shadow-lg flex items-center justify-center hover:bg-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        >
            <i data-lucide="chevron-left" class="w-6 h-6 text-slate-700"></i>
        </button>

        <button
            @click="next()"
            aria-label="Slide berikutnya"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 backdrop-blur shadow-lg flex items-center justify-center hover:bg-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
        >
            <i data-lucide="chevron-right" class="w-6 h-6 text-slate-700"></i>
        </button>

        {{-- Dot indicators --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
            <template x-for="(item, index) in total" :key="index">
                <button
                    @click="goTo(index)"
                    :aria-label="'Ke slide ' + (index + 1)"
                    role="button"
                    :class="index === current ? 'w-8 bg-primary-600' : 'w-3 bg-slate-300 hover:bg-slate-400'"
                    class="h-3 rounded-full transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                ></button>
            </template>
        </div>
    </section>

    {{-- ======================================================
       | PROMO BANNER — Kelas Reguler AMC Surabaya 23 Mei 2026
       |====================================================== --}}
    <section class="py-10 lg:py-14 bg-slate-50" aria-label="Promo event terdekat">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-5 text-center">
                Jadwal Terdekat
            </p>
            <a
                href="https://wa.me/6281230633464?text=Saya%20mau%20daftar%20Kelas%20Reguler%20AMC%20Surabaya%2023%20Mei%202026"
                target="_blank"
                rel="noopener"
                class="block rounded-2xl lg:rounded-3xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                aria-label="Daftar Kelas Reguler Alpha Mind Control Surabaya 23 Mei 2026 via WhatsApp"
            >
                <img
                    src="{{ asset('assets/images/jadwal-amc-surabaya.webp') }}"
                    alt="Kelas Reguler Alpha Mind Control — Surabaya 23 Mei 2026 di Hotel Bisanta bersama Mas Firman"
                    width="1280"
                    height="312"
                    loading="lazy"
                    decoding="async"
                    class="w-full h-auto"
                >
            </a>
        </div>
    </section>

    {{-- ==============================================================
       | WELCOME / SELAMAT DATANG — Intro Mas Firman + Buku Karya
       |============================================================== --}}
    <section id="welcome" class="py-20 lg:py-24 bg-white border-t border-slate-100" aria-label="Selamat Datang">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Selamat Datang
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Selamat Datang di <span class="text-gradient">Website Ini</span>
                </h2>
                <p class="text-lg text-slate-600 mb-2">
                    Saya ingin berbagi ilmu tentang Kesadaran yang Sesungguhnya.
                </p>
                <p class="text-lg text-slate-600">
                    Banyak manusia sesungguhnya dalam kerugian jika tidak menyadari tugasnya.
                </p>
            </div>

            <div class="max-w-4xl mx-auto">
                <p class="text-center text-base md:text-lg text-slate-700 font-medium mb-8">
                    Buku-buku karya saya yang sudah membantu banyak orang untuk merubah hidupnya
                </p>

                @if(isset($welcomeBooks) && $welcomeBooks->isNotEmpty())
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-5 md:gap-6">
                        @foreach ($welcomeBooks as $book)
                            <a
                                href="{{ route('products.show', $book->slug) }}"
                                class="group flex flex-col items-center text-center rounded-2xl bg-slate-50 border border-slate-100 p-4 transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-primary-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                aria-label="Lihat detail buku {{ $book->title }}"
                            >
                                @if($book->image_path)
                                    <img
                                        src="{{ asset($book->image_path) }}"
                                        alt="Sampul buku {{ $book->title }}"
                                        width="160"
                                        height="220"
                                        loading="lazy"
                                        decoding="async"
                                        class="w-24 h-32 md:w-28 md:h-36 object-cover rounded-xl mb-3 shadow-sm group-hover:shadow-md transition-shadow"
                                    >
                                @else
                                    <div class="w-24 h-32 md:w-28 md:h-36 rounded-xl mb-3 bg-primary-100 flex items-center justify-center text-primary-500">
                                        <i data-lucide="book-open" class="w-8 h-8"></i>
                                    </div>
                                @endif
                                <span class="text-sm font-semibold text-slate-800 group-hover:text-primary-600 transition-colors leading-snug">
                                    {{ $book->title }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ==============================================================
       | 2. BENEFIT AMC (4 cards)
       |============================================================== --}}
    <section id="benefit" class="py-20 lg:py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Kenapa Alpha Mind Control?
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Empat Alasan Kenapa AMC <span class="text-gradient">Berbeda dari Metode Lain</span>
                </h2>
                <p class="text-lg text-slate-600">
                    Bukan teori abstrak, bukan janji manis. AMC adalah formula praktis yang sudah membantu ribuan orang berubah secara nyata.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($benefits as $benefit)
                    <x-benefit-card
                        :icon="$benefit['icon']"
                        :title="$benefit['title']"
                        :iconColor="$benefit['color']"
                    >
                        {{ $benefit['body'] }}
                    </x-benefit-card>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================================================
       | 3. KATALOG BUKU (6 produk)
       |====================================================== --}}
    <section id="katalog" class="py-20 lg:py-24 bg-slate-50 border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6 mb-12">
                <div class="max-w-2xl">
                    <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-3">
                        Karya Best Seller
                    </p>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                        Buku &amp; Kitab Ajaib Mas Firman
                    </h2>
                    <p class="text-lg text-slate-600">
                        Pelajari pondasi alam bawah sadar, manipulasi telepati sehat, dan teknik AMC otodidak hanya dengan membaca karya-karya bestseller ini.
                    </p>
                </div>
                <x-button href="{{ url('/produk') }}" variant="outline" size="md" icon="arrow-right" iconPosition="right">
                    Lihat Semua Produk
                </x-button>
            </div>

            @if($products->isEmpty())
                <div class="col-span-full text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-slate-100 text-slate-500 mb-5">
                        <i data-lucide="book-x" class="w-8 h-8"></i>
                    </div>
                    <p class="text-slate-500">Belum ada buku yang tersedia saat ini.</p>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 gap-5 md:gap-6 lg:gap-8">
                    @foreach ($products as $product)
                        <x-product-card
                            :image="$product['image']"
                            :title="$product['title']"
                            :price="$product['price']"
                            :originalPrice="$product['originalPrice']"
                            :category="$product['category']"
                            :badge="$product['badge']"
                            :href="$product['href']"
                        />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ======================================================
       | 4. PRICING KELAS — Formula AMC
       |====================================================== --}}
    <section id="kelas" class="py-20 lg:py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Formula Alpha Mind Control
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Pilih Format Kelas yang <span class="text-gradient">Sesuai Hidupmu</span>
                </h2>
                <p class="text-lg text-slate-600">
                    Daring, luring, atau eksklusif satu lawan satu bersama Mas Firman. Semua bisa dicicil sampai lunas.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                @foreach ($pricing as $tier)
                    @php
                        $isHighlight = $tier['highlight'] ?? false;
                        $isDark = $tier['dark'] ?? false;
                        $cardClass = $isDark
                            ? 'group relative bg-slate-900 rounded-3xl p-8 border border-slate-800 shadow-xl hover-lift flex flex-col h-full overflow-hidden'
                            : ($isHighlight
                                ? 'group relative bg-white rounded-3xl p-8 border border-slate-100 shadow-lg hover-lift flex flex-col h-full overflow-hidden ring-2 ring-primary-500 transform lg:-translate-y-4'
                                : 'group relative bg-white rounded-3xl p-8 border border-slate-100 shadow-sm hover-lift flex flex-col h-full overflow-hidden');
                        $titleClass = $isDark ? 'text-white' : 'text-slate-900';
                        $taglineClass = $isDark ? 'text-slate-300' : 'text-slate-600';
                        $priceClass = $isDark ? 'text-white' : 'text-slate-900';
                        $featureTextClass = $isDark ? 'text-slate-300' : 'text-slate-700';
                        $checkIconClass = $isDark ? 'text-secondary-400' : 'text-secondary-500';
                        $borderClass = $isDark ? 'border-slate-800' : 'border-slate-100';
                        $noteClass = $isDark ? 'text-slate-500' : 'text-slate-500';
                    @endphp

                    <div class="{{ $cardClass }}">
                        <div class="absolute top-0 right-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity pointer-events-none" aria-hidden="true">
                            <i data-lucide="{{ $tier['iconAccent'] }}" class="w-32 h-32 {{ $tier['iconColor'] }}"></i>
                        </div>

                        @if (! empty($tier['badge']))
                            <div class="absolute top-0 left-1/2 -translate-x-1/2 bg-primary-500 text-white px-4 py-1 rounded-b-xl text-xs font-bold tracking-wider uppercase">
                                {{ $tier['badge'] }}
                            </div>
                        @endif

                        <div class="mb-6 relative z-10 {{ $isHighlight ? 'mt-2' : '' }}">
                            <h3 class="text-2xl font-bold {{ $titleClass }} mb-3">{{ $tier['name'] }}</h3>
                            <p class="text-sm {{ $taglineClass }} min-h-[60px] leading-relaxed">{{ $tier['tagline'] }}</p>
                        </div>

                        <div class="mb-8 relative z-10 pb-8 border-b {{ $borderClass }}">
                            <div class="text-3xl font-extrabold {{ $priceClass }}">{{ $tier['price'] }}</div>
                        </div>

                        <ul class="space-y-4 mb-8 flex-grow relative z-10 text-sm {{ $featureTextClass }} font-medium">
                            @foreach ($tier['features'] as $feature)
                                <li class="flex items-start gap-3">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 {{ $checkIconClass }} shrink-0 mt-0.5"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-auto relative z-10">
                            @if ($isHighlight)
                                <a
                                    href="{{ $tier['ctaHref'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="ripple block w-full text-center bg-primary-600 text-white hover:bg-primary-700 hover:shadow-lg rounded-xl py-3.5 font-bold transition-all"
                                >
                                    {{ $tier['ctaLabel'] }}
                                </a>
                            @elseif ($isDark)
                                <a
                                    href="{{ $tier['ctaHref'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block w-full text-center bg-white text-slate-900 hover:bg-secondary-50 hover:text-secondary-700 rounded-xl py-3.5 font-bold transition-all shadow-md"
                                >
                                    {{ $tier['ctaLabel'] }}
                                </a>
                            @else
                                <a
                                    href="{{ $tier['ctaHref'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="block w-full text-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-xl py-3.5 font-bold transition-all shadow-sm"
                                >
                                    {{ $tier['ctaLabel'] }}
                                </a>
                            @endif

                            <p class="text-xs {{ $noteClass }} mt-4 text-center leading-relaxed">
                                {{ $tier['priceNote'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================================================
       | 5. TESTIMONI — static grid
       |====================================================== --}}
    <section id="testimoni" class="py-20 lg:py-24 bg-slate-50 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-1/3 h-full bg-primary-50 rounded-l-full blur-3xl -z-10" aria-hidden="true"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Kisah Nyata Alumni
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Cerita Mereka yang Sudah <span class="text-gradient">Berubah Lebih Dulu</span>
                </h2>
                <p class="text-lg text-slate-600">
                    AMC sudah memberi jalan terang pada bisnis, karier, finansial rumah tangga, hingga kebahagiaan ribuan alumni kami.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($testimonials as $i => $t)
                    <figure class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative {{ $i === 1 ? 'lg:-translate-y-4' : '' }}">
                        <i data-lucide="quote" class="absolute top-8 right-8 w-12 h-12 text-primary-100" aria-hidden="true"></i>
                        <blockquote class="text-slate-700 mb-6 relative z-10 font-medium leading-relaxed italic">
                            "{{ $t['quote'] }}"
                        </blockquote>
                        <figcaption class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full border-2 border-primary-100 bg-slate-100 flex items-center justify-center text-primary-600 font-bold">
                                {{ $t['initial'] }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-900">{{ $t['name'] }}</p>
                                <p class="text-sm text-primary-600 font-medium">{{ $t['role'] }}</p>
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================================================
       | 6. MEDIA COVERAGE
       |====================================================== --}}
    @php
        $mediaLogos = [
            ['src' => asset('assets/images/sindonews.webp'), 'alt' => 'SindoNews'],
            ['src' => asset('assets/images/tribunnews.webp'), 'alt' => 'TribunNews'],
            ['src' => asset('assets/images/merdeka.webp'), 'alt' => 'Merdeka.com'],
            ['src' => asset('assets/images/radarsurabaya.webp'), 'alt' => 'Radar Surabaya', 'hideOnSm' => true],
            ['src' => asset('assets/images/duta.co.webp'), 'alt' => 'Duta Nusantara', 'hideOnMd' => true],
        ];
    @endphp
    <x-media-coverage :logos="$mediaLogos" class="py-16 border-t border-slate-200 bg-white" />

    {{-- ======================================================
       | 7. FINAL CTA
       |====================================================== --}}
    <section class="py-20 lg:py-24 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-primary-900 rounded-[2.5rem] overflow-hidden relative shadow-2xl">
                <div class="absolute inset-0" aria-hidden="true">
                    <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-600 rounded-full mix-blend-multiply opacity-50 blur-3xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-accent-600 rounded-full mix-blend-multiply opacity-20 blur-3xl"></div>
                </div>

                <div class="relative p-10 md:p-16 text-center z-10">
                    <h2 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-white mb-4 leading-tight">
                        Siap Mengubah Hidupmu Hari Ini?
                    </h2>
                    <p class="text-primary-100 mb-10 max-w-2xl mx-auto text-lg leading-relaxed">
                        Konsultasi gratis dengan tim AMC via WhatsApp. Kami bantu pilih program yang paling cocok dengan kondisimu sekarang.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        <a
                            href="https://wa.me/6281230633464?text=Halo%2C%20saya%20mau%20konsultasi%20program%20AMC"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="ripple inline-flex items-center justify-center gap-2 bg-accent-500 hover:bg-accent-600 text-white px-8 py-4 rounded-full font-bold text-lg transition-all shadow-lg shadow-accent-500/30 transform hover:-translate-y-1"
                        >
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                            Konsultasi via WhatsApp
                        </a>
                        <a
                            href="{{ url('/produk') }}"
                            class="inline-flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 backdrop-blur text-white border border-white/30 px-8 py-4 rounded-full font-bold text-lg transition-all"
                        >
                            <i data-lucide="library" class="w-5 h-5"></i>
                            Jelajahi Semua Produk
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.store>
