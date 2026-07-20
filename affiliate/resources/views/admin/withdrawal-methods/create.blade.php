@extends('admin.layouts.admin')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.withdrawal-methods.index') }}" class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 mb-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
    </a>
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Tambah Metode Penarikan</h1>
</div>

<div class="max-w-2xl">
    <x-card>
        <form method="POST" action="{{ route('admin.withdrawal-methods.store') }}" class="space-y-4">
            @csrf

            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="Nama Metode" name="name" required>
                    <x-form.input name="name" value="{{ old('name') }}" required placeholder="BCA, Dana, ..." />
                </x-form.group>
                <x-form.group label="Tipe" name="type" required>
                    <x-form.select name="type" required>
                        @foreach (\App\Models\WithdrawalMethod::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.group>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <x-form.group label="Minimum Penarikan (Rp)" name="min_withdrawal" required
                    hint="Dibandingkan dengan jumlah yang diminta affiliator, sebelum dipotong biaya.">
                    <x-form.input type="number" name="min_withdrawal" value="{{ old('min_withdrawal', 50000) }}" min="1" step="1" required />
                </x-form.group>
                <x-form.group label="Biaya Admin (Rp)" name="fee_flat" required
                    hint="Dipotong dari jumlah yang ditransfer. Isi 0 kalau gratis. Harus lebih kecil dari minimum.">
                    <x-form.input type="number" name="fee_flat" value="{{ old('fee_flat', 0) }}" min="0" step="1" required />
                </x-form.group>
            </div>

            {{-- Hidden field lebih dulu: checkbox yang tidak dicentang tidak terkirim
                 sama sekali, sehingga old('is_active') jatuh ke default dan kotaknya
                 tercentang lagi setelah validasi gagal. --}}
            <input type="hidden" name="is_active" value="0">
            <x-form.checkbox name="is_active" value="1" :checked="(bool) old('is_active', 1)" label="Aktif — bisa dipilih affiliator" />

            <div class="flex items-center gap-2 pt-2">
                <x-button type="submit" icon="save">Simpan</x-button>
                <x-button :href="route('admin.withdrawal-methods.index')" variant="ghost">Batal</x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection
