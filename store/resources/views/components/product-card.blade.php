@props([
    'image' => null,
    'imageAlt' => '',
    'title',
    'price',
    'originalPrice' => null,
    'category' => null,
    'categoryVariant' => 'category',
    'href' => '#',
    'badge' => null,
])

@php
    $formattedPrice = is_numeric($price) ? 'Rp ' . number_format((float) $price, 0, ',', '.') : $price;
    $formattedOriginal = is_numeric($originalPrice) ? 'Rp ' . number_format((float) $originalPrice, 0, ',', '.') : $originalPrice;
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'group relative flex flex-col bg-white rounded-2xl overflow-hidden border border-slate-100 shadow-sm hover-lift focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2',
    ]) }}
>
    <div class="img-zoom-container relative aspect-square bg-slate-100 overflow-hidden p-4">
        @if ($image)
            <img
                src="{{ $image }}"
                alt="{{ $imageAlt ?: $title }}"
                loading="lazy"
                class="img-zoom w-full h-full object-contain"
            >
        @else
            <div class="flex items-center justify-center w-full h-full text-slate-300">
                <i data-lucide="image" class="w-16 h-16"></i>
            </div>
        @endif

        @if ($category)
            <div class="absolute top-3 left-3">
                <x-badge :variant="$categoryVariant">{{ $category }}</x-badge>
            </div>
        @endif

        @if ($badge)
            <div class="absolute top-3 right-3">
                <x-badge variant="warning" icon="flame">{{ $badge }}</x-badge>
            </div>
        @endif
    </div>

    <div class="flex flex-col flex-1 p-5 gap-3">
        <h3 class="text-base font-bold text-slate-900 leading-snug line-clamp-2 group-hover:text-primary-700 transition-colors">
            {{ $title }}
        </h3>

        <div class="mt-auto flex items-baseline gap-2">
            <span class="text-lg font-extrabold text-primary-600">{{ $formattedPrice }}</span>
            @if ($formattedOriginal)
                <span class="text-sm text-slate-500 line-through">{{ $formattedOriginal }}</span>
            @endif
        </div>

        <div class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 group-hover:text-primary-700">
            Lihat Detail
            <i data-lucide="arrow-right" class="w-4 h-4 transform transition-transform group-hover:translate-x-1"></i>
        </div>
    </div>
</a>
