@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Dashboard Admin" subtitle="Ringkasan operasional program affiliate." />

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <x-stat-card label="Total Affiliator" value="{{ $stats['total_affiliators'] }}" icon="users" tone="primary" />
    <x-stat-card label="Pending Approval" value="{{ $stats['pending_affiliators'] }}" icon="user-check" tone="accent" />
    <x-stat-card label="Pending Withdraw" value="{{ $stats['pending_withdrawals'] }}" icon="wallet" tone="sky" />
    <x-stat-card label="Total Komisi" value="Rp {{ number_format($stats['total_commissions'], 0, ',', '.') }}" icon="coins" tone="secondary" />
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Pending affiliators --}}
    <x-card title="Menunggu Persetujuan" :padded="false">
        <x-slot:actions>
            <a href="{{ route('admin.affiliators.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Semua</a>
        </x-slot:actions>
        <div class="divide-y divide-slate-100">
            @forelse ($pendingAffiliators as $aff)
                <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $aff->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $aff->email }} · {{ $aff->type->name }}</p>
                    </div>
                    <form method="POST" action="{{ route('admin.affiliators.approve', $aff) }}" class="shrink-0">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm" icon="check">Setujui</x-button>
                    </form>
                </div>
            @empty
                <x-empty-state icon="user-check" title="Tidak ada yang menunggu" message="Semua affiliator sudah diproses." />
            @endforelse
        </div>
    </x-card>

    {{-- Pending withdrawals --}}
    <x-card title="Penarikan Menunggu" :padded="false">
        <x-slot:actions>
            <a href="{{ route('admin.withdrawals.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Semua</a>
        </x-slot:actions>
        <div class="divide-y divide-slate-100">
            @forelse ($pendingWithdrawals as $wd)
                <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $wd->affiliator->name }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ $wd->methodName() }} · {{ $wd->account_number }}</p>
                    </div>
                    <p class="text-sm font-bold text-slate-800 shrink-0">Rp {{ number_format($wd->amount, 0, ',', '.') }}</p>
                </div>
            @empty
                <x-empty-state icon="wallet" title="Tidak ada penarikan" message="Belum ada permintaan penarikan baru." />
            @endforelse
        </div>
    </x-card>
</div>
@endsection
