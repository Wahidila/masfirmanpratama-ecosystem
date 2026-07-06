@php
    // admin.css prebuilt (tak bisa rebuild): tak ada utility `file:*`, `max-h-16`,
    // `border-gray-800`, atau grid arbitrary — pakai class ter-compile + inline style.
    $fileInputClass = 'block w-full text-xs text-gray-600 dark:text-gray-400';
    $labelClass = 'block text-sm font-medium text-gray-700 dark:text-gray-300';
    $checkClass = 'rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700';
@endphp

<x-admin.card>
    @if ($errors->any())
        <div class="mb-4">
            <x-admin.alert tone="error">{{ $errors->first() }}</x-admin.alert>
        </div>
    @endif

    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Unggah logo gambar untuk header (navbar) dan footer. Kosongkan = pakai logo default (ikon + teks brand).
        Disarankan <strong>PNG transparan</strong>. Format: PNG, JPG, WebP. Maks 2 MB.
    </p>

    <form method="POST" action="{{ route('admin.settings.logo.update') }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- ════ Logo Header ════ --}}
        <div x-data="{
                preview: {{ $brandingData['header_logo_url'] ? Illuminate\Support\Js::from($brandingData['header_logo_url']) : 'null' }},
                hasCurrent: {{ $brandingData['header_logo_url'] ? 'true' : 'false' }},
                remove: false,
                onPick(e) { const f = e.target.files[0]; if (f) { this.preview = URL.createObjectURL(f); this.remove = false; } },
            }">
            <div class="mb-3">
                <span class="{{ $labelClass }}">Logo Header (navbar)</span>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Tampil di latar terang, tinggi dirender ±40px.</p>
            </div>

            {{-- Preview: latar terang meniru navbar --}}
            <div class="flex h-24 max-w-sm items-center justify-center rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800">
                <template x-if="preview && !remove">
                    <img :src="preview" alt="Preview logo header" class="w-auto max-w-full object-contain" style="max-height:4rem">
                </template>
                <template x-if="!preview || remove">
                    <span class="text-xs text-gray-400">Belum ada — pakai default (ikon + teks)</span>
                </template>
            </div>

            <div class="mt-3 space-y-2">
                <input type="file" name="header_logo" accept="image/png,image/jpeg,image/webp" @change="onPick" class="{{ $fileInputClass }}">
                @error('header_logo')<p class="text-xs text-error-600 dark:text-error-500">{{ $message }}</p>@enderror

                <label x-show="hasCurrent" x-cloak class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_header_logo" value="1" x-model="remove" class="{{ $checkClass }}">
                    Hapus logo header, kembali ke default
                </label>
            </div>
        </div>

        {{-- ════ Logo Footer ════ --}}
        <div class="border-t border-gray-200 pt-8 dark:border-gray-800"
            x-data="{
                preview: {{ $brandingData['footer_logo_url'] ? Illuminate\Support\Js::from($brandingData['footer_logo_url']) : 'null' }},
                hasCurrent: {{ $brandingData['footer_logo_url'] ? 'true' : 'false' }},
                remove: false,
                onPick(e) { const f = e.target.files[0]; if (f) { this.preview = URL.createObjectURL(f); this.remove = false; } },
            }">
            <div class="mb-3">
                <span class="{{ $labelClass }}">Logo Footer</span>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Tampil di latar gelap — pakai versi terang/putih. Tinggi dirender ±36px.</p>
            </div>

            {{-- Preview: latar gelap meniru footer --}}
            <div class="flex h-24 max-w-sm items-center justify-center rounded-xl bg-gray-900 p-3">
                <template x-if="preview && !remove">
                    <img :src="preview" alt="Preview logo footer" class="w-auto max-w-full object-contain" style="max-height:4rem">
                </template>
                <template x-if="!preview || remove">
                    <span class="text-xs text-gray-500">Belum ada — pakai default (ikon + teks)</span>
                </template>
            </div>

            <div class="mt-3 space-y-2">
                <input type="file" name="footer_logo" accept="image/png,image/jpeg,image/webp" @change="onPick" class="{{ $fileInputClass }}">
                @error('footer_logo')<p class="text-xs text-error-600 dark:text-error-500">{{ $message }}</p>@enderror

                <label x-show="hasCurrent" x-cloak class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                    <input type="checkbox" name="remove_footer_logo" value="1" x-model="remove" class="{{ $checkClass }}">
                    Hapus logo footer, kembali ke default
                </label>
            </div>
        </div>

        <div class="flex justify-end border-t border-gray-200 pt-4 dark:border-gray-800">
            <x-admin.button type="submit">Simpan Logo</x-admin.button>
        </div>
    </form>
</x-admin.card>
