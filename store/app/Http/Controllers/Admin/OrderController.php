<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * Status enum sesuai migration orders. Source of truth: DB schema.
     */
    public const STATUSES = [
        'pending',
        'partial_paid',
        'paid',
        'shipped',
        'completed',
        'cancelled',
        'refunded',
    ];

    public function index(Request $request): View
    {
        $filterStatus = $request->query('status');
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = Order::query()->latest('created_at');

        if (in_array($filterStatus, self::STATUSES, true)) {
            $query->where('status', $filterStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($dateFrom = $this->parseDate($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $this->parseDate($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $orders = $query->paginate(25)->withQueryString();

        // Stats: total + breakdown per status
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'partial_paid' => Order::where('status', 'partial_paid')->count(),
            'paid' => Order::where('status', 'paid')->count(),
            'shipped' => Order::where('status', 'shipped')->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'refunded' => Order::where('status', 'refunded')->count(),
        ];

        return view('admin.orders.index', [
            'orders' => $orders,
            'stats' => $stats,
            'filterStatus' => $filterStatus,
            'search' => $search,
            'dateFrom' => $request->query('date_from'),
            'dateTo' => $request->query('date_to'),
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Show order detail with items, payments, customer info.
     */
    public function show(Order $order): View
    {
        $order->load([
            'items' => fn ($q) => $q->orderBy('id'),
            'items.product',
            'payments' => fn ($q) => $q->orderBy('created_at'),
            'payments.verifier',
        ]);

        $totalPaid = (float) $order->payments
            ->where('status', 'verified')
            ->sum('amount');
        $totalPending = (float) $order->payments
            ->where('status', 'pending')
            ->sum('amount');
        $totalRejected = (float) $order->payments
            ->where('status', 'rejected')
            ->sum('amount');
        $remaining = max(0, (float) $order->total - $totalPaid);

        return view('admin.orders.show', [
            'order' => $order,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'totalRejected' => $totalRejected,
            'remaining' => $remaining,
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Parse YYYY-MM-DD ke Carbon, atau null kalau invalid/empty.
     */
    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
