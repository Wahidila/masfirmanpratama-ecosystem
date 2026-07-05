@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program" title="Daftar Affiliate" subtitle="Bergabung dan mulai hasilkan komisi.">
    @if ($errors->any())
        <x-alert tone="danger" class="mb-4">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </x-alert>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-form.group label="Nama Lengkap">
            <x-form.input name="name" value="{{ old('name') }}" required autofocus placeholder="Nama lengkap kamu" />
        </x-form.group>
        <x-form.group label="Email">
            <x-form.input type="email" name="email" value="{{ old('email') }}" required placeholder="email@kamu.com" />
        </x-form.group>
        <x-form.group label="No. WhatsApp" hint="Opsional">
            <x-form.input name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" />
        </x-form.group>
        <x-form.group label="Tipe Affiliator">
            <x-form.select name="affiliator_type_id" required>
                <option value="">Pilih tipe...</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(old('affiliator_type_id') == $type->id)>
                        {{ $type->name }} — Komisi {{ $type->default_commission_rate }}%
                    </option>
                @endforeach
            </x-form.select>
        </x-form.group>
        <x-form.group label="Password">
            <x-form.input type="password" name="password" required placeholder="Minimal 8 karakter" />
        </x-form.group>
        <x-form.group label="Konfirmasi Password">
            <x-form.input type="password" name="password_confirmation" required placeholder="Ulangi password" />
        </x-form.group>
        <x-button type="submit" icon="user-plus" class="w-full">Daftar Sekarang</x-button>
    </form>

    <x-slot:below>
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-primary-600 font-semibold hover:text-primary-700">Masuk</a>
    </x-slot:below>
</x-auth-card>
@endsection
