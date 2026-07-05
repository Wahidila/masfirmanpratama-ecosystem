@props([
    'tone' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $tones = [
        'info' => ['bg-primary-50 border-primary-100 text-primary-800', 'info', 'text-primary-500'],
        'success' => ['bg-secondary-50 border-secondary-100 text-secondary-800', 'check-circle', 'text-secondary-500'],
        'warning' => ['bg-amber-50 border-amber-100 text-amber-800', 'alert-triangle', 'text-amber-500'],
        'danger' => ['bg-rose-50 border-rose-100 text-rose-800', 'alert-octagon', 'text-rose-500'],
    ];
    [$cls, $icon, $iconCls] = $tones[$tone] ?? $tones['info'];
@endphp

<div
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" x-transition.opacity @endif
    {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border px-4 py-3 text-sm $cls"]) }}
    role="alert"
>
    <i data-lucide="{{ $icon }}" class="w-4 h-4 mt-0.5 shrink-0 {{ $iconCls }}"></i>
    <div class="flex-1 min-w-0">
        @if ($title)<p class="font-semibold">{{ $title }}</p>@endif
        <div class="{{ $title ? 'mt-0.5' : '' }}">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="shrink-0 opacity-60 hover:opacity-100 transition" aria-label="Tutup">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    @endif
</div>
