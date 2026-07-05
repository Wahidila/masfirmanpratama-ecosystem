@extends('admin.layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.events.index') }}" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke daftar event
    </a>
    <h1 class="text-2xl font-bold text-slate-900">Buat Event Baru</h1>
</div>

<div class="max-w-3xl">
    <x-card>
        <form method="POST" action="{{ route('admin.events.store') }}" class="space-y-4">
            @csrf
            @include('admin.events._form')
            <div class="flex justify-end pt-2">
                <x-button type="submit" icon="save">Simpan Event</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
