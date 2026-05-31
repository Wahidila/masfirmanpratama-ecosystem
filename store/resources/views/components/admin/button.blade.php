@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium shadow-theme-xs transition-colors focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 disabled:cursor-not-allowed disabled:opacity-50';

    $variantClasses = match ($variant) {
        'primary' => 'rounded-lg bg-brand-500 text-white hover:bg-brand-600',
        'outline' => 'rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]',
        'ghost' => 'rounded-lg text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/[0.03]',
        'danger' => 'rounded-lg bg-error-500 text-white hover:bg-error-600',
        'success' => 'rounded-lg bg-success-500 text-white hover:bg-success-600',
        default => 'rounded-lg bg-brand-500 text-white hover:bg-brand-600',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-2 text-xs',
        'md' => 'px-4 py-3 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
        default => 'px-4 py-3 text-sm',
    };

    $classes = "$baseClasses $variantClasses $sizeClasses";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes])->merge(['disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
