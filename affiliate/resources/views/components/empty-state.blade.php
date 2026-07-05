@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center px-6 py-12']) }}>
    <span class="flex items-center justify-center w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 mb-4">
        <i data-lucide="{{ $icon }}" class="w-7 h-7"></i>
    </span>
    <p class="text-sm font-semibold text-slate-700">{{ $title }}</p>
    @if ($message)<p class="mt-1 text-sm text-slate-500 max-w-sm">{{ $message }}</p>@endif
    @if (isset($slot) && trim($slot) !== '')
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
