@extends('layouts.admin', ['active' => 'settings'])

@section('title', 'Settings · Admin')

@section('content')
    <x-admin.page-header
        title="Settings"
        subtitle="Kelola info toko, rekening bank, pengiriman, WhatsApp, & konten footer yang dipakai di halaman publik." />

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="mb-6 flex items-center gap-2 text-xs font-medium border-b border-gray-200 dark:border-gray-800">
        <a href="{{ route('admin.settings.index', ['tab' => 'store-info']) }}"
            class="px-4 py-2.5 -mb-px border-b-2 transition {{ $tab === 'store-info' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90' }}">
            Store Info
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'bank-accounts']) }}"
            class="px-4 py-2.5 -mb-px border-b-2 transition {{ $tab === 'bank-accounts' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90' }}">
            Bank Accounts
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'shipping']) }}"
            class="px-4 py-2.5 -mb-px border-b-2 transition {{ $tab === 'shipping' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90' }}">
            Shipping
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'whatsapp']) }}"
            class="px-4 py-2.5 -mb-px border-b-2 transition {{ $tab === 'whatsapp' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90' }}">
            WhatsApp
        </a>
        <a href="{{ route('admin.settings.index', ['tab' => 'footer']) }}"
            class="px-4 py-2.5 -mb-px border-b-2 transition {{ $tab === 'footer' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white/90' }}">
            Footer
        </a>
    </div>

    @if ($tab === 'store-info')
        @include('admin.settings._store_info', ['storeInfo' => $storeInfo])
    @elseif ($tab === 'shipping')
        @include('admin.settings._shipping', [
            'shippingData' => $shippingData,
            'availableCouriers' => $availableCouriers,
        ])
    @elseif ($tab === 'whatsapp')
        @include('admin.settings._whatsapp', ['whatsappData' => $whatsappData])
    @elseif ($tab === 'footer')
        @include('admin.settings._footer', ['footerData' => $footerData])
    @else
        @include('admin.settings._bank_accounts', ['bankAccounts' => $bankAccounts])
    @endif
@endsection
