@php
    // CATATAN: admin.css prebuilt (tidak bisa rebuild) — hanya pakai utility yang
    // sudah ter-compile. Grid baris repeatable memakai md:grid-cols-2/3 (ADA),
    // BUKAN md:col-span-4/5/6/7 (tidak ter-compile → kolom kolaps).
    $inputClass = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 placeholder:text-gray-400 dark:placeholder:text-white/30';
    $labelClass = 'block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300';
    $removeBtnClass = 'inline-flex h-11 w-10 shrink-0 items-center justify-center rounded-lg border border-error-200 bg-white text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15';
    $rowCardClass = 'rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]';
    $emptyClass = 'rounded-xl border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:text-gray-400';

    // Saran nama grup untuk datalist (konsistensi penamaan kolom).
    $groupOptions = collect($footerData['links'])->pluck('group')->filter()->unique()->values();

    // Icon picker media sosial: nama Lucide => path SVG inline (dirender server-side
    // karena baris di-clone template Alpine — lucide.js tidak reliable di clone).
    // Daftar nama + label dari Settings::FOOTER_SOCIAL_ICONS (sumber tunggal dgn validasi).
    $socialIconPaths = [
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
        'youtube' => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
        'twitter' => '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>',
        'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
        'send' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'message-circle' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'music-2' => '<circle cx="8" cy="18" r="4"/><path d="M12 18V2l7 4"/>',
        'at-sign' => '<circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/>',
        'github' => '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>',
        'twitch' => '<path d="M21 2H3v16h5v4l4-4h5l4-4V2zm-10 9V7m5 4V7"/>',
        'dribbble' => '<circle cx="12" cy="12" r="10"/><path d="M19.13 5.09C15.22 9.14 10 10.44 2.25 10.94"/><path d="M21.75 12.84c-6.62-1.41-12.14 1-16.38 6.32"/><path d="M8.56 2.75c4.37 6 6 9.42 8 17.72"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'mail' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'phone' => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'rss' => '<path d="M4 11a9 9 0 0 1 9 9"/><path d="M4 4a16 16 0 0 1 16 16"/><circle cx="5" cy="19" r="1"/>',
    ];
    $socialIconSvg = function (string $name, string $size = '18') use ($socialIconPaths): string {
        $paths = $socialIconPaths[$name] ?? '<circle cx="12" cy="12" r="10"/>';

        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    };
    $socialIcons = \App\Services\Settings::FOOTER_SOCIAL_ICONS;
@endphp

