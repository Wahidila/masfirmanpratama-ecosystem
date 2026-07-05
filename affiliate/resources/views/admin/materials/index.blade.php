@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Materi Marketing" subtitle="Kelola materi promosi untuk affiliator.">
    <x-slot:actions>
        <x-button :href="route('admin.materials.create')" icon="upload">Upload Materi</x-button>
    </x-slot:actions>
</x-page-header>

@if ($materials->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="folder-open" title="Belum ada materi" message="Upload materi pertama untuk dibagikan ke affiliator.">
            <x-button :href="route('admin.materials.create')" icon="upload" size="sm">Upload Materi</x-button>
        </x-empty-state>
    </x-card>
@else
    <x-table :heads="['Judul', 'Tipe', 'Download', 'Status', 'Aksi']">
        @foreach ($materials as $m)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $m->title }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ ucfirst($m->type) }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $m->download_count }}</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$m->is_active ? 'active' : 'inactive'" /></td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                        <form method="POST" action="{{ route('admin.materials.toggle', $m) }}" class="inline">@csrf
                            <x-button type="submit" variant="outline" size="sm">{{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</x-button>
                        </form>
                        <x-modal title="Hapus materi ini?" icon="trash-2" tone="danger">
                            <x-slot:trigger>
                                <x-button variant="danger-ghost" size="sm" icon="trash-2">Hapus</x-button>
                            </x-slot:trigger>
                            <p class="text-sm text-slate-600">Materi <strong>{{ $m->title }}</strong> akan dihapus permanen.</p>
                            <form method="POST" action="{{ route('admin.materials.destroy', $m) }}" class="mt-6 flex justify-end gap-2">
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

    @if ($materials->hasPages())
        <div class="mt-4">{{ $materials->links() }}</div>
    @endif
@endif
@endsection
