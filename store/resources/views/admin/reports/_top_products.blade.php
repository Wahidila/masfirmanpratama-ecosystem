@php
    $maxRevenue = $topProducts->isNotEmpty() ? (float) $topProducts->first()['revenue'] : 1;
    $maxRevenue = $maxRevenue > 0 ? $maxRevenue : 1;
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Produk Terlaris</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">Berdasarkan revenue</span>
    </div>
    <div class="p-5">
        @if ($topProducts->isEmpty())
            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                Belum ada penjualan produk pada periode ini.
            </p>
        @else
            <div class="space-y-4">
                @foreach ($topProducts as $index => $product)
                    @php
                        $percentage = round(((float) $product['revenue'] / $maxRevenue) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-brand-50 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 shrink-0">
                                    {{ $index + 1 }}
                                </span>
                                {{-- Drill-down: klik nama → halaman edit produk. --}}
                                <a href="{{ route('admin.products.edit', $product['slug']) }}"
                                   class="text-sm font-medium text-gray-800 dark:text-white/90 truncate hover:text-brand-600 dark:hover:text-brand-400">
                                    {{ $product['title'] }}
                                </a>
                            </div>
                            <span class="text-sm font-semibold text-gray-800 dark:text-white/90 shrink-0 ml-2">
                                Rp {{ number_format((float) $product['revenue'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $percentage }}%"></div>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0 w-20 text-right">
                                {{ $product['qty'] }} terjual
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
