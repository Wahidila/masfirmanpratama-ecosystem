@extends('layouts.app')

@section('body')
<x-auth-card heading="Admin Panel" title="Masuk Admin" subtitle="Kelola program affiliate MasFirmanPratama.">
    @if ($errors->any())
        <x-alert tone="danger" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-alert>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
        @csrf
        <x-form.group label="Email">
            <x-form.input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@masfirmanpratama.com" />
        </x-form.group>
        <x-form.group label="Password">
            <x-form.input type="password" name="password" required placeholder="••••••••" />
        </x-form.group>
        <x-button type="submit" icon="log-in" class="w-full">Masuk</x-button>
    </form>

    <x-slot:below>
        <a href="{{ route('login') }}" class="text-slate-500 hover:text-primary-600 transition">← Kembali ke login affiliator</a>
    </x-slot:below>
</x-auth-card>
@endsection
