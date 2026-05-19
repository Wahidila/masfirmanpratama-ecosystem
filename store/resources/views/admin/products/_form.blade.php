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

    // Image preview src — pakai existing image kalau edit, else null.
    $existingImage = $product->image_path
        ? asset('storage/'.$product->image_path)
        : null;
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
    })"
    @submit="onSubmit($event)"
    class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- Top: identity --}}
    <x-admin.card title="Identitas produk">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Judul produk" for="title" name="title" required class="sm:col-span-2">
                <input
                    type="text"
                    id="title"
                    name="title"
                    x-model="title"
                    @input="onTitleChange()"
                    value="{{ old('title', $product->title) }}"
                    maxlength="200"
                    required
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                    placeholder="mis. Buku Mind Power 101">
            </x-admin.form-group>

            <x-admin.form-group label="Slug" for="slug" name="slug" required
                hint="Otomatis dari judul. Pakai huruf kecil + tanda hubung (mis. mind-power-101).">
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-xl border border-r-0 border-slate-200 bg-slate-50 px-3 text-xs text-slate-500">/produk/</span>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        x-model="slug"
                        @input="autoSlug = false"
                        value="{{ old('slug', $product->slug) }}"
                        maxlength="200"
                        class="block w-full rounded-r-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </div>
            </x-admin.form-group>

            <x-admin.form-group label="Tipe produk" for="type" name="type" required>
                <select
                    id="type"
                    name="type"
                    required
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    @foreach (['book' => 'Buku', 'course' => 'Kelas / Kursus'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $product->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- Pricing & stock --}}
    <x-admin.card title="Harga & stok">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-admin.form-group label="Harga (Rp)" for="price" name="price" required>
                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0"
                    step="1"
                    value="{{ old('price', $product->price ?? 0) }}"
                    required
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                    placeholder="150000">
            </x-admin.form-group>

            <x-admin.form-group label="Stok" for="stock" name="stock" required hint="Isi 0 kalau sold out.">
                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    step="1"
                    value="{{ old('stock', $product->stock ?? 0) }}"
                    required
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </x-admin.form-group>

            <x-admin.form-group label="Status" for="status" name="status" required>
                <select
                    id="status"
                    name="status"
                    required
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                    @foreach (['draft' => 'Draft (belum tayang)', 'active' => 'Active (live)', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $product->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- Image upload --}}
    <x-admin.card title="Gambar produk">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group
                label="{{ $isEdit ? 'Ganti gambar (opsional)' : 'Upload gambar' }}"
                for="image"
                name="image"
                hint="JPG, PNG, atau WebP. Maks 2 MB. Resolusi minimal 800 × 800 piksel.">
                <input
                    type="file"
                    id="image"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    @change="onImageChange($event)"
                    class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-primary-700 hover:file:bg-primary-100">

                @if ($isEdit && $product->image_path)
                    <label class="mt-3 inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-200">
                        Hapus gambar saat ini
                    </label>
                @endif
            </x-admin.form-group>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Preview</label>
                <div class="relative aspect-square w-full max-w-[240px] overflow-hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Preview gambar produk" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!previewUrl">
                        <div class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-slate-400">
                            <x-admin.icon name="image" class="h-8 w-8" />
                            <span class="text-xs">Belum ada gambar</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-admin.card>

    {{-- Description + SEO --}}
    <x-admin.card title="Deskripsi & SEO">
        <div class="space-y-5">
            <x-admin.form-group
                label="Deskripsi produk"
                for="description"
                name="description"
                hint="Plain text atau markdown ringan. Maksimal 8.000 karakter.">
                <textarea
                    id="description"
                    name="description"
                    rows="6"
                    maxlength="8000"
                    class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100"
                    placeholder="Ceritakan produk ini ke calon pembeli...">{{ old('description', $product->description) }}</textarea>
            </x-admin.form-group>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.form-group label="Meta title (SEO)" for="meta_title" name="meta_title"
                    hint="Maks 160 karakter. Tampil di Google search result.">
                    <input
                        type="text"
                        id="meta_title"
                        name="meta_title"
                        maxlength="160"
                        value="{{ old('meta_title', $metaSeo['title'] ?? '') }}"
                        class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </x-admin.form-group>

                <x-admin.form-group label="Meta description (SEO)" for="meta_description" name="meta_description"
                    hint="Maks 320 karakter. Snippet di hasil pencarian Google.">
                    <input
                        type="text"
                        id="meta_description"
                        name="meta_description"
                        maxlength="320"
                        value="{{ old('meta_description', $metaSeo['description'] ?? '') }}"
                        class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                </x-admin.form-group>
            </div>
        </div>
    </x-admin.card>

    {{-- Footer actions --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
        <a href="{{ route('admin.products.index') }}"
            class="inline-flex items-center justify-center rounded-full border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
            Batal
        </a>
        <button type="submit"
            :disabled="submitting"
            :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
            class="inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-500/30 transition hover:bg-primary-700">
            <x-admin.icon name="check" class="h-4 w-4" />
            <span x-text="submitting ? 'Menyimpan…' : '{{ $isEdit ? 'Simpan perubahan' : 'Tambahkan produk' }}'"></span>
        </button>
    </div>
</form>

@push('scripts')
<script>
    function productForm({ autoSlug, existingImage, initialTitle, initialSlug }) {
        return {
            title: initialTitle || '',
            slug: initialSlug || '',
            autoSlug: autoSlug,
            previewUrl: existingImage || null,
            submitting: false,

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
