@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program">
    <div class="text-center">
        <div class="w-16 h-16 bg-accent-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="mail" class="w-8 h-8 text-accent-600"></i>
        </div>
        <h2 class="text-xl font-semibold text-slate-900 mb-2">Verifikasi Email</h2>
        <p class="text-slate-500 text-sm mb-6">
            Kami telah mengirim link verifikasi ke email Anda. Klik link tersebut untuk mengaktifkan akun.
        </p>

        @if (session('success'))
            <x-alert tone="success" class="mb-4 text-left">{{ session('success') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button type="submit" icon="send" class="w-full">Kirim Ulang Email Verifikasi</x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-700">Keluar</button>
        </form>
    </div>
</x-auth-card>
@endsection
