@extends('layouts.dashboard')

@section('content')
<x-page-header title="Tarik Saldo">
    <x-slot:subtitle>Saldo tersedia: <span class="font-semibold text-secondary-600">Rp {{ number_format($availableBalance, 0, ',', '.') }}</span></x-slot:subtitle>
</x-page-header>

<div class="max-w-lg">
    <x-card>
        @if ($availableBalance <= 0)
            <x-empty-state icon="wallet" title="Saldo belum tersedia" message="Saldo Anda belum tersedia untuk ditarik.">
                <x-button :href="route('commissions.index')" variant="outline" size="sm">Lihat status komisi</x-button>
            </x-empty-state>
        @else
            <form method="POST" action="{{ route('withdrawals.store') }}" class="space-y-4">
                @csrf
                <x-form.group label="Metode Penarikan" name="withdrawal_method_id">
                    <x-form.select name="withdrawal_method_id" required>
                        <option value="">Pilih metode...</option>
                        @foreach ($methods as $method)
                            <option value="{{ $method->id }}" @selected(old('withdrawal_method_id') == $method->id)>
                                {{ $method->name }} ({{ $method->type === 'bank_transfer' ? 'Bank' : 'E-Wallet' }}) — Min Rp {{ number_format($method->min_withdrawal, 0, ',', '.') }}
                            </option>
                        @endforeach
                    </x-form.select>
                </x-form.group>
                <x-form.group label="Jumlah Penarikan" name="amount">
                    <x-form.input type="number" name="amount" value="{{ old('amount') }}" required min="1" max="{{ $availableBalance }}" placeholder="0" />
                </x-form.group>
                <x-form.group label="Nomor Rekening / No. HP" name="account_number">
                    <x-form.input name="account_number" value="{{ old('account_number', auth()->user()->bank_account_number) }}" required placeholder="Nomor rekening" />
                </x-form.group>
                <x-form.group label="Nama Pemilik Rekening" name="account_name">
                    <x-form.input name="account_name" value="{{ old('account_name', auth()->user()->bank_account_name) }}" required placeholder="Nama sesuai rekening" />
                </x-form.group>
                <div class="flex items-center gap-3 pt-2">
                    <x-button type="submit" icon="send">Ajukan Penarikan</x-button>
                    <x-button :href="route('withdrawals.index')" variant="ghost">Batal</x-button>
                </div>
            </form>
        @endif
    </x-card>
</div>
@endsection
