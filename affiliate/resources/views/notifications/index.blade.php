@extends('layouts.dashboard')

@section('content')
<x-page-header title="Notifikasi" subtitle="Pemberitahuan terbaru untuk akun Anda.">
    <x-slot:actions>
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <x-button type="submit" variant="outline" size="sm" icon="check-check">Tandai semua dibaca</x-button>
        </form>
    </x-slot:actions>
</x-page-header>

@if ($notifications->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="bell-off" title="Belum ada notifikasi" message="Semua pemberitahuan akan tampil di sini." />
    </x-card>
@else
    <div class="space-y-2">
        @foreach ($notifications as $notification)
            <x-card :padded="false" class="{{ $notification->isRead() ? 'opacity-70' : '' }}">
                <div class="flex items-start gap-3 p-4">
                    <span class="w-2 h-2 rounded-full mt-2 shrink-0 {{ $notification->isRead() ? 'bg-slate-300' : 'bg-primary-500' }}"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800">{{ $notification->title }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $notification->message }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    @unless ($notification->isRead())
                        <form method="POST" action="{{ route('notifications.read', $notification) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-primary-600 hover:text-primary-700">Baca</button>
                        </form>
                    @endunless
                </div>
            </x-card>
        @endforeach
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
@endif
@endsection
