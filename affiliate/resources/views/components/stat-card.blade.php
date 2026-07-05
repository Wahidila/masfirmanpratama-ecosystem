@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'hint' => null,
    'tone' => 'primary',
])

@php
    $tones = [
        'primary' => 'bg-primary-50 text-primary-600',
        'secondary' => 'bg-secondary-50 text-secondary-600',
        'accent' => 'bg-accent-50 text-accent-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'sky' => 'bg-sky-50 text-sky-600',
        'slate' => 'bg-slate-100 text-slate-600',
    ];
    $iconCls = $tones[$tone] ?? $tones['primary'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/70 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-slate-500 truncate">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 leading-tight">{{ $value }}</p>
            @if ($hint)<p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>@endif
        </div>
        @if ($icon)
            <span class="flex items-center justify-center w-11 h-11 rounded-xl shrink-0 {{ $iconCls }}">
                <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
            </span>
        @endif
    </div>
</div>
