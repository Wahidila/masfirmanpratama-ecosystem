@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
    'hover' => false,
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200/70 bg-white shadow-sm '.($hover ? 'hover-lift' : '')]) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                @if ($title)<h3 class="text-base font-semibold text-slate-900 truncate">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2 shrink-0">{{ $actions }}</div>@endisset
        </div>
    @endif

    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-100 px-5 py-3 text-sm text-slate-500">{{ $footer }}</div>
    @endisset
</div>
