@php
    $chartData = [
        'categories' => $dailyRevenue['categories'],
        'series' => $dailyRevenue['series'],
    ];
@endphp

<section class="mt-6">
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white px-5 pt-5 sm:px-6 sm:pt-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                    Tren Revenue Harian
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Berdasarkan pembayaran terverifikasi
                </p>
            </div>
        </div>
        <div class="max-w-full overflow-x-auto">
            <div id="revenueChart" class="-ml-5 h-full min-w-[500px] pl-2 xl:min-w-full"></div>
        </div>
    </div>
    <script type="application/json" id="revenue-chart-data">@json($chartData)</script>
</section>
