@props([
    'name' => null,
    'rows' => 4,
])

@php
    $hasError = $name && $errors->has($name);
    $border = $hasError ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/20' : 'border-slate-200 focus:border-primary-400 focus:ring-primary-500/20';
@endphp

<textarea
    @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
    rows="{{ $rows }}"
    {{ $attributes->merge(['class' => "w-full rounded-xl border bg-white px-4 py-3 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:outline-none focus:ring-2 $border"]) }}
>{{ $slot }}</textarea>
