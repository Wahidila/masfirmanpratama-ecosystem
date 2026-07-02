@props([
    'course',
    'mode' => 'create', // create | edit
])

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('admin.courses.update', $course)
        : route('admin.courses.store');

    $metaSeo = is_array($course->meta_seo ?? null) ? $course->meta_seo : [];

    $existingImage = $course->image_path
        ? asset($course->image_path)
        : null;

    $initialSchedule = old('schedule', $course->schedule ?? []);
    $initialBenefits = old('benefits', $course->benefits ?? []);
    $initialTestimonials = old('testimonials', $course->testimonials ?? []);

    $descriptionRaw = old('description_raw', is_array($course->description ?? null) ? implode("\n\n", $course->description) : '');
    $syllabusRaw = old('syllabus_raw', is_array($course->syllabus ?? null) ? implode("\n", $course->syllabus) : '');
    $cardFeaturesRaw = old('card_features_raw', is_array($course->card_features ?? null) ? implode("\n", $course->card_features) : '');
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="courseForm({
        autoSlug: !{{ $isEdit ? 'true' : 'false' }},
        existingImage: @js($existingImage),
        initialTitle: @js(old('title', $course->title)),
        initialSlug: @js(old('slug', $course->slug)),
        initialStatus: @js(old('status', $course->status ?? 'draft')),
        initialSchedule: @js($initialSchedule),
        initialBenefits: @js($initialBenefits),
        initialTestimonials: @js($initialTestimonials),
    })"
    @submit="onSubmit($event)">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    {{-- Status disetir segmented control di sidebar Publikasi; dikirim via hidden. --}}
    <input type="hidden" name="status" :value="status">

    <div class="grid items-start gap-6 lg:grid-cols-3">
        {{-- ══════════ MAIN COLUMN ══════════ --}}
        <div class="space-y-6 lg:col-span-2">

    {{-- 1. Identitas Kelas --}}
    <x-admin.card title="Identitas kelas">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Judul kelas" for="title" name="title" required class="sm:col-span-2">
                <input
                    type="text"
                    id="title"
                    name="title"
                    x-model="title"
                    @input="onTitleChange()"
                    value="{{ old('title', $course->title) }}"
                    maxlength="200"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="mis. Kelas AMC Reguler">
            </x-admin.form-group>

            <x-admin.form-group label="Slug" for="slug" name="slug" required
                hint="Otomatis dari judul. Pakai huruf kecil + tanda hubung (mis. kelas-amc-reguler).">
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">/produk/</span>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        x-model="slug"
                        @input="autoSlug = false"
                        value="{{ old('slug', $course->slug) }}"
                        maxlength="200"
                        class="h-11 w-full rounded-r-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </x-admin.form-group>

            <x-admin.form-group label="Subtitle" for="subtitle" name="subtitle" class="sm:col-span-2">
                <textarea
                    id="subtitle"
                    name="subtitle"
                    rows="2"
                    maxlength="500"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="Deskripsi singkat kelas (tampil di hero / card).">{{ old('subtitle', $course->subtitle) }}</textarea>
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- 2. Harga --}}
    <x-admin.card title="Harga">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Harga (Rp)" for="price" name="price" required>
                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0"
                    step="1"
                    value="{{ old('price', $course->price ?? 0) }}"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="150000">
            </x-admin.form-group>

            <x-admin.form-group label="Harga asli (coret) Rp" for="original_price" name="original_price"
                hint="Tampil sebagai harga coret untuk diskon.">
                <input
                    type="number"
                    id="original_price"
                    name="original_price"
                    min="0"
                    step="1"
                    value="{{ old('original_price', $course->original_price ?? '') }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="250000">
            </x-admin.form-group>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="installment_available"
                        value="1"
                        {{ old('installment_available', $course->installment_available ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    Tersedia cicilan
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Centang jika kelas ini bisa dibayar dengan skema cicilan.</p>
                @if ($course->exists ?? false)
                    <a href="{{ route('admin.installment-schemes.index', ['course' => $course->id]) }}"
                       class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                        <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                        Kelola skema cicilan kelas ini
                    </a>
                @endif
            </div>
        </div>
    </x-admin.card>

    {{-- 3. Meta Tampilan --}}
    <x-admin.card title="Meta tampilan">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-admin.form-group label="Badge" for="badge" name="badge"
                hint="Label kecil di card (mis. POPULER).">
                <input
                    type="text"
                    id="badge"
                    name="badge"
                    maxlength="80"
                    value="{{ old('badge', $course->badge) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="POPULER">
            </x-admin.form-group>

            <x-admin.form-group label="Ikon badge" for="badge_icon" name="badge_icon"
                hint="Pilih ikon dari daftar.">
                <x-admin.icon-picker name="badge_icon" :value="old('badge_icon', $course->badge_icon)" />
            </x-admin.form-group>

            <x-admin.form-group label="Label kategori" for="category_label" name="category_label"
                hint="Mis. KELAS REGULER / WORKSHOP.">
                <input
                    type="text"
                    id="category_label"
                    name="category_label"
                    maxlength="80"
                    value="{{ old('category_label', $course->category_label) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="KELAS REGULER">
            </x-admin.form-group>

            <x-admin.form-group label="Rating" for="rating" name="rating"
                hint="Mis. 4.9">
                <input
                    type="text"
                    id="rating"
                    name="rating"
                    maxlength="20"
                    value="{{ old('rating', $course->rating) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="4.9">
            </x-admin.form-group>

            <x-admin.form-group label="Jumlah siswa" for="student_count" name="student_count"
                hint="Mis. 500+">
                <input
                    type="text"
                    id="student_count"
                    name="student_count"
                    maxlength="30"
                    value="{{ old('student_count', $course->student_count) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="500+">
            </x-admin.form-group>

            <x-admin.form-group label="Tagline" for="tagline" name="tagline" class="sm:col-span-2"
                hint="Tagline singkat di bawah judul hero.">
                <input
                    type="text"
                    id="tagline"
                    name="tagline"
                    maxlength="300"
                    value="{{ old('tagline', $course->tagline) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="Transformasi hidup dimulai dari sini.">
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- 4. Kartu Homepage — Pilih Format Kelas --}}
    <x-admin.card title="Kartu Homepage — Pilih Format Kelas">
        <div class="grid gap-5 sm:grid-cols-3">
            <div class="sm:col-span-3">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="show_on_homepage"
                        value="1"
                        {{ old('show_on_homepage', $course->show_on_homepage ?? false) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    Tampilkan kartu ini di homepage (section Pilih Format Kelas)
                </label>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kartu ini akan muncul di bagian "Pilih Format Kelas" pada halaman utama.</p>
            </div>

            <x-admin.form-group label="Urutan tampil" for="sort_order" name="sort_order"
                hint="Angka lebih kecil tampil lebih kiri (1 = paling kiri).">
                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    min="0"
                    max="9999"
                    value="{{ old('sort_order', $course->sort_order ?? 0) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="1">
            </x-admin.form-group>

            <x-admin.form-group label="Gaya kartu" for="card_style" name="card_style"
                hint="Tampilan visual kartu di homepage.">
                <select
                    id="card_style"
                    name="card_style"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @foreach (['default' => 'Default', 'highlight' => 'Highlight (badge menonjol)', 'dark' => 'Dark (kartu gelap)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('card_style', $course->card_style ?? 'default') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-group>

            <x-admin.form-group label="Label tombol CTA" for="cta_label" name="cta_label"
                hint="Teks tombol ajakan di kartu.">
                <input
                    type="text"
                    id="cta_label"
                    name="cta_label"
                    maxlength="60"
                    value="{{ old('cta_label', $course->cta_label) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="Daftar Reguler">
            </x-admin.form-group>

            <x-admin.form-group label="Ikon kartu" for="card_icon" name="card_icon"
                hint="Pilih ikon dari daftar.">
                <x-admin.icon-picker name="card_icon" :value="old('card_icon', $course->card_icon)" />
            </x-admin.form-group>

            <x-admin.form-group label="Warna ikon" for="card_icon_color" name="card_icon_color"
                hint="Pilih warna dari palet.">
                <x-admin.color-picker name="card_icon_color" :value="old('card_icon_color', $course->card_icon_color)" />
            </x-admin.form-group>

            <x-admin.form-group label="Fitur ringkas kartu (satu per baris)" for="card_features_raw" name="card_features_raw" class="sm:col-span-3"
                hint="Satu fitur per baris. Ditampilkan sebagai bullet list di kartu homepage.">
                <textarea
                    id="card_features_raw"
                    name="card_features_raw"
                    rows="5"
                    maxlength="4000"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="Akses materi seumur hidup&#10;Sertifikat resmi&#10;Grup diskusi eksklusif">{{ $cardFeaturesRaw }}</textarea>
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- 6. Deskripsi (paragraf) --}}
    <x-admin.card title="Deskripsi (paragraf)">
        <x-admin.form-group
            label="Paragraf deskripsi"
            for="description_raw"
            name="description_raw"
            hint="Satu paragraf per baris. Pisahkan dengan baris kosong.">
            <textarea
                id="description_raw"
                name="description_raw"
                rows="6"
                maxlength="8000"
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                placeholder="Paragraf pertama&#10;&#10;Paragraf kedua (setelah baris kosong)&#10;&#10;Paragraf ketiga...">{{ $descriptionRaw }}</textarea>
        </x-admin.form-group>
    </x-admin.card>

    {{-- 7. Silabus --}}
    <x-admin.card title="Silabus">
        <x-admin.form-group
            label="Poin silabus"
            for="syllabus_raw"
            name="syllabus_raw"
            hint="Satu poin per baris.">
            <textarea
                id="syllabus_raw"
                name="syllabus_raw"
                rows="6"
                maxlength="8000"
                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                placeholder="Memahami konsep Mind Power&#10;Teknik Visualisasi Dasar&#10;Latihan Afirmasi Harian">{{ $syllabusRaw }}</textarea>
        </x-admin.form-group>
    </x-admin.card>

    {{-- 8. Jadwal --}}
    <x-admin.card title="Jadwal">
        <template x-for="(item, idx) in schedule" :key="idx">
            <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.03]">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-admin.form-group label="Judul sesi" for="schedule_title" name="schedule[*][title]">
                        <input
                            type="text"
                            :id="'schedule_title_' + idx"
                            :name="'schedule[' + idx + '][title]'"
                            x-model="item.title"
                            maxlength="120"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Sesi 1: Pengenalan">
                    </x-admin.form-group>

                    <x-admin.form-group label="Detail" for="schedule_detail" name="schedule[*][detail]">
                        <input
                            type="text"
                            :id="'schedule_detail_' + idx"
                            :name="'schedule[' + idx + '][detail]'"
                            x-model="item.detail"
                            maxlength="300"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Minggu, 14:00-16:00 WIB">
                    </x-admin.form-group>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="button" @click="schedule.splice(idx, 1)"
                        class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15">
                        <x-admin.icon name="trash" class="h-3 w-3" />
                        Hapus jadwal
                    </button>
                </div>
            </div>
        </template>

        <button type="button" @click="schedule.push({ title: '', detail: '' })"
            class="inline-flex items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-600 hover:border-brand-300 hover:text-brand-600 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:border-brand-500/30 dark:hover:text-brand-400">
            <x-admin.icon name="plus" class="h-4 w-4" />
            Tambah jadwal
        </button>
    </x-admin.card>

    {{-- 9. Benefit --}}
    <x-admin.card title="Benefit">
        <template x-for="(item, idx) in benefits" :key="idx">
            <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.03]">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-admin.form-group label="Ikon" for="benefit_icon" name="benefits[*][icon]"
                        hint="Pilih ikon dari daftar.">
                        <div
                            x-data="{
                                open: false,
                                search: '',
                                get icons() { return window.MFP_ICON_LIST || []; },
                                filtered() { const q = this.search.trim().toLowerCase(); return q ? this.icons.filter(i => i.includes(q)) : this.icons; },
                                renderSoon() { this.$nextTick(() => window.lucide && window.lucide.createIcons()); },
                                pick(ic) { item.icon = ic; this.open = false; },
                            }"
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                            class="relative"
                        >
                            <input type="hidden" :name="'benefits[' + idx + '][icon]'" :value="item.icon">
                            <button type="button" @click="open = !open; if (open) renderSoon()"
                                class="flex h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-brand-600 dark:text-brand-400">
                                        <template x-if="item.icon"><i :data-lucide="item.icon" class="h-5 w-5"></i></template>
                                    </span>
                                    <span class="truncate" :class="!item.icon && 'text-gray-400'" x-text="item.icon || 'Pilih ikon…'"></span>
                                </span>
                                <x-admin.icon name="chevron-right" class="h-4 w-4 shrink-0 text-gray-400" />
                            </button>
                            <div x-show="open" x-cloak
                                class="absolute left-0 z-30 mt-1 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900">
                                <input type="text" x-model="search" @input="renderSoon()" placeholder="Cari ikon…"
                                    class="mb-2 h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                                <div class="grid max-h-72 grid-cols-6 gap-1 overflow-y-auto">
                                    <template x-for="ic in filtered()" :key="ic">
                                        <button type="button" @click="pick(ic)" :title="ic"
                                            :class="item.icon === ic ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-transparent text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]'"
                                            class="flex h-9 w-9 items-center justify-center rounded-lg border">
                                            <i :data-lucide="ic" class="h-5 w-5"></i>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </x-admin.form-group>

                    <x-admin.form-group label="Judul benefit" for="benefit_title" name="benefits[*][title]">
                        <input
                            type="text"
                            :id="'benefit_title_' + idx"
                            :name="'benefits[' + idx + '][title]'"
                            x-model="item.title"
                            maxlength="120"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Pola Pikir Baru">
                    </x-admin.form-group>

                    <x-admin.form-group label="Deskripsi" for="benefit_desc" name="benefits[*][desc]">
                        <input
                            type="text"
                            :id="'benefit_desc_' + idx"
                            :name="'benefits[' + idx + '][desc]'"
                            x-model="item.desc"
                            maxlength="300"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Kembangkan mindset positif untuk transformasi diri.">
                    </x-admin.form-group>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="button" @click="benefits.splice(idx, 1)"
                        class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15">
                        <x-admin.icon name="trash" class="h-3 w-3" />
                        Hapus benefit
                    </button>
                </div>
            </div>
        </template>

        <button type="button" @click="benefits.push({ icon: '', title: '', desc: '' })"
            class="inline-flex items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-600 hover:border-brand-300 hover:text-brand-600 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:border-brand-500/30 dark:hover:text-brand-400">
            <x-admin.icon name="plus" class="h-4 w-4" />
            Tambah benefit
        </button>
    </x-admin.card>

    {{-- 10. Testimoni --}}
    <x-admin.card title="Testimoni">
        <template x-for="(item, idx) in testimonials" :key="idx">
            <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.03]">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-admin.form-group label="Nama" for="testimonial_name" name="testimonials[*][name]">
                        <input
                            type="text"
                            :id="'testimonial_name_' + idx"
                            :name="'testimonials[' + idx + '][name]'"
                            x-model="item.name"
                            maxlength="120"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Siti Aisyah">
                    </x-admin.form-group>

                    <x-admin.form-group label="Peran" for="testimonial_role" name="testimonials[*][role]">
                        <input
                            type="text"
                            :id="'testimonial_role_' + idx"
                            :name="'testimonials[' + idx + '][role]'"
                            x-model="item.role"
                            maxlength="120"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Peserta AMC 2025">
                    </x-admin.form-group>

                    <x-admin.form-group label="Kutipan" for="testimonial_quote" name="testimonials[*][quote]">
                        <input
                            type="text"
                            :id="'testimonial_quote_' + idx"
                            :name="'testimonials[' + idx + '][quote]'"
                            x-model="item.quote"
                            maxlength="500"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Kelas ini benar-benar mengubah cara pandang saya...">
                    </x-admin.form-group>
                </div>

                <div class="mt-3 flex justify-end">
                    <button type="button" @click="testimonials.splice(idx, 1)"
                        class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15">
                        <x-admin.icon name="trash" class="h-3 w-3" />
                        Hapus testimoni
                    </button>
                </div>
            </div>
        </template>

        <button type="button" @click="testimonials.push({ name: '', role: '', quote: '' })"
            class="inline-flex items-center gap-2 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-600 hover:border-brand-300 hover:text-brand-600 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400 dark:hover:border-brand-500/30 dark:hover:text-brand-400">
            <x-admin.icon name="plus" class="h-4 w-4" />
            Tambah testimoni
        </button>
    </x-admin.card>

    {{-- 11. SEO --}}
    <x-admin.card title="SEO">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Meta title (SEO)" for="meta_title" name="meta_title"
                hint="Maks 160 karakter. Tampil di Google search result.">
                <input
                    type="text"
                    id="meta_title"
                    name="meta_title"
                    maxlength="160"
                    value="{{ old('meta_title', $metaSeo['title'] ?? '') }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>

            <x-admin.form-group label="Meta description (SEO)" for="meta_description" name="meta_description"
                hint="Maks 320 karakter. Snippet di hasil pencarian Google.">
                <input
                    type="text"
                    id="meta_description"
                    name="meta_description"
                    maxlength="320"
                    value="{{ old('meta_description', $metaSeo['description'] ?? '') }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>
        </div>
    </x-admin.card>

        </div>{{-- ══════════ /MAIN COLUMN ══════════ --}}

        {{-- ══════════ SIDEBAR ══════════ --}}
        <div class="space-y-6 lg:col-span-1 lg:sticky lg:top-28">

            {{-- Publikasi (status paling menonjol) --}}
            <x-admin.card title="Publikasi">
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-1 rounded-xl border border-gray-200 p-1 dark:border-gray-700"
                        role="radiogroup" aria-label="Status publikasi kelas">
                        @foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Arsip'] as $value => $label)
                            <button type="button" @click="status = '{{ $value }}'"
                                role="radio" :aria-checked="status === '{{ $value }}' ? 'true' : 'false'"
                                :class="status === '{{ $value }}'
                                    ? '{{ $value === 'active' ? 'bg-success-500 text-white' : ($value === 'archived' ? 'bg-gray-500 text-white' : 'bg-brand-500 text-white') }}'
                                    : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/[0.03]'"
                                class="rounded-lg px-2 py-2 text-xs font-semibold transition">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        <span x-show="status === 'draft'" x-cloak>Belum tayang — tersimpan tapi tak muncul di store.</span>
                        <span x-show="status === 'active'" x-cloak>Live — tampil di store untuk pembeli.</span>
                        <span x-show="status === 'archived'" x-cloak>Disembunyikan dari store.</span>
                    </p>
                    @error('status')<p class="text-theme-xs text-error-500">{{ $message }}</p>@enderror
                </div>
            </x-admin.card>

            {{-- Gambar kelas --}}
            <x-admin.card title="Gambar kelas">
                <div class="space-y-4">
                    <div class="relative aspect-square w-full overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Preview gambar kelas" class="h-full w-full object-cover">
                        </template>
                        <template x-if="!previewUrl">
                            <div class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-gray-400 dark:text-gray-500">
                                <x-admin.icon name="image" class="h-8 w-8" />
                                <span class="text-xs">Belum ada gambar</span>
                            </div>
                        </template>
                    </div>

                    <x-admin.form-group
                        label="{{ $isEdit ? 'Ganti gambar (opsional)' : 'Upload gambar' }}"
                        for="image" name="image"
                        hint="JPG, PNG, WebP. Maks 2 MB. Min 600 × 600 px.">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                            @change="onImageChange($event)"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                        @if ($isEdit && $course->image_path)
                            <label class="mt-3 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                <input type="checkbox" name="remove_image" value="1" @change="if ($event.target.checked) previewUrl = null; else previewUrl = @js($existingImage)"
                                    class="rounded border-gray-300 text-error-500 focus:ring-error-200 dark:border-gray-700">
                                Hapus gambar saat ini
                            </label>
                        @endif
                    </x-admin.form-group>
                </div>
            </x-admin.card>
        </div>
    </div>{{-- ══════════ /GRID ══════════ --}}

    {{-- Sticky action bar — Simpan selalu terlihat saat scroll --}}
    <div class="sticky bottom-0 z-20 mt-6 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <x-admin.button href="{{ route('admin.courses.index') }}" variant="outline">Batal</x-admin.button>
        <button type="submit"
            :disabled="submitting"
            :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600">
            <x-admin.icon name="check" class="h-4 w-4" />
            <span x-text="submitting ? 'Menyimpan…' : '{{ $isEdit ? 'Simpan perubahan' : 'Tambahkan kelas' }}'"></span>
        </button>
    </div>
</form>

@push('scripts')
<script>
    function courseForm({ autoSlug, existingImage, initialTitle, initialSlug, initialStatus, initialSchedule, initialBenefits, initialTestimonials }) {
        return {
            title: initialTitle || '',
            slug: initialSlug || '',
            status: initialStatus || 'draft',
            autoSlug: autoSlug,
            previewUrl: existingImage || null,
            submitting: false,
            schedule: Array.isArray(initialSchedule) ? initialSchedule : [],
            benefits: Array.isArray(initialBenefits) ? initialBenefits : [],
            testimonials: Array.isArray(initialTestimonials) ? initialTestimonials : [],

            kebabCase(str) {
                return String(str || '')
                    .toLowerCase()
                    .normalize('NFKD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim()
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
            },

            onTitleChange() {
                if (this.autoSlug) {
                    this.slug = this.kebabCase(this.title);
                }
            },

            onImageChange(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) {
                    this.previewUrl = existingImage || null;
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => { this.previewUrl = e.target.result; };
                reader.readAsDataURL(file);
            },

            onSubmit() {
                this.submitting = true;
            },
        };
    }
</script>
@endpush
