@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-primary-50 text-primary-700 border-primary-100',
        'success' => 'bg-secondary-50 text-secondary-700 border-secondary-100',
        'warning' => 'bg-amber-50 text-amber-700 border-amber-100',
        'danger' => 'bg-rose-50 text-rose-700 border-rose-100',
        'info' => 'bg-sky-50 text-sky-700 border-sky-100',
        'neutral' => 'bg-slate-100 text-slate-600 border-slate-200',
    ];
    $cls = $variants[$variant] ?? $variants['neutral'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border tracking-wide $cls"]) }}>
    @if ($icon)<i data-lucide="{{ $icon }}" class="w-3.5 h-3.5"></i>@endif
    {{ $slot }}
</span>
