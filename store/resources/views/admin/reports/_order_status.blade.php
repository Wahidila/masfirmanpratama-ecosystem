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

<div id="orderStatusChart" class="h-72"></div>
<script type="application/json" id="order-status-chart-data">@json($statusChartData)</script>

<div class="mt-4 space-y-2">
    @foreach ($statusLabels as $status => $label)
        @php $count = $orderStatusBreakdown[$status] ?? 0; @endphp
        <div class="flex items-center justify-between text-sm">
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
        </div>
    @endforeach
</div>
