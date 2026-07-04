@php
    $statusLabels = [
        'pending' => 'Pending',
        'partial_paid' => 'Cicilan',
        'paid' => 'Lunas',
        'shipped' => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
        'refunded' => 'Refund',
    ];
    $statusColors = [
        'pending' => '#f59e0b',
        'partial_paid' => '#8b5cf6',
        'paid' => '#10b981',
        'shipped' => '#3b82f6',
        'completed' => '#22c55e',
        'cancelled' => '#ef4444',
        'refunded' => '#6b7280',
    ];
    $totalOrders = array_sum(array_map(fn ($s) => $orderStatusBreakdown[$s] ?? 0, array_keys($statusLabels)));
    $statusChartData = [
        'labels' => collect(array_keys($statusLabels))->map(fn ($s) => $statusLabels[$s])->values(),
        'series' => collect(array_keys($statusLabels))->map(fn ($s) => $orderStatusBreakdown[$s] ?? 0)->values(),
        'colors' => collect(array_keys($statusLabels))->map(fn ($s) => $statusColors[$s])->values(),
    ];
@endphp

{{-- Card wrapper — seimbang dengan card "Produk Terlaris" di sebelahnya. --}}
<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Status Pesanan</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $totalOrders }} pesanan</span>
    </div>
    <div class="p-5">
        @if ($totalOrders === 0)
            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada pesanan pada periode ini.
            </p>
        @else
            <div id="orderStatusChart" class="h-72"></div>
            <script type="application/json" id="order-status-chart-data">@json($statusChartData)</script>
        @endif

        <div class="mt-4 space-y-2">
            @foreach ($statusLabels as $status => $label)
                @php $count = $orderStatusBreakdown[$status] ?? 0; @endphp
                {{-- Drill-down: klik baris → daftar pesanan terfilter status tsb. --}}
                <a href="{{ route('admin.orders.index', ['status' => $status]) }}"
                   class="flex items-center justify-between rounded-lg px-2 py-1 text-sm transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full" style="background: {{ $statusColors[$status] }}"></span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-gray-800 dark:text-white/90">{{ $count }}</span>
                        @if ($totalOrders > 0)
                            <span class="text-xs text-gray-400">({{ number_format(($count / $totalOrders) * 100, 1) }}%)</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
