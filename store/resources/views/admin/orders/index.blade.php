@extends('layouts.admin', ['active' => 'orders'])

@section('title', 'Pesanan · Admin')

@section('content')
    <x-admin.page-header
        title="Pesanan"
        subtitle="Daftar pesanan dengan filter status & pencarian.">
        <x-slot:actions>
            <span class="text-xs text-slate-500">{{ $stats['total'] }} total</span>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>
                {{ session('status') }}
            </x-admin.alert>
        </div>
    @endif

    {{-- Stat strip per-status --}}
    @php
        $statusToneMap = [
            'pending' => 'amber',
            'partial_paid' => 'amber',
            'paid' => 'primary',
            'shipped' => 'primary',
            'completed' => 'secondary',
            'cancelled' => 'slate',
            'refunded' => 'slate',
        ];
        $statusLabel = [
            'pending' => 'Pending',
            'partial_paid' => 'Cicilan',
            'paid' => 'Lunas',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Batal',
            'refunded' => 'Refund',
        ];
    @endphp

    <section class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4 lg:grid-cols-7">
        @foreach ($statuses as $s)
            <a href="{{ route('admin.orders.index', ['status' => $s]) }}"
               class="rounded-xl border px-3 py-2.5 transition {{ $filterStatus === $s ? 'border-primary-300 bg-primary-50' : 'border-slate-100 bg-white hover:border-slate-200' }}">
                <div class="text-xs text-slate-500">{{ $statusLabel[$s] ?? $s }}</div>
                <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats[$s] ?? 0 }}</div>
            </a>
        @endforeach
    </section>

    {{-- Filter form --}}
    <x-admin.card class="mb-6" :padded="false">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="search" class="sr-only">Cari</label>
                <input
                    id="search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari order number, nama, telp, atau email..."
                    class="block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
            </div>

            <div>
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status" class="block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected($filterStatus === $s)>{{ $statusLabel[$s] ?? $s }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="date_from" class="sr-only">Dari tanggal</label>
                <input id="date_from" type="date" name="date_from" value="{{ $dateFrom }}"
                       class="block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
            </div>

            <div class="flex gap-2">
                <input id="date_to" type="date" name="date_to" value="{{ $dateTo }}"
                       class="block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40"
                       placeholder="Sampai">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 transition">
                    Filter
                </button>
            </div>

            @if ($filterStatus || $search || $dateFrom || $dateTo)
                <div class="lg:col-span-5">
                    <a href="{{ route('admin.orders.index') }}" class="text-xs text-slate-500 hover:text-slate-700">
                        Reset filter
                    </a>
                </div>
            @endif
        </form>
    </x-admin.card>

    {{-- Tabel orders --}}
    <x-admin.table
        :columns="[
            ['label' => 'Order #'],
            ['label' => 'Customer'],
            ['label' => 'Total'],
            ['label' => 'Status'],
            ['label' => 'Dibuat'],
            ['label' => '', 'align' => 'text-right'],
        ]"
        :rows="$orders"
        empty="Tidak ada pesanan yang cocok dengan filter.">
        @foreach ($orders as $order)
            <tr class="hover:bg-slate-50/60">
                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $order->order_number }}</td>
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ $order->customer_name }}</div>
                    <div class="text-xs text-slate-500">{{ $order->phone ?? $order->email }}</div>
                </td>
                <td class="px-4 py-3 font-medium">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</td>
                <td class="px-4 py-3">
                    @php $tone = $statusToneMap[$order->status] ?? 'slate'; @endphp
                    @php
                        $toneClass = match ($tone) {
                            'amber' => 'bg-accent-50 text-accent-800 ring-accent-200',
                            'primary' => 'bg-primary-50 text-primary-800 ring-primary-200',
                            'secondary' => 'bg-secondary-50 text-secondary-800 ring-secondary-200',
                            default => 'bg-slate-100 text-slate-700 ring-slate-200',
                        };
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $toneClass }}">
                        {{ $statusLabel[$order->status] ?? $order->status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs text-slate-500">
                    <div>{{ $order->created_at?->format('d M Y') }}</div>
                    <div>{{ $order->created_at?->format('H:i') }} WIB</div>
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="text-xs font-medium text-primary-600 hover:text-primary-700">
                        Detail →
                    </a>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($orders->hasPages())
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif
@endsection
