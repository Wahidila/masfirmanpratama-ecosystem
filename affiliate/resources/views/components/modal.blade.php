@props([
    'title' => null,
    'icon' => null,
    'tone' => 'primary',
    'maxWidth' => 'md',
])

@php
    $iconTones = [
        'primary' => 'bg-primary-50 text-primary-600',
        'danger' => 'bg-rose-50 text-rose-600',
        'warning' => 'bg-amber-50 text-amber-600',
        'success' => 'bg-secondary-50 text-secondary-600',
    ];
    $iconCls = $iconTones[$tone] ?? $iconTones['primary'];
    $widths = ['sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg', 'xl' => 'max-w-xl'];
    $w = $widths[$maxWidth] ?? $widths['md'];
@endphp

<div x-data="{ open: false }" @keydown.escape.window="open = false" class="contents">
    <div @click="open = true" class="contents">{{ $trigger }}</div>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div x-show="open" x-transition.opacity @click="open = false"
                 class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="relative w-full {{ $w }} rounded-2xl bg-white shadow-xl border border-slate-100 p-6"
                 @click.stop>
                @if ($title)
                    <div class="flex items-start gap-3 mb-4">
                        @if ($icon)
                            <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 {{ $iconCls }}">
                                <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
                            </span>
                        @endif
                        <h3 class="text-lg font-semibold text-slate-900 pt-1.5">{{ $title }}</h3>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </template>
</div>
