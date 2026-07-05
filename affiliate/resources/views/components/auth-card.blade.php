@props([
    'title' => null,
    'subtitle' => null,
    'heading' => 'Affiliate Program',
])

<div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 bg-gradient-to-br from-primary-50 via-white to-secondary-50">
    {{-- Ambient blobs --}}
    <div class="pointer-events-none fixed inset-0 overflow-hidden -z-10" aria-hidden="true">
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-secondary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-400"></div>
    </div>

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ url('/') }}" class="inline-flex"><x-brand-mark size="md" /></a>
            <h1 class="mt-6 text-2xl font-bold text-slate-900">{{ $heading }}</h1>
        </div>

        <x-card :padded="false" class="p-8">
            @if ($title)<h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>@endif
            @if ($subtitle)<p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>@endif

            <div class="{{ $title || $subtitle ? 'mt-6' : '' }}">
                {{ $slot }}
            </div>
        </x-card>

        @isset($below)
            <p class="mt-6 text-center text-sm text-slate-500">{{ $below }}</p>
        @endisset
    </div>
</div>
