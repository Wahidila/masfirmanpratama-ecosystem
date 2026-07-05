@extends('layouts.dashboard')

@section('content')
<x-page-header title="Dashboard" :subtitle="'Selamat datang kembali, '.$affiliator->name.'!'" />

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <x-stat-card label="Saldo Tersedia" value="Rp {{ number_format($stats['available_balance'], 0, ',', '.') }}" icon="wallet" tone="secondary" />
    <x-stat-card label="Total Pendapatan" value="Rp {{ number_format($stats['total_earnings'], 0, ',', '.') }}" icon="trending-up" tone="primary" />
    <x-stat-card label="Dalam Cooling" value="Rp {{ number_format($stats['pending_commissions'], 0, ',', '.') }}" icon="hourglass" tone="accent" hint="7 hari" />
    <x-stat-card label="Link Referral" value="{{ $stats['total_referrals'] }}" icon="link" tone="sky" />
    <x-stat-card label="Total Klik" value="{{ number_format($stats['total_clicks']) }}" icon="mouse-pointer-click" tone="primary" />
    <x-stat-card label="Total Order" value="{{ $stats['total_orders'] }}" icon="shopping-bag" tone="secondary" />
</div>

{{-- Recent activity --}}
<div class="grid lg:grid-cols-2 gap-6">
    <x-card title="Komisi Terbaru" :padded="false">
        <x-slot:actions>
            <a href="{{ route('commissions.index') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">Lihat semua</a>
        </x-slot:actions>
        <div class="divide-y divide-slate-100">
            @forelse ($recentCommissions as $commission)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Rp {{ number_format($commission->amount, 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-400">{{ $commission->created_at->diffForHumans() }}</p>
                    </div>
                    <x-status-badge :status="$commission->status" />
                </div>
            @empty
                <x-empty-state icon="coins" title="Belum ada komisi" message="Komisi akan muncul setelah ada order referral." />
            @endforelse
        </div>
    </x-card>

    <x-card title="Order Referral Terbaru" :padded="false">
        <div class="divide-y divide-slate-100">
            @forelse ($recentOrders as $order)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ $order->buyer_name }}</p>
                        <p class="text-xs text-slate-400">{{ $order->referralCode->code }} · {{ $order->ordered_at->diffForHumans() }}</p>
                    </div>
                    <p class="text-sm font-semibold text-slate-800 shrink-0">Rp {{ number_format($order->order_total, 0, ',', '.') }}</p>
                </div>
            @empty
                <x-empty-state icon="shopping-bag" title="Belum ada order" message="Order dari link referral Anda akan tampil di sini." />
            @endforelse
        </div>
    </x-card>
</div>
@endsection
