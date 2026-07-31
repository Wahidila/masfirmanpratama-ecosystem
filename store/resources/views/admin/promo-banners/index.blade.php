@extends('layouts.admin', ['active' => 'promo-banners'])

@section('title', 'Banner Promo')

@section('content')
    <x-admin.page-header
        title="Banner Promo"
        subtitle="Banner jadwal terdekat / promo di homepage. Atur jendela tayang supaya banner event otomatis hilang setelah tanggalnya lewat.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.promo-banners.create') }}" size="sm">
                <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                Tambah Banner
            </x-admin.button>
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <x-admin.stat-card title="Total" :value="$stats['total']" tone="slate" />
        <x-admin.stat-card title="Tampil Sekarang" :value="$stats['visible']" tone="secondary" />
        <x-admin.stat-card title="Aktif" :value="$stats['active']" tone="primary" />
        <x-admin.stat-card title="Nonaktif" :value="$stats['inactive']" tone="slate" />
    </section>

    <form method="GET" action="{{ route('admin.promo-banners.index') }}"
        class="mb-6 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex-1">
            <label for="q" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Judul banner..."
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-3 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            </div>
        </div>

        <div class="sm:w-40">
            <label for="status" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Status</label>
            <select id="status" name="status"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua</option>
                @foreach (['active' => 'Aktif', 'inactive' => 'Nonaktif'] as $value => $label)
                    <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-admin.button type="submit" size="sm">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </x-admin.button>

        @if ($search || $filterStatus)
            <x-admin.button href="{{ route('admin.promo-banners.index') }}" variant="outline" size="sm">
                Reset
            </x-admin.button>
        @endif
    </form>

    <x-admin.table
        :columns="[
            ['label' => 'Banner'],
            ['label' => 'Jendela tayang'],
            ['label' => 'Status'],
            ['label' => 'Urutan'],
            ['label' => 'Aksi', 'align' => 'text-right'],
        ]"
        :rows="$banners"
        empty="Belum ada banner yang cocok dengan filter ini.">
        @foreach ($banners as $banner)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="h-12 w-24 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-900">
                            <img src="{{ $banner->imageUrl() }}" alt="{{ $banner->title }}" class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate dark:text-white/90">{{ $banner->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">#{{ $banner->id }}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                    @if ($banner->starts_at || $banner->ends_at)
                        <p>{{ $banner->starts_at?->format('d M Y H:i') ?? 'Sekarang' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">s/d {{ $banner->ends_at?->format('d M Y H:i') ?? 'selamanya' }}</p>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">Selalu</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <div class="flex flex-col items-start gap-1">
                        @if ($banner->active)
                            <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Aktif</span>
                        @else
                            <span class="inline-flex rounded-full bg-gray-50 px-2.5 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">Nonaktif</span>
                        @endif
                        @if ($banner->active && ! in_array($banner->id, $visibleIds, true))
                            <span class="inline-flex rounded-full bg-warning-50 px-2 py-0.5 text-theme-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Di luar jadwal</span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $banner->sort_order }}</td>
                <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-1.5">
                        <form method="POST" action="{{ route('admin.promo-banners.toggle', $banner) }}" class="inline">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
                                <x-admin.icon name="{{ $banner->active ? 'x' : 'check' }}" class="h-3 w-3" />
                                {{ $banner->active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </form>
                        <a href="{{ route('admin.promo-banners.edit', $banner) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.06]">
                            <x-admin.icon name="edit" class="h-3 w-3" />
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.promo-banners.destroy', $banner) }}"
                            onsubmit="return confirm('Hapus banner &quot;{{ $banner->title }}&quot;?');"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15">
                                <x-admin.icon name="trash" class="h-3 w-3" />
                                Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($banners->hasPages())
        <div class="mt-6">
            {{ $banners->links() }}
        </div>
    @endif
@endsection
