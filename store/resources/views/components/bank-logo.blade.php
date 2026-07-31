@props([
    'bank',
    'size' => 'lg',
])

@php
    // Palette badge fallback (dipakai saat bank tak punya logo SVG / bank kustom).
    $palette = [
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'emerald' => 'bg-secondary-50 text-secondary-700 ring-secondary-200',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    ];

    $box = $size === 'sm' ? 'h-11 w-14' : 'h-12 w-16';
    $slug = (string) ($bank['logo'] ?? '');
    $hasLogo = $slug !== '' && is_file(public_path("images/bank-logos/{$slug}.svg"));
    $colorClass = $palette[$bank['logo_color'] ?? 'indigo'] ?? $palette['indigo'];
@endphp

@if ($hasLogo)
    <span class="inline-flex {{ $box }} shrink-0 items-center justify-center rounded-xl border border-slate-100 bg-white p-2 ring-1 ring-slate-200">
        <img
            src="{{ asset('images/bank-logos/'.$slug.'.svg') }}"
            alt="Logo {{ $bank['bank'] ?? $slug }}"
            class="max-h-full max-w-full object-contain"
            loading="lazy"
        >
    </span>
@else
    <span class="inline-flex {{ $box }} shrink-0 items-center justify-center rounded-xl text-xs font-extrabold uppercase tracking-wider ring-1 {{ $colorClass }}">
        {{ $bank['bank'] ?? '' }}
    </span>
@endif
