@extends('layouts.admin', ['active' => 'installments'])

@section('title', 'Skema Cicilan · Admin')

@section('content')
    <x-admin.page-header
        title="Skema Cicilan"
        subtitle="Skema pembayaran yang bisa dipilih customer di checkout. Skema global berlaku untuk semua produk; skema spesifik hanya muncul untuk produk yang ditandai.">
        <x-slot:actions>
            <a href="{{ route('admin.installment-schemes.create') }}"
               class="inline-flex items-center gap-1.5 rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 transition">
                + Skema Baru
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Stat strip --}}
    <section class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
        <a href="{{ route('admin.installment-schemes.index') }}"
           class="rounded-xl border px-3 py-2.5 transition {{ ! $filterScope ? 'border-primary-300 bg-primary-50' : 'border-slate-100 bg-white hover:border-slate-200' }}">
            <div class="text-xs text-slate-500">Total</div>
            <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['total'] }}</div>
        </a>
        <div class="rounded-xl border border-slate-100 bg-white px-3 py-2.5">
            <div class="text-xs text-slate-500">Aktif</div>
            <div class="mt-1 text-lg font-semibold text-secondary-700">{{ $stats['active'] }}</div>
        </div>
        <a href="{{ route('admin.installment-schemes.index', ['scope' => 'global']) }}"
           class="rounded-xl border px-3 py-2.5 transition {{ $filterScope === 'global' ? 'border-primary-300 bg-primary-50' : 'border-slate-100 bg-white hover:border-slate-200' }}">
            <div class="text-xs text-slate-500">Global</div>
            <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['global'] }}</div>
        </a>
        <a href="{{ route('admin.installment-schemes.index', ['scope' => 'product']) }}"
           class="rounded-xl border px-3 py-2.5 transition {{ $filterScope === 'product' ? 'border-primary-300 bg-primary-50' : 'border-slate-100 bg-white hover:border-slate-200' }}">
            <div class="text-xs text-slate-500">Per Produk</div>
            <div class="mt-1 text-lg font-semibold text-slate-900">{{ $stats['product'] }}</div>
        </a>
    </section>

    {{-- Search --}}
    <x-admin.card class="mb-6" :padded="false">
        <form method="GET" action="{{ route('admin.installment-schemes.index') }}" class="flex gap-2 p-4">
            @if ($filterScope)
                <input type="hidden" name="scope" value="{{ $filterScope }}">
            @endif
            <input type="search" name="q" value="{{ $search }}"
                   placeholder="Cari nama skema atau produk..."
                   class="block w-full rounded-xl border-slate-200 text-sm focus:border-primary-500 focus:ring-primary-500/40">
            <button type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500 transition">
                Cari
            </button>
            @if ($search || $filterScope)
                <a href="{{ route('admin.installment-schemes.index') }}"
                   class="inline-flex items-center text-xs text-slate-500 hover:text-slate-700">
                    Reset
                </a>
            @endif
        </form>
    </x-admin.card>

    {{-- Tabel --}}
    <x-admin.table
        :columns="[
            ['label' => 'Nama'],
            ['label' => 'Scope'],
            ['label' => 'DP %'],
            ['label' => 'Cicilan'],
            ['label' => 'Interval'],
            ['label' => 'Status'],
            ['label' => '', 'align' => 'text-right'],
        ]"
        :rows="$schemes"
        empty="Belum ada skema. Klik 'Skema Baru' untuk mulai.">
        @foreach ($schemes as $scheme)
            <tr class="hover:bg-slate-50/60">
                <td class="px-4 py-3">
                    <div class="font-medium text-slate-900">{{ $scheme->name }}</div>
                    <div class="text-xs text-slate-500 font-mono">#{{ $scheme->id }}</div>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if ($scheme->product_id && $scheme->product)
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-primary-50 text-primary-800 ring-primary-200">
                            {{ $scheme->product->title }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset bg-secondary-50 text-secondary-800 ring-secondary-200">
                            Global (semua produk)
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm">{{ rtrim(rtrim((string) $scheme->dp_pct, '0'), '.') }}%</td>
                <td class="px-4 py-3 text-sm">
                    @if ($scheme->n_installments <= 1)
                        <span class="text-slate-700">Lunas</span>
                    @else
                        <span class="text-slate-700">{{ $scheme->n_installments }}x</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-sm text-slate-600">{{ $scheme->interval_days }} hari</td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.installment-schemes.toggle', $scheme) }}" class="inline">
                        @csrf
                        @if ($scheme->active)
                            <button type="submit"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-secondary-50 text-secondary-800 ring-secondary-200 hover:bg-secondary-100 transition">
                                ✓ Aktif
                            </button>
                        @else
                            <button type="submit"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-slate-100 text-slate-600 ring-slate-200 hover:bg-slate-200 transition">
                                Nonaktif
                            </button>
                        @endif
                    </form>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2 text-xs">
                        <a href="{{ route('admin.installment-schemes.edit', $scheme) }}"
                           class="font-medium text-primary-600 hover:text-primary-700">Edit</a>
                        <form method="POST" action="{{ route('admin.installment-schemes.destroy', $scheme) }}"
                              onsubmit="return confirm('Hapus skema {{ addslashes($scheme->name) }}?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($schemes->hasPages())
        <div class="mt-4">
            {{ $schemes->links() }}
        </div>
    @endif
@endsection
