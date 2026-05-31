@props([
    'status',
    'label' => null,
])

@php
    $statusMap = [
        'pending' => ['tone' => 'warning', 'label' => 'Pending'],
        'partial_paid' => ['tone' => 'warning', 'label' => 'Cicilan'],
        'paid' => ['tone' => 'brand', 'label' => 'Lunas'],
        'shipped' => ['tone' => 'brand', 'label' => 'Dikirim'],
        'completed' => ['tone' => 'success', 'label' => 'Selesai'],
        'cancelled' => ['tone' => 'error', 'label' => 'Batal'],
        'refunded' => ['tone' => 'gray', 'label' => 'Refund'],
    ];

    $cfg = $statusMap[$status] ?? ['tone' => 'gray', 'label' => ucfirst(str_replace('_', ' ', $status))];
    $displayLabel = $label ?? $cfg['label'];
    $tone = $cfg['tone'];

    $toneClasses = match ($tone) {
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-500',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
        'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
        'gray' => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
        default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
    };
@endphp

<span {{ $attributes->class(["inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium $toneClasses"]) }}>
    {{ $displayLabel }}
</span>
