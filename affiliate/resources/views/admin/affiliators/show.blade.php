@extends('admin.layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.affiliators.index') }}" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
    </a>
    <div class="flex items-center gap-3 flex-wrap">
        <h1 class="text-2xl font-bold text-slate-900">{{ $affiliator->name }}</h1>
        <x-status-badge :status="$affiliator->status" />
    </div>
    <p class="text-slate-500 text-sm mt-1">{{ $affiliator->email }} · {{ $affiliator->type->name }}</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-stat-card label="Total Pendapatan" value="Rp {{ number_format($stats['total_earnings'], 0, ',', '.') }}" icon="trending-up" tone="primary" />
    <x-stat-card label="Saldo Tersedia" value="Rp {{ number_format($stats['available_balance'], 0, ',', '.') }}" icon="wallet" tone="secondary" />
    <x-stat-card label="Total Order" value="{{ $stats['total_orders'] }}" icon="shopping-bag" tone="accent" />
    <x-stat-card label="Total Klik" value="{{ $stats['total_clicks'] }}" icon="mouse-pointer-click" tone="sky" />
</div>

<x-card title="Informasi" class="mb-6">
    <div class="grid sm:grid-cols-2 gap-4 text-sm">
        <div><span class="text-slate-500">Telepon:</span> <span class="text-slate-800">{{ $affiliator->phone ?: '—' }}</span></div>
        <div><span class="text-slate-500">Bank:</span> <span class="text-slate-800">{{ $affiliator->bank_name ?: '—' }} {{ $affiliator->bank_account_number }}</span></div>
        <div><span class="text-slate-500">Terdaftar:</span> <span class="text-slate-800">{{ $affiliator->created_at->format('d M Y H:i') }}</span></div>
        <div><span class="text-slate-500">Disetujui:</span> <span class="text-slate-800">{{ $affiliator->approved_at ? $affiliator->approved_at->format('d M Y') : '—' }}</span></div>
    </div>
</x-card>

<div class="flex flex-wrap gap-3">
    @if ($affiliator->status === 'pending')
        <form method="POST" action="{{ route('admin.affiliators.approve', $affiliator) }}">@csrf<x-button type="submit" variant="secondary" icon="check">Approve</x-button></form>
    @elseif ($affiliator->status === 'active')
        <form method="POST" action="{{ route('admin.affiliators.suspend', $affiliator) }}">@csrf<x-button type="submit" variant="danger" icon="ban">Suspend</x-button></form>
    @elseif ($affiliator->status === 'suspended')
        <form method="POST" action="{{ route('admin.affiliators.reactivate', $affiliator) }}">@csrf<x-button type="submit" icon="rotate-ccw">Reactivate</x-button></form>
    @endif

    <x-modal title="Hapus affiliator ini?" icon="trash-2" tone="danger">
        <x-slot:trigger>
            <x-button variant="outline" icon="trash-2">Hapus</x-button>
        </x-slot:trigger>
        <p class="text-sm text-slate-600">Akun <strong>{{ $affiliator->name }}</strong> beserta datanya akan dihapus. Tindakan ini tidak bisa dibatalkan.</p>
        <form method="POST" action="{{ route('admin.affiliators.destroy', $affiliator) }}" class="mt-6 flex justify-end gap-2">
            @csrf @method('DELETE')
            <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
            <x-button type="submit" variant="danger" icon="trash-2">Ya, hapus</x-button>
        </form>
    </x-modal>
</div>
@endsection
