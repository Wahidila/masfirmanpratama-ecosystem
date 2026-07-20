@extends('layouts.admin', ['active' => 'participants'])

@section('title', 'Peserta Kursus')

@php
    $statusTone = [
        'registered' => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
        'active' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
        'graduated' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
        'cancelled' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
    ];
@endphp

@section('content')
    <x-admin.page-header
        title="Peserta Kursus"
        subtitle="Peserta dari pesanan kelas yang sudah ada pembayaran (lunas atau cicilan berjalan), plus peserta yang ditambahkan manual.">
        <x-slot:actions>
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

    <form method="GET" action="{{ route('admin.participants.index') }}"
        class="mb-6 flex flex-wrap items-end gap-3">
        <div class="min-w-[220px] flex-1">
            <label for="q" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Cari</label>
            <div class="relative">
                <x-admin.icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nama, email, atau nomor WA..."
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent pl-9 pr-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
            </div>
        </div>

        <div>
            <label for="course" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Kelas</label>
            <select id="course" name="course"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua kelas</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) ($filters['course'] ?? '') === (string) $course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Status</label>
            <select id="status" name="status"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="payment" class="block text-xs font-medium text-gray-700 mb-1 dark:text-gray-300">Pembayaran</label>
            <select id="payment" name="payment"
                class="h-11 rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                <option value="">Semua</option>
                @foreach ($paymentStatuses as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['payment'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <x-admin.button type="submit" size="sm">
            <x-admin.icon name="filter" class="h-3.5 w-3.5" />
            Filter
        </x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button href="{{ route('admin.participants.index') }}" variant="outline" size="sm">
                Reset
            </x-admin.button>
        @endif
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
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $statusTone[$participant->status] ?? $statusTone['registered'] }}">
                        {{ $participant->statusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium
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
                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
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
