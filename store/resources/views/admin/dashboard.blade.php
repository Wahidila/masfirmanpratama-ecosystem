@extends('layouts.admin', ['active' => 'dashboard'])

@section('title', 'Dashboard Admin')

@section('content')
    {{-- Page Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white/90">Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan operasional store hari ini.</p>
    </div>

    @if (session('status'))
        <div class="mb-6">
            <x-admin.alert tone="success" dismissible>{{ session('status') }}</x-admin.alert>
        </div>
    @endif

    {{-- Metric Cards Grid --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
        <x-admin.metric-card
            title="Total Pesanan"
            :value="$stats['orders_total']"
            icon="orders"
            hint="Semua status" />

        <x-admin.metric-card
            title="Pesanan Pending"
            :value="$stats['orders_pending']"
            icon="pending"
            hint="Belum upload bukti bayar"
            badge="{{ $stats['orders_pending'] > 0 ? 'Perlu aksi' : '' }}"
            badgeTone="warning" />

        <x-admin.metric-card
            title="Verifikasi Pembayaran"
            :value="$stats['payments_to_verify']"
            icon="verify"
            hint="Bukti bayar menunggu"
            badge="{{ $stats['payments_to_verify'] > 0 ? 'Pending' : '' }}"
            badgeTone="warning" />

        <x-admin.metric-card
            title="Cicilan Berjalan"
            :value="$stats['orders_partial_paid']"
            icon="installment"
            hint="Order partial_paid" />

        <x-admin.metric-card
            title="Lunas (Belum Kirim)"
            :value="$stats['orders_paid']"
            icon="paid"
            hint="Siap input resi" />

        <x-admin.metric-card
            title="Produk Aktif"
            :value="$stats['products_active']"
            icon="product"
            hint="status=active" />

        <x-admin.metric-card
            title="Video Testimoni"
            :value="$stats['video_testimonials_homepage']"
            icon="product"
            hint="Tampil di homepage" />

        <x-admin.metric-card
            title="Revenue"
            value="Rp {{ number_format($revenueTotal, 0, ',', '.') }}"
            icon="revenue"
            hint="Order paid/shipped/completed" />

        <x-admin.metric-card
            title="Pesanan Bulan Ini"
            :value="$ordersThisMonth"
            icon="calendar"
            hint="Bulan berjalan" />
    </section>

    {{-- Chart: Pesanan 6 Bulan Terakhir --}}
    <section class="mt-6">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Pesanan 6 Bulan Terakhir
                </h3>
            </div>
            <div class="max-w-full overflow-x-auto">
                <div id="salesChart" class="-ml-5 h-full min-w-[500px] pl-2 xl:min-w-full"></div>
            </div>
        </div>
        <script type="application/json" id="dashboard-chart-data">@json($chartData)</script>
    </section>

    {{-- Recent Orders Table --}}
    <section class="mt-6">
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6">
            <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Pesanan Terbaru</h3>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">5 terakhir</span>
            </div>

            <div class="max-w-full overflow-x-auto">
                @if($recentOrders->isEmpty())
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada pesanan.</p>
                @else
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-t border-gray-100 dark:border-gray-800">
                                <th class="py-3 text-left">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Order #</p>
                                </th>
                                <th class="py-3 text-left">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                                </th>
                                <th class="py-3 text-left">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Total</p>
                                </th>
                                <th class="py-3 text-left">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                                </th>
                                <th class="py-3 text-left">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Dibuat</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                @php
                                    [$statusLabel, $tone] = match($order->status) {
                                        'pending' => ['Pending', 'warning'],
                                        'partial_paid' => ['Cicilan', 'warning'],
                                        'paid' => ['Lunas', 'brand'],
                                        'shipped' => ['Dikirim', 'brand'],
                                        'completed' => ['Selesai', 'success'],
                                        'cancelled' => ['Batal', 'error'],
                                        'refunded' => ['Refund', 'error'],
                                        default => [$order->status, 'gray'],
                                    };
                                    $badgeClass = match($tone) {
                                        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                                        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                                        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-500',
                                        'error' => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                                        default => 'bg-gray-50 text-gray-600 dark:bg-gray-500/15 dark:text-gray-400',
                                    };
                                @endphp
                                <tr class="border-t border-gray-100 dark:border-gray-800">
                                    <td class="py-3 whitespace-nowrap">
                                        <p class="font-mono text-theme-sm text-gray-800 dark:text-white/90">{{ $order->order_number }}</p>
                                    </td>
                                    <td class="py-3 whitespace-nowrap">
                                        <p class="text-theme-sm text-gray-800 dark:text-white/90">{{ $order->customer_name }}</p>
                                    </td>
                                    <td class="py-3 whitespace-nowrap">
                                        <p class="text-gray-500 text-theme-sm dark:text-gray-400">Rp {{ number_format((float) $order->total, 0, ',', '.') }}</p>
                                    </td>
                                    <td class="py-3 whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-theme-xs font-medium {{ $badgeClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="py-3 whitespace-nowrap">
                                        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $order->created_at?->diffForHumans() }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </section>
@endsection
