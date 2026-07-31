@props([
    'product',
    'mode' => 'create', // create | edit
])

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('admin.products.update', $product)
        : route('admin.products.store');

    $metaSeo = is_array($product->meta_seo ?? null) ? $product->meta_seo : [];
    $hasSeo = ! empty($metaSeo['title']) || ! empty($metaSeo['description']);

    $existingImage = $product->image_path ? asset($product->image_path) : null;

    // Kelas input dipakai berulang — definisikan sekali biar markup ramping.
    $inputCls = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="productForm({
        autoSlug: !{{ $isEdit ? 'true' : 'false' }},
        existingImage: @js($existingImage),
        initialTitle: @js(old('title', $product->title)),
        initialSlug: @js(old('slug', $product->slug)),
        initialStatus: @js(old('status', $product->status ?? 'draft')),
        initialPrice: @js((string) old('price', $product->price ?? '')),
        initialDescription: @js(old('description', $product->description ?? '')),
        initialMetaTitle: @js(old('meta_title', $metaSeo['title'] ?? '')),
        initialMetaDescription: @js(old('meta_description', $metaSeo['description'] ?? '')),
    })"
    @submit="onSubmit($event)">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    <input type="hidden" name="type" value="book">
    {{-- Status disetir segmented control di sidebar; nilainya dikirim lewat hidden ini. --}}
    <input type="hidden" name="status" :value="status">

    <div class="grid items-start gap-6 lg:grid-cols-3">

        {{-- ══════════════ MAIN COLUMN ══════════════ --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- Identitas --}}
            <x-admin.card title="Identitas produk">
                <div class="space-y-5">
                    <x-admin.form-group label="Judul produk" for="title" name="title" required>
                        <input type="text" id="title" name="title" x-model="title" @input="onTitleChange()"
                            value="{{ old('title', $product->title) }}" maxlength="200" required
                            class="{{ $inputCls }}" placeholder="mis. Buku Mind Power 101">
                        <p class="mt-1.5 text-theme-xs text-gray-400 dark:text-gray-500 text-right">
                            <span x-text="title.length"></span>/200
                        </p>
                    </x-admin.form-group>

                    <x-admin.form-group label="Slug" for="slug" name="slug" required
                        hint="Otomatis dari judul. Huruf kecil + tanda hubung (mis. mind-power-101).">
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">/produk/</span>
                            <input type="text" id="slug" name="slug" x-model="slug" @input="autoSlug = false"
                                value="{{ old('slug', $product->slug) }}" maxlength="200"
                                class="h-11 w-full rounded-r-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        </div>
                    </x-admin.form-group>
                </div>
            </x-admin.card>

            {{-- Harga & stok (berat = satu-satunya sumber, dipakai ongkir) --}}
            <x-admin.card title="Harga & stok">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <x-admin.form-group label="Harga (Rp)" for="price" name="price" required>
                        <input type="number" id="price" name="price" min="0" step="1" x-model="price"
                            value="{{ old('price', $product->price ?? 0) }}" required
                            class="{{ $inputCls }}" placeholder="150000">
                        <p class="mt-1.5 text-theme-xs text-brand-600 dark:text-brand-400" x-show="priceRupiah" x-text="priceRupiah" x-cloak></p>
                    </x-admin.form-group>

                    <x-admin.form-group label="Stok" for="stock" name="stock" required hint="Isi 0 kalau sold out.">
                        <input type="number" id="stock" name="stock" min="0" step="1"
                            value="{{ old('stock', $product->stock ?? 0) }}" required class="{{ $inputCls }}">
                    </x-admin.form-group>

                    <x-admin.form-group label="Berat (kg)" for="weight_kg" name="weight_kg" required
                        hint="Dipakai untuk kalkulasi ongkir.">
                        <input type="number" id="weight_kg" name="weight_kg" min="0" step="0.01"
                            value="{{ old('weight_kg', $product->weight_kg ?? 0) }}" required
                            class="{{ $inputCls }}" placeholder="0.35">
                    </x-admin.form-group>
                </div>
            </x-admin.card>

            {{-- Spesifikasi buku --}}
            <x-admin.card title="Spesifikasi buku">
                @php
                    $specs = old('specs', is_array($product->specs ?? null) ? $product->specs : []);
                    // 'berat' sengaja DIHILANGKAN — berat dikelola field weight_kg (hindari 2 sumber).
                    $defaultKeys = ['penulis', 'penerbit', 'jumlah_halaman', 'tahun_terbit', 'isbn', 'bahasa', 'ukuran'];
                @endphp
                <div x-data="specsForm(@js($specs), @js($defaultKeys))" class="space-y-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Tambahkan spesifikasi lewat pintasan di bawah, atau tombol "Tambah". Baris kosong diabaikan.
                    </p>

                    {{-- Chip pintasan: key umum yang belum dipakai --}}
                    <div class="flex flex-wrap gap-2" x-show="availableSuggestions().length">
                        <template x-for="key in availableSuggestions()" :key="key">
                            <button type="button" @click="addKey(key)"
                                class="inline-flex items-center gap-1 rounded-full border border-dashed border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 transition hover:border-brand-300 hover:bg-gray-50 hover:text-brand-600 dark:border-gray-700 dark:text-gray-300">
                                <x-admin.icon name="plus" class="h-3 w-3" />
                                <span x-text="key"></span>
                            </button>
                        </template>
                    </div>

                    <template x-for="(row, index) in rows" :key="index">
                        <div class="flex items-start gap-2">
                            <input type="text" :name="`specs_keys[${index}]`" x-model="row.key"
                                @blur="row.key = row.key.trim().toLowerCase()"
                                placeholder="Label (mis. penulis)"
                                class="h-10 w-1/3 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                            <input type="text" :name="`specs_values[${index}]`" x-model="row.value"
                                placeholder="Nilai (mis. Firman Pratama)"
                                class="h-10 flex-1 rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                            <button type="button" @click="removeRow(index)" title="Hapus baris"
                                class="inline-flex h-10 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-error-50 hover:text-error-600">
                                <x-admin.icon name="trash" class="h-4 w-4" />
                            </button>
                        </div>
                    </template>

                    <template x-if="rows.length === 0">
                        <p class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-xs text-gray-400 dark:border-gray-800 dark:text-gray-500">
                            Belum ada spesifikasi. Klik pintasan di atas untuk menambah.
                        </p>
                    </template>

                    <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition hover:text-brand-700">
                        <x-admin.icon name="plus" class="h-4 w-4" />
                        Tambah spesifikasi kustom
                    </button>
                </div>
            </x-admin.card>

            {{-- Deskripsi + SEO --}}
            <x-admin.card title="Deskripsi">
                <div class="space-y-5">
                    <x-admin.form-group label="Deskripsi produk" for="description" name="description"
                        hint="Plain text atau markdown ringan.">
                        <textarea id="description" name="description" rows="6" maxlength="8000" x-model="description"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Ceritakan produk ini ke calon pembeli...">{{ old('description', $product->description) }}</textarea>
                        <p class="mt-1.5 text-theme-xs text-gray-400 dark:text-gray-500 text-right">
                            <span x-text="description.length"></span>/8.000
                        </p>
                    </x-admin.form-group>

                    <details class="group rounded-lg border border-gray-200 dark:border-gray-800" @if($hasSeo) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center gap-2">
                                <x-admin.icon name="chevron-right" class="h-4 w-4" />
                                Pengaturan SEO (opsional)
                            </span>
                            <span class="text-xs font-normal text-gray-400">Meta title & description</span>
                        </summary>
                        <div class="space-y-5 border-t border-gray-100 p-4 dark:border-gray-800">
                            <x-admin.form-group label="Meta title" for="meta_title" name="meta_title"
                                hint="Ideal ≤ 60 karakter — di atas itu terpotong di Google.">
                                <input type="text" id="meta_title" name="meta_title" maxlength="160" x-model="metaTitle"
                                    value="{{ old('meta_title', $metaSeo['title'] ?? '') }}" class="{{ $inputCls }}">
                                <p class="mt-1.5 text-theme-xs text-right" :class="metaTitle.length > 60 ? 'text-warning-600 dark:text-warning-500' : 'text-gray-400 dark:text-gray-500'">
                                    <span x-text="metaTitle.length"></span>/160<span x-show="metaTitle.length > 60"> · kepanjangan buat Google</span>
                                </p>
                            </x-admin.form-group>

                            <x-admin.form-group label="Meta description" for="meta_description" name="meta_description"
                                hint="Ideal ≤ 155 karakter untuk snippet Google.">
                                <input type="text" id="meta_description" name="meta_description" maxlength="320" x-model="metaDescription"
                                    value="{{ old('meta_description', $metaSeo['description'] ?? '') }}" class="{{ $inputCls }}">
                                <p class="mt-1.5 text-theme-xs text-right" :class="metaDescription.length > 155 ? 'text-warning-600 dark:text-warning-500' : 'text-gray-400 dark:text-gray-500'">
                                    <span x-text="metaDescription.length"></span>/320<span x-show="metaDescription.length > 155"> · kepanjangan buat Google</span>
                                </p>
                            </x-admin.form-group>
                        </div>
                    </details>
                </div>
            </x-admin.card>
        </div>

        {{-- ══════════════ SIDEBAR ══════════════ --}}
        <div class="space-y-6 lg:col-span-1 lg:sticky lg:top-28">

            {{-- Publikasi (kontrol paling penting → paling menonjol) --}}
            <x-admin.card title="Publikasi">
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-1 rounded-xl border border-gray-200 p-1 dark:border-gray-700"
                        role="radiogroup" aria-label="Status publikasi produk">
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

            {{-- Gambar --}}
            <x-admin.card title="Gambar produk">
                <div class="space-y-4">
                    <div class="relative aspect-square w-full overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                        <template x-if="previewUrl">
                            <img :src="previewUrl" alt="Preview gambar produk" class="h-full w-full object-cover">
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
                        hint="JPG, PNG, WebP. Maks 2 MB. Min 800 × 800 px.">
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                            @change="onImageChange($event)"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                        @if ($isEdit && $product->image_path)
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
    </div>

    {{-- Sticky action bar — selalu terlihat saat scroll (tak perlu ke bawah untuk Simpan) --}}
    <div class="sticky bottom-0 z-20 mt-6 flex items-center justify-end gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900">
        <x-admin.button href="{{ route('admin.products.index') }}" variant="outline">Batal</x-admin.button>
        <button type="submit" :disabled="submitting" :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600">
            <x-admin.icon name="check" class="h-4 w-4" />
            <span x-text="submitting ? 'Menyimpan…' : '{{ $isEdit ? 'Simpan perubahan' : 'Tambahkan produk' }}'"></span>
        </button>
    </div>
