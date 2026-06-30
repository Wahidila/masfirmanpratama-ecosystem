@extends('layouts.admin', ['active' => 'video-testimonials'])

@section('title', 'Tambah Video Testimoni')

@section('content')
    <x-admin.page-header
        title="Tambah Video Testimoni"
        subtitle="Tambahkan video card untuk section Testimoni Peserta AMC di homepage.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.video-testimonials.index') }}" variant="outline" size="sm">
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

    @include('admin.video-testimonials._form', ['videoTestimonial' => $videoTestimonial, 'mode' => 'create'])
@endsection
