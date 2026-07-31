@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program" title="Masuk ke akun Anda" subtitle="Selamat datang kembali di program affiliate.">
    @if ($errors->any())
        <x-alert tone="danger" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-form.group label="Email">
            <x-form.input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="email@kamu.com" />
        </x-form.group>
        <x-form.group label="Password">
            <x-form.input type="password" name="password" required placeholder="••••••••" />
        </x-form.group>
        <div class="flex items-center justify-between">
            <x-form.checkbox name="remember" label="Ingat saya" />
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-700">Lupa password?</a>
        </div>
        <x-button type="submit" icon="log-in" class="w-full">Masuk</x-button>
    </form>

    <x-slot:below>
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-primary-600 font-semibold hover:text-primary-700">Daftar sekarang</a>
    </x-slot:below>
</x-auth-card>
@endsection
