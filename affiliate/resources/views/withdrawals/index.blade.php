@extends('layouts.dashboard')

@section('content')
<x-page-header title="Penarikan" subtitle="Riwayat penarikan komisi Anda.">
    <x-slot:actions>
        <x-button :href="route('withdrawals.create')" icon="banknote">Tarik Saldo</x-button>
    </x-slot:actions>
</x-page-header>

@if ($withdrawals->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="banknote" title="Belum ada penarikan" message="Ajukan penarikan saat saldo komisi Anda sudah tersedia.">
            <x-button :href="route('withdrawals.create')" icon="banknote" size="sm">Tarik Saldo</x-button>
        </x-empty-state>
    </x-card>
@else
    <x-table :heads="['Tanggal', 'Metode', 'Rekening', 'Jumlah', 'Status']">
        @foreach ($withdrawals as $withdrawal)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $withdrawal->created_at->format('d M Y H:i') }}</td>
                <td class="px-5 py-3.5 text-slate-700">{{ $withdrawal->method->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $withdrawal->account_name }} · {{ $withdrawal->account_number }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">Rp {{ number_format($withdrawal->amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$withdrawal->status" /></td>
            </tr>
        @endforeach
    </x-table>

    @if ($withdrawals->hasPages())
        <div class="mt-4">{{ $withdrawals->links() }}</div>
    @endif
@endif
@endsection
