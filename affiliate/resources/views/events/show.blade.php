@extends('layouts.dashboard')

@section('content')
<div class="mb-6">
    <a href="{{ route('events.index') }}" class="text-sm text-primary-600 hover:text-primary-700 mb-2 inline-block">&larr; Kembali</a>
    <h1 class="text-2xl font-bold text-slate-800">{{ $event->title }}</h1>
    <p class="text-slate-500 mt-1">{{ $event->start_date->format('d M Y') }} — {{ $event->end_date->format('d M Y') }}</p>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl border border-slate-100 p-6 mb-6">
            <h3 class="font-semibold text-slate-800 mb-3">Deskripsi</h3>
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $event->description }}</p>

            @if($event->rules)
            <h3 class="font-semibold text-slate-800 mt-6 mb-3">Aturan</h3>
            <ul class="list-disc list-inside text-sm text-slate-600 space-y-1">
                @foreach($event->rules as $rule)
                <li>{{ $rule }}</li>
                @endforeach
            </ul>
            @endif

            @if($event->rewards)
            <h3 class="font-semibold text-slate-800 mt-6 mb-3">Hadiah</h3>
            <div class="space-y-2">
                @foreach($event->rewards as $reward)
                <div class="flex items-center gap-3 p-3 bg-accent-50 rounded-xl">
                    <span class="text-lg">🏆</span>
                    <div>
                        <p class="text-sm font-medium text-slate-700">{{ $reward['description'] ?? $reward['prize'] ?? '-' }}</p>
                        @if(isset($reward['reward_value']))
                        <p class="text-xs text-slate-500">{{ $reward['reward_type'] ?? 'cash' }}: Rp {{ number_format($reward['reward_value'], 0, ',', '.') }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Join button --}}
        @if(!$participation && $event->isActive())
        <form method="POST" action="{{ route('events.join', $event) }}">
            @csrf
            <button type="submit" class="w-full py-3 bg-primary-600 text-white font-medium rounded-xl hover:bg-primary-700 transition">
                Gabung Event Ini
            </button>
        </form>
        @elseif($participation)
        <div class="p-4 bg-secondary-50 border border-secondary-200 rounded-xl text-sm text-secondary-700">
            ✅ Anda sudah terdaftar di event ini. Score: <strong>{{ $participation->score }}</strong>
            @if($participation->rank)
             · Rank: <strong>#{{ $participation->rank }}</strong>
            @endif
        </div>
        @endif
    </div>

    {{-- Leaderboard sidebar --}}
    <div>
        <div class="bg-white rounded-2xl border border-slate-100 p-6">
            <h3 class="font-semibold text-slate-800 mb-4">Leaderboard</h3>
            @forelse($leaderboard as $i => $participant)
            <div class="flex items-center gap-3 py-2 {{ $i < 3 ? 'font-medium' : '' }}">
                <span class="text-sm w-6 text-center {{ $i === 0 ? 'text-accent-600' : ($i === 1 ? 'text-slate-500' : ($i === 2 ? 'text-amber-700' : 'text-slate-400')) }}">
                    {{ $i + 1 }}
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm text-slate-700 truncate">{{ $participant->affiliator->name }}</p>
                </div>
                <span class="text-sm text-slate-600">{{ $participant->score }}</span>
            </div>
            @empty
            <p class="text-sm text-slate-400 text-center">Belum ada peserta</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
