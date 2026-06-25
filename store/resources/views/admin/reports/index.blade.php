@extends('layouts.admin', ['active' => 'reports'])

@section('title', 'Laporan Penjualan · Admin')

@section('content')
    <x-admin.page-header
        title="Laporan Penjualan"
        subtitle="Ringkasan revenue, produk terlaris, status pesanan, dan pembayaran.">
        <x-slot:actions>
            <a href="{{ route('admin.reports.export', $filters) }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-admin.icon name="download" class="h-4 w-4" />
                Export CSV
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Filter form --}}
    <x-admin.card class="mb-6" :padded="false">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="from" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Dari Tanggal</label>
                <input
                    id="from"
                    type="date"
                    name="from"
                    value="{{ $filters['from'] }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Sampai Tanggal</label>
                <input
                    id="to"
                    type="date"
                    name="to"
                    value="{{ $filters['to'] }}"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:ring-3 focus:ring-brand-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
            </div>
            <div class="flex items-end">
                <button type="submit"
                    class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                    <x-admin.icon name="filter" class="h-4 w-4" />
                    Terapkan Filter
                </button>
            </div>
            <div class="flex items-end">
                <a href="{{ route('admin.reports.index') }}"
                   class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    Reset
                </a>
            </div>
        </form>
    </x-admin.card>

    @if ($errors->any())
        <div class="mb-6">
            <x-admin.alert tone="error" dismissible>
                @foreach ($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </x-admin.alert>
        </div>
    @endif

    {{-- Revenue summary metric cards --}}
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6 mb-6">
        <x-admin.metric-card
            title="Total Revenue"
            value="Rp {{ number_format((float) $summary['total_revenue'], 0, ',', '.') }}"
            icon="revenue"
            hint="Pembayaran terverifikasi" />

        <x-admin.metric-card
            title="Jumlah Transaksi"
            :value="$summary['transactions_count']"
            icon="orders"
            hint="Pembayaran verified" />

        <x-admin.metric-card
            title="Rata-rata per Transaksi"
            value="Rp {{ number_format((float) $summary['avg_transaction'], 0, ',', '.') }}"
            icon="revenue"
            hint="Revenue / transaksi" />

        <x-admin.metric-card
            title="Total Pesanan"
            :value="$summary['orders_count']"
            icon="orders"
            hint="Semua status dalam rentang" />
    </section>

    {{-- Revenue chart --}}
    <div class="mb-6">
        @include('admin.reports._revenue_chart')
    </div>

    {{-- Top products + Order status breakdown --}}
    <section class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        @include('admin.reports._top_products')
        @include('admin.reports._order_status')
    </section>

    {{-- Payment summary --}}
    <div class="mb-6">
        @include('admin.reports._payment_summary')
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var revenueEl = document.getElementById('revenue-chart-data');
            if (!revenueEl) return;

            var revenueData = JSON.parse(revenueEl.textContent);
            var revenueChartEl = document.querySelector('#revenueChart');
            if (!revenueChartEl) return;

            var isDark = document.documentElement.classList.contains('dark');
            var labelColor = isDark ? '#e5e7eb' : '#6b7280';
            var borderColor = isDark ? '#1f2937' : '#f3f4f6';

            new ApexCharts(revenueChartEl, {
                series: revenueData.series,
                chart: {
                    type: 'area',
                    height: 320,
                    fontFamily: 'Outfit, sans-serif',
                    toolbar: { show: false }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: revenueData.categories,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: labelColor } }
                },
                yaxis: {
                    title: false,
                    labels: {
                        style: { colors: labelColor },
                        formatter: function (val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val));
                        }
                    }
                },
                grid: {
                    borderColor: borderColor,
                    yaxis: { lines: { show: true } }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val));
                        }
                    }
                },
                colors: ['#465fff']
            }).render();

            // Order status pie chart
            var statusEl = document.getElementById('order-status-chart-data');
            if (statusEl) {
                var statusData = JSON.parse(statusEl.textContent);
                var statusChartEl = document.querySelector('#orderStatusChart');
                if (statusChartEl) {
                    new ApexCharts(statusChartEl, {
                        series: statusData.series,
                        chart: {
                            type: 'donut',
                            height: 300,
                            fontFamily: 'Outfit, sans-serif',
                        },
                        labels: statusData.labels,
                        legend: {
                            position: 'bottom',
                            fontFamily: 'Outfit',
                        },
                        colors: ['#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#22c55e', '#ef4444', '#6b7280'],
                        dataLabels: {
                            formatter: function (val, opts) {
                                return opts.w.config.series[opts.seriesIndex];
                            }
                        },
                        tooltip: {
                            y: {
                                formatter: function (val) {
                                    return val + ' pesanan';
                                }
                            }
                        }
                    }).render();
                }
            }
        });
    </script>
@endpush
