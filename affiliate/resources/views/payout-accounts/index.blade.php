@extends('layouts.dashboard')

@section('content')
<x-page-header title="Rekening Tujuan" subtitle="Simpan rekening atau e-wallet Anda di sini, lalu tinggal pilih saat menarik saldo." />

@if (session('success'))
    <x-alert tone="success" class="mb-4">{{ session('success') }}</x-alert>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-3">
        @forelse ($accounts as $account)
            <x-card>
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-slate-900">{{ $account->method?->name ?? 'Metode dihapus' }}</span>
                            @if ($account->is_primary)
                                <x-badge variant="primary">Utama</x-badge>
                            @endif
                            @if ($account->method && ! $account->method->is_active)
                                <x-badge variant="neutral">Sedang tidak tersedia</x-badge>
                            @endif
                        </div>
                        <p class="text-sm text-slate-600 mt-1 break-all">{{ $account->account_number }}</p>
                        <p class="text-sm text-slate-500">a.n. {{ $account->account_name }}</p>
                        @if ($account->method && $account->method->is_active)
                            <p class="text-xs text-slate-400 mt-2">
                                Min. Rp {{ number_format($account->method->min_withdrawal, 0, ',', '.') }}
                                @if ($account->method->fee_flat > 0)
                                    · biaya admin Rp {{ number_format($account->method->fee_flat, 0, ',', '.') }}
                                @else
                                    · tanpa biaya admin
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if (! $account->is_primary)
                            <form method="POST" action="{{ route('payout-accounts.primary', $account) }}">@csrf
                                <x-button type="submit" variant="outline" size="sm">Jadikan utama</x-button>
                            </form>
                        @endif
                        <x-modal title="Hapus rekening ini?" icon="trash-2" tone="danger">
                            <x-slot:trigger>
                                <x-button variant="danger-ghost" size="sm">Hapus</x-button>
                            </x-slot:trigger>
                            <p class="text-sm text-slate-600">
                                Rekening <strong>{{ $account->account_number }}</strong> akan dihapus dari daftar. Riwayat penarikan yang sudah ada tidak terpengaruh.
                            </p>
                            <form method="POST" action="{{ route('payout-accounts.destroy', $account) }}" class="mt-6 flex justify-end gap-2">
                                @csrf @method('DELETE')
                                <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
                                <x-button type="submit" variant="danger" icon="trash-2">Ya, hapus</x-button>
                            </form>
                        </x-modal>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card :padded="false">
                <x-empty-state icon="landmark" title="Belum ada rekening tersimpan" message="Tambahkan satu dulu supaya bisa menarik saldo." />
            </x-card>
        @endforelse
    </div>

    <x-card title="Tambah Rekening" class="h-fit">
        <form method="POST" action="{{ route('payout-accounts.store') }}" class="space-y-4">
            @csrf
            <x-form.group label="Bank / E-Wallet" name="withdrawal_method_id" required>
                <x-form.select name="withdrawal_method_id" required>
                    <option value="">Pilih...</option>
                    @foreach ($methods as $method)
                        <option value="{{ $method->id }}" @selected(old('withdrawal_method_id') == $method->id)>
                            {{ $method->name }} ({{ $method->typeLabel() }})
                        </option>
                    @endforeach
                </x-form.select>
            </x-form.group>
            <x-form.group label="Nomor Rekening / No. HP" name="account_number" required>
                <x-form.input name="account_number" value="{{ old('account_number') }}" required placeholder="Nomor rekening" />
            </x-form.group>
            <x-form.group label="Nama Pemilik" name="account_name" required
                hint="Harus sama persis dengan yang terdaftar di bank/e-wallet, agar transfer tidak tertahan.">
                <x-form.input name="account_name" value="{{ old('account_name') }}" required placeholder="Nama sesuai rekening" />
            </x-form.group>
            <x-button type="submit" icon="save" class="w-full justify-center">Simpan Rekening</x-button>
        </form>
    </x-card>
</div>
@endsection
