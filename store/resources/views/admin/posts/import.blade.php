@extends('layouts.admin', ['active' => 'posts'])

@section('title', 'Import dari WordPress')

@section('content')
    <x-admin.page-header
        title="Import dari WordPress"
        subtitle="Migrasi artikel lama: export dari WordPress (Tools → Export → All content), lalu upload file XML-nya di sini.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.posts.index') }}" variant="outline" size="sm">← Kembali ke artikel</x-admin.button>
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6"><x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert></div>
    @endif
    @if ($errors->any())
        <div class="mb-6"><x-admin.alert tone="error" title="Gagal">{{ $errors->first() }}</x-admin.alert></div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-admin.card title="Upload file export WordPress (WXR / .xml)">
                <form method="POST" action="{{ route('admin.posts.import') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    <x-admin.form-group label="File export (.xml)" for="wxr" name="wxr" required
                        hint="Dari wp-admin: Tools → Export → 'All content' (atau 'Posts'). Maks 50 MB.">
                        <input type="file" id="wxr" name="wxr" accept=".xml,text/xml,application/xml" required
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100 dark:text-gray-400 dark:file:bg-brand-500/15 dark:file:text-brand-400">
                    </x-admin.form-group>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <input type="checkbox" name="download_media" value="1" checked
                            class="mt-0.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                        <span>
                            <span class="font-medium text-gray-800 dark:text-white/90">Download & rehost gambar</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Ambil featured image + gambar inline dari situs lama. <strong>Jalankan SEBELUM cutover DNS</strong> selagi situs lama masih online.</span>
                            <span class="mt-1 block text-xs text-gray-400 dark:text-gray-500">Blog dengan banyak gambar bisa makan waktu beberapa menit — jangan tutup tab. Import aman diulang (idempotent), jadi kalau terputus tinggal jalankan lagi. Untuk migrasi sangat besar, pakai <span class="font-mono">php artisan blog:import-wordpress</span>.</span>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <input type="checkbox" name="dry_run" value="1"
                            class="mt-0.5 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                        <span>
                            <span class="font-medium text-gray-800 dark:text-white/90">Preview dulu (dry-run)</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Hanya menghitung, tidak menyimpan apa pun. Centang untuk cek isi file sebelum import beneran.</span>
                        </span>
                    </label>

                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <x-admin.icon name="upload" class="h-4 w-4" /> Jalankan import
                    </button>
                </form>
            </x-admin.card>

            @if ($result)
                <div class="mt-6">
                    <x-admin.card title="Hasil {{ $result['dry_run'] ? 'preview (dry-run)' : 'import' }}">
                        @php $s = $result['summary']; @endphp
                        <dl class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            @foreach ([
                                'Kategori' => $s['categories'], 'Tag' => $s['tags'],
                                'Artikel baru' => $s['posts_created'], 'Artikel diperbarui' => $s['posts_updated'],
                                'Media (download)' => $s['media_downloaded'], 'Media (skip)' => $s['media_skipped'],
                                'Link internal dirapikan' => $s['links_relinked'] ?? 0,
                                'Item dilewati' => $s['items_skipped'],
                            ] as $label => $value)
                                <div class="rounded-lg border border-gray-100 p-3 dark:border-gray-800">
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                    <dd class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if (! empty($result['slug_collisions']))
                            <p class="mt-4 text-xs text-warning-600 dark:text-warning-500">
                                Slug bentrok yang disuffix: {{ implode(', ', $result['slug_collisions']) }}
                            </p>
                        @endif
                    </x-admin.card>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1">
            <x-admin.card title="Panduan singkat">
                <ol class="list-decimal space-y-2 pl-4 text-sm text-gray-600 dark:text-gray-400">
                    <li>Login ke wp-admin situs lama.</li>
                    <li>Buka <span class="font-mono text-xs">Tools → Export</span>.</li>
                    <li>Pilih <strong>All content</strong> (atau hanya Posts) → Download Export File.</li>
                    <li>Upload file <span class="font-mono text-xs">.xml</span> tersebut di sini.</li>
                    <li>Centang <strong>Preview</strong> dulu untuk cek, lalu import beneran.</li>
                    <li>Import ulang aman — tidak menghasilkan duplikat (idempotent via <span class="font-mono text-xs">wp_post_id</span>).</li>
                </ol>
            </x-admin.card>
        </div>
    </div>
@endsection
