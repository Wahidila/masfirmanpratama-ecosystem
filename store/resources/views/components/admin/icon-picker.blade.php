@props([
    'name',
    'value' => '',
])

{{-- Icon picker visual (Lucide) — pengganti input teks nama ikon manual.
     Nilai terpilih dikirim lewat hidden input; preview pakai Lucide (data-lucide). --}}
<div
    x-data="iconPicker(@js((string) $value))"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selected">

    {{-- Trigger --}}
    <button
        type="button"
        @click="toggle()"
        class="flex h-11 w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
    >
        <span class="flex min-w-0 items-center gap-2">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center text-brand-600 dark:text-brand-400">
                <template x-if="selected"><i :data-lucide="selected" class="h-5 w-5"></i></template>
                <template x-if="!selected"><x-admin.icon name="image" class="h-4 w-4 text-gray-300" /></template>
            </span>
            <span class="truncate" :class="!selected && 'text-gray-400'" x-text="selected || 'Pilih ikon…'"></span>
        </span>
        <x-admin.icon name="chevron-right" class="h-4 w-4 shrink-0 text-gray-400" />
    </button>

    {{-- Popover --}}
    <div
        x-show="open"
        x-cloak
        class="absolute left-0 z-30 mt-1 w-72 rounded-xl border border-gray-200 bg-white p-3 shadow-theme-xs dark:border-gray-700 dark:bg-gray-900"
    >
        <input
            type="text"
            x-model="search"
            @input="renderSoon()"
            placeholder="Cari ikon…"
            class="mb-2 h-9 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm text-gray-800 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white/90"
        >

        <div class="grid max-h-72 grid-cols-6 gap-1 overflow-y-auto">
            <template x-for="icon in filtered()" :key="icon">
                <button
                    type="button"
                    @click="select(icon)"
                    :title="icon"
                    :class="selected === icon ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15' : 'border-transparent text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]'"
                    class="flex h-9 w-9 items-center justify-center rounded-lg border"
                >
                    <i :data-lucide="icon" class="h-5 w-5"></i>
                </button>
            </template>
        </div>
        <p x-show="filtered().length === 0" class="py-4 text-center text-xs text-gray-400">Tak ada ikon cocok.</p>

        <button type="button" x-show="selected" @click="clearIcon()"
            class="mt-2 text-xs font-medium text-gray-500 hover:text-error-600">
            Hapus pilihan
        </button>
    </div>
</div>

@once
    @push('scripts')
        <script>
            window.MFP_ICON_LIST = [
                'star','sparkles','award','trophy','medal','crown','gem','badge-check','check','check-circle',
                'heart','flame','zap','rocket','target','lightbulb','brain','graduation-cap','book','book-open',
                'bookmark','library','pencil','feather','users','user','user-check','user-plus','shield','shield-check',
                'lock','key','gift','thumbs-up','handshake','clock','calendar','calendar-check','timer','infinity',
                'layers','puzzle','atom','telescope','compass','map-pin','mic','headphones','music','play',
                'video','camera','image','message-circle','mail','phone','bell','dollar-sign','credit-card','wallet',
                'banknote','tag','percent','trending-up','activity','sun','moon','leaf','sprout','eye',
                'smile','coffee','briefcase','building','home','globe','settings','sliders-horizontal','list-checks','clipboard-check',
                'file-text','folder','quote','hand','flag','bookmark-check','wand-2','circle-check-big','pen-tool','notebook',
            ];

            window.iconPicker = function (initial) {
                return {
                    open: false,
                    selected: initial || '',
                    search: '',
                    icons: window.MFP_ICON_LIST,
                    filtered() {
                        const q = this.search.trim().toLowerCase();
                        return q ? this.icons.filter((i) => i.includes(q)) : this.icons;
                    },
                    renderSoon() {
                        this.$nextTick(() => window.lucide && window.lucide.createIcons());
                    },
                    toggle() {
                        this.open = !this.open;
                        if (this.open) this.renderSoon();
                    },
                    select(icon) {
                        this.selected = icon;
                        this.open = false;
                    },
                    clearIcon() {
                        this.selected = '';
                        this.open = false;
                    },
                };
            };
        </script>
    @endpush
@endonce
