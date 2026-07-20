@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Metode Penarikan" subtitle="Metode yang bisa dipilih affiliator saat menarik komisi. Biaya admin dipotong dari jumlah yang ditransfer.">
    <x-slot:actions>
        <x-button :href="route('admin.withdrawal-methods.create')" icon="plus">Tambah Metode</x-button>
    </x-slot:actions>
</x-page-header>

@if (session('error'))
    <x-alert tone="danger" class="mb-4">{{ session('error') }}</x-alert>
@endif

@if ($methods->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="landmark" title="Belum ada metode penarikan" message="Tambahkan minimal satu metode agar affiliator bisa menarik komisi." />
    </x-card>
@else
    <x-table :heads="['Nama', 'Tipe', 'Minimum', 'Biaya Admin', 'Dipakai', 'Status', 'Aksi']">
        @foreach ($methods as $method)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $method->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $method->typeLabel() }}</td>
                <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">Rp {{ number_format($method->min_withdrawal, 0, ',', '.') }}</td>
                <td class="px-5 py-3.5 whitespace-nowrap">
                    @if ($method->fee_flat > 0)
                        <span class="text-slate-600">Rp {{ number_format($method->fee_flat, 0, ',', '.') }}</span>
                    @else
                        <span class="text-slate-400">Gratis</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 text-slate-600">{{ $method->withdrawals_count }}</td>
                <td class="px-5 py-3.5">
                    <x-badge :variant="$method->is_active ? 'success' : 'neutral'">{{ $method->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                </td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-2">
                        <form method="POST" action="{{ route('admin.withdrawal-methods.toggle', $method) }}" class="inline">@csrf
                            <x-button type="submit" :variant="$method->is_active ? 'outline' : 'secondary'" size="sm">
                                {{ $method->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-button>
                        </form>
                        <x-button :href="route('admin.withdrawal-methods.edit', $method)" variant="outline" size="sm">Edit</x-button>
                        @if ($method->withdrawals_count === 0 && $method->payout_accounts_count === 0)
                            <x-modal title="Hapus metode ini?" icon="trash-2" tone="danger">
                                <x-slot:trigger>
                                    <x-button variant="danger-ghost" size="sm">Hapus</x-button>
                                </x-slot:trigger>
                                <p class="text-sm text-slate-600">Metode <strong>{{ $method->name }}</strong> akan dihapus. Metode ini belum pernah dipakai, jadi tidak ada riwayat yang terpengaruh.</p>
                                <form method="POST" action="{{ route('admin.withdrawal-methods.destroy', $method) }}" class="mt-6 flex justify-end gap-2">
                                    @csrf @method('DELETE')
                                    <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
                                    <x-button type="submit" variant="danger" icon="trash-2">Ya, hapus</x-button>
                                </form>
                            </x-modal>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @if ($methods->hasPages())
        <div class="mt-4">{{ $methods->links() }}</div>
    @endif
@endif

<p class="mt-4 text-xs text-slate-500">
    Metode yang sudah dipakai penarikan atau rekening tersimpan tidak bisa dihapus — nonaktifkan saja supaya riwayat penarikan tetap terbaca.
</p>
@endsection
