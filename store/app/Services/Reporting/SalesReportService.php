<?php

namespace App\Services\Reporting;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesReportService
{
    /**
     * Order statuses sesuai migration schema.
     */
    public const ORDER_STATUSES = [
        'pending',
        'partial_paid',
        'paid',
        'shipped',
        'completed',
        'cancelled',
        'refunded',
    ];

    /**
     * Statuses yang dianggap menghasilkan revenue (lunas / terkirim / selesai).
     */
    public const REVENUE_STATUSES = ['paid', 'shipped', 'completed'];

    /**
     * Revenue summary: total revenue, order count, AOV, verified payment total.
     *
     * Revenue = sum of OrderPayment where status='verified' (within date range).
     * Also includes order-based revenue (sum of order.total for REVENUE_STATUSES)
     * for comparison and dashboard display.
     *
     * @return array{
     *     revenue: float,
     *     order_revenue: float,
     *     orders_count: int,
     *     aov: float,
     *     verified_payments_total: float,
     *     pending_payments_total: float,
     *     from: Carbon,
     *     to: Carbon,
     * }
     */
    public function revenueSummary(Carbon $from, Carbon $to): array
    {
        // Revenue dari pembayaran terverifikasi (uang masuk riil).
        $verifiedQuery = OrderPayment::where('status', 'verified')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()]);

        $verifiedPaymentsTotal = (float) (clone $verifiedQuery)->sum('amount');
        $verifiedPaymentsCount = (int) (clone $verifiedQuery)->count();

        // Revenue dari order yang lunas/dikirim/selesai (akrual).
        $orderRevenue = (float) Order::whereIn('status', self::REVENUE_STATUSES)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('total');

        $ordersCount = (int) Order::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])->count();

        $pendingPaymentsTotal = (float) OrderPayment::where('status', 'pending')
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount');

        return [
            'revenue' => $verifiedPaymentsTotal,
            'order_revenue' => $orderRevenue,
            'orders_count' => $ordersCount,
            'aov' => $ordersCount > 0 ? $orderRevenue / $ordersCount : 0.0,
            'verified_payments_total' => $verifiedPaymentsTotal,
            'verified_payments_count' => $verifiedPaymentsCount,
            'pending_payments_total' => $pendingPaymentsTotal,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Daily revenue series untuk chart line/bar.
     *
     * @return array{categories: list<string>, series: list<array{name: string, data: list<float>>>, raw: list<array{date: string, revenue: float, orders: int}>}
     */
    public function dailyRevenue(Carbon $from, Carbon $to): array
    {
        $period = CarbonPeriod::create($from->startOfDay(), $to->endOfDay());

        // Jika range > 90 hari, switch ke monthly aggregation.
        if ($period->count() > 91) {
            return $this->monthlyRevenue($from, $to);
        }

        $raw = OrderPayment::where('status', 'verified')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as revenue')
            ->groupByRaw('DATE(paid_at)')
            ->pluck('revenue', 'date');

        $ordersPerDay = Order::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')
            ->groupByRaw('DATE(created_at)')
            ->pluck('cnt', 'date');

        $categories = [];
        $revenueData = [];
        $ordersData = [];
        $rawOut = [];

        foreach ($period as $day) {
            $dateKey = $day->format('Y-m-d');
            $categories[] = $day->format('d M');
            $rev = (float) ($raw[$dateKey] ?? 0);
            $ord = (int) ($ordersPerDay[$dateKey] ?? 0);
            $revenueData[] = $rev;
            $ordersData[] = $ord;
            $rawOut[] = ['date' => $dateKey, 'revenue' => $rev, 'orders' => $ord];
        }

        return [
            'categories' => $categories,
            'series' => [
                ['name' => 'Revenue', 'data' => $revenueData],
                ['name' => 'Pesanan', 'data' => array_map(fn ($v) => (float) $v, $ordersData)],
            ],
            'raw' => $rawOut,
        ];
    }

    /**
     * Monthly revenue series untuk range panjang (>90 hari).
     *
     * @return array{categories: list<string>, series: list<array{name: string, data: list<float>>>, raw: list<array{date: string, revenue: float, orders: int}>}
     */
    public function monthlyRevenue(Carbon $from, Carbon $to): array
    {
        // copy() WAJIB — Carbon mutable; tanpa copy, startOfMonth/endOfMonth
        // mengubah $from/$to milik caller → query berikutnya (top products,
        // status, payment summary) diam-diam memakai range yang melebar.
        $start = $from->copy()->startOfMonth();
        $end = $to->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, '1 month', $end);

        // Ekspresi grup per-bulan portable: SQLite (dev/test) pakai strftime,
        // MySQL/MariaDB (produksi) pakai DATE_FORMAT.
        $monthExpr = fn (string $col) => DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$col})"
            : "DATE_FORMAT({$col}, '%Y-%m')";

        $raw = OrderPayment::where('status', 'verified')
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw($monthExpr('paid_at').' as month, SUM(amount) as revenue')
            ->groupByRaw($monthExpr('paid_at'))
            ->pluck('revenue', 'month');

        $ordersPerMonth = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw($monthExpr('created_at').' as month, COUNT(*) as cnt')
            ->groupByRaw($monthExpr('created_at'))
            ->pluck('cnt', 'month');

        $categories = [];
        $revenueData = [];
        $ordersData = [];
        $rawOut = [];

        foreach ($period as $month) {
            $monthKey = $month->format('Y-m');
            $categories[] = $month->translatedFormat('M Y');
            $rev = (float) ($raw[$monthKey] ?? 0);
            $ord = (int) ($ordersPerMonth[$monthKey] ?? 0);
            $revenueData[] = $rev;
            $ordersData[] = (float) $ord;
            $rawOut[] = ['date' => $monthKey, 'revenue' => $rev, 'orders' => $ord];
        }

        return [
            'categories' => $categories,
            'series' => [
                ['name' => 'Revenue', 'data' => $revenueData],
                ['name' => 'Pesanan', 'data' => $ordersData],
            ],
            'raw' => $rawOut,
        ];
    }

    /**
     * Top products by revenue (sum of order_items.subtotal).
     *
     * Hanya menghitung order yang tidak cancelled/refunded.
     *
     * @param  int  $limit  Jumlah produk teratas
     * @return Collection<int, array{product_id: int, title: string, slug: string, qty: int, revenue: float}>
     */
    public function topProducts(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        $excludedStatuses = ['cancelled', 'refunded'];

        return OrderItem::query()
            ->whereHas('order', function (Builder $q) use ($excludedStatuses, $from, $to) {
                $q->whereNotIn('status', $excludedStatuses)
                    ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);
            })
            ->whereNotNull('product_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select([
                'products.id as product_id',
                'products.title',
                'products.slug',
                DB::raw('SUM(order_items.qty) as qty_sold'),
                DB::raw('SUM(order_items.subtotal) as revenue'),
            ])
            ->groupBy('products.id', 'products.title', 'products.slug')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'product_id' => (int) $row->product_id,
                'title' => $row->title,
                'slug' => $row->slug,
                'qty' => (int) $row->qty_sold,
                'revenue' => (float) $row->revenue,
            ]);
    }

    /**
     * Order status breakdown untuk pie chart.
     *
     * @return array<string, int> Map of status => count
     */
    public function orderStatusBreakdown(Carbon $from, Carbon $to): array
    {
        $counts = Order::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $breakdown = [];
        foreach (self::ORDER_STATUSES as $status) {
            $breakdown[$status] = (int) ($counts[$status] ?? 0);
        }

        return $breakdown;
    }

    /**
     * Payment summary: verified vs pending vs rejected.
     *
     * @return array{
     *     verified: array{count: int, total: float},
     *     pending: array{count: int, total: float},
     *     rejected: array{count: int, total: float},
     * }
     */
    public function paymentSummary(Carbon $from, Carbon $to): array
    {
        $baseQuery = fn () => OrderPayment::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()]);

        return [
            'verified' => [
                'count' => (int) $baseQuery()->where('status', 'verified')->count(),
                'total' => (float) $baseQuery()->where('status', 'verified')->sum('amount'),
            ],
            'pending' => [
                'count' => (int) $baseQuery()->where('status', 'pending')->count(),
                'total' => (float) $baseQuery()->where('status', 'pending')->sum('amount'),
            ],
            'rejected' => [
                'count' => (int) $baseQuery()->where('status', 'rejected')->count(),
                'total' => (float) $baseQuery()->where('status', 'rejected')->sum('amount'),
            ],
        ];
    }

    /**
     * Build CSV rows for export as a generator.
     *
     * @return \Generator<int, string, void, void>
     */
    public function csvRows(Carbon $from, Carbon $to): \Generator
    {
        // Header
        yield 'Tanggal,Order Number,Customer,Status,Total,Qty Items,Payment Status,Payment Method,Payment Amount';

        $orders = Order::with(['items', 'payments'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderBy('created_at')
            ->cursor();

        foreach ($orders as $order) {
            $qtyItems = $order->items->sum('qty');
            $payment = $order->payments->firstWhere('status', 'verified')
                ?? $order->payments->first();

            $row = [
                $order->created_at?->format('Y-m-d H:i'),
                $this->csvEscape($order->order_number),
                $this->csvEscape($order->customer_name),
                $order->status,
                number_format((float) $order->total, 2, '.', ''),
                $qtyItems,
                $payment?->status ?? '-',
                $payment?->method ?? '-',
                $payment ? number_format((float) $payment->amount, 2, '.', '') : '0.00',
            ];

            yield implode(',', $row);
        }
    }

    /**
     * Human-readable label untuk status order.
     */
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'partial_paid' => 'Cicilan',
            'paid' => 'Lunas',
            'shipped' => 'Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Batal',
            'refunded' => 'Refund',
            default => ucfirst($status),
        };
    }

    /**
     * Escape value for CSV (RFC 4180 — wrap in quotes if contains comma/quote/newline).
     */
    private function csvEscape(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n") || str_contains($value, "\r")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
