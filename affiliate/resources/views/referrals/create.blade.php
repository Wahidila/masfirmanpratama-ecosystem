@extends('layouts.dashboard')

@section('content')
<x-page-header title="Buat Link Referral" subtitle="Buat link baru untuk mempromosikan produk." />

<div class="max-w-lg">
    <x-card>
        <form method="POST" action="{{ route('referrals.store') }}" class="space-y-4">
            @csrf
            <x-form.group label="Label" name="label" hint="Untuk membantu Anda membedakan tiap link.">
                <x-form.input name="label" value="{{ old('label') }}" placeholder="Contoh: Instagram Bio, WhatsApp Group" />
            </x-form.group>
            <x-form.group label="Target URL" name="target_url" hint="Kosongkan untuk redirect ke halaman utama store.">
                <x-form.input type="url" name="target_url" value="{{ old('target_url') }}" placeholder="https://masfirmanpratama.com/produk/..." />
            </x-form.group>
            <div class="flex items-center gap-3 pt-2">
                <x-button type="submit" icon="plus">Buat Link</x-button>
                <x-button :href="route('referrals.index')" variant="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
