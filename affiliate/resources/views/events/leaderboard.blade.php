@extends('layouts.dashboard')

@section('content')
<x-page-header title="Leaderboard" subtitle="Top affiliator berdasarkan total pendapatan." />

@if ($topAffiliators->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="bar-chart-3" title="Belum ada data" message="Peringkat akan muncul setelah ada aktivitas komisi." />
    </x-card>
@else
    <x-table :heads="['#', 'Nama', 'Total Order', 'Total Pendapatan']">
        @foreach ($topAffiliators as $i => $aff)
            <tr class="hover:bg-slate-50/70 transition-colors {{ $i < 3 ? 'bg-accent-50/40' : '' }}">
                <td class="px-5 py-3.5 text-center w-16">
                    <span class="sr-only">Peringkat {{ $i + 1 }}</span>
                    @if ($i === 0)
                        <span class="text-lg" aria-hidden="true">🥇</span>
                    @elseif ($i === 1)
                        <span class="text-lg" aria-hidden="true">🥈</span>
                    @elseif ($i === 2)
                        <span class="text-lg" aria-hidden="true">🥉</span>
                    @else
                        <span class="font-semibold text-slate-400" aria-hidden="true">{{ $i + 1 }}</span>
                    @endif
                </td>
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $aff->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $aff->referral_orders_count }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">Rp {{ number_format($aff->total_earned ?? 0, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </x-table>
@endif
@endsection
