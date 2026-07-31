@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program" title="Atur ulang password" subtitle="Buat password baru untuk akun affiliate Anda.">
    @if ($errors->any())
        <x-alert tone="danger" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-alert>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <x-form.group label="Email">
            <x-form.input type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus placeholder="email@kamu.com" />
        </x-form.group>
        <x-form.group label="Password baru">
            <x-form.input type="password" name="password" required placeholder="Minimal 8 karakter" />
        </x-form.group>
        <x-form.group label="Konfirmasi password baru">
            <x-form.input type="password" name="password_confirmation" required placeholder="Ulangi password baru" />
        </x-form.group>
        <x-button type="submit" icon="key-round" class="w-full">Simpan password baru</x-button>
    </form>
</x-auth-card>
@endsection
