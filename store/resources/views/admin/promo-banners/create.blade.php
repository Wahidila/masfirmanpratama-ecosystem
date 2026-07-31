@extends('layouts.admin', ['active' => 'promo-banners'])

@section('title', 'Banner Baru · Admin')

@section('content')
    <x-admin.page-header
        title="Banner Promo Baru"
        subtitle="Upload gambar banner jadwal/promo yang akan tampil di homepage.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.promo-banners.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.promo-banners._form', [
        'banner' => $banner,
        'action' => route('admin.promo-banners.store'),
        'method' => 'POST',
    ])
@endsection
