@props(['banner', 'action', 'method' => 'POST'])

@php
    $startsAtValue = old('starts_at', optional($banner->starts_at)->format('Y-m-d\TH:i'));
    $endsAtValue = old('ends_at', optional($banner->ends_at)->format('Y-m-d\TH:i'));
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data"
      x-data="{ preview: {{ $banner->exists && $banner->imageUrl() ? '\''.e($banner->imageUrl()).'\'' : 'null' }},
                onPick(e) { const f = e.target.files[0]; if (f) this.preview = URL.createObjectURL(f); } }"
      class="space-y-6">
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <x-admin.card>
        <div class="space-y-5">
            {{-- Judul --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Judul Banner <span class="text-error-500">*</span>
                </label>
                <input id="title" type="text" name="title"
                       value="{{ old('title', $banner->title) }}"
                       required maxlength="200"
                       placeholder="Mis. Kelas Reguler AMC — Surabaya 23 Mei 2026"
                       class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dipakai sebagai alt text gambar (SEO/aksesibilitas) & label di daftar.</p>
                @error('title')
                    <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Gambar --}}
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Gambar Banner @if (! $banner->exists) <span class="text-error-500">*</span> @endif
                </label>
                <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       @change="onPick"
                       class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    JPG/PNG/WebP, maks 4 MB. Rasio lebar (mis. 1280×312) paling pas untuk slot homepage.
                    @if ($banner->exists) Kosongkan bila tidak ingin mengganti gambar. @endif
                </p>
                @error('image')
                    <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                @enderror

                {{-- Preview --}}
                <template x-if="preview">
                    <img :src="preview" alt="Preview banner"
                         class="mt-3 w-full max-w-xl rounded-xl ring-1 ring-gray-200 dark:ring-gray-700">
                </template>
            </div>

            {{-- Link tujuan --}}
            <div>
                <label for="link_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Link Tujuan (saat banner diklik)
                </label>
                <input id="link_url" type="url" name="link_url"
                       value="{{ old('link_url', $banner->link_url) }}"
                       maxlength="2048"
                       placeholder="https://wa.me/6281230633464?text=Saya%20mau%20daftar..."
                       class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan bila banner tidak perlu bisa diklik.</p>
                @error('link_url')
                    <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Jendela tayang --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="starts_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mulai Tayang</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ $startsAtValue }}"
                           class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosong = langsung tayang.</p>
                    @error('starts_at')
                        <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ends_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Akhir Tayang</label>
                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ $endsAtValue }}"
                           class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Isi tanggal event supaya banner otomatis hilang setelah lewat.</p>
                    @error('ends_at')
                        <p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Urutan --}}
            <div class="max-w-[180px]">
                <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Urutan</label>
                <input id="sort_order" type="number" name="sort_order" min="0" max="9999" step="1"
                       value="{{ old('sort_order', $banner->sort_order ?? 0) }}" required
                       class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kecil = tampil duluan.</p>
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                <input id="active" type="checkbox" name="active" value="1"
                       @checked(old('active', $banner->active ?? true))
                       class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                <label for="active" class="text-sm text-gray-700 dark:text-gray-300">
                    Aktif — tampil di homepage (dalam jendela tayang)
                </label>
            </div>
        </div>
    </x-admin.card>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.promo-banners.index') }}"
           class="text-sm text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90">Batal</a>
        <x-admin.button type="submit">
            {{ $banner->exists ? 'Simpan Perubahan' : 'Buat Banner' }}
        </x-admin.button>
    </div>
</form>
