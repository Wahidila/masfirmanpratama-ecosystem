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

    $fallbackVideoTestimonials = [
        ['video' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/27-1.mp4', 'poster' => null, 'title' => 'Dari AMC Saya Sadar Hidup Ini Indah, Enak dan Menyenangkan', 'name' => 'Ria Handayani', 'role' => 'Alumni AMC'],
        ['video' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/bener-28-2.mp4', 'poster' => null, 'title' => 'Kita Bisa Mencapai Apapun dengan Kekuatan Pikiran', 'name' => 'Fitria', 'role' => 'Alumni AMC'],
        ['video' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/bener-1.mp4', 'poster' => null, 'title' => 'AMC Adalah Ilmu yang Sangat Mind Blowing', 'name' => 'Edi', 'role' => 'Alumni AMC'],
        ['video' => 'https://masfirmanpratama.com/wp-content/uploads/2024/08/27-3.mp4', 'poster' => null, 'title' => 'AMC Adalah Ilmu yang "Daging" Banget', 'name' => 'Ane', 'role' => 'Alumni AMC'],
    ];
    $videoTestimonials = ! empty($videoTestimonials ?? []) ? $videoTestimonials : $fallbackVideoTestimonials;

    $journeyStats = [
        [
            'value' => '2,550+',
            'label' => 'Peserta yang sudah belajar bersama Mas Firman dan merasakan Keajaiban',
            'icon' => 'users',
        ],
        [
            'value' => '12+',
            'label' => 'Tahun Berpengalaman membantu masalah Banyak orang',
            'icon' => 'badge-check',
        ],
        [
            'value' => '125+',
            'label' => 'Artikel tentang Keajaiban Pikiran yang sudah ditulis Mas Firman',
            'icon' => 'newspaper',
        ],
        [
            'value' => '1,000+',
            'label' => 'Video Mas Firman di Channel Youtube Cahaya Kehidupan',
            'icon' => 'youtube',
        ],
    ];

    $lifeProblems = [
        [
            'title' => 'Bisnis Terasa Lesu',
            'body' => 'Sudah melakukan berbagai usaha berbagai ilmu penjualan tapi bisnis masih sepi saja.',
            'icon' => 'trending-down',
        ],
        [
            'title' => 'Hutang Menumpuk',
            'body' => 'Hutang terus menumpuk dan tidak kunjung lunas padahal sudah berusaha kesana kemari.',
            'icon' => 'wallet',
        ],
        [
            'title' => 'Susah Naik Jabatan',
            'body' => 'Sudah rajin bekerja bertahun-tahun, rajin absen tapi susah untuk naik jabatan malah dicuekin atasan.',
            'icon' => 'briefcase-business',
        ],
        [
            'title' => 'Hidup Terasa Stagnan',
            'body' => 'Usia bertambah tetapi hidup masih biasa saja, belum punya rumah dan belum ada mobil.',
            'icon' => 'circle-pause',
        ],
        [
            'title' => 'Anak Susah Menurut',
            'body' => 'Sering memarahi anak, tapi anak malah semakin susah menurut dan malas belajar.',
            'icon' => 'heart-handshake',
        ],
        [
            'title' => 'Pasangan Pergi',
            'body' => 'Orang yang anda cintai tiba-tiba berubah. Anda ingin membuat sesorang suka kepada anda?',
            'icon' => 'heart-crack',
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

                                <div class="absolute -top-4 -right-8 bg-white p-4 rounded-xl shadow-xl glass z-30 animate-float-delayed flex items-center gap-3 border border-white/50">
                                    <div class="w-10 h-10 bg-accent-100 rounded-full flex items-center justify-center shrink-0">
                                        <i data-lucide="award" class="w-5 h-5 text-accent-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Expertise</p>
                                        <p class="text-sm font-bold text-slate-800">Mindset Mastery</p>
                                    </div>
                                </div>

                                <div class="absolute -bottom-6 -left-8 bg-white p-4 rounded-xl shadow-xl glass z-30 animate-float flex items-center gap-3 border border-white/50">
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
       | PROMO BANNER — Jadwal terdekat (dinamis dari admin,
       | CRUD /admin/promo-banners; auto-hide di luar jendela tayang)
       |====================================================== --}}
    @if (($promoBanners ?? collect())->isNotEmpty())
        <section class="py-10 lg:py-14 bg-slate-50" aria-label="Promo event terdekat">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase text-center">
                    Jadwal Terdekat
                </p>
                @foreach ($promoBanners as $banner)
                    @if ($banner->link_url)
                        <a
                            href="{{ $banner->link_url }}"
                            target="_blank"
                            rel="noopener"
                            class="block rounded-2xl lg:rounded-3xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                            aria-label="{{ $banner->title }}"
                        >
                            <img
                                src="{{ $banner->imageUrl() }}"
                                alt="{{ $banner->title }}"
                                width="1280"
                                height="312"
                                loading="lazy"
                                decoding="async"
                                class="w-full h-auto"
                            >
                        </a>
                    @else
                        <div class="rounded-2xl lg:rounded-3xl overflow-hidden shadow-lg">
                            <img
                                src="{{ $banner->imageUrl() }}"
                                alt="{{ $banner->title }}"
                                width="1280"
                                height="312"
                                loading="lazy"
                                decoding="async"
                                class="w-full h-auto"
                            >
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

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
       | JOURNEY + PROBLEM SECTIONS
       |============================================================== --}}
    <section class="py-20 lg:py-24 bg-slate-950 text-white relative overflow-hidden" aria-labelledby="journey-heading">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.18),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(245,158,11,0.14),transparent_34%)]" aria-hidden="true"></div>
        <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent" aria-hidden="true"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mb-12 lg:mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-300 uppercase mb-4">
                    Jejak Perubahan
                </p>
                <h2 id="journey-heading" class="text-3xl md:text-4xl lg:text-5xl font-extrabold leading-tight mb-5">
                    Perjalanan Hidup Menjadi Solusi Bagi Manusia
                </h2>
                <p class="text-lg text-slate-300 leading-relaxed">
                    Dari kelas, artikel, sampai video pembelajaran, Mas Firman terus membagikan Formula AMC untuk membantu lebih banyak orang menemukan jalan perubahan.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                @foreach ($journeyStats as $stat)
                    <div class="group rounded-2xl border border-white/10 bg-white/[0.06] p-6 lg:p-7 backdrop-blur transition-all duration-300 hover:-translate-y-1 hover:bg-white/[0.09] hover:border-accent-300/40">
                        <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 text-accent-300 ring-1 ring-white/10">
                            <i data-lucide="{{ $stat['icon'] }}" class="h-6 w-6"></i>
                        </div>
                        <div class="text-4xl lg:text-5xl font-extrabold tracking-tight text-white mb-4">
                            {{ $stat['value'] }}
                        </div>
                        <p class="text-sm leading-relaxed text-slate-300">
                            {{ $stat['label'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="py-20 lg:py-24 bg-white border-t border-slate-100" aria-labelledby="problems-heading">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Saatnya Berubah
                </p>
                <h2 id="problems-heading" class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Anda Punya Masalah Seperti ini ?
                </h2>
                <p class="text-lg text-slate-600">
                    Jika salah satu kondisi ini terasa dekat dengan hidup anda sekarang, Formula AMC bisa menjadi jalan praktis untuk mulai membalik keadaan.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                @foreach ($lifeProblems as $problem)
                    <article class="group rounded-2xl border border-slate-100 bg-slate-50 p-6 transition-all duration-300 hover:-translate-y-1 hover:border-primary-200 hover:bg-white hover:shadow-lg">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100 text-primary-600 transition-colors group-hover:bg-primary-600 group-hover:text-white">
                            <i data-lucide="{{ $problem['icon'] }}" class="h-6 w-6"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-900 mb-3">
                            {{ $problem['title'] }}
                        </h3>
                        <p class="text-slate-600 leading-relaxed mb-5">
                            {{ $problem['body'] }}
                        </p>
                        <p class="font-bold text-primary-700">
                            Maka anda butuh Formula AMC
                        </p>
                    </article>
                @endforeach
            </div>

            <div class="text-center">
                <a
                    href="https://wa.me/6281230633464?text=Halo,%20saya%20tertarik%20untuk%20mendaftar%20Kelas%20AMC%20"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="ripple inline-flex items-center justify-center gap-2 rounded-full bg-accent-500 px-7 py-4 text-base md:text-lg font-extrabold text-white shadow-lg shadow-accent-500/30 transition-all hover:-translate-y-1 hover:bg-accent-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2"
                >
                    <i data-lucide="message-circle" class="h-5 w-5"></i>
                    Saya Mau Mengubah Hidup #DisiniJalannya
                </a>
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
                @foreach (($classFormats ?? $pricing) as $tier)
                    @php
                        $isHighlight = $tier['highlight'] ?? false;
                        $isDark = $tier['dark'] ?? false;

                        // Hierarki 3 tingkat lewat elevasi/ring/lift — bukan 3 background ramai.
                        // Reguler datar (shadow-sm), Terlaris dominan (ring + shadow-2xl + terangkat),
                        // Platinum dark seremonial (hairline emas tipis).
                        $cardClass = 'group relative flex flex-col h-full rounded-3xl p-8 overflow-hidden hover-lift transition-all '
                            .($isDark
                                ? 'bg-slate-900 border border-slate-800 ring-1 ring-accent-500/25 shadow-xl'
                                : ($isHighlight
                                    ? 'bg-white ring-2 ring-primary-500 shadow-2xl lg:-translate-y-6 z-10'
                                    : 'bg-white border border-slate-200 shadow-sm'));

                        $titleClass = $isDark ? 'text-white' : 'text-slate-900';
                        $taglineClass = $isDark ? 'text-slate-300' : 'text-slate-600';
                        $priceClass = $isDark ? 'text-white' : 'text-slate-900';
                        $featureTextClass = $isDark ? 'text-slate-300' : 'text-slate-700';
                        $checkIconClass = $isDark ? 'text-secondary-400' : 'text-secondary-500';
                        $borderClass = $isDark ? 'border-slate-800' : 'border-slate-100';
                        $noteClass = $isDark ? 'text-slate-400' : 'text-slate-500';

                        // Badge jadi "eyebrow rail" identitas tier (bukan stiker nempel di tepi).
                        $badgeGlyph = $isHighlight ? 'badge-check' : ($isDark ? 'crown' : 'star');
                        $badgeColor = $isHighlight ? 'text-primary-600' : ($isDark ? 'text-accent-400' : 'text-slate-500');

                        // CTA solid & high-contrast per varian; hero=primary, entry=slate, dark=putih.
                        // Hierarki tetap terjaga lewat kartu (elevasi/ring), bukan tombol lemah.
                        $ctaClass = $isHighlight
                            ? 'ripple bg-primary-600 text-white hover:bg-primary-700 shadow-lg shadow-primary-600/25'
                            : ($isDark
                                ? 'bg-white text-slate-900 hover:bg-accent-50 hover:text-accent-700 shadow-md'
                                : 'bg-slate-900 text-white hover:bg-slate-800');
                    @endphp

                    <div class="{{ $cardClass }}">
                        {{-- Wash lembut di puncak kartu hero (Terlaris) --}}
                        @if ($isHighlight)
                            <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-primary-50/70 to-transparent pointer-events-none" aria-hidden="true"></div>
                        @endif

                        {{-- Watermark ikon tier (dekoratif, halus) --}}
                        <div class="absolute top-0 right-0 p-6 opacity-[0.04] group-hover:opacity-10 transition-opacity pointer-events-none" aria-hidden="true">
                            <i data-lucide="{{ $tier['iconAccent'] }}" class="w-32 h-32 {{ $tier['iconColor'] }}"></i>
                        </div>

                        {{-- Eyebrow rail: identitas tier. Tinggi tetap (h-4) agar 3 kartu sebaris --}}
                        <div class="relative z-10 flex items-center gap-1.5 mb-5 h-4 text-[11px] font-extrabold uppercase tracking-[0.18em] {{ $badgeColor }}">
                            @if (! empty($tier['badge']))
                                <i data-lucide="{{ $badgeGlyph }}" class="w-3.5 h-3.5"></i>
                                <span>{{ $tier['badge'] }}</span>
                            @endif
                        </div>

                        {{-- Header: nama + tagline (line-clamp 2 baris, ganti hack min-height) --}}
                        <div class="relative z-10">
                            <h3 class="text-2xl font-bold tracking-tight {{ $titleClass }}">{{ $tier['name'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed line-clamp-2 min-h-[40px] {{ $taglineClass }}">{{ $tier['tagline'] }}</p>
                        </div>

                        {{-- Value ledger: coret kecil di atas → harga besar → chip Hemat inline.
                             Dibaca sebagai satu pernyataan nilai, bukan tumpukan vertikal. --}}
                        <div class="relative z-10 mt-6 mb-6 pb-6 border-b {{ $borderClass }}">
                            @if (! empty($tier['originalPrice']))
                                <div class="text-sm line-through font-medium {{ $isDark ? 'text-slate-500' : 'text-slate-400' }}">{{ $tier['originalPrice'] }}</div>
                            @endif
                            <div class="mt-0.5 flex items-baseline gap-2.5 flex-wrap">
                                {{-- whitespace-nowrap = harga selalu 1 baris. Ukuran naik hanya
                                     di xl (≥1280) di mana kartu kolom-3 cukup lebar; di rentang
                                     lg (1024–1279) kartu sempit → tetap text-3xl agar "Rp 22.500.000" muat. --}}
                                <span class="text-3xl xl:text-4xl font-extrabold tracking-tight leading-none whitespace-nowrap {{ $priceClass }}">{{ $tier['price'] }}</span>
                                @if (! empty($tier['discountPercent']))
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isDark ? 'bg-rose-500/15 text-rose-300' : 'bg-rose-50 text-rose-600' }}">
                                        <i data-lucide="tag" class="w-3 h-3"></i>
                                        Hemat {{ $tier['discountPercent'] }}%
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Fitur --}}
                        <ul class="relative z-10 space-y-3.5 mb-8 flex-grow text-sm font-medium {{ $featureTextClass }}">
                            @foreach ($tier['features'] as $feature)
                                <li class="flex items-start gap-3">
                                    <i data-lucide="check-circle-2" class="w-5 h-5 shrink-0 mt-0.5 {{ $checkIconClass }}"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        {{-- CTA + reassurance cicilan --}}
                        <div class="relative z-10 mt-auto">
                            <a
                                href="{{ $tier['ctaHref'] }}"
                                aria-label="{{ $tier['ctaLabel'] }} — {{ $tier['name'] }}, {{ $tier['price'] }}"
                                class="group/btn inline-flex w-full items-center justify-center gap-2 rounded-xl py-3.5 font-bold transition-all hover:shadow-lg active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-primary-500 {{ $isDark ? 'focus-visible:ring-offset-slate-900' : '' }} {{ $ctaClass }}"
                            >
                                <span>{{ $tier['ctaLabel'] }}</span>
                                <i data-lucide="arrow-right" class="w-4 h-4 transition-transform group-hover/btn:translate-x-0.5"></i>
                            </a>

                            @if (! empty($tier['priceNote']))
                                <p class="mt-4 text-xs text-center flex items-center justify-center gap-1.5 {{ $noteClass }}">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span>{{ ltrim($tier['priceNote'], '*') }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================================================
       | 5. TESTIMONI VIDEO — hosted cards
       |====================================================== --}}
    <section id="testimoni-video" class="py-20 lg:py-24 bg-white border-t border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <p class="text-xs tracking-[0.2em] font-extrabold text-accent-600 uppercase mb-4">
                    Testimoni Peserta AMC
                </p>
                <h2 class="text-3xl md:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Sebagian Kecil Kisah Nyata dari <span class="text-gradient">Peserta Kelas AMC</span>
                </h2>
                <p class="text-lg text-slate-600">
                    Dengarkan langsung pengalaman alumni yang sudah mempraktikkan Formula AMC dan merasakan perubahan dalam hidupnya.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($videoTestimonials as $videoTestimonial)
                    <article class="group overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:border-primary-200">
                        <div class="relative bg-slate-950 aspect-[9/16] overflow-hidden">
                            <video
                                class="h-full w-full object-cover"
                                src="{{ $videoTestimonial['video'] }}"
                                @if(! empty($videoTestimonial['poster'])) poster="{{ $videoTestimonial['poster'] }}" @endif
                                controls
                                preload="metadata"
                                playsinline
                                controlsList="nodownload"
                                aria-label="Video testimoni {{ $videoTestimonial['name'] }}"
                            ></video>
                        </div>
                        <div class="p-5">
                            <h3 class="text-base font-extrabold leading-snug text-slate-900 mb-3">
                                {{ $videoTestimonial['title'] }}
                            </h3>
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-sm font-extrabold text-primary-700">
                                    {{ mb_substr($videoTestimonial['name'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $videoTestimonial['name'] }}</p>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">{{ $videoTestimonial['role'] ?? 'Alumni AMC' }}</p>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ======================================================
       | 6. TESTIMONI — static grid
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
