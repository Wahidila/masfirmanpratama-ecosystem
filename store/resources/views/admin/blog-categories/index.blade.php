@extends('layouts.admin', ['active' => 'posts'])

@section('title', 'Kategori Blog')

@section('content')
    <x-admin.page-header
        title="Kategori Blog"
        subtitle="Kelola kategori artikel. Kategori dipakai untuk filter di /blog dan pengelompokan konten.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.posts.index') }}" variant="outline" size="sm">
                ← Kembali ke artikel
            </x-admin.button>
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6">
            <x-admin.alert tone="error" title="Form belum valid">{{ $errors->first() }}</x-admin.alert>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Add form --}}
        <div class="lg:col-span-1">
            <x-admin.card title="Tambah kategori">
                <form method="POST" action="{{ route('admin.blog-categories.store') }}" class="space-y-4">
                    @csrf
                    <x-admin.form-group label="Nama" for="name" name="name" required>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="mis. Kekuatan Pikiran">
                    </x-admin.form-group>
                    <x-admin.form-group label="Slug (opsional)" for="slug" name="slug" hint="Otomatis dari nama kalau kosong.">
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" maxlength="140"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="kekuatan-pikiran">
                    </x-admin.form-group>
                    <x-admin.form-group label="Deskripsi (opsional)" for="description" name="description">
                        <textarea id="description" name="description" rows="2" maxlength="500"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('description') }}</textarea>
                    </x-admin.form-group>
                    <button type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <x-admin.icon name="plus" class="h-4 w-4" /> Tambah kategori
                    </button>
                </form>
            </x-admin.card>
        </div>

        {{-- List --}}
        <div class="lg:col-span-2">
            <x-admin.card title="Daftar kategori ({{ $categories->count() }})">
                @if ($categories->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada kategori. Tambahkan lewat form di sebelah kiri.</p>
                @else
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($categories as $category)
                            <div class="flex flex-wrap items-center gap-3 py-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $category->name }}</p>
                                    <p class="text-xs text-gray-500 font-mono dark:text-gray-400">/blog?category={{ $category->slug }} · {{ $category->posts_count }} artikel</p>
                                </div>
                                <form method="POST" action="{{ route('admin.blog-categories.update', $category) }}"
                                    class="flex items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $category->name }}" required
                                        class="h-9 w-40 rounded-lg border border-gray-300 bg-transparent px-3 text-xs text-gray-800 focus:border-brand-300 focus:ring-2 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                                    <input type="hidden" name="slug" value="{{ $category->slug }}">
                                    <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">Simpan</button>
                                </form>
                                <form method="POST" action="{{ route('admin.blog-categories.destroy', $category) }}"
                                    onsubmit="return confirm('Hapus kategori &quot;{{ $category->name }}&quot;? Artikel tidak ikut terhapus.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-error-200 px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 dark:border-error-500/30 dark:text-error-500">
                                        <x-admin.icon name="trash" class="h-3 w-3" />
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
