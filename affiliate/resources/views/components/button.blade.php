@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 shadow-sm shadow-primary-500/30 hover:shadow-primary-500/40',
        'secondary' => 'bg-secondary-600 text-white hover:bg-secondary-700 shadow-sm shadow-secondary-500/30 hover:shadow-secondary-500/40',
        'outline' => 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 shadow-sm',
        'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100',
        'danger-ghost' => 'bg-transparent text-rose-600 hover:bg-rose-50',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 shadow-sm shadow-rose-500/30',
        'accent' => 'bg-accent-500 text-white hover:bg-accent-600 shadow-sm shadow-accent-500/30',
    ];

    $sizes = [
        'sm' => 'px-3.5 py-2 text-xs gap-1.5',
        'md' => 'px-5 py-2.5 text-sm gap-2',
        'lg' => 'px-7 py-3.5 text-base gap-2.5',
    ];

    $iconSizes = ['sm' => 'w-4 h-4', 'md' => 'w-4 h-4', 'lg' => 'w-5 h-5'];

    $base = 'ripple inline-flex items-center justify-center rounded-xl font-semibold transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    $classes = trim($base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']));
    $iconClass = $iconSizes[$size] ?? 'w-4 h-4';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')<i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')<i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>@endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')<i data-lucide="{{ $icon }}" class="{{ $iconClass }}"></i>@endif
    </button>
@endif
