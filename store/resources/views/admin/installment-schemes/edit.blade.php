@extends('layouts.admin', ['active' => 'installments'])

@section('title', 'Edit Skema · Admin')

@section('content')
    <x-admin.page-header
        :title="'Edit Skema: ' . $scheme->name"
        subtitle="Ubah skema. Perubahan langsung tercermin di dropdown checkout.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.installment-schemes.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.installment-schemes._form', [
        'scheme' => $scheme,
        'courses' => $courses,
        'action' => route('admin.installment-schemes.update', $scheme),
        'method' => 'PUT',
    ])
@endsection
