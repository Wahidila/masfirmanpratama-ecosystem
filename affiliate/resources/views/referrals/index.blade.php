@extends('layouts.dashboard')

@section('content')
<x-page-header title="Link Referral" subtitle="Kelola link referral Anda untuk promosi produk.">
    <x-slot:actions>
        <x-button :href="route('referrals.create')" icon="plus">Buat Link Baru</x-button>
    </x-slot:actions>
</x-page-header>

@if ($referrals->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="link" title="Belum ada link referral" message="Buat link referral pertama Anda untuk mulai promosi.">
            <x-button :href="route('referrals.create')" icon="plus" size="sm">Buat Link Baru</x-button>
        </x-empty-state>
    </x-card>
@else
    <x-table :heads="['Kode', 'Label', 'Klik', 'Order', 'Status', 'Aksi']">
        @foreach ($referrals as $referral)
            <tr class="hover:bg-slate-50/70 transition-colors">
                <td class="px-5 py-3.5">
                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                        <code class="text-xs bg-slate-100 px-2 py-1 rounded-md font-mono text-slate-700">{{ $referral->code }}</code>
                        <button type="button"
                                @click="navigator.clipboard.writeText('{{ url('/ref/'.$referral->code) }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="text-slate-400 hover:text-primary-600" aria-label="Salin link">
                            <i x-show="!copied" data-lucide="copy" class="w-4 h-4"></i>
                            <i x-show="copied" x-cloak data-lucide="check" class="w-4 h-4 text-secondary-500"></i>
                        </button>
                    </div>
                </td>
                <td class="px-5 py-3.5 text-slate-600">{{ $referral->label ?: '—' }}</td>
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ number_format($referral->clicks_count) }}</td>
                <td class="px-5 py-3.5 font-medium text-slate-700">{{ number_format($referral->orders_count) }}</td>
                <td class="px-5 py-3.5"><x-status-badge :status="$referral->is_active ? 'active' : 'inactive'" /></td>
                <td class="px-5 py-3.5">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('referrals.edit', $referral) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-primary-600 hover:bg-primary-50 transition" aria-label="Edit">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </a>
                        <form method="POST" action="{{ route('referrals.toggle', $referral) }}" class="inline">
                            @csrf
                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-accent-600 hover:bg-accent-50 transition" aria-label="{{ $referral->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i data-lucide="{{ $referral->is_active ? 'pause' : 'play' }}" class="w-4 h-4"></i>
                            </button>
                        </form>
                        <x-modal title="Hapus link referral?" icon="trash-2" tone="danger">
                            <x-slot:trigger>
                                <button type="button" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" aria-label="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </x-slot:trigger>
                            <p class="text-sm text-slate-600">Link <code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded font-mono">{{ $referral->code }}</code> akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.</p>
                            <form method="POST" action="{{ route('referrals.destroy', $referral) }}" class="mt-6 flex justify-end gap-2">
                                @csrf @method('DELETE')
                                <x-button type="button" variant="ghost" x-on:click="open = false">Batal</x-button>
                                <x-button type="submit" variant="danger" icon="trash-2">Ya, hapus</x-button>
                            </form>
                        </x-modal>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>

    @if ($referrals->hasPages())
        <div class="mt-4">{{ $referrals->links() }}</div>
    @endif
@endif
@endsection