<x-admin.card>
    @if ($errors->any())
        <div class="mb-4">
            <x-admin.alert tone="error">{{ $errors->first() }}</x-admin.alert>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.footer.update') }}"
        x-data="{
            socials: {{ Illuminate\Support\Js::from($footerData['socials']) }},
            links: {{ Illuminate\Support\Js::from($footerData['links']) }},
            legal: {{ Illuminate\Support\Js::from($footerData['legal']) }},
            addSocial() { this.socials.push({ icon: '', href: '', label: '' }); },
            addLink() { this.links.push({ group: '', label: '', href: '' }); },
            addLegal() { this.legal.push({ label: '', href: '' }); },
        }">
        @csrf
        @method('PUT')

        {{-- Datalist saran nama grup (dipakai input Grup di Kolom Link) --}}
        <datalist id="footer-group-options">
            @foreach ($groupOptions as $g)
                <option value="{{ $g }}"></option>
            @endforeach
        </datalist>

        {{-- ════ 1 · Identitas & Kontak ════ --}}
        <div class="mb-5">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Bagian 1</p>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Identitas &amp; Kontak</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Brand, tagline, dan info kontak di kolom kiri footer.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label for="f-brand-text" class="{{ $labelClass }}">Nama brand</label>
                <input id="f-brand-text" type="text" name="brand_text" maxlength="60" value="{{ old('brand_text', $footerData['brand_text']) }}" placeholder="Firman" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="f-brand-accent" class="{{ $labelClass }}">Nama brand (aksen warna)</label>
                <input id="f-brand-accent" type="text" name="brand_accent" maxlength="60" value="{{ old('brand_accent', $footerData['brand_accent']) }}" placeholder="Pratama" class="{{ $inputClass }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ditampilkan menyambung: <span class="font-semibold">{{ $footerData['brand_text'] }}<span class="text-brand-500">{{ $footerData['brand_accent'] }}</span></span></p>
            </div>
            <div class="md:col-span-2">
                <label for="f-tagline" class="{{ $labelClass }}">Tagline</label>
                <textarea id="f-tagline" name="tagline" rows="2" maxlength="300" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('tagline', $footerData['tagline']) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label for="f-address" class="{{ $labelClass }}">Alamat</label>
                <input id="f-address" type="text" name="address" maxlength="300" value="{{ old('address', $footerData['address']) }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="f-phone" class="{{ $labelClass }}">Telepon</label>
                <input id="f-phone" type="text" name="phone" maxlength="40" value="{{ old('phone', $footerData['phone']) }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label for="f-email" class="{{ $labelClass }}">Email</label>
                <input id="f-email" type="email" name="email" maxlength="120" value="{{ old('email', $footerData['email']) }}" class="{{ $inputClass }}">
                @error('email')<p class="mt-1 text-xs text-error-600 dark:text-error-500">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- ════ 2 · Media Sosial ════ --}}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Bagian 2</p>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Media Sosial</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ikon bulat di bawah tagline. Klik kolom icon untuk memilih dari galeri — label otomatis terisi.</p>
                </div>
                <x-admin.button type="button" size="sm" variant="outline" @click="addSocial()">
                    <x-admin.icon name="plus" class="h-3.5 w-3.5" /> Tambah
                </x-admin.button>
            </div>

            <div class="flex flex-col gap-3">
                <template x-for="(s, idx) in socials" :key="idx">
                    <div class="{{ $rowCardClass }}">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            {{-- Icon picker: tombol berpratinjau + popover galeri icon Lucide
                                 (SVG inline server-side; nilai tersimpan via hidden input). --}}
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <label class="{{ $labelClass }}">Icon</label>
                                <button type="button" @click="open = !open"
                                    class="flex h-11 w-full items-center gap-2 rounded-lg border border-gray-300 bg-transparent px-4 text-left text-sm text-gray-800 shadow-theme-xs transition hover:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    @foreach ($socialIcons as $iconName => $iconLabel)
                                        <span x-show="s.icon === '{{ $iconName }}'" x-cloak class="inline-flex min-w-0 items-center gap-2">
                                            <span class="shrink-0 text-gray-600 dark:text-gray-300">{!! $socialIconSvg($iconName) !!}</span>
                                            <span class="truncate">{{ $iconLabel }}</span>
                                        </span>
                                    @endforeach
                                    <span x-show="!s.icon" x-cloak class="text-gray-400 dark:text-white/30">Pilih icon…</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-auto shrink-0 text-gray-400" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                                </button>
                                <input type="hidden" :name="`socials[${idx}][icon]`" :value="s.icon">

                                <div x-show="open" x-cloak
                                    class="absolute z-50 mt-2 w-full rounded-xl border border-gray-200 bg-white p-3 shadow-xl dark:border-gray-700 dark:bg-gray-900">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($socialIcons as $iconName => $iconLabel)
                                            <button type="button" title="{{ $iconLabel }}" aria-label="{{ $iconLabel }}"
                                                @click="s.icon = '{{ $iconName }}'; if (!s.label) s.label = '{{ $iconLabel }}'; open = false"
                                                :class="s.icon === '{{ $iconName }}' ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-200 text-gray-600 hover:border-brand-300 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.06]'"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border transition">
                                                {!! $socialIconSvg($iconName) !!}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Label</label>
                                <input type="text" :name="`socials[${idx}][label]`" x-model="s.label" placeholder="Instagram" class="{{ $inputClass }}">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1 min-w-0">
                                    <label class="{{ $labelClass }}">URL</label>
                                    <input type="text" :name="`socials[${idx}][href]`" x-model="s.href" placeholder="https://instagram.com/username" class="{{ $inputClass }}">
                                </div>
                                <button type="button" @click="socials.splice(idx, 1)" class="{{ $removeBtnClass }}" aria-label="Hapus media sosial" title="Hapus">
                                    <x-admin.icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="socials.length === 0" x-cloak class="{{ $emptyClass }}">
                    Belum ada media sosial. Klik <span class="font-medium text-gray-700 dark:text-gray-300">Tambah</span> untuk mulai.
                </div>
            </div>
        </div>

        {{-- ════ 3 · Kolom Link ════ --}}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Bagian 3</p>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Kolom Link</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Link dengan <strong>Grup</strong> sama tampil satu kolom (judul kolom = nama grup). URL boleh relatif, mis. <span class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-white/[0.06]">/blog</span></p>
                </div>
                <x-admin.button type="button" size="sm" variant="outline" @click="addLink()">
                    <x-admin.icon name="plus" class="h-3.5 w-3.5" /> Tambah
                </x-admin.button>
            </div>

            <div class="flex flex-col gap-3">
                <template x-for="(l, idx) in links" :key="idx">
                    <div class="{{ $rowCardClass }}">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div>
                                <label class="{{ $labelClass }}">Grup (judul kolom)</label>
                                <input type="text" :name="`links[${idx}][group]`" x-model="l.group" list="footer-group-options" placeholder="Layanan" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Label link</label>
                                <input type="text" :name="`links[${idx}][label]`" x-model="l.label" placeholder="Kelas Privat AMC" class="{{ $inputClass }}">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1 min-w-0">
                                    <label class="{{ $labelClass }}">URL</label>
                                    <input type="text" :name="`links[${idx}][href]`" x-model="l.href" placeholder="/produk?kategori=privat" class="{{ $inputClass }}">
                                </div>
                                <button type="button" @click="links.splice(idx, 1)" class="{{ $removeBtnClass }}" aria-label="Hapus link" title="Hapus">
                                    <x-admin.icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="links.length === 0" x-cloak class="{{ $emptyClass }}">
                    Belum ada link. Klik <span class="font-medium text-gray-700 dark:text-gray-300">Tambah</span> untuk mulai.
                </div>
            </div>
        </div>

        {{-- ════ 4 · Legal & Copyright ════ --}}
        <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Bagian 4</p>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">Legal &amp; Copyright</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Baris paling bawah footer: teks copyright di kiri, link legal di kanan.</p>
                </div>
                <x-admin.button type="button" size="sm" variant="outline" @click="addLegal()">
                    <x-admin.icon name="plus" class="h-3.5 w-3.5" /> Tambah
                </x-admin.button>
            </div>

            <div class="flex flex-col gap-3">
                <template x-for="(g, idx) in legal" :key="idx">
                    <div class="{{ $rowCardClass }}">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div>
                                <label class="{{ $labelClass }}">Label</label>
                                <input type="text" :name="`legal[${idx}][label]`" x-model="g.label" placeholder="Kebijakan Privasi" class="{{ $inputClass }}">
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1 min-w-0">
                                    <label class="{{ $labelClass }}">URL</label>
                                    <input type="text" :name="`legal[${idx}][href]`" x-model="g.href" placeholder="/privacy" class="{{ $inputClass }}">
                                </div>
                                <button type="button" @click="legal.splice(idx, 1)" class="{{ $removeBtnClass }}" aria-label="Hapus link legal" title="Hapus">
                                    <x-admin.icon name="trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
                <div x-show="legal.length === 0" x-cloak class="{{ $emptyClass }}">
                    Belum ada link legal.
                </div>
            </div>

            <div class="mt-4">
                <label for="f-copyright" class="{{ $labelClass }}">Teks copyright</label>
                <input id="f-copyright" type="text" name="copyright" maxlength="200" value="{{ old('copyright', $footerData['copyright']) }}" class="{{ $inputClass }}">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tulis <span class="rounded bg-gray-100 px-2 py-1 dark:bg-white/[0.06]">{year}</span> untuk tahun berjalan otomatis ({{ now()->year }}).</p>
            </div>
        </div>

        {{-- ════ Simpan ════ --}}
        <div class="mt-8 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
            <a href="{{ url('/') }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-2 text-xs font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400">
                <x-admin.icon name="search" class="h-3.5 w-3.5" />
                Lihat footer di situs (tab baru)
            </a>
            <x-admin.button type="submit">Simpan Footer</x-admin.button>
        </div>
    </form>
</x-admin.card>
