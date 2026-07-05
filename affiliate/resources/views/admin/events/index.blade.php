@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Event &amp; Gamifikasi" subtitle="Kelola event, tantangan, dan reward affiliator.">
    <x-slot:actions>
        <x-button :href="route('admin.events.create')" icon="plus">Buat Event</x-button>
    </x-slot:actions>
</x-page-header>

@if ($events->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="trophy" title="Belum ada event" message="Buat event pertama untuk mulai gamifikasi.">
            <x-button :href="route('admin.events.create')" icon="plus" size="sm">Buat Event</x-button>
        </x-empty-state>
    </x-card>
@else
    <x-table :heads="['Judul', 'Tipe', 'Status', 'Peserta', 'Periode', 'Aksi']">
        @foreach ($events as $event)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $event->title }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ ucfirst($event->type) }}</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$event->status" /></td>
                <td class="px-5 py-3.5 text-slate-600">{{ $event->participants_count }}</td>
                <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $event->start_date->format('d M Y') }} — {{ $event->end_date->format('d M Y') }}</td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                        @if ($event->status === 'draft')
                            <form method="POST" action="{{ route('admin.events.activate', $event) }}" class="inline">@csrf
                                <x-button type="submit" variant="secondary" size="sm">Aktifkan</x-button>
                            </form>
                        @endif
                        <x-button :href="route('admin.events.edit', $event)" variant="outline" size="sm">Edit</x-button>
                        <x-modal title="Hapus event ini?" icon="trash-2" tone="danger">
                            <x-slot:trigger>
                                <x-button variant="danger-ghost" size="sm" icon="trash-2">Hapus</x-button>
                            </x-slot:trigger>
                            <p class="text-sm text-slate-600">Event <strong>{{ $event->title }}</strong> akan dihapus permanen.</p>
                            <form method="POST" action="{{ route('admin.events.destroy', $event) }}" class="mt-6 flex justify-end gap-2">
                                @csrf @method('DELETE')
                                <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
                                <x-button type="submit" variant="danger" icon="trash-2">Ya, hapus</x-button>
                            </form>
                        </x-modal>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @if ($events->hasPages())
        <div class="mt-4">{{ $events->links() }}</div>
    @endif
@endif
@endsection
