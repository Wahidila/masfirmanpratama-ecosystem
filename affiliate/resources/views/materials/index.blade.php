@extends('layouts.dashboard')

@section('content')
@php
    $typeMeta = [
        'image' => ['image', 'sky'],
        'video' => ['video', 'primary'],
        'document' => ['file-text', 'accent'],
        'template' => ['layout-template', 'secondary'],
    ];
@endphp

<x-page-header title="Materi Marketing" subtitle="Download materi promosi untuk membantu penjualan Anda." />

{{-- Filter --}}
<form method="GET" class="mb-4 flex items-center gap-2">
    <x-form.select name="type" aria-label="Filter tipe materi" onchange="this.form.submit()" class="max-w-xs">
        <option value="all" @selected(request('type') === 'all')>Semua Tipe</option>
        <option value="image" @selected(request('type') === 'image')>Gambar</option>
        <option value="video" @selected(request('type') === 'video')>Video</option>
        <option value="document" @selected(request('type') === 'document')>Dokumen</option>
        <option value="template" @selected(request('type') === 'template')>Template</option>
    </x-form.select>
</form>

@if ($materials->isEmpty())
    <x-card :padded="false">
        <x-empty-state icon="folder-open" title="Belum ada materi" message="Materi marketing akan tampil di sini setelah admin menambahkannya." />
    </x-card>
@else
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($materials as $material)
            @php [$icon, $tone] = $typeMeta[$material->type] ?? ['file', 'slate']; @endphp
            <x-card class="{{ ! $material->accessible ? 'opacity-70' : '' }}">
                <div class="flex items-start gap-3 mb-3">
                    <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 bg-{{ $tone }}-50 text-{{ $tone }}-600">
                        <i data-lucide="{{ $icon }}" class="w-5 h-5"></i>
                    </span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-slate-800 truncate">{{ $material->title }}</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ strtoupper($material->type) }} · {{ number_format($material->file_size / 1024 / 1024, 1) }} MB</p>
                    </div>
                </div>
                @if ($material->description)
                    <p class="text-xs text-slate-500 mb-3 line-clamp-2">{{ $material->description }}</p>
                @endif
                <div class="flex items-center justify-between pt-1">
                    <span class="text-xs text-slate-400">{{ $material->download_count }} download</span>
                    @if ($material->accessible)
                        <x-button :href="route('materials.download', $material)" size="sm" icon="download">Download</x-button>
                    @else
                        <x-badge variant="neutral" icon="lock">Terkunci</x-badge>
                    @endif
                </div>
            </x-card>
        @endforeach
    </div>

    @if ($materials->hasPages())
        <div class="mt-6">{{ $materials->withQueryString()->links() }}</div>
    @endif
@endif
@endsection
