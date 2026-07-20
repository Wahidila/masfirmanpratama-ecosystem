@extends('admin.layouts.admin')

@section('content')
<x-page-header title="Kelola Penarikan" subtitle="Setujui atau tolak permintaan penarikan komisi." />

<form method="GET" class="mb-4 flex items-center gap-2">
    <x-form.select name="status" aria-label="Filter status penarikan" onchange="this.form.submit()" class="max-w-xs">
        <option value="all" @selected(request('status') === 'all')>Semua Status</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="processing" @selected(request('status') === 'processing')>Processing</option>
        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
    </x-form.select>
</form>

@if ($withdrawals->isEmpty())
    <x-card :padded="false"><x-empty-state icon="wallet" title="Belum ada penarikan" /></x-card>
@else
    <x-table :heads="['Affiliator', 'Metode', 'Rekening', 'Ditransfer', 'Status', 'Aksi']">
        @foreach ($withdrawals as $wd)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ $wd->affiliator->name }}</td>
                <td class="px-5 py-3.5 text-slate-600">{{ $wd->methodName() }}</td>
                <td class="px-5 py-3.5 text-slate-600 text-xs">{{ $wd->account_name }}<br>{{ $wd->account_number }}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 whitespace-nowrap">
                    Rp {{ number_format($wd->net_amount, 0, ',', '.') }}
                    @if ($wd->fee > 0)
                        <span class="block text-xs font-normal text-slate-400">dari Rp {{ number_format($wd->amount, 0, ',', '.') }} · biaya Rp {{ number_format($wd->fee, 0, ',', '.') }}</span>
                    @endif
                </td>
                <td class="px-5 py-3.5"><x-status-badge :status="$wd->status" /></td>
                <td class="px-5 py-3.5">
                    @if ($wd->status === 'pending')
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.withdrawals.approve', $wd) }}" class="inline">@csrf
                                <x-button type="submit" variant="secondary" size="sm">Approve</x-button>
                            </form>
                            <x-modal title="Tolak penarikan?" icon="x-circle" tone="danger">
                                <x-slot:trigger>
                                    <x-button variant="outline" size="sm">Reject</x-button>
                                </x-slot:trigger>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $wd) }}" class="space-y-4">
                                    @csrf
                                    <x-form.group label="Alasan penolakan" name="admin_note">
                                        <x-form.textarea name="admin_note" rows="3" placeholder="Tulis alasan penolakan..." />
                                    </x-form.group>
                                    <div class="flex justify-end gap-2">
                                        <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
                                        <x-button type="submit" variant="danger">Tolak Penarikan</x-button>
                                    </div>
                                </form>
                            </x-modal>
                        </div>
                    @else
                        <p class="text-right text-xs text-slate-400">{{ $wd->processed_at ? $wd->processed_at->format('d/m/Y') : '—' }}</p>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>

    @if ($withdrawals->hasPages())
        <div class="mt-4">{{ $withdrawals->withQueryString()->links() }}</div>
    @endif
@endif
@endsection
