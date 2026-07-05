@props([
    'size' => 'md',
    'dark' => false,
])

@php
    $boxSizes = ['sm' => 'w-8 h-8', 'md' => 'w-10 h-10', 'lg' => 'w-12 h-12'];
    $iconSizes = ['sm' => 'w-4 h-4', 'md' => 'w-5 h-5', 'lg' => 'w-6 h-6'];
    $textSizes = ['sm' => 'text-lg', 'md' => 'text-xl', 'lg' => 'text-2xl'];
    $box = $boxSizes[$size] ?? $boxSizes['md'];
    $ic = $iconSizes[$size] ?? $iconSizes['md'];
    $tx = $textSizes[$size] ?? $textSizes['md'];
    $wordColor = $dark ? 'text-white' : 'text-slate-900';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="flex items-center justify-center {{ $box }} bg-primary-600 rounded-xl text-white shadow-sm shadow-primary-500/30">
        <i data-lucide="git-fork" class="{{ $ic }}"></i>
    </span>
    <span class="font-bold {{ $tx }} tracking-tight {{ $wordColor }}">
        MFP<span class="text-primary-600">Affiliate</span>
    </span>
</span>
