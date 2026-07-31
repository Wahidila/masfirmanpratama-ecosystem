@extends('layouts.admin', ['active' => 'promo-banners'])

@section('title', 'Edit Banner · Admin')

@section('content')
    <x-admin.page-header
        :title="'Edit Banner: ' . $banner->title"
        subtitle="Perubahan langsung tercermin di homepage.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.promo-banners.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @include('admin.promo-banners._form', [
        'banner' => $banner,
        'action' => route('admin.promo-banners.update', $banner),
        'method' => 'PUT',
    ])
@endsection
