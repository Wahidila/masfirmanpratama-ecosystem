@props([
    'post',
    'mode' => 'create',
    'categories' => collect(),
    'products' => collect(),
    'selectedCategories' => [],
    'selectedProducts' => [],
])

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('admin.posts.update', $post)
        : route('admin.posts.store');

    $metaSeo = is_array($post->meta_seo ?? null) ? $post->meta_seo : [];
    $existingImage = $post->exists ? $post->imageUrl() : null;

    $selectedCategories = collect(old('category_ids', $selectedCategories))->map(fn ($v) => (int) $v)->all();
    $selectedProducts = collect(old('product_ids', $selectedProducts))->map(fn ($v) => (int) $v)->all();
    $tagsValue = old('tags', $post->exists ? $post->tags->pluck('name')->implode(', ') : '');
    $publishedAtValue = old('published_at', optional($post->published_at)->format('Y-m-d\TH:i'));
    $primaryCategory = old('primary_category_id', $post->primary_category_id);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="postForm({
        autoSlug: !{{ $isEdit ? 'true' : 'false' }},
        existingImage: @js($existingImage),
        initialTitle: @js(old('title', $post->title)),
        initialSlug: @js(old('slug', $post->slug)),
        initialStatus: @js(old('status', $post->status ?? 'draft')),
    })"
    @submit="onSubmit()"
    class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- Identity --}}
    <x-admin.card title="Identitas artikel">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Judul artikel" for="title" name="title" required class="sm:col-span-2">
                <input type="text" id="title" name="title" x-model="title" @input="onTitleChange()"
                    value="{{ old('title', $post->title) }}" maxlength="255" required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="mis. Stop Berpikir Positif: Mengapa Pikiran Positif Saja Tidak Cukup">
            </x-admin.form-group>

            <x-admin.form-group label="Slug" for="slug" name="slug" required
                hint="Otomatis dari judul. Pakai huruf kecil + tanda hubung.">
                <div class="flex">
                    <span class="inline-flex items-center rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 px-3 text-xs text-gray-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">/blog/</span>
                    <input type="text" id="slug" name="slug" x-model="slug" @input="autoSlug = false"
                        value="{{ old('slug', $post->slug) }}" maxlength="255"
                        class="h-11 w-full rounded-r-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
            </x-admin.form-group>

            <x-admin.form-group label="Excerpt (ringkasan)" for="excerpt" name="excerpt"
                hint="Tampil di kartu daftar & meta description default. Maks 500 karakter." class="sm:col-span-2">
                <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"
                    class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    placeholder="Ringkasan singkat artikel…">{{ old('excerpt', $post->excerpt) }}</textarea>
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- Body (Trix rich text) --}}
    <x-admin.card title="Isi artikel">
        <x-admin.form-group label="Konten" for="post-content-input" name="content" required
            hint="Editor rich text. Format: heading, bold, italic, list, link, kutipan.">
            <input id="post-content-input" type="hidden" name="content" value="{{ old('content', $post->content) }}">
            <trix-editor input="post-content-input"
                class="trix-content min-h-[320px] rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"></trix-editor>
        </x-admin.form-group>
    </x-admin.card>

    {{-- Publishing --}}
    <x-admin.card title="Publikasi">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Status" for="status" name="status" required>
                <select id="status" name="status" x-model="status" required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @foreach (['draft' => 'Draft (belum tayang)', 'published' => 'Published (tayang)', 'scheduled' => 'Scheduled (terjadwal)'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $post->status ?? 'draft') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-group>

            <x-admin.form-group label="Tanggal tayang" for="published_at" name="published_at"
                x-show="status !== 'draft'"
                hint="Wajib untuk scheduled. Kosong + published = tayang sekarang.">
                <input type="datetime-local" id="published_at" name="published_at"
                    value="{{ $publishedAtValue }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- Taxonomy --}}
    <x-admin.card title="Kategori & Tag">
        <div class="space-y-5">
            <x-admin.form-group label="Kategori" name="category_ids" hint="Pilih satu atau lebih.">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($categories as $category)
                        <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}"
                                @checked(in_array($category->id, $selectedCategories, true))
                                class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                            {{ $category->name }}
                        </label>
                    @empty
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada kategori. <a href="{{ route('admin.blog-categories.index') }}" class="text-brand-600 underline">Buat kategori</a> dulu.</p>
                    @endforelse
                </div>
            </x-admin.form-group>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-admin.form-group label="Kategori utama (primary)" for="primary_category_id" name="primary_category_id"
                    hint="Kategori yang ditonjolkan di kartu & breadcrumb.">
                    <select id="primary_category_id" name="primary_category_id"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        <option value="">— Otomatis (kategori pertama) —</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) $primaryCategory === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-admin.form-group>

                <x-admin.form-group label="Tag" for="tags" name="tags"
                    hint="Pisahkan dengan koma (mis. pikiran, mindset, rezeki).">
                    <input type="text" id="tags" name="tags" value="{{ $tagsValue }}"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="pikiran, mindset, rezeki">
                </x-admin.form-group>
            </div>
        </div>
    </x-admin.card>

    {{-- Featured image --}}
    <x-admin.card title="Featured image">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group
                label="{{ $isEdit ? 'Ganti gambar (opsional)' : 'Upload gambar' }}"
                for="image" name="image"
                hint="JPG, PNG, atau WebP. Maks 4 MB.">
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"
                    @change="onImageChange($event)"
                    class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                @if ($isEdit && $post->image_path)
                    <label class="mt-3 inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-error-500 focus:ring-error-200 dark:border-gray-700">
                        Hapus gambar saat ini
                    </label>
                @endif
            </x-admin.form-group>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5 dark:text-gray-300">Preview</label>
                <div class="relative aspect-video w-full max-w-[320px] overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-white/[0.03]">
                    <template x-if="previewUrl">
                        <img :src="previewUrl" alt="Preview featured image" class="h-full w-full object-cover">
                    </template>
                    <template x-if="!previewUrl">
                        <div class="flex h-full w-full flex-col items-center justify-center gap-1.5 text-gray-400 dark:text-gray-500">
                            <x-admin.icon name="image" class="h-8 w-8" />
                            <span class="text-xs">Belum ada gambar</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </x-admin.card>

    {{-- Related products (funnel CTA) --}}
    <x-admin.card title="Produk / kelas terkait (CTA)">
        <x-admin.form-group label="Tampilkan produk terkait di akhir artikel" name="product_ids"
            hint="Opsional — jadi funnel dari artikel ke kelas/buku.">
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($products as $product)
                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}"
                            @checked(in_array($product->id, $selectedProducts, true))
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                        <span class="truncate">{{ $product->title }}</span>
                    </label>
                @empty
                    <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada produk.</p>
                @endforelse
            </div>
        </x-admin.form-group>
    </x-admin.card>

    {{-- SEO --}}
    <x-admin.card title="SEO">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Meta title" for="meta_title" name="meta_title"
                hint="Maks 160 karakter. Default: judul artikel.">
                <input type="text" id="meta_title" name="meta_title" maxlength="160"
                    value="{{ old('meta_title', $metaSeo['title'] ?? '') }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>

            <x-admin.form-group label="Meta description" for="meta_description" name="meta_description"
                hint="Maks 320 karakter. Default: excerpt.">
                <input type="text" id="meta_description" name="meta_description" maxlength="320"
                    value="{{ old('meta_description', $metaSeo['description'] ?? '') }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </x-admin.form-group>
        </div>
    </x-admin.card>

    {{-- Footer --}}
    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
        <x-admin.button href="{{ route('admin.posts.index') }}" variant="outline">Batal</x-admin.button>
        <button type="submit" :disabled="submitting" :class="submitting ? 'opacity-60 cursor-not-allowed' : ''"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-theme-xs transition hover:bg-brand-600">
            <x-admin.icon name="check" class="h-4 w-4" />
            <span x-text="submitting ? 'Menyimpan…' : '{{ $isEdit ? 'Simpan perubahan' : 'Tambahkan artikel' }}'"></span>
        </button>
    </div>
