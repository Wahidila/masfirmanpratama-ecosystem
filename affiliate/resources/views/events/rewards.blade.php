@extends('layouts.dashboard')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Reward Saya</h1>
    <p class="text-slate-500 mt-1">Daftar reward yang Anda dapatkan dari event</p>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-secondary-50 border border-secondary-200 rounded-xl text-sm text-secondary-700">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div class="mb-4 p-4 bg-rose-50 border border-rose-200 rounded-xl text-sm text-rose-700">
    {{ $errors->first() }}
</div>
@endif

{{-- Unclaimed rewards --}}
@if($unclaimed->count())
<div class="mb-8">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Belum Diklaim</h2>
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($unclaimed as $reward)
        <div class="bg-white rounded-2xl border border-slate-100 p-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-2xl">🏆</span>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800">{{ $reward->description ?? 'Reward' }}</p>
                    <p class="text-sm text-slate-500">{{ $reward->event->title ?? '-' }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4">
                <div>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-primary-50 text-primary-700 font-medium">{{ ucfirst($reward->reward_type) }}</span>
                    @if($reward->reward_value > 0)
                    <span class="ml-2 text-sm font-semibold text-slate-700">Rp {{ number_format($reward->reward_value, 0, ',', '.') }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('rewards.claim', $reward) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-full hover:bg-primary-700 transition">
                        Klaim
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Claimed rewards --}}
<div>
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Sudah Diklaim</h2>
    @if($claimed->count())
    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($claimed as $reward)
        <div class="bg-white rounded-2xl border border-slate-100 p-6 opacity-75">
            <div class="flex items-center gap-3 mb-3">
                <span class="text-2xl">✅</span>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800">{{ $reward->description ?? 'Reward' }}</p>
                    <p class="text-sm text-slate-500">{{ $reward->event->title ?? '-' }}</p>
                </div>
            </div>
            <div class="flex items-center justify-between mt-4">
                <div>
                    <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 font-medium">{{ ucfirst($reward->reward_type) }}</span>
                    @if($reward->reward_value > 0)
                    <span class="ml-2 text-sm font-semibold text-slate-700">Rp {{ number_format($reward->reward_value, 0, ',', '.') }}</span>
                    @endif
                </div>
                <span class="text-xs text-slate-400">Diklaim {{ $reward->claimed_at->format('d M Y') }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center">
        <p class="text-sm text-slate-400">Belum ada reward yang diklaim</p>
    </div>
    @endif
</div>

@if($unclaimed->isEmpty() && $claimed->isEmpty())
<div class="bg-white rounded-2xl border border-slate-100 p-8 text-center">
    <p class="text-slate-400">Anda belum memiliki reward. Ikuti event untuk mendapatkan reward!</p>
    <a href="{{ route('events.index') }}" class="inline-block mt-4 px-6 py-2 bg-primary-600 text-white text-sm font-medium rounded-full hover:bg-primary-700 transition">
        Lihat Event
    </a>
</div>
@endif
@endsection
