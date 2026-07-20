@extends('layouts.admin', ['active' => 'participants'])

@section('title', 'Tambah Peserta Kursus')

@section('content')
    <x-admin.page-header
        title="Tambah Peserta"
        subtitle="Untuk peserta yang mendaftar di luar sistem (offline/manual). Peserta dari pesanan kelas masuk otomatis.">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.participants.index') }}" variant="outline" size="sm">
                ← Kembali
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.card>
        <form method="POST" action="{{ route('admin.participants.store') }}">
            @csrf
            @include('admin.participants._form')

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-800">
                <x-admin.button href="{{ route('admin.participants.index') }}" variant="outline">Batal</x-admin.button>
                <x-admin.button type="submit">Simpan Peserta</x-admin.button>
            </div>
        </form>
    </x-admin.card>
@endsection
