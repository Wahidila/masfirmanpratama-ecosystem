@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Semua Komisi" subtitle="Pantau seluruh komisi affiliator." />

<form method="GET" class="mb-4 flex items-center gap-2">
    <x-form.select name="status" aria-label="Filter status komisi" onchange="this.form.submit()" class="max-w-xs">
        <option value="all" @selected(request('status') === 'all')>Semua Status</option>
        <option value="cooling" @selected(request('status') === 'cooling')>Cooling</option>
        <option value="available" @selected(request('status') === 'available')>Available</option>
        <option value="withdrawn" @selected(request('status') === 'withdrawn')>Withdrawn</option>
    </x-form.select>
</form>

@if ($commissions->isEmpty())
    <x-card :padded="false"><x-empty-state icon="coins" title="Belum ada komisi" /></x-card>
@else
    <x-table :heads="['Affiliator', 'Order', 'Jumlah', 'Rate', 'Status', 'Tersedia']">
        @foreach ($commissions as $c)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $c->affiliator->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $c->referralOrder->buyer_name ?? '—' }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">Rp {{ number_format($c->amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $c->rate_applied }}%</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$c->status" /></td>
                <td class="px-5 py-3.5 text-slate-500 text-xs whitespace-nowrap">{{ $c->available_at?->format('d M Y') ?? '—' }}</td>
            </tr>
        @endforeach
    </x-table>

    @if ($commissions->hasPages())
        <div class="mt-4">{{ $commissions->withQueryString()->links() }}</div>
    @endif
@endif
@endsection
