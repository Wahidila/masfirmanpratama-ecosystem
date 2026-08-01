@extends('layouts.admin', ['active' => 'participants'])

@section('title', 'Peserta Kursus')

@php
    $statusTone = [
        'registered' => 'bg-gray-100 text-gray-700 dark:bg-gray-500/15 dark:text-gray-300',
        'active' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'graduated' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'cancelled' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    ];
    $fieldClass = 'h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90';
    $labelClass = 'mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400';
@endphp

@section('content')
    <x-admin.page-header
        title="Peserta Kursus"
        subtitle="Peserta dari pesanan kelas yang sudah ada pembayaran (lunas atau cicilan berjalan), plus peserta yang ditambahkan manual.">
        <x-slot:actions>
            <x-admin.button
                href="{{ route('admin.participants.export', request()->only(['q', 'course', 'status', 'payment'])) }}"
                variant="outline" size="sm">
                <x-admin.icon name="download" class="h-3.5 w-3.5" />
                Export Excel
            </x-admin.button>
            <x-admin.button href="{{ route('admin.participants.create') }}" size="sm">
                <x-admin.icon name="plus" class="h-3.5 w-3.5" />
                Tambah Peserta
            </x-admin.button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Filter --}}
    <form method="GET" action="{{ route('admin.participants.index') }}"
        class="mb-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="sm:col-span-2 xl:col-span-1">
                <label for="q" class="{{ $labelClass }}">Cari</label>
                <div class="relative">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                    <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                        placeholder="Nama, email, atau nomor WA"
                        class="{{ $fieldClass }} pl-9">
                </div>
            </div>

            <div>
                <label for="course" class="{{ $labelClass }}">Kelas</label>
                <select id="course" name="course" class="{{ $fieldClass }}">
                    <option value="">Semua kelas</option>
                    @foreach ($courses as $course)
                        <option value="{{ $course->id }}" @selected((string) ($filters['course'] ?? '') === (string) $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="{{ $labelClass }}">Status peserta</label>
                <select id="status" name="status" class="{{ $fieldClass }}">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="payment" class="{{ $labelClass }}">Pembayaran</label>
                <select id="payment" name="payment" class="{{ $fieldClass }}">
                    <option value="">Semua pembayaran</option>
                    @foreach ($paymentStatuses as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['payment'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-800">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @if ($participants->total() > 0)
                    Menampilkan <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format($participants->firstItem(), 0, ',', '.') }}–{{ number_format($participants->lastItem(), 0, ',', '.') }}</span>
                    dari <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format($participants->total(), 0, ',', '.') }}</span> peserta
                @else
                    Tidak ada peserta yang cocok
                @endif
                @if ($filters) <span class="text-gray-400">· filter aktif</span> @endif
            </p>

            <div class="flex items-center gap-2">
                @if ($filters)
                    <x-admin.button href="{{ route('admin.participants.index') }}" variant="outline" size="sm">
                        Reset
                    </x-admin.button>
                @endif
                <x-admin.button type="submit" size="sm">
                    <x-admin.icon name="filter" class="h-3.5 w-3.5" />
                    Terapkan Filter
                </x-admin.button>
            </div>
        </div>
    </form>

    <x-admin.table
        :columns="[
            ['label' => 'Peserta'],
            ['label' => 'Kelas'],
            ['label' => 'Status'],
            ['label' => 'Pembayaran'],
            ['label' => 'Asal'],
            ['label' => 'Bergabung'],
            ['label' => 'Aksi', 'align' => 'text-right'],
        ]"
        :rows="$participants"
        empty="Belum ada peserta yang cocok dengan filter ini.">
        @foreach ($participants as $participant)
            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.03] transition">
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800 dark:text-white/90">{{ $participant->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $participant->email ?: '—' }}@if ($participant->phone) · {{ $participant->phone }}@endif
                    </p>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    {{ $participant->course?->title ?? '—' }}
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $statusTone[$participant->status] ?? $statusTone['registered'] }}">
                        {{ $participant->statusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-theme-xs font-medium
                        {{ $participant->payment_status === 'lunas'
                            ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500'
                            : 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500' }}">
                        {{ $participant->paymentStatusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3 text-xs">
                    @if ($participant->order)
                        <a href="{{ route('admin.orders.show', $participant->order) }}"
                           class="font-mono text-brand-600 hover:underline dark:text-brand-400">
                            {{ $participant->order->order_number }}
                        </a>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">Manual</span>
                    @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                    {{ $participant->joined_at?->format('d M Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                        <a href="{{ route('admin.participants.edit', $participant) }}"
                           class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                            <x-admin.icon name="edit" class="h-3 w-3" />
                            Edit
                        </a>
                        <form method="POST" action="{{ route('admin.participants.destroy', $participant) }}"
                              onsubmit="return confirm('Hapus peserta {{ $participant->name }}? Tindakan ini tidak bisa dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center gap-1 rounded-lg border border-error-200 bg-white px-3 py-1.5 text-xs font-medium text-error-600 hover:bg-error-50 transition dark:border-error-500/30 dark:bg-white/[0.03] dark:text-error-500">
                                <x-admin.icon name="trash" class="h-3 w-3" />
                                Hapus
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    @if ($participants->hasPages())
        <div class="mt-4">
            {{ $participants->links() }}
        </div>
    @endif
@endsection
