# Rancangan Desain dan Sistem Antarmuka (Firman Pratama - AMC)

Dokumen ini merupakan panduan desain komprehensif, diekstraksi dari penerapan gaya pada `index.html`. Tujuannya adalah untuk memastikan konsistensi visual dan pengalaman pengguna (UX) di seluruh halaman (prototype maupun implementasi final) pada aplikasi Firman Pratama Web/Affiliate.

## 1. Filosofi Desain
Mengadopsi gaya modern *SaaS/Start-up* yang bersih, elegan, dan profesional, dipadukan dengan kesan magis dan futuristik melalui penggunaan Abstract Gradients, efek Blur, dan Glassmorphism.

## 2. Fondasi / Tech-Stack UI
- **Framework CSS:** Tailwind CSS v3 (via CDN)
- **Ikonografi:** Lucide Icons (script tag)
- **Tipografi:** Google Fonts - **'Inter'**, sans-serif

## 3. Palet Warna Utama
*Color Palette* ini wajib di-\*extend* di `tailwind.config` pada setiap halaman untuk memastikan ketersediaan kelas warna secara global:

- **Primary (Indigo/Blue)**
  - `50`: `#eef2ff` (Background badge/tanda ringan)
  - `100`: `#e0e7ff`
  - `500`: `#6366f1`
  - `600`: `#4f46e5` (Warna utama untuk Tombol CTA dan Aksen)
  - `700`: `#4338ca`
  - `900`: `#312e81`
- **Secondary (Teal)**
  - `50`: `#f0fdfa`
  - `500`: `#14b8a6`
  - `600`: `#0d9488`
  - `900`: `#134e4a`
- **Accent (Amber/Orange)**
  - `500`: `#f59e0b`
  - `600`: `#d97706`
- **Base / Neutral (Slate - Default Tailwind)**
  - `slate-50` (`#f8fafc`) s/d `slate-100`: Background warna terang (seksi & kartu).
  - `slate-600`: Warna standar untuk teks paragraf/body copy.
  - `slate-900`: Warna dasar teks untuk Judul/Heading.
  - Tambahan ekstensi `slate-850: '#1e293b'` untuk variasi warna gelap transisi.

## 4. Tipografi
- Semua teks menggunakan font **Inter**.
- **Heading (H1, H2, H3):** Umumnya menggunakan kelas `font-extrabold` (weight 800) atau `font-bold` (weight 700) dengan `leading-tight`.
- **Sub-heading / Kicker:** Kerap menggunakan gaya all-caps dengan letter-spacing yang besar (contoh: `text-xs tracking-[0.2em] font-extrabold uppercase`).
- **Body Text (p):** Biasanya menggunakan standar `text-lg text-slate-600 leading-relaxed` untuk kemudahan membaca.
- **Teks Gradasi (Special Effect):**
  Menggunakan kustom CSS class `.text-gradient` (gradient linear ke kanan dari `#4f46e5` primary ke `#0d9488` secondary).

## 5. Komponen Visual & Style Global

### A. Background Ornamen Abstrak (Blob & Gradients)
Menghasilkan nuansa futuristis, desain menggunakan _div_ tersembunyi dengan konfigurasi khusus di background seksi yang direlatifkan (`relative overflow-hidden`):
- **Lingkaran Abstrak:** `<div class="w-96 h-96 bg-primary-300 rounded-full mix-blend-multiply blur-3xl opacity-30"></div>`
- Lingkaran ini biasa dikombinasikan dengan efek animasi bergetar (blob).

### B. Glassmorphism (Efek Kaca)
Digunakan pada Navigation Bar, Panel Transparan, dan elemen Accent melayang:
- **Class CSS `.glass`:**
  - `background: rgba(255, 255, 255, 0.85);`
  - `backdrop-filter: blur(12px);`
  - `border: 1px solid rgba(255, 255, 255, 0.5);`
- Tersedia pula varian `.glass-dark` untuk bagian antarmuka Gelap (`slate-900`).

### C. Cards (Kartu)
Ciri khas gaya Card yang konsisten untuk fitur layanan, materi kelas, dan buku fisik:
- **Gaya Standar:** `bg-white rounded-2xl` atau `rounded-3xl` (Bahkan sering sampai px-rad ekstrim `rounded-[2.5rem]`).
- **Border & Shadow:** `border border-slate-100 shadow-sm`.
- **Transisi Hover:** Menggunakan custom class `.hover-lift` yang mana pada saat cursor disorot kartu akan sedikit naik (`transform: translateY(-8px)`) dan bayangan membesar/melebar.

### D. Buttons (Tombol CTA)
- **Tombol Utama (Solid):** `bg-primary-600 hover:bg-primary-700 text-white rounded-full` (kemudian sering ditambahkan dengan `font-bold` / `font-semibold` serta bayangan rona `shadow-lg shadow-primary-500/30`).
- **Tombol Outline:** `bg-white border border-slate-200 text-slate-700 hover:bg-slate-50`.
- **Tombol Transisi:** Hover translation via utilities Tailwind `transform hover:-translate-y-1`.
- **Ripple Effect:** Sebuah efek gelombang animasi pasca klik ditangani via manipulasi native pseudo-element pada custom class `.ripple`.

### E. Ikonografi
Ikon yang dirender menggunakan atribut `data-lucide`. Pada banyak modul seperti badge layanan, ikon dibungkus dalam wadah (container/kotak atau bulat) berbentuk `w-10 h-10` atau `w-14 h-14` dengan background yang senada tapi lebih soft (Contoh: `bg-primary-50 text-primary-500`).

### F. Interaktif & Animasi
Animasi dideklarasikan di Custom Tailwind Config JS:
- **`.animate-float` / `.animate-float-delayed`**: Element bergerak naik-turun halus (bagus untuk aksen absolut atau gambar hero).
- **`.animate-blob`**: Modifikasi scale dan translate dari sudut ke sudut untuk blob background.
- **`.animate-fade-in-up` / `.hover-lift`**: Efek presentasi saat kartu atau elemen pertama kali muncul di layar saat di *scroll*. (Catatan: Index `intersectionObserver` untuk counter number logic ada di skrip bawaan `index.html`.

## 6. Layout dan Spacing
- Container layout disentralkan menggunakan: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8` (Sekitar `1280px`).
- Jarak seksi (Section Padding) biasanya antara `py-20` sampai `py-24`.
- Grid: Pemanfaatan `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8` yang ramah ponsel, beradaptasi dan reflow fluidly dari 1 pilar (HP), 2 pilar (Tablet), sampai 3 atau 4 Pilar konten (Desktop).

## 7. Ketentuan Implementasi Halaman Baru / Revisi
1. Setiap halaman HTML (atau file `.blade.php` kelak) baru harus menyalin skrip konfigurasi `tailwind.config` serta stye base `#custom` yang berada di dalam blok `<head>` pada `index.html`.
2. Jangan menggunakan stylesheet terpisah untuk style utility yang diakomodasi oleh Tailwind *class*, melainkan buat di dalam block `tailwind.config` agar sejalan dengan filosofi utility-first (Kecuali untuk animasi custom komplit seperti pseudo `.ripple`).
3. Tetap gunakan **Lucide Icons** dan hindari font custom icon lain seperti FontAwesome agar berat aset (size) lebih kecil dan seragam.
4. Terapkan kelas `.glass` pada Navbar dan komponen melayang overlay.
5. Pertahankan corner-radius yang besar pada bagian penampang / wrapper kotak dengan `rounded-2xl` atau `rounded-3xl` dibanding radius kecil tajam.
