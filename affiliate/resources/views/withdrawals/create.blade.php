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
        @elseif ($accounts->isEmpty())
            <x-empty-state icon="landmark" title="Belum ada rekening tujuan" message="Simpan rekening atau e-wallet Anda dulu supaya bisa menarik saldo.">
                <x-button :href="route('payout-accounts.index')" variant="outline" size="sm">Tambah rekening tujuan</x-button>
            </x-empty-state>
        @else
            <form method="POST" action="{{ route('withdrawals.store') }}" class="space-y-4">
                @csrf
                <x-form.group label="Rekening Tujuan" name="payout_account_id">
                    <x-form.select name="payout_account_id" required>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected(old('payout_account_id', $accounts->firstWhere('is_primary', true)?->id) == $account->id)>
                                {{ $account->method->name }} ({{ $account->method->typeLabel() }}) — {{ $account->account_number }} a.n. {{ $account->account_name }}
                            </option>
                        @endforeach
                    </x-form.select>
                </x-form.group>
                <p class="-mt-2 text-xs text-slate-500">
                    <a href="{{ route('payout-accounts.index') }}" class="text-primary-600 hover:text-primary-700">Kelola rekening tujuan</a>
                </p>

                <x-form.group label="Jumlah Penarikan" name="amount">
                    <x-form.input type="number" name="amount" value="{{ old('amount') }}" required min="1" max="{{ $availableBalance }}" placeholder="0" />
                </x-form.group>

                <div class="rounded-xl bg-slate-50 p-4 text-sm space-y-1">
                    <p class="font-medium text-slate-700 mb-2">Ketentuan tiap metode</p>
                    @foreach ($accounts->pluck('method')->filter()->unique('id') as $method)
                        <p class="text-slate-600">
                            {{ $method->name }} — min. Rp {{ number_format($method->min_withdrawal, 0, ',', '.') }},
                            @if ($method->fee_flat > 0)
                                biaya admin Rp {{ number_format($method->fee_flat, 0, ',', '.') }}
                            @else
                                tanpa biaya admin
                            @endif
                        </p>
                    @endforeach
                    <p class="text-xs text-slate-400 pt-2">Saldo terpotong sebesar jumlah yang Anda minta; biaya admin dipotong dari jumlah yang ditransfer.</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-button type="submit" icon="send">Ajukan Penarikan</x-button>
                    <x-button :href="route('withdrawals.index')" variant="ghost">Batal</x-button>
                </div>
            </form>
        @endif
    </x-card>
</div>
@endsection
