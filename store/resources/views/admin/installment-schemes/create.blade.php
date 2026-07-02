@extends('layouts.admin', ['active' => 'installments'])

@section('title', 'Skema Baru · Admin')

@section('content')
    <x-admin.page-header
        title="Skema Cicilan Baru"
        subtitle="Skema cicilan untuk pendaftaran kelas. Akan tampil di checkout kelas terkait.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.installment-schemes.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.installment-schemes._form', [
        'scheme' => $scheme,
        'courses' => $courses,
        'action' => route('admin.installment-schemes.store'),
        'method' => 'POST',
    ])
@endsection
