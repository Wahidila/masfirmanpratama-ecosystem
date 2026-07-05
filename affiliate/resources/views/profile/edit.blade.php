@extends('layouts.dashboard')

@section('content')
<x-page-header title="Profil Saya" subtitle="Kelola informasi akun dan data bank Anda." />

<div class="max-w-2xl space-y-6">
    {{-- Personal info --}}
    <x-card title="Informasi Personal">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf @method('PUT')
            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="Nama Lengkap" name="name">
                    <x-form.input name="name" value="{{ old('name', $affiliator->name) }}" required />
                </x-form.group>
                <x-form.group label="Email">
                    <input type="email" value="{{ $affiliator->email }}" disabled
                           class="h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm text-slate-500 cursor-not-allowed">
                </x-form.group>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="No. WhatsApp" name="phone">
                    <x-form.input name="phone" value="{{ old('phone', $affiliator->phone) }}" placeholder="08xxxxxxxxxx" />
                </x-form.group>
                <x-form.group label="Tipe Affiliator">
                    <input type="text" value="{{ $affiliator->type->name }}" disabled
                           class="h-11 w-full rounded-xl border border-slate-200 bg-slate-100 px-4 text-sm text-slate-500 cursor-not-allowed">
                </x-form.group>
            </div>
            <x-form.group label="Bio" name="bio">
                <x-form.textarea name="bio" rows="3" class="resize-none" placeholder="Ceritakan sedikit tentang Anda...">{{ old('bio', $affiliator->bio) }}</x-form.textarea>
            </x-form.group>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="Password Baru" name="password" hint="Opsional">
                    <x-form.input type="password" name="password" placeholder="••••••••" />
                </x-form.group>
                <x-form.group label="Konfirmasi Password" name="password_confirmation">
                    <x-form.input type="password" name="password_confirmation" placeholder="••••••••" />
                </x-form.group>
            </div>
            <x-button type="submit" icon="save">Simpan Profil</x-button>
        </form>
    </x-card>

    {{-- Bank info --}}
    <x-card title="Informasi Bank / E-Wallet">
        <form method="POST" action="{{ route('profile.bank') }}" class="space-y-4">
            @csrf @method('PUT')
            <x-form.group label="Nama Bank / E-Wallet" name="bank_name">
                <x-form.input name="bank_name" value="{{ old('bank_name', $affiliator->bank_name) }}" placeholder="BCA, Mandiri, Dana, dll" />
            </x-form.group>
            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="Nomor Rekening" name="bank_account_number">
                    <x-form.input name="bank_account_number" value="{{ old('bank_account_number', $affiliator->bank_account_number) }}" />
                </x-form.group>
                <x-form.group label="Nama Pemilik" name="bank_account_name">
                    <x-form.input name="bank_account_name" value="{{ old('bank_account_name', $affiliator->bank_account_name) }}" />
                </x-form.group>
            </div>
            <x-button type="submit" icon="save">Simpan Data Bank</x-button>
        </form>
    </x-card>
</div>
@endsection
