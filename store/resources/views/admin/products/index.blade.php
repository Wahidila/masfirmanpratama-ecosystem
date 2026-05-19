@extends('layouts.admin', ['active' => 'products'])

@section('title', 'Produk')

@php
    $statusBadge = [
        'draft' => 'bg-slate-100 text-slate-700',
        'active' => 'bg-secondary-50 text-secondary-700',
        'archived' => 'bg-amber-50 text-amber-700',
    ];

    $typeLabel = [
        'book' => 'Buku',
        'course' => 'Kelas',
    ];
@endphp

@section('content')
    <x-admin.page-header
        title="Produk"
        subtitle="Kelola katalog buku & kelas. Status draft = belum tayang, active = live di store, archived = disembunyikan.">
        <x-slot name="actions">
            <a href="{{ route('admin.products.create') }}"
                class="inline-flex items-center gap-1.5 rounded-full bg-primary-600 px-4 py-2 text-xs font-semibold text-white shadow-lg shadow-primary-500/30 hover:bg-primary-700 transition">
                <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                Tambah Produk
            </a>
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Stats --}}
    <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-admin.stat-card title="Total Produk" :value="$stats['total']" tone="slate" />
        <x-admin.stat-card title="Active" :value="$stats['active']" tone="secondary" />
        <x-admin.stat-card title="Draft" :value="$stats['draft']" tone="primary" />
        <x-admin.stat-card title="Archived" :value="$stats['archived']" tone="amber" />
    </section>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.products.index') }}"
        class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
        <div class="flex-1">
            <label for="q" class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Judul atau slug…"
                    class="block w-full rounded-xl border-slate-200 bg-white pl-9 pr-3 py-2 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
            </div>
        </div>

        <div class="sm:w-40">
            <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select id="status" name="status"
                class="block w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">Semua</option>
                @foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="sm:w-40">
            <label for="type" class="block text-xs font-medium text-slate-600 mb-1">Tipe</label>
            <select id="type" name="type"
                class="block w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-400 focus:ring-2 focus:ring-primary-100">
                <option value="">Semua</option>
                @foreach (['book' => 'Buku', 'course' => 'Kelas'] as $value => $label)
                    <option value="{{ $value }}" @selected($filterType === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit"
            class="inline-flex items-center justify-center gap-1.5 rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800 transition">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </button>

        @if ($search || $filterStatus || $filterType)
            <a href="{{ route('admin.products.index') }}"
                class="inline-flex items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 transition">
                Reset
            </a>
        @endif
    </form>

    {{-- Table --}}
    <x-admin.table
        :columns="[
            ['label' => 'Produk'],
            ['label' => 'Tipe'],
            ['label' => 'Harga'],
            ['label' => 'Stok'],
            ['label' => 'Status'],
            ['label' => 'Aksi', 'align' => 'text-right'],
        ]"
        :rows="$products"
        empty="Belum ada produk yang cocok dengan filter ini.">
        @foreach ($products as $product)
            <tr class="hover:bg-slate-50/50 transition">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-100 bg-slate-50">
                            @if ($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <x-admin.icon name="image" class="h-5 w-5" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-slate-900 truncate">{{ $product->title }}</p>
                            <p class="text-xs text-slate-500 font-mono truncate">/{{ $product->slug }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                        {{ $typeLabel[$product->type] ?? $product->type }}
                    </span>
                </td>
                <td class="px-4 py-3 font-medium text-slate-900">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                <td class="px-4 py-3 text-slate-700">{{ $product->stock }}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusBadge[$product->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($product->status) }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-1.5">
                        <a href="{{ route('admin.products.edit', $product) }}"
                            class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100 transition">
                            <x-admin.icon name="edit" class="h-3 w-3" />
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                            onsubmit="return confirm('Hapus produk &quot;{{ $product->title }}&quot;? Bisa di-restore dari arsip.');"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-white px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition">
                                <x-admin.icon name="trash" class="h-3 w-3" />
                                Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
@endsection
