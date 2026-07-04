@extends('layouts.admin', ['active' => 'installments'])

@section('title', 'Skema Cicilan · Admin')

@section('content')
    <x-admin.page-header
        title="Skema Cicilan Kelas"
        subtitle="Skema cicilan untuk pendaftaran kelas/kursus. Skema global berlaku untuk semua kelas; skema spesifik hanya untuk kelas yang dipilih. Produk/buku selalu bayar penuh (tanpa cicilan).">
        <x-slot:actions>
            <x-admin.button href="{{ route('admin.installment-schemes.create') }}" size="sm">
                + Skema Baru
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Banner filter per-kelas --}}
    @if ($filterCourseModel)
        <div class="mb-6 flex items-center justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 dark:border-brand-500/30 dark:bg-brand-500/10">
            <p class="text-sm text-brand-700 dark:text-brand-300">
                Menampilkan skema untuk kelas: <strong>{{ $filterCourseModel->title }}</strong>
                <span class="text-brand-500/70">(termasuk skema global yang juga berlaku)</span>
            </p>
            <a href="{{ route('admin.installment-schemes.index') }}"
               class="shrink-0 text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">Lihat semua</a>
        </div>
    @endif

    {{-- Stat strip --}}
    <section class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
        <a href="{{ route('admin.installment-schemes.index') }}"
           class="rounded-xl border px-3 py-2.5 transition {{ ! $filterScope && ! $filterCourseModel ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/15' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700' }}">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
            <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['total'] }}</div>
        </a>
        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xs text-gray-500 dark:text-gray-400">Aktif</div>
            <div class="mt-1 text-lg font-semibold text-success-600 dark:text-success-500">{{ $stats['active'] }}</div>
        </div>
        <a href="{{ route('admin.installment-schemes.index', ['scope' => 'global']) }}"
           class="rounded-xl border px-3 py-2.5 transition {{ $filterScope === 'global' ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/15' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700' }}">
            <div class="text-xs text-gray-500 dark:text-gray-400">Semua Kelas</div>
            <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['global'] }}</div>
        </a>
        <a href="{{ route('admin.installment-schemes.index', ['scope' => 'course']) }}"
           class="rounded-xl border px-3 py-2.5 transition {{ $filterScope === 'course' ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/15' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-gray-700' }}">
            <div class="text-xs text-gray-500 dark:text-gray-400">Per Kelas</div>
            <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['course'] }}</div>
        </a>
    </section>

    {{-- Search --}}
    <x-admin.card class="mb-6" :padded="false">
        <form method="GET" action="{{ route('admin.installment-schemes.index') }}" class="flex gap-2 p-4">
            @if ($filterScope)
                <input type="hidden" name="scope" value="{{ $filterScope }}">
            @endif
            <input type="search" name="q" value="{{ $search }}"
                   placeholder="Cari nama skema atau nama kelas..."
                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            <x-admin.button type="submit" size="sm">
                Cari
            </x-admin.button>
            @if ($search || $filterScope || $filterCourseModel)
                <a href="{{ route('admin.installment-schemes.index') }}"
                   class="inline-flex items-center text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                    Reset
                </a>
            @endif
        </form>
    </x-admin.card>

    {{-- Tabel --}}
    <x-admin.table
        :columns="[
            ['label' => 'Nama'],
            ['label' => 'Berlaku untuk'],
            ['label' => 'Struktur pembayaran'],
            ['label' => 'Status'],
            ['label' => '', 'align' => 'text-right'],
        ]"
        :rows="$schemes"
        empty="Belum ada skema cicilan. Klik 'Skema Baru' untuk membuat.">
        @foreach ($schemes as $scheme)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800 dark:text-white/90">{{ $scheme->name }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">#{{ $scheme->id }}</div>
                </td>
                <td class="px-4 py-3 text-sm">
                    @if ($scheme->course_id && $scheme->course)
                        <a href="{{ route('admin.installment-schemes.index', ['course' => $scheme->course_id]) }}"
                           class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-brand-50 text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400">
                            <i data-lucide="graduation-cap" class="h-3 w-3"></i>
                            {{ $scheme->course->title }}
                        </a>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">
                            <i data-lucide="globe" class="h-3 w-3"></i>
                            Semua Kelas
                        </span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    @if ($scheme->n_installments <= 1 && (float) $scheme->dp_pct >= 100)
                        <span class="text-sm text-gray-700 dark:text-gray-300">Bayar penuh (lunas)</span>
                    @else
                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">
                            DP {{ $scheme->dp_label }}% + {{ $scheme->n_installments }}× cicilan
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">tiap {{ $scheme->interval_days }} hari</div>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <form method="POST" action="{{ route('admin.installment-schemes.toggle', $scheme) }}" class="inline">
                        @csrf
                        @if ($scheme->active)
                            <button type="submit"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-success-50 text-success-600 hover:bg-success-100 transition dark:bg-success-500/15 dark:text-success-500 dark:hover:bg-success-500/25">
                                ✓ Aktif
                            </button>
                        @else
                            <button type="submit"
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition dark:bg-white/[0.03] dark:text-gray-400 dark:hover:bg-white/[0.06]">
                                Nonaktif
                            </button>
                        @endif
                    </form>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2 text-xs">
                        <a href="{{ route('admin.installment-schemes.edit', $scheme) }}"
                           class="font-medium text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-500">Edit</a>
                        <form method="POST" action="{{ route('admin.installment-schemes.destroy', $scheme) }}"
                              onsubmit="return confirm('Hapus skema {{ addslashes($scheme->name) }}?');" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="font-medium text-error-600 hover:text-error-700 dark:text-error-500 dark:hover:text-error-600">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($schemes->hasPages())
        <div class="mt-4">
            {{ $schemes->links() }}
        </div>
    @endif
@endsection
