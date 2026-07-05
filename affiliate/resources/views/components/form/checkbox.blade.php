@props([
    'name' => null,
    'label' => null,
])

<label class="flex items-center gap-2.5 cursor-pointer select-none">
    <input
        type="checkbox"
        @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'h-4 w-4 rounded border-slate-300 text-primary-600 focus:ring-2 focus:ring-primary-500/30']) }}
    />
    @if ($label)<span class="text-sm text-slate-600">{{ $label }}</span>@endif
    {{ $slot }}
</label>
