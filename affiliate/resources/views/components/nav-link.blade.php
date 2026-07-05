@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a href="{{ $href }}" @if ($active) aria-current="page" @endif
   {{ $attributes->merge(['class' => 'group flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors '.($active ? 'bg-primary-50 text-primary-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900')]) }}>
    @if ($icon)
        <i data-lucide="{{ $icon }}" class="w-5 h-5 shrink-0 {{ $active ? 'text-primary-600' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
    @endif
    <span class="truncate">{{ $slot }}</span>
</a>
