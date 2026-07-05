@extends('layouts.dashboard')

@section('content')
<x-page-header title="Komisi Saya" subtitle="Riwayat dan status komisi dari referral Anda." />

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Cooling (7 hari)" value="Rp {{ number_format($summary['cooling'], 0, ',', '.') }}" icon="hourglass" tone="accent" />
    <x-stat-card label="Tersedia" value="Rp {{ number_format($summary['available'], 0, ',', '.') }}" icon="wallet" tone="secondary" />
    <x-stat-card label="Sudah Ditarik" value="Rp {{ number_format($summary['withdrawn'], 0, ',', '.') }}" icon="banknote" tone="slate" />
    <x-stat-card label="Total Pendapatan" value="Rp {{ number_format($summary['total'], 0, ',', '.') }}" icon="trending-up" tone="primary" />
</div>

{{-- Filter --}}
<form method="GET" class="mb-4 flex items-center gap-2">
    <x-form.select name="status" onchange="this.form.submit()" class="max-w-xs">
        <option value="all" @selected(request('status') === 'all')>Semua Status</option>
        <option value="cooling" @selected(request('status') === 'cooling')>Cooling</option>
        <option value="available" @selected(request('status') === 'available')>Tersedia</option>
        <option value="withdrawn" @selected(request('status') === 'withdrawn')>Ditarik</option>
    </x-form.select>
</form>

@if ($commissions->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="coins" title="Belum ada data komisi" message="Komisi muncul otomatis setelah ada order dari link referral Anda." />
    </x-card>
@else
    <x-table :heads="['Tanggal', 'Order', 'Jumlah', 'Rate', 'Status', 'Tersedia']">
        @foreach ($commissions as $commission)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 text-slate-600 whitespace-nowrap">{{ $commission->created_at->format('d M Y') }}</td>
                <td class="px-5 py-3.5 text-slate-700">{{ $commission->referralOrder->buyer_name ?? '—' }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">Rp {{ number_format($commission->amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $commission->rate_applied }}%</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$commission->status" /></td>
                <td class="px-5 py-3.5 text-slate-500 text-xs whitespace-nowrap">{{ $commission->available_at?->format('d M Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($commissions->hasPages())
        <div class="mt-4">{{ $commissions->links() }}</div>
    @endif
@endif
@endsection
