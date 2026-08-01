@extends('layouts.admin', ['active' => 'video-testimonials'])

@section('title', 'Edit Video Testimoni')

@section('content')
    <x-admin.page-header
        title="Edit Video Testimoni"
        :subtitle="$videoTestimonial->title">
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

    @include('admin.video-testimonials._form', ['videoTestimonial' => $videoTestimonial, 'mode' => 'edit'])
@endsection
