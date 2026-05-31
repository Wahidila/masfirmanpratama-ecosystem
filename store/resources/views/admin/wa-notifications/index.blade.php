@extends('layouts.admin', ['active' => 'wa-notifications'])

@section('title', 'WA Notifikasi · Admin')

@section('content')
    <x-admin.page-header
        title="WA Notifikasi"
        subtitle="Log antrean notifikasi WhatsApp (M2 stub — gateway sender M3+).">
        <x-slot:actions>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $stats['total'] }} total</span>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>
                {{ session('status') }}
            </x-admin.alert>
        </div>
    @endif

    {{-- Stat strip per-status --}}
    <section class="grid grid-cols-2 gap-3 mb-6 sm:grid-cols-4">
        <div class="rounded-xl border border-warning-200 bg-warning-50 px-3 py-2.5 dark:border-warning-500/30 dark:bg-warning-500/15">
            <div class="text-xs text-warning-700 dark:text-warning-400">Queued</div>
            <div class="mt-1 text-lg font-semibold text-warning-800 dark:text-warning-500" data-testid="stat-queued">{{ $stats['queued'] }}</div>
        </div>
        <div class="rounded-xl border border-success-200 bg-success-50 px-3 py-2.5 dark:border-success-500/30 dark:bg-success-500/15">
            <div class="text-xs text-success-700 dark:text-success-400">Sent</div>
            <div class="mt-1 text-lg font-semibold text-success-800 dark:text-success-500" data-testid="stat-sent">{{ $stats['sent'] }}</div>
        </div>
        <div class="rounded-xl border border-error-200 bg-error-50 px-3 py-2.5 dark:border-error-500/30 dark:bg-error-500/15">
            <div class="text-xs text-error-700 dark:text-error-400">Failed</div>
            <div class="mt-1 text-lg font-semibold text-error-800 dark:text-error-500" data-testid="stat-failed">{{ $stats['failed'] }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="text-xs text-gray-500 dark:text-gray-400">Total</div>
            <div class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $stats['total'] }}</div>
        </div>
    </section>

    {{-- Filter form --}}
    <x-admin.card class="mb-6" :padded="false">
        <form method="GET" action="{{ route('admin.wa-notifications.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="search" class="sr-only">Cari</label>
                <input
                    id="search"
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari recipient, order number, atau nama customer..."
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
            </div>

            <div>
                <label for="status" class="sr-only">Status</label>
                <select id="status" name="status"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua status</option>
                    @foreach (['queued', 'sent', 'failed'] as $s)
                        <option value="{{ $s }}" @selected($statusFilter === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <select name="template"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                    <option value="">Semua template</option>
                    @foreach ($templates as $t)
                        <option value="{{ $t }}" @selected($templateFilter === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                <x-admin.button type="submit" size="sm">
                    Filter
                </x-admin.button>
            </div>
        </form>
    </x-admin.card>

    {{-- Notification list --}}
    <x-admin.card :padded="false">
        @if ($notifications->isEmpty())
            <div class="px-6 py-12 text-center" data-testid="empty-state">
                <div class="mx-auto mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-white/[0.03]">
                    <x-admin.icon name="message-square" class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada notifikasi WhatsApp.</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Notifikasi akan ke-queue otomatis saat upload bukti, verifikasi, atau input resi.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" data-testid="wa-notifications-table">
                    <thead class="bg-gray-50 dark:bg-white/[0.03]">
                        <tr class="text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Template</th>
                            <th class="px-4 py-3">Recipient</th>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Queued</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white text-sm dark:divide-gray-800 dark:bg-white/[0.03]">
                        @foreach ($notifications as $notif)
                            @php
                                $notifBadgeClass = match ($notif->status) {
                                    'queued' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                                    'sent' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                    'failed' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                                    default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
                                };
                            @endphp
                            <tr data-testid="wa-notif-row" data-id="{{ $notif->id }}" data-status="{{ $notif->status }}">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $notifBadgeClass }}">
                                        {{ ucfirst($notif->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $notif->template }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $notif->recipient }}</td>
                                <td class="px-4 py-3">
                                    @if ($notif->order)
                                        <a href="{{ route('admin.orders.show', $notif->order) }}" class="text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-500">
                                            {{ $notif->order->order_number }}
                                        </a>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $notif->order->customer_name }}</div>
                                    @else
                                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400" title="{{ $notif->created_at }}">
                                    {{ $notif->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-admin.card>
@endsection
