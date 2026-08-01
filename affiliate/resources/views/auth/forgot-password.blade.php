@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program" title="Lupa password?" subtitle="Masukkan email Anda, kami kirim tautan untuk atur ulang password.">
    @if (session('status'))
        <x-alert tone="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert tone="danger" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-form.group label="Email">
            <x-form.input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="email@kamu.com" />
        </x-form.group>
        <x-button type="submit" icon="mail" class="w-full">Kirim tautan reset</x-button>
    </form>

    <x-slot:below>
        Ingat password Anda?
        <a href="{{ route('login') }}" class="text-primary-600 font-semibold hover:text-primary-700">Kembali ke login</a>
    </x-slot:below>
</x-auth-card>
@endsection
