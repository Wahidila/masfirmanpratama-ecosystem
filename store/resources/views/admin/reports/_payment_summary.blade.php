@php
    $paymentStatusLabels = [
        'verified' => 'Terverifikasi',
        'pending' => 'Menunggu Verifikasi',
        'rejected' => 'Ditolak',
    ];
    $paymentStatusColors = [
        'verified' => '#10b981',
        'pending' => '#f59e0b',
        'rejected' => '#ef4444',
    ];
    $totalPaymentCount = array_sum(array_map(fn ($s) => $paymentSummary['counts'][$s] ?? 0, array_keys($paymentStatusLabels)));
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Ringkasan Pembayaran</h3>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $totalPaymentCount }} transaksi</span>
    </div>
    <div class="p-5">
        {{-- Total verified revenue highlight --}}
        <div class="mb-5 rounded-xl bg-brand-50 p-4 dark:bg-brand-500/10">
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Pembayaran Terverifikasi</p>
            <p class="mt-1 text-2xl font-bold text-brand-600 dark:text-brand-400">
                Rp {{ number_format((float) ($paymentSummary['totals']['verified'] ?? 0), 0, ',', '.') }}
            </p>
        </div>

        {{-- Breakdown per status --}}
        <div class="space-y-3">
            @foreach ($paymentStatusLabels as $status => $label)
                @php
                    $count = $paymentSummary['counts'][$status] ?? 0;
                    $total = (float) ($paymentSummary['totals'][$status] ?? 0);
                    $percentage = $totalPaymentCount > 0 ? round(($count / $totalPaymentCount) * 100) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-block w-3 h-3 rounded-full" style="background: {{ $paymentStatusColors[$status] }}"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        </div>
                        <span class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $count }}</span>
                    </div>
                    <div class="flex items-center justify-between pl-5">
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            Rp {{ number_format($total, 0, ',', '.') }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $percentage }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
