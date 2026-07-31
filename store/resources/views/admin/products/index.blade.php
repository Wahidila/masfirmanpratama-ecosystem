@extends('layouts.admin', ['active' => 'products'])

@section('title', 'Produk')

@php
    $isTrashed = ($view ?? 'active') === 'trashed';
@endphp

@section('content')
    <x-admin.page-header
        title="Produk"
        subtitle="Kelola katalog buku & kelas. Status draft = belum tayang, active = live di store, archived = disembunyikan dari FE.">
        <x-slot name="actions">
            @unless ($isTrashed)
                <x-admin.button href="{{ route('admin.products.create') }}" size="sm">
                    <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                    Tambah Produk
                </x-admin.button>
            @endunless
        </x-slot>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- View tabs (active vs trashed/arsip) --}}
    <div class="mb-6 flex items-center gap-2 text-xs font-medium">
        <a href="{{ route('admin.products.index') }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 transition {{ ! $isTrashed ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400' }}">
            Aktif
            <span class="text-[10px] opacity-70">{{ $stats['total'] }}</span>
        </a>
        <a href="{{ route('admin.products.index', ['view' => 'trashed']) }}"
            class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 transition {{ $isTrashed ? 'border-brand-500 bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-400' }}">
            <x-admin.icon name="trash" class="h-3 w-3" />
            Arsip (soft-deleted)
            <span class="text-[10px] opacity-70">{{ $stats['trashed'] }}</span>
        </a>
    </div>

    {{-- Stats --}}
    @unless ($isTrashed)
        <section class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <x-admin.stat-card title="Total Produk" :value="$stats['total']" tone="slate" />
            <x-admin.stat-card title="Active" :value="$stats['active']" tone="secondary" />
            <x-admin.stat-card title="Draft" :value="$stats['draft']" tone="primary" />
            <x-admin.stat-card title="Archived" :value="$stats['archived']" tone="amber" />
        </section>
    @endunless

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('admin.products.index') }}"
        class="mb-6 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-xs sm:flex-row sm:items-end dark:border-gray-800 dark:bg-white/[0.03]">
        <input type="hidden" name="view" value="{{ $view ?? 'active' }}">
        @if ($sort) <input type="hidden" name="sort" value="{{ $sort }}"> <input type="hidden" name="dir" value="{{ $dir }}"> @endif
        <div class="flex-1">
            <label for="q" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input type="text" id="q" name="q" value="{{ $search }}" placeholder="Judul atau slug…"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-3 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            </div>
        </div>

        @unless ($isTrashed)
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
        @endunless

        {{-- Filter tipe hanya muncul kalau katalog benar-benar campuran (>1 tipe).
             Toko ini praktis semua 'book'; kelas dikelola di modul terpisah. --}}
        @if (count($types) > 1)
            <div class="sm:w-40">
                <label for="type" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Tipe</label>
                <select id="type" name="type"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua</option>
                    @foreach ($types as $t)
                        <option value="{{ $t }}" @selected($filterType === $t)>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="sm:w-40">
            <label for="stock" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Stok</label>
            <select id="stock" name="stock"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua</option>
                <option value="low" @selected($filterStock === 'low')>Menipis (≤ {{ $lowStockThreshold }})</option>
                <option value="out" @selected($filterStock === 'out')>Habis (0)</option>
            </select>
        </div>

        <x-admin.button type="submit" size="sm">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </x-admin.button>

        @if ($search || $filterStatus || $filterType || $filterStock || $sort)
            <x-admin.button href="{{ route('admin.products.index', ['view' => $view ?? 'active']) }}" variant="outline" size="sm">
                Reset
            </x-admin.button>
        @endif
    </form>

    {{-- Alpine x-data dinaikkan ke wrapper <div> (BUKAN di <form>) supaya checkbox
         baris — yang ada di dalam tabel, di LUAR <form id="bulk-form"> — tetap satu
         scope. Bulk form & form aksi per-baris (Hapus/Restore) jadi BERSEBELAHAN,
         tidak bersarang. Nested <form> = HTML invalid → dulu tombol "Hapus" malah
         men-submit bulk form (ke products.bulk tanpa action/ids). --}}
    <div x-data="{
            selected: [],
            selectAllMatching: false,
            pageIds: {{ \Illuminate\Support\Js::from($products->pluck('id')->values()) }},
            get hasSelection() { return this.selected.length > 0 || this.selectAllMatching; },
            get allOnPageSelected() { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
            toggleAllOnPage(check) { this.selectAllMatching = false; this.selected = check ? [...this.pageIds] : []; },
        }">
        <form id="bulk-form" method="POST" action="{{ route('admin.products.bulk') }}">
            @csrf
            <input type="hidden" name="view" value="{{ $view ?? 'active' }}">
            @if ($filterStatus) <input type="hidden" name="status" value="{{ $filterStatus }}"> @endif
            @if ($filterType) <input type="hidden" name="type" value="{{ $filterType }}"> @endif
            @if ($filterStock) <input type="hidden" name="stock" value="{{ $filterStock }}"> @endif
            @if ($search) <input type="hidden" name="q" value="{{ $search }}"> @endif
            {{-- Flag "pilih semua sesuai filter": '1' saat aktif, '' saat tidak.
                 Controller pakai request->boolean('select_all'). --}}
            <input type="hidden" name="select_all" x-bind:value="selectAllMatching ? '1' : ''">
            {{-- Saat select-all, kirim juga snapshot filter agar server bangun set yang sama. --}}

        {{-- Bulk action toolbar (sticky bottom-style, conditional) --}}
        <div x-show="hasSelection" x-cloak
            class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 p-3 text-xs dark:border-brand-500/30 dark:bg-brand-500/15">
            <span class="font-medium text-brand-900 dark:text-brand-400">
                <span x-show="!selectAllMatching"><span x-text="selected.length"></span> dipilih</span>
                <span x-show="selectAllMatching" x-cloak>Semua {{ number_format($filteredTotal, 0, ',', '.') }} produk sesuai filter</span>
            </span>

            {{-- Tawarkan "pilih semua N sesuai filter" saat 1 halaman penuh terpilih
                 dan masih ada baris lain di luar halaman ini. --}}
            @if ($filteredTotal > $products->count())
                <template x-if="allOnPageSelected && !selectAllMatching">
                    <button type="button" @click="selectAllMatching = true"
                        class="underline font-medium text-brand-700 hover:text-brand-800 dark:text-brand-300">
                        Pilih semua {{ number_format($filteredTotal, 0, ',', '.') }} sesuai filter
                    </button>
                </template>
                <template x-if="selectAllMatching">
                    <button type="button" @click="selectAllMatching = false; selected = []"
                        class="underline font-medium text-brand-700 hover:text-brand-800 dark:text-brand-300">
                        Batalkan
                    </button>
                </template>
            @endif

            <span class="text-gray-400 dark:text-gray-500">·</span>

            @if ($isTrashed)
                <button type="submit" name="action" value="restore"
                    class="inline-flex items-center gap-1 rounded-lg bg-success-500 px-3 py-1.5 font-medium text-white hover:bg-success-600 transition">
                    <x-admin.icon name="check" class="h-3 w-3" />
                    Restore
                </button>
                <button type="submit" name="action" value="force_delete"
                    @click="if (!confirm((selectAllMatching ? {{ $filteredTotal }} : selected.length) + ' produk akan DIHAPUS PERMANEN & tidak bisa dibatalkan. Lanjut?')) $event.preventDefault()"
                    class="inline-flex items-center gap-1 rounded-lg bg-error-500 px-3 py-1.5 font-medium text-white hover:bg-error-600 transition">
                    <x-admin.icon name="trash" class="h-3 w-3" />
                    Hapus permanen
                </button>
            @else
                <button type="submit" name="action" value="activate"
                    class="inline-flex items-center gap-1 rounded-lg bg-success-500 px-3 py-1.5 font-medium text-white hover:bg-success-600 transition">
                    Activate
                </button>
                <button type="submit" name="action" value="archive"
                    class="inline-flex items-center gap-1 rounded-lg bg-warning-500 px-3 py-1.5 font-medium text-white hover:bg-warning-600 transition">
                    Archive (status)
                </button>
                <button type="submit" name="action" value="soft_delete"
                    @click="if (!confirm((selectAllMatching ? {{ $filteredTotal }} : selected.length) + ' produk akan dipindahkan ke arsip (soft delete). Bisa di-restore. Lanjut?')) $event.preventDefault()"
                    class="inline-flex items-center gap-1 rounded-lg bg-error-500 px-3 py-1.5 font-medium text-white hover:bg-error-600 transition">
                    <x-admin.icon name="trash" class="h-3 w-3" />
                    Soft delete
                </button>
            @endif

            <button type="button" @click="selected = []; selectAllMatching = false"
                class="ml-auto inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                Clear
            </button>
        </div>
    </form>

    @php
        // Query aktif (tanpa sort/dir) untuk link header sortable.
        $baseQuery = array_filter([
            'view' => ($view ?? 'active') !== 'active' ? $view : null,
            'status' => $filterStatus ?: null,
            'type' => $filterType ?: null,
            'stock' => $filterStock ?: null,
            'q' => $search ?: null,
        ]);
        $sortLink = fn ($col) => route('admin.products.index', array_merge($baseQuery, [
            'sort' => $col,
            'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
        ]));
        $sortIcon = fn ($col) => $sort === $col ? ($dir === 'asc' ? '↑' : '↓') : '';
    @endphp

    {{-- Tabel di LUAR <form id="bulk-form"> — checkbox baris terhubung ke bulk
         form via atribut form="bulk-form", form aksi per-baris tak bersarang. --}}
    <x-admin.table
            :columns="[
                ['label' => '', 'align' => 'w-8'],
                ['label' => 'Produk'],
                ['label' => 'Harga'],
                ['label' => 'Stok'],
                ['label' => 'Status'],
                ['label' => 'Aksi', 'align' => 'text-right'],
            ]"
            :rows="$products"
            empty="Belum ada produk yang cocok dengan filter ini.">
            <x-slot:head>
                <tr class="border-b border-gray-100 text-theme-xs font-medium text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <th class="w-8 px-5 py-3 sm:px-6">
                        <input type="checkbox" :checked="allOnPageSelected" @change="toggleAllOnPage($event.target.checked)"
                            aria-label="Pilih semua di halaman"
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    </th>
                    <th class="px-5 py-3 text-left sm:px-6">
                        <a href="{{ $sortLink('title') }}" class="inline-flex items-center gap-1 hover:text-brand-500">Produk <span class="text-brand-500">{{ $sortIcon('title') }}</span></a>
                    </th>
                    <th class="px-5 py-3 text-left sm:px-6">
                        <a href="{{ $sortLink('price') }}" class="inline-flex items-center gap-1 hover:text-brand-500">Harga <span class="text-brand-500">{{ $sortIcon('price') }}</span></a>
                    </th>
                    <th class="px-5 py-3 text-left sm:px-6">
                        <a href="{{ $sortLink('stock') }}" class="inline-flex items-center gap-1 hover:text-brand-500">Stok <span class="text-brand-500">{{ $sortIcon('stock') }}</span></a>
                    </th>
                    <th class="px-5 py-3 text-left sm:px-6">Status</th>
                    <th class="px-5 py-3 text-right sm:px-6">Aksi</th>
                </tr>
            </x-slot:head>
            @foreach ($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition {{ $isTrashed ? 'opacity-75' : '' }}">
                    <td class="px-4 py-3">
                        <input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-form"
                            x-model.number="selected"
                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">
                                @if ($product->image_path)
                                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-gray-300 dark:text-gray-600">
                                        <x-admin.icon name="image" class="h-5 w-5" />
                                    </div>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate dark:text-white/90">{{ $product->title }}</p>
                                <p class="text-xs text-gray-500 font-mono truncate dark:text-gray-400">/{{ $product->slug }}</p>
                                @if ($product->deleted_at)
                                    <p class="text-[10px] text-warning-600 mt-0.5 dark:text-warning-500">Soft-deleted {{ $product->deleted_at->diffForHumans() }}</p>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800 dark:text-white/90">Rp {{ number_format((float) $product->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        @if ($product->is_shippable && $product->stock <= 0)
                            <span class="inline-flex items-center rounded-full bg-error-50 px-2 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Habis</span>
                        @elseif ($product->is_shippable && $product->stock <= $lowStockThreshold)
                            <span title="Stok menipis (≤ {{ $lowStockThreshold }})"
                                class="inline-flex items-center rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">{{ $product->stock }} · Menipis</span>
                        @else
                            <span class="text-gray-700 dark:text-gray-300">{{ $product->stock }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <x-admin.status-badge :status="$product->status" />
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            @if ($isTrashed)
                                {{-- Restore single --}}
                                <form method="POST" action="{{ route('admin.products.restore', $product) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-success-200 bg-white px-3 py-1.5 text-xs font-medium text-success-700 hover:bg-success-50 transition dark:border-success-500/30 dark:bg-white/[0.03] dark:text-success-500 dark:hover:bg-success-500/15">
                                        <x-admin.icon name="check" class="h-3 w-3" />
                                        Restore
                                    </button>
                                </form>
                            @else
                                {{-- Toggle status cepat: active <-> archived tanpa buka Edit --}}
                                <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    @if ($product->status === 'active')
                                        <button type="submit" title="Sembunyikan dari store (archived)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-warning-200 bg-white px-3 py-1.5 text-xs font-medium text-warning-700 hover:bg-warning-50 transition dark:border-warning-500/30 dark:bg-white/[0.03] dark:text-warning-500 dark:hover:bg-warning-500/15">
                                            <x-admin.icon name="archive" class="h-3 w-3" />
                                            Sembunyikan
                                        </button>
                                    @else
                                        <button type="submit" title="Terbitkan ke store (active)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-success-200 bg-white px-3 py-1.5 text-xs font-medium text-success-700 hover:bg-success-50 transition dark:border-success-500/30 dark:bg-white/[0.03] dark:text-success-500 dark:hover:bg-success-500/15">
                                            <x-admin.icon name="check-circle" class="h-3 w-3" />
                                            Terbitkan
                                        </button>
                                    @endif
                                </form>
                                <a href="{{ route('admin.products.edit', $product) }}"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                    <x-admin.icon name="edit" class="h-3 w-3" />
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                    onsubmit="return confirm('Hapus produk &quot;{{ $product->title }}&quot;? Bisa di-restore dari arsip.');"
                                    class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500 dark:hover:bg-error-500/15">
                                        <x-admin.icon name="trash" class="h-3 w-3" />
                                        Hapus
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-admin.table>
    </div>

    @if ($products->hasPages())
        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
@endsection
