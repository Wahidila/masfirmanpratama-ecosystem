@extends('layouts.admin', ['active' => 'products'])

@section('title', 'Edit Produk · ' . $product->title)

@section('content')
    <x-admin.page-header
        title="Edit Produk"
        :subtitle="'Edit data produk: ' . $product->title">
        <x-slot name="actions">
            <a href="{{ route('admin.products.index') }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-100 transition">
                ← Kembali ke daftar
            </a>
            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                onsubmit="return confirm('Hapus produk &quot;{{ $product->title }}&quot;? Bisa di-restore dari arsip.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-full border border-red-200 bg-white px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50 transition">
                    <x-admin.icon name="trash" class="h-3.5 w-3.5" />
                    Hapus
                </button>
            </form>
        </x-slot>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="mb-6">
            <x-admin.alert tone="error" title="Form belum valid">
                Periksa kembali field di bawah — ada kolom yang perlu diperbaiki.
            </x-admin.alert>
        </div>
    @endif

    @include('admin.products._form', ['product' => $product, 'mode' => 'edit'])
@endsection
