@props([
    'name' => null,
    'type' => 'text',
])

@php
    $hasError = $name && $errors->has($name);
    $border = $hasError ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/20' : 'border-slate-200 focus:border-primary-400 focus:ring-primary-500/20';
    $base = "h-11 w-full rounded-xl border bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:outline-none focus:ring-2 $border";
@endphp

@if ($type === 'password')
    {{-- Password + tombol lihat/sembunyikan (Alpine). --}}
    <div x-data="{ show: false }" class="relative">
        <input
            x-bind:type="show ? 'text' : 'password'"
            @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
            {{ $attributes->merge(['class' => $base.' pr-11']) }}
        />
        <button
            type="button"
            tabindex="-1"
            x-on:click="show = ! show"
            x-bind:aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'"
            aria-label="Tampilkan password"
            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 transition hover:text-slate-600 focus:outline-none"
        >
            {{-- mata terbuka: password tersembunyi --}}
            <svg x-show="! show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            {{-- mata dicoret: password terlihat --}}
            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.879 9.879" />
            </svg>
        </button>
    </div>
@else
    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $attributes->merge(['class' => $base]) }}
    />
@endif
