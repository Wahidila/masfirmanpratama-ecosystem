@extends('layouts.admin', ['active' => 'installments'])

@section('title', 'Edit Skema · Admin')

@section('content')
    <x-admin.page-header
        :title="'Edit Skema: ' . $scheme->name"
        subtitle="Ubah skema. Perubahan langsung tercermin di dropdown checkout.">
        <x-slot:actions>
            <a href="{{ route('admin.installment-schemes.index') }}"
               class="inline-flex items-center rounded-xl border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50 transition">
                ← Kembali
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.installment-schemes._form', [
        'scheme' => $scheme,
        'products' => $products,
        'action' => route('admin.installment-schemes.update', $scheme),
        'method' => 'PUT',
    ])
@endsection
