@props([
    'videoTestimonial',
    'mode' => 'create',
])

@php
    $isEdit = $mode === 'edit';
    $action = $isEdit
        ? route('admin.video-testimonials.update', $videoTestimonial)
        : route('admin.video-testimonials.store');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-admin.card title="Konten video">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-admin.form-group label="Judul testimoni" for="title" name="title" required class="sm:col-span-2">
                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="200"
                    value="{{ old('title', $videoTestimonial->title) }}"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="mis. AMC Adalah Ilmu yang Sangat Mind Blowing">
            </x-admin.form-group>

            <x-admin.form-group label="Nama peserta" for="participant_name" name="participant_name" required>
                <input
                    type="text"
                    id="participant_name"
                    name="participant_name"
                    maxlength="120"
                    value="{{ old('participant_name', $videoTestimonial->participant_name) }}"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="Ria Handayani">
            </x-admin.form-group>

            <x-admin.form-group label="Role" for="role" name="role" hint="Default: Alumni AMC.">
                <input
                    type="text"
                    id="role"
                    name="role"
                    maxlength="120"
                    value="{{ old('role', $videoTestimonial->role) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="Alumni AMC">
            </x-admin.form-group>

            <x-admin.form-group label="URL video" for="video_url" name="video_url" required class="sm:col-span-2"
                hint="Bisa pakai MP4 hosted seperti dari website lama, atau URL video langsung lain.">
                <input
                    type="url"
                    id="video_url"
                    name="video_url"
                    maxlength="2048"
                    value="{{ old('video_url', $videoTestimonial->video_url) }}"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="https://masfirmanpratama.com/wp-content/uploads/.../video.mp4">
            </x-admin.form-group>

            <x-admin.form-group label="URL poster / thumbnail" for="poster_url" name="poster_url" class="sm:col-span-2"
                hint="Opsional. Kalau kosong, browser akan pakai metadata video.">
                <input
                    type="url"
                    id="poster_url"
                    name="poster_url"
                    maxlength="2048"
                    value="{{ old('poster_url', $videoTestimonial->poster_url) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="https://.../thumbnail.jpg">
            </x-admin.form-group>
        </div>
    </x-admin.card>

    <x-admin.card title="Publikasi">
        <div class="grid gap-5 sm:grid-cols-3">
            <x-admin.form-group label="Status" for="status" name="status" required>
                <select
                    id="status"
                    name="status"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    @foreach (['draft' => 'Draft (belum tayang)', 'active' => 'Active (live)', 'archived' => 'Archived'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $videoTestimonial->status ?? 'active') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-admin.form-group>

            <x-admin.form-group label="Urutan tampil" for="sort_order" name="sort_order"
                hint="Angka kecil tampil lebih dulu.">
                <input
                    type="number"
                    id="sort_order"
                    name="sort_order"
                    min="0"
                    max="9999"
                    value="{{ old('sort_order', $videoTestimonial->sort_order ?? 0) }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            </x-admin.form-group>

            <div class="flex items-center pt-7">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        name="show_on_homepage"
                        value="1"
                        {{ old('show_on_homepage', $videoTestimonial->show_on_homepage ?? true) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    Tampilkan di homepage
                </label>
            </div>
        </div>
    </x-admin.card>

    <x-admin.card title="Preview">
        @if (old('video_url', $videoTestimonial->video_url))
            <div class="max-w-xs overflow-hidden rounded-2xl border border-gray-200 bg-gray-950 dark:border-gray-800">
                <video
                    src="{{ old('video_url', $videoTestimonial->video_url) }}"
                    @if(old('poster_url', $videoTestimonial->poster_url)) poster="{{ old('poster_url', $videoTestimonial->poster_url) }}" @endif
                    controls
                    preload="metadata"
                    playsinline
                    class="aspect-[9/16] w-full object-cover"></video>
            </div>
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">Preview akan muncul setelah URL video diisi dan form disimpan.</p>
        @endif
    </x-admin.card>

    <div class="flex items-center justify-end gap-3">
        <x-admin.button href="{{ route('admin.video-testimonials.index') }}" variant="outline">
            Batal
        </x-admin.button>
        <x-admin.button type="submit">
            <x-admin.icon name="check" class="h-4 w-4" />
            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Video' }}
        </x-admin.button>
    </div>
</form>
