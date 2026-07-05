@extends('layouts.dashboard')

@section('content')
<x-page-header title="Edit Link Referral" subtitle="Kode referral: {{ $referral->code }}" />

<div class="max-w-lg">
    <x-card>
        <form method="POST" action="{{ route('referrals.update', $referral) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-form.group label="Label" name="label">
                <x-form.input name="label" value="{{ old('label', $referral->label) }}" placeholder="Contoh: Instagram Bio" />
            </x-form.group>
            <x-form.group label="Target URL" name="target_url">
                <x-form.input type="url" name="target_url" value="{{ old('target_url', $referral->target_url) }}" placeholder="https://masfirmanpratama.com/..." />
            </x-form.group>
            <div class="flex items-center gap-3 pt-2">
                <x-button type="submit" icon="save">Simpan</x-button>
                <x-button :href="route('referrals.index')" variant="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
