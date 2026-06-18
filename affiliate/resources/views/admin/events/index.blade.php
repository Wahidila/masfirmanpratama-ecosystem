@extends('admin.layouts.admin')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-slate-800">Event & Gamifikasi</h1>
    <a href="{{ route('admin.events.create') }}" class="px-4 py-2 bg-primary-600 text-white text-sm rounded-xl hover:bg-primary-700">+ Buat Event</a>
</div>

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-secondary-50 text-secondary-700 rounded-xl text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="mb-4 px-4 py-3 bg-rose-50 text-rose-700 rounded-xl text-sm">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-100">
            <tr>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Judul</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Tipe</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Status</th>
                <th class="text-center px-4 py-3 font-medium text-slate-600">Peserta</th>
                <th class="text-left px-4 py-3 font-medium text-slate-600">Periode</th>
                <th class="text-right px-4 py-3 font-medium text-slate-600">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @forelse($events as $event)
            <tr>
                <td class="px-4 py-3 text-slate-700 font-medium">{{ $event->title }}</td>
                <td class="px-4 py-3 text-slate-600">{{ ucfirst($event->type) }}</td>
                <td class="px-4 py-3 text-center">
                    @if($event->status === 'active')
                        <span class="text-xs px-2 py-1 rounded-full bg-secondary-50 text-secondary-700">Aktif</span>
                    @elseif($event->status === 'draft')
                        <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">Draft</span>
                    @else
                        <span class="text-xs px-2 py-1 rounded-full bg-rose-50 text-rose-700">Selesai</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center text-slate-600">{{ $event->participants_count }}</td>
                <td class="px-4 py-3 text-slate-600">{{ $event->start_date->format('d M Y') }} — {{ $event->end_date->format('d M Y') }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-1">
                        @if($event->status === 'draft')
                        <form method="POST" action="{{ route('admin.events.activate', $event) }}" class="inline">@csrf
                            <button class="text-xs px-2 py-1 bg-secondary-50 text-secondary-700 rounded-lg">Aktifkan</button>
                        </form>
                        @endif
                        <a href="{{ route('admin.events.edit', $event) }}" class="text-xs px-2 py-1 bg-slate-100 text-slate-700 rounded-lg">Edit</a>
                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="inline" onsubmit="return confirm('Hapus event ini?')">
                            @csrf @method('DELETE')
                            <button class="text-xs px-2 py-1 bg-rose-50 text-rose-700 rounded-lg">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada event</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($events->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">{{ $events->links() }}</div>
    @endif
</div>
@endsection
