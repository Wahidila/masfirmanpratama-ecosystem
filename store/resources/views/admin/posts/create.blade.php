@extends('layouts.admin', ['active' => 'posts'])

@section('title', 'Tambah Artikel')

@section('content')
    <x-admin.page-header
        title="Tambah Artikel Baru"
        subtitle="Tulis artikel blog. Status default draft — publish saat siap tayang.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.posts.index') }}" variant="outline" size="sm">
                ← Kembali ke daftar
            </x-admin.button>
        </x-slot>
    </x-admin.page-header>

    @if ($errors->any())
        <div class="mb-6">
            <x-admin.alert tone="error" title="Form belum valid">
                Periksa kembali field di bawah — ada kolom yang perlu diperbaiki.
            </x-admin.alert>
        </div>
    @endif

    @include('admin.posts._form', [
        'post' => $post,
        'mode' => 'create',
        'categories' => $categories,
        'products' => $products,
        'selectedCategories' => $selectedCategories,
        'selectedProducts' => $selectedProducts,
    ])
@endsection