</form>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/trix@2.1.15/dist/trix.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/trix@2.1.15/dist/trix.umd.min.js"></script>
<script>
    // Disable Trix file attachments for v1 (featured image handled separately;
    // inline images arrive via WordPress import). Prevents unhandled upload attempts.
    document.addEventListener('trix-file-accept', (e) => e.preventDefault());

    function postForm({ autoSlug, existingImage, initialTitle, initialSlug, initialStatus }) {
        return {
            title: initialTitle || '',
            slug: initialSlug || '',
            status: initialStatus || 'draft',
            autoSlug: autoSlug,
            previewUrl: existingImage || null,
            submitting: false,

            kebabCase(str) {
                return String(str || '')
                    .toLowerCase().normalize('NFKD')
                    .replace(/[̀-ͯ]/g, '')
                    .replace(/[^a-z0-9\s-]/g, '')
                    .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
            },
            onTitleChange() {
                if (this.autoSlug) this.slug = this.kebabCase(this.title);
            },
            onImageChange(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) { this.previewUrl = existingImage || null; return; }
                const reader = new FileReader();
                reader.onload = (e) => { this.previewUrl = e.target.result; };
                reader.readAsDataURL(file);
            },
            onSubmit() { this.submitting = true; },
        };
    }
</script>
@endpush
