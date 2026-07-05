@extends('layouts.dashboard')

@section('content')
<x-page-header title="Reward Saya" subtitle="Daftar reward yang Anda dapatkan dari event." />

{{-- Unclaimed --}}
@if ($unclaimed->count())
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">Belum Diklaim</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach ($unclaimed as $reward)
                <x-card>
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">🏆</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800">{{ $reward->description ?? 'Reward' }}</p>
                            <p class="text-sm text-slate-500 truncate">{{ $reward->event->title ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-badge variant="primary">{{ ucfirst($reward->reward_type) }}</x-badge>
                            @if ($reward->reward_value > 0)<span class="text-sm font-semibold text-slate-700">Rp {{ number_format($reward->reward_value, 0, ',', '.') }}</span>@endif
                        </div>
                        <form method="POST" action="{{ route('rewards.claim', $reward) }}">
                            @csrf
                            <x-button type="submit" size="sm" icon="gift">Klaim</x-button>
                        </form>
                    </div>
                </x-card>
            @endforeach
        </div>
    </div>
@endif

{{-- Claimed --}}
<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Sudah Diklaim</h2>
    @if ($claimed->count())
        <div class="grid sm:grid-cols-2 gap-4">
            @foreach ($claimed as $reward)
                <x-card class="opacity-80">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="text-2xl">✅</span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-800">{{ $reward->description ?? 'Reward' }}</p>
                            <p class="text-sm text-slate-500 truncate">{{ $reward->event->title ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-badge variant="neutral">{{ ucfirst($reward->reward_type) }}</x-badge>
                            @if ($reward->reward_value > 0)<span class="text-sm font-semibold text-slate-700">Rp {{ number_format($reward->reward_value, 0, ',', '.') }}</span>@endif
                        </div>
                        <span class="text-xs text-slate-400">Diklaim {{ $reward->claimed_at->format('d M Y') }}</span>
                    </div>
                </x-card>
            @endforeach
        </div>
    @else
        <x-card :padded="false"><x-empty-state icon="gift" title="Belum ada reward diklaim" /></x-card>
    @endif
</div>

@if ($unclaimed->isEmpty() && $claimed->isEmpty())
    <x-card :padded="false" class="mt-2">
        <x-empty-state icon="gift" title="Belum ada reward" message="Ikuti event untuk mendapatkan reward!">
            <x-button :href="route('events.index')" size="sm" icon="trophy">Lihat Event</x-button>
        </x-empty-state>
    </x-card>
@endif
@endsection
