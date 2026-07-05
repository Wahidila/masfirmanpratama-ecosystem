@extends('admin.layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.materials.index') }}" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
    </a>
    <h1 class="text-2xl font-bold text-slate-900">Upload Materi Baru</h1>
</div>

<div class="max-w-lg">
    <x-card>
        <form method="POST" action="{{ route('admin.materials.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-form.group label="Judul" name="title">
                <x-form.input name="title" value="{{ old('title') }}" required placeholder="Judul materi" />
            </x-form.group>
            <x-form.group label="Deskripsi" name="description">
                <x-form.textarea name="description" rows="3" class="resize-none" placeholder="Deskripsi singkat...">{{ old('description') }}</x-form.textarea>
            </x-form.group>
            <x-form.group label="Tipe" name="type">
                <x-form.select name="type" required>
                    <option value="image">Gambar</option>
                    <option value="video">Video</option>
                    <option value="document">Dokumen</option>
                    <option value="template">Template</option>
                </x-form.select>
            </x-form.group>
            <x-form.group label="File" name="file" hint="Maksimal 50MB">
                <input type="file" name="file" required
                       class="w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-primary-50 file:text-primary-700 file:font-medium file:cursor-pointer hover:file:bg-primary-100">
            </x-form.group>
            <div class="flex items-center gap-3 pt-2">
                <x-button type="submit" icon="upload">Upload</x-button>
                <x-button :href="route('admin.materials.index')" variant="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
