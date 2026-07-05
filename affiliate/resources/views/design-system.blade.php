@extends('layouts.app')

@section('body')
@php
    $colors = [
        'primary' => ['600' => '#4f46e5', '500' => '#6366f1', '100' => '#e0e7ff'],
        'secondary' => ['600' => '#0d9488', '500' => '#14b8a6', '100' => '#ccfbf1'],
        'accent' => ['500' => '#f59e0b', '600' => '#d97706', '100' => '#fef3c7'],
        'slate' => ['900' => '#0f172a', '500' => '#64748b', '100' => '#f1f5f9'],
    ];
    $statuses = ['active','pending','suspended','cooling','available','withdrawn','approved','rejected','ended','claimed','unclaimed'];
@endphp

<div class="min-h-screen">
    <header class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <p class="text-xs font-bold tracking-[0.2em] text-primary-300 uppercase">Design System</p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold">Affiliate <span class="text-primary-400">Style Guide</span></h1>
            <p class="mt-3 text-slate-400 max-w-2xl">Satu sistem visual untuk seluruh app affiliate — token, komponen, dan pola. Diturunkan dari brand MasFirmanPratama.com (Inter · indigo/teal/amber).</p>
        </div>
    </header>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-14">

        {{-- Warna --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Warna</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach ($colors as $name => $shades)
                    <div class="rounded-2xl border border-slate-200/70 bg-white shadow-sm overflow-hidden">
                        <div class="h-20" style="background: {{ array_values($shades)[0] }}"></div>
                        <div class="p-3">
                            <p class="text-sm font-semibold text-slate-800 capitalize">{{ $name }}</p>
                            <div class="mt-2 flex gap-1">
                                @foreach ($shades as $k => $hex)
                                    <span class="flex-1 h-6 rounded" style="background: {{ $hex }}" title="{{ $name }}-{{ $k }} {{ $hex }}"></span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Tipografi --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Tipografi — Inter</h2>
            <x-card>
                <div class="space-y-3">
                    <p class="text-4xl font-extrabold text-slate-900">Hasilkan komisi dari setiap referral</p>
                    <p class="text-2xl font-bold text-slate-900">Heading halaman (2xl / bold)</p>
                    <p class="text-base font-semibold text-slate-800">Judul kartu (base / semibold)</p>
                    <p class="text-sm text-slate-600">Body teks reguler — 14px slate-600 untuk isi utama dan deskripsi.</p>
                    <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Label kecil / eyebrow</p>
                    <p class="text-2xl font-bold text-gradient inline-block">Text gradient brand</p>
                </div>
            </x-card>
        </section>

        {{-- Buttons --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Tombol</h2>
            <x-card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-button variant="primary" icon="plus">Primary</x-button>
                    <x-button variant="secondary" icon="check">Secondary</x-button>
                    <x-button variant="accent">Accent</x-button>
                    <x-button variant="outline" icon="download">Outline</x-button>
                    <x-button variant="ghost">Ghost</x-button>
                    <x-button variant="danger" icon="trash-2">Danger</x-button>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <x-button size="sm">Small</x-button>
                    <x-button size="md">Medium</x-button>
                    <x-button size="lg" icon="arrow-right" iconPosition="right">Large</x-button>
                </div>
            </x-card>
        </section>

        {{-- Badges & status --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Badge &amp; Status</h2>
            <x-card>
                <div class="flex flex-wrap gap-2 mb-4">
                    <x-badge variant="primary">Primary</x-badge>
                    <x-badge variant="success" icon="check">Success</x-badge>
                    <x-badge variant="warning" icon="alert-triangle">Warning</x-badge>
                    <x-badge variant="danger">Danger</x-badge>
                    <x-badge variant="info">Info</x-badge>
                    <x-badge variant="neutral">Neutral</x-badge>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($statuses as $s)
                        <x-status-badge :status="$s" />
                    @endforeach
                </div>
            </x-card>
        </section>

        {{-- Stat cards --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Stat Card</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Saldo Tersedia" value="Rp 2.450.000" icon="wallet" tone="primary" hint="Siap ditarik" />
                <x-stat-card label="Total Komisi" value="Rp 8.120.000" icon="trending-up" tone="secondary" />
                <x-stat-card label="Cooling" value="Rp 640.000" icon="hourglass" tone="accent" hint="7 hari" />
                <x-stat-card label="Referral" value="18" icon="link" tone="sky" />
            </div>
        </section>

        {{-- Alerts --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Alert</h2>
            <div class="space-y-3">
                <x-alert tone="info" title="Info">Ini pesan informasi netral.</x-alert>
                <x-alert tone="success" title="Berhasil" dismissible>Data berhasil disimpan.</x-alert>
                <x-alert tone="warning" title="Perhatian">Ada yang perlu dicek sebelum lanjut.</x-alert>
                <x-alert tone="danger" title="Gagal">Terjadi kesalahan pada permintaan.</x-alert>
            </div>
        </section>

        {{-- Form --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Form</h2>
            <x-card>
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-form.group label="Nama Lengkap" name="ds_name" required>
                        <x-form.input name="ds_name" placeholder="Nama kamu" />
                    </x-form.group>
                    <x-form.group label="Tipe" name="ds_type">
                        <x-form.select name="ds_type">
                            <option>Alumni</option>
                            <option>Non-Alumni</option>
                        </x-form.select>
                    </x-form.group>
                    <x-form.group label="Bio" name="ds_bio" hint="Maksimal 200 karakter" class="sm:col-span-2">
                        <x-form.textarea name="ds_bio" placeholder="Ceritakan tentang dirimu..." />
                    </x-form.group>
                    <div class="sm:col-span-2">
                        <x-form.checkbox name="ds_remember" label="Ingat saya" />
                    </div>
                </div>
            </x-card>
        </section>

        {{-- Table --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Tabel</h2>
            <x-table :heads="['Kode', 'Klik', 'Order', 'Status']">
                @foreach ([['REF-A1', 128, 12, 'active'], ['REF-B2', 64, 5, 'active'], ['REF-C3', 9, 0, 'inactive']] as $row)
                    <tr class="hover:bg-slate-50/70 transition-colors">
                        <td class="px-5 py-3.5 font-mono text-sm text-slate-800">{{ $row[0] }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $row[1] }}</td>
                        <td class="px-5 py-3.5 text-slate-600">{{ $row[2] }}</td>
                        <td class="px-5 py-3.5"><x-status-badge :status="$row[3]" /></td>
                    </tr>
                @endforeach
            </x-table>
        </section>

        {{-- Empty + Modal --}}
        <section class="grid md:grid-cols-2 gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Empty State</h2>
                <x-card :padded="false">
                    <x-empty-state icon="inbox" title="Belum ada data" message="Data akan muncul di sini setelah ada aktivitas.">
                        <x-button size="sm" icon="plus">Tambah</x-button>
                    </x-empty-state>
                </x-card>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Modal</h2>
                <x-card>
                    <x-modal title="Hapus item ini?" icon="trash-2" tone="danger">
                        <x-slot:trigger>
                            <x-button variant="danger" icon="trash-2">Buka modal</x-button>
                        </x-slot:trigger>
                        <p class="text-sm text-slate-600">Tindakan ini tidak bisa dibatalkan.</p>
                        <div class="mt-6 flex justify-end gap-2">
                            <x-button variant="ghost" x-on:click="open = false">Batal</x-button>
                            <x-button variant="danger">Ya, hapus</x-button>
                        </div>
                    </x-modal>
                </x-card>
            </div>
        </section>

    </div>
</div>
@endsection
