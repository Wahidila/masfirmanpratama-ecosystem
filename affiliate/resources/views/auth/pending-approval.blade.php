@extends('layouts.app')

@section('body')
<x-auth-card heading="Affiliate Program">
    <div class="text-center">
        <div class="w-16 h-16 bg-accent-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i data-lucide="clock" class="w-8 h-8 text-accent-600"></i>
        </div>
        <h2 class="text-xl font-semibold text-slate-900 mb-2">Menunggu Persetujuan</h2>
        <p class="text-slate-500 text-sm mb-6">
            Akun Anda sedang dalam proses review oleh admin. Anda akan menerima notifikasi ketika akun disetujui.
        </p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-button variant="outline" type="submit" icon="log-out">Keluar</x-button>
        </form>
    </div>
</x-auth-card>
@endsection
