@props([
    'name',
    'value' => '',
])

@php
    // Palet warna ikon. `class` = kelas Tailwind yang dipakai storefront (di-safelist
    // di tailwind.config.js supaya ter-compile), `hex` = warna asli untuk swatch,
    // `label` = nama manusiawi. Admin cukup pilih warna, tak perlu tahu class.
    $palette = [
        ['class' => 'text-primary-500', 'hex' => '#6366f1', 'label' => 'Indigo'],
        ['class' => 'text-primary-600', 'hex' => '#4f46e5', 'label' => 'Indigo tua'],
        ['class' => 'text-secondary-400', 'hex' => '#2dd4bf', 'label' => 'Tosca'],
        ['class' => 'text-secondary-600', 'hex' => '#0d9488', 'label' => 'Tosca tua'],
        ['class' => 'text-accent-500', 'hex' => '#f59e0b', 'label' => 'Amber'],
        ['class' => 'text-accent-600', 'hex' => '#d97706', 'label' => 'Amber tua'],
        ['class' => 'text-rose-500', 'hex' => '#f43f5e', 'label' => 'Rose'],
        ['class' => 'text-pink-500', 'hex' => '#ec4899', 'label' => 'Pink'],
        ['class' => 'text-violet-500', 'hex' => '#8b5cf6', 'label' => 'Violet'],
        ['class' => 'text-blue-600', 'hex' => '#2563eb', 'label' => 'Biru'],
        ['class' => 'text-sky-500', 'hex' => '#0ea5e9', 'label' => 'Langit'],
        ['class' => 'text-emerald-500', 'hex' => '#10b981', 'label' => 'Hijau'],
        ['class' => 'text-slate-700', 'hex' => '#334155', 'label' => 'Netral'],
    ];
@endphp

<div
    x-data="colorPicker(@js($palette), @js((string) $value))"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selected">

    {{-- Trigger --}}
    <button
        type="button"
        @click="open = !open"
        class="flex h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
    >
        <span class="flex min-w-0 items-center gap-2">
            <span x-show="current" class="h-5 w-5 shrink-0 rounded-full border border-gray-200 dark:border-gray-700" :style="current ? ('background:' + current.hex) : ''"></span>
            <span x-show="!current" class="h-5 w-5 shrink-0 rounded-full border border-dashed border-gray-300 dark:border-gray-600"></span>
            <span class="truncate" :class="!current && 'text-gray-400'" x-text="current ? current.label : 'Pilih warna…'"></span>
        </span>
        <x-admin.icon name="chevron-right" class="h-4 w-4 shrink-0 text-gray-400" />
    </button>

    {{-- Popover --}}
    <div
        x-show="open"
        x-cloak
        class="absolute left-0 z-30 mt-1 w-64 rounded-xl border border-gray-200 bg-white p-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900"
    >
        <div class="grid grid-cols-6 gap-2">
            <template x-for="c in palette" :key="c.class">
                <button
                    type="button"
                    @click="select(c)"
                    :title="c.label"
                    :aria-label="c.label"
                    :style="'background:' + c.hex"
                    :class="selected === c.class ? 'border-gray-900' : 'border-transparent'"
                    class="h-8 w-8 rounded-full border-2 transition"
                ></button>
            </template>
        </div>

        <button type="button" x-show="selected" @click="clearColor()"
            class="mt-3 text-xs font-medium text-gray-500 hover:text-error-600">
            Hapus pilihan
        </button>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.colorPicker = function (palette, initial) {
                return {
                    open: false,
                    palette: palette,
                    selected: initial || '',
                    get current() {
                        return this.palette.find((c) => c.class === this.selected) || null;
                    },
                    select(c) {
                        this.selected = c.class;
                        this.open = false;
                    },
                    clearColor() {
                        this.selected = '';
                        this.open = false;
                    },
                };
            };
        </script>
    @endpush
@endonce
