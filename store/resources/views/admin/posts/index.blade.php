@extends('layouts.admin', ['active' => 'posts'])

@section('title', 'Blog')

@php
    $isTrashed = ($view ?? 'active') === 'trashed';
    $statusTone = [
        'published' => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400',
        'draft' => 'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300',
        'scheduled' => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400',
    ];
@endphp

@section('content')
    <x-admin.page-header
        title="Blog"
        subtitle="Kelola artikel. Draft = belum tayang, Published = live di /blog, Scheduled = tayang otomatis saat tanggal tiba.">
        <x-slot name="actions">
            @unless ($isTrashed)
                @if (\Illuminate\Support\Facades\Route::has('admin.posts.import.form'))
                    <x-admin.button href="{{ route('admin.posts.import.form') }}" variant="outline" size="sm">
                        <x-admin.icon name="upload" class="h-3.5 w-3.5" />
                        Import WordPress
                    </x-admin.button>
                @endif
                <x-admin.button href="{{ route('admin.posts.create') }}" size="sm">
                    <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                    Tambah Artikel
                </x-admin.button>
            @endunless
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- View tabs --}}
    <div class="mb-6 flex items-center gap-2 text-xs font-medium">
        <a href="{{ route('admin.posts.index') }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 transition {{ ! $isTrashed ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400' }}">
            Aktif <span class="text-[10px] opacity-70">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('admin.posts.index', ['view' => 'trashed']) }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 transition {{ $isTrashed ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400' }}">
            <x-admin.icon name="trash" class="h-3 w-3" />
            Arsip <span class="text-[10px] opacity-70">{{ $stats['trashed'] }}</span>
        </a>
        <a href="{{ route('admin.blog-categories.index') }}"
            class="ml-auto inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400">
            <x-admin.icon name="tag" class="h-3 w-3" />
            Kelola Kategori
        </a>
    </div>

    {{-- Stats --}}
    @unless ($isTrashed)
        <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <x-admin.stat-card title="Total Artikel" :value="$stats['total']" tone="slate" />
            <x-admin.stat-card title="Published" :value="$stats['published']" tone="secondary" />
            <x-admin.stat-card title="Draft" :value="$stats['draft']" tone="primary" />
            <x-admin.stat-card title="Scheduled" :value="$stats['scheduled']" tone="amber" />
        </section>
    @endunless

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.posts.index') }}"
        class="mb-6 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end dark:border-gray-800 dark:bg-white/[0.03]">
        <input type="hidden" name="view" value="{{ $view ?? 'active' }}">
        <div class="flex-1">
            <label for="q" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Judul, excerpt, atau slug…"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-3 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>

        @unless ($isTrashed)
            <div class="sm:w-44">
                <label for="status" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Status</label>
                <select id="status" name="status"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua status</option>
                    @foreach (['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled'] as $value => $label)
                        <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:w-44">
                <label for="category" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Kategori</label>
                <select id="category" name="category"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->slug }}" @selected($filterCategory === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        @endunless

        <x-admin.button type="submit" size="sm">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </x-admin.button>

        @if ($search || $filterStatus || $filterCategory)
            <x-admin.button href="{{ route('admin.posts.index', ['view' => $view ?? 'active']) }}" variant="outline" size="sm">Reset</x-admin.button>
        @endif
    </form>

    {{-- Bulk form + table --}}
    <form id="bulk-form" method="POST" action="{{ route('admin.posts.bulk') }}"
        x-data="{ selected: [], get hasSelection() { return this.selected.length > 0; } }">
        @csrf
        <input type="hidden" name="view" value="{{ $view ?? 'active' }}">
        @if ($filterStatus) <input type="hidden" name="status" value="{{ $filterStatus }}"> @endif
        @if ($filterCategory) <input type="hidden" name="category" value="{{ $filterCategory }}"> @endif
        @if ($search) <input type="hidden" name="q" value="{{ $search }}"> @endif

        <div x-show="hasSelection" x-cloak
            class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3 text-xs dark:border-brand-500/30 dark:bg-brand-500/15">
            <span class="font-medium text-brand-900 dark:text-brand-400"><span x-text="selected.length"></span> dipilih</span>
            <span class="text-gray-400 dark:text-gray-500">·</span>
            @if ($isTrashed)
                <button type="submit" name="action" value="restore"
                    class="inline-flex items-center gap-1 rounded-lg bg-success-500 px-3 py-1.5 font-medium text-white hover:bg-success-600 transition">Restore</button>
                <button type="submit" name="action" value="force_delete"
                    onclick="return confirm('Hapus permanen artikel yang dipilih?');"
                    class="inline-flex items-center gap-1 rounded-lg bg-error-500 px-3 py-1.5 font-medium text-white hover:bg-error-600 transition">Hapus permanen</button>
            @else
                <button type="submit" name="action" value="publish"
                    class="inline-flex items-center gap-1 rounded-lg bg-success-500 px-3 py-1.5 font-medium text-white hover:bg-success-600 transition">Publish</button>
                <button type="submit" name="action" value="draft"
                    class="inline-flex items-center gap-1 rounded-lg bg-warning-500 px-3 py-1.5 font-medium text-white hover:bg-warning-600 transition">Jadikan draft</button>
                <button type="submit" name="action" value="soft_delete"
                    onclick="return confirm('Pindahkan ke arsip (soft delete)?');"
                    class="inline-flex items-center gap-1 rounded-lg bg-error-500 px-3 py-1.5 font-medium text-white hover:bg-error-600 transition">Soft delete</button>
            @endif
            <button type="button" @click="selected = []; document.querySelectorAll('input[name=&quot;ids[]&quot;]').forEach(el => el.checked = false)"
                class="ml-auto inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Clear</button>
        </div>

        <x-admin.table
            :columns="[
                ['label' => '', 'align' => 'w-8'],
                ['label' => 'Artikel'],
                ['label' => 'Kategori'],
                ['label' => 'Status'],
                ['label' => 'Tanggal'],
                ['label' => 'Aksi', 'align' => 'text-right'],
            ]"
            :rows="$posts"
            empty="Belum ada artikel yang cocok dengan filter ini.">
            @foreach ($posts as $post)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition {{ $isTrashed ? 'opacity-75' : '' }}">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $post->id }}"
                            x-on:change="$event.target.checked ? selected.push({{ $post->id }}) : (selected = selected.filter(id => id !== {{ $post->id }}))"
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-16 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                                @if ($post->image_path)
                                    <img src="{{ $post->imageUrl() }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-gray-300 dark:text-gray-600">
                                        <x-admin.icon name="image" class="h-5 w-5" />
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate dark:text-white/90">{{ $post->title }}</p>
                                <p class="text-xs text-gray-500 font-mono truncate dark:text-gray-400">/blog/{{ $post->slug }}</p>
                                @if ($post->deleted_at)
                                    <p class="text-[10px] text-warning-600 mt-0.5 dark:text-warning-500">Soft-deleted {{ $post->deleted_at->diffForHumans() }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            @forelse ($post->categories->take(2) as $category)
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[11px] text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">{{ $category->name }}</span>
                            @empty
                                <span class="text-xs text-gray-400">—</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium capitalize {{ $statusTone[$post->status] ?? 'bg-gray-100 text-gray-600' }}">{{ $post->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                        {{ optional($post->published_at)->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            @if ($isTrashed)
                                <form method="POST" action="{{ route('admin.posts.restore', $post) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-success-200 bg-white px-3 py-1.5 text-xs font-medium text-success-700 hover:bg-success-50 transition dark:border-success-500/30 dark:bg-white/[0.03] dark:text-success-500">
                                        <x-admin.icon name="check" class="h-3 w-3" /> Restore
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('admin.posts.edit', $post) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                    <x-admin.icon name="edit" class="h-3 w-3" /> Edit
                                </a>
                                <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                                    onsubmit="return confirm('Hapus artikel &quot;{{ $post->title }}&quot;? Bisa di-restore dari arsip.');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500">
                                        <x-admin.icon name="trash" class="h-3 w-3" /> Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    </form>

    @if ($posts->hasPages())
        <div class="mt-6">{{ $posts->links() }}</div>
    @endif
@endsection