</form>

@push('scripts')
<script>
    function specsForm(existingSpecs, defaultKeys) {
        const rows = [];
        if (existingSpecs && typeof existingSpecs === 'object' && Object.keys(existingSpecs).length > 0) {
            for (const [key, value] of Object.entries(existingSpecs)) {
                rows.push({ key, value: String(value) });
            }
        }
        // Create: mulai KOSONG (tanpa 8 baris kosong) — pakai chip pintasan.
        return {
            rows,
            suggestions: defaultKeys,
            availableSuggestions() {
                const used = this.rows.map(r => (r.key || '').trim().toLowerCase());
                return this.suggestions.filter(k => !used.includes(k));
            },
            addRow() {
                this.rows.push({ key: '', value: '' });
            },
            addKey(key) {
                const empty = this.rows.find(r => !r.key && !r.value);
                if (empty) { empty.key = key; } else { this.rows.push({ key, value: '' }); }
            },
            removeRow(index) {
                this.rows.splice(index, 1);
            },
        };
    }

    function productForm(opts) {
        return {
            title: opts.initialTitle || '',
            slug: opts.initialSlug || '',
            status: opts.initialStatus || 'draft',
            price: opts.initialPrice || '',
            description: opts.initialDescription || '',
            metaTitle: opts.initialMetaTitle || '',
            metaDescription: opts.initialMetaDescription || '',
            autoSlug: opts.autoSlug,
            previewUrl: opts.existingImage || null,
            submitting: false,

            get priceRupiah() {
                const n = parseInt(String(this.price).replace(/\D/g, ''), 10);
                return isNaN(n) || n <= 0 ? '' : 'Rp ' + n.toLocaleString('id-ID');
            },

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
                    this.previewUrl = opts.existingImage || null;
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
