@props(['title', 'value', 'hint' => null, 'tone' => 'slate'])

@php
    $accentColors = [
        'primary' => 'text-brand-500',
        'secondary' => 'text-success-500',
        'amber' => 'text-warning-500',
        'slate' => 'text-gray-500',
    ];
    $accent = $accentColors[$tone] ?? $accentColors['slate'];
@endphp

<div {{ $attributes->class(['rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6']) }}>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $title }}</p>
    <p class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-theme-xs {{ $accent }}">{{ $hint }}</p>
    @endif
</div>
