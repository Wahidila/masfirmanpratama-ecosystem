@props([
    'label' => null,
    'name' => null,
    'hint' => null,
    'required' => false,
])

@php $hasError = $name && $errors->has($name); @endphp

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="block text-sm font-medium text-slate-700">
            {{ $label }}@if ($required)<span class="text-rose-500"> *</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $hasError)<p class="text-xs text-slate-500">{{ $hint }}</p>@endif
    @if ($hasError)<p class="text-xs text-rose-600">{{ $errors->first($name) }}</p>@endif
</div>
