@extends('layouts.admin', ['active' => 'video-testimonials'])

@section('title', 'Testimoni Video')

@section('content')
    <x-admin.page-header
        title="Testimoni Video"
        subtitle="Kelola video card section Testimoni Peserta AMC di homepage.">
        <x-slot name="actions">
            <x-admin.button href="{{ route('admin.video-testimonials.create') }}" size="sm">
                <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                Tambah Video
            </x-admin.button>
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
        <x-admin.stat-card title="Total" :value="$stats['total']" tone="slate" />
        <x-admin.stat-card title="Homepage" :value="$stats['homepage']" tone="primary" />
        <x-admin.stat-card title="Active" :value="$stats['active']" tone="secondary" />
        <x-admin.stat-card title="Draft" :value="$stats['draft']" tone="slate" />
        <x-admin.stat-card title="Archived" :value="$stats['archived']" tone="amber" />
    </section>

    <form method="GET" action="{{ route('admin.video-testimonials.index') }}"
        class="mb-6 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex-1">
            <label for="q" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Judul atau nama peserta..."
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-3 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            </div>
        </div>

        <div class="sm:w-40">
            <label for="status" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Status</label>
            <select id="status" name="status"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua</option>
                @foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                    <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-admin.button type="submit" size="sm">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </x-admin.button>

        @if ($search || $filterStatus)
            <x-admin.button href="{{ route('admin.video-testimonials.index') }}" variant="outline" size="sm">
                Reset
            </x-admin.button>
        @endif
    </form>

    <x-admin.table
        :columns="[
            ['label' => 'Video'],
            ['label' => 'Peserta'],
            ['label' => 'Homepage'],
            ['label' => 'Status'],
            ['label' => 'Urutan'],
            ['label' => 'Aksi', 'align' => 'text-right'],
        ]"
        :rows="$videoTestimonials"
        empty="Belum ada video testimoni yang cocok dengan filter ini.">
        @foreach ($videoTestimonials as $item)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <div class="h-16 w-12 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-900 dark:border-gray-800">
                            <video src="{{ $item->video_url }}" @if($item->poster_url) poster="{{ $item->poster_url }}" @endif class="h-full w-full object-cover" preload="metadata" muted></video>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 truncate dark:text-white/90">{{ $item->title }}</p>
                            <a href="{{ $item->video_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-brand-600 hover:underline dark:text-brand-400">
                                Buka video
                            </a>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800 dark:text-white/90">{{ $item->participant_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->role ?: 'Alumni AMC' }}</p>
                </td>
                <td class="px-4 py-3">
                    @if ($item->show_on_homepage)
                        <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-theme-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Tampil</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-50 px-2.5 py-0.5 text-theme-xs font-medium text-gray-600 dark:bg-gray-500/15 dark:text-gray-400">Hidden</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-admin.status-badge :status="$item->status" />
                </td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                    {{ $item->sort_order }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-1.5">
                        <a href="{{ route('admin.video-testimonials.edit', $item) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            <x-admin.icon name="edit" class="h-3 w-3" />
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.video-testimonials.destroy', $item) }}"
                            onsubmit="return confirm('Hapus video testimoni &quot;{{ $item->title }}&quot;?');"
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

    @if ($videoTestimonials->hasPages())
        <div class="mt-6">
            {{ $videoTestimonials->links() }}
        </div>
    @endif
@endsection
