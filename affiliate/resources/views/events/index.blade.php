@extends('layouts.dashboard')

@section('content')
@php $typeVariant = ['challenge' => 'primary', 'contest' => 'warning', 'bonus' => 'success']; @endphp

<x-page-header title="Event &amp; Gamifikasi" subtitle="Ikuti event dan dapatkan reward tambahan." />

@if ($events->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="trophy" title="Belum ada event aktif" message="Event dan tantangan akan tampil di sini saat tersedia." />
    </x-card>
@else
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach ($events as $event)
            <x-card hover>
                <div class="flex items-center gap-2 mb-3">
                    <x-badge :variant="$typeVariant[$event->type] ?? 'neutral'">{{ ucfirst($event->type) }}</x-badge>
                    @if ($event->isActive())
                        <x-badge variant="success" icon="zap">Berlangsung</x-badge>
                    @endif
                </div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">{{ $event->title }}</h3>
                <p class="text-sm text-slate-500 mb-4 line-clamp-2">{{ $event->description }}</p>
                <div class="flex items-center justify-between text-xs text-slate-400 mb-4">
                    <span class="inline-flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> {{ $event->start_date->format('d M') }} — {{ $event->end_date->format('d M Y') }}</span>
                    <span class="inline-flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i> {{ $event->participants()->count() }} peserta</span>
                </div>
                <x-button :href="route('events.show', $event)" variant="outline" class="w-full" icon="arrow-right" iconPosition="right">Lihat Detail</x-button>
            </x-card>
        @endforeach
    </div>

    @if ($events->hasPages())
        <div class="mt-6">{{ $events->links() }}</div>
    @endif
@endif
@endsection
