<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderRefunded;
use App\Events\OrderShipped;
use App\Events\PaymentRejected;
use App\Events\PaymentVerified;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Shipping\FulfillmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Kurir yang didukung untuk input resi.
     */
    public const COURIERS = ['JNE', 'JNT', 'SiCepat', 'Pos', 'Other'];

    /**
     * Status precondition yang valid untuk transition ke 'shipped'.
     * Schema enum source-of-truth: 'paid' = lunas terverifikasi, siap kirim.
     */
    public const SHIPPABLE_FROM = ['paid'];

    /**
     * Status precondition yang valid untuk transition ke 'refunded'.
     * Order bisa di-refund dari paid, partial_paid, shipped, atau completed.
     */
    public const REFUNDABLE_FROM = ['paid', 'partial_paid', 'shipped', 'completed'];

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
            'items.course',
            'payments' => fn ($q) => $q->orderBy('created_at'),
            'payments.verifier',
            'waNotifications' => fn ($q) => $q->orderBy('id'),
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
            'couriers' => self::COURIERS,
            'canShip' => in_array($order->status, self::SHIPPABLE_FROM, true),
            'canRefund' => in_array($order->status, self::REFUNDABLE_FROM, true),
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

    /**
     * Approve payment: set verified, recalculate order status (full vs partial).
     */
    public function approvePayment(Request $request, Order $order, OrderPayment $payment): RedirectResponse
    {
        abort_if($payment->order_id !== $order->id, 404);
        abort_if($payment->status !== 'pending', 422, 'Payment sudah diproses sebelumnya.');

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($order, $payment, $validated, $request) {
            if (isset($validated['amount'])) {
                $payment->amount = $validated['amount'];
            }
            $payment->status = 'verified';
            $payment->verified_at = now();
            $payment->verified_by = $request->user('admin')?->id;
            $payment->rejection_reason = null;
            $payment->save();

            $this->recalcOrderStatus($order);
        });

        // Refresh order untuk dapet status terbaru pasca recalc, lalu fire event
        // → SendCustomerPaymentVerifiedNotification queue WA notif (task t_e5d877f3).
        $order->refresh();
        event(new PaymentVerified($order, $payment));

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Pembayaran berhasil diverifikasi.');
    }

    /**
     * Reject payment: set rejected with reason, order status NOT changed.
     */
    public function rejectPayment(Request $request, Order $order, OrderPayment $payment): RedirectResponse
    {
        abort_if($payment->order_id !== $order->id, 404);
        abort_if($payment->status !== 'pending', 422, 'Payment sudah diproses sebelumnya.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        DB::transaction(function () use ($payment, $validated, $request) {
            $payment->status = 'rejected';
            $payment->rejection_reason = $validated['reason'];
            $payment->verified_at = now();
            $payment->verified_by = $request->user('admin')?->id;
            $payment->save();
        });

        // Fire PaymentRejected event → SendCustomerPaymentRejectedNotification
        // queue WA notif dengan rejection_reason + signed re-upload URL (task t_e5d877f3).
        event(new PaymentRejected($order, $payment, $validated['reason']));

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Pembayaran ditolak dengan alasan tercatat.');
    }

    /**
     * Recompute order.status based on verified payments vs order.total.
     * Cancelled / refunded / shipped+ stay sticky — only mutate from
     * pending/partial_paid/paid (early flow before fulfillment).
     */
    protected function recalcOrderStatus(Order $order): void
    {
        if (in_array($order->status, ['shipped', 'completed', 'cancelled', 'refunded'], true)) {
            return;
        }

        $totalVerified = (float) $order->payments()
            ->where('status', 'verified')
            ->sum('amount');
        $orderTotal = (float) $order->total;

        if ($totalVerified <= 0) {
            $order->status = 'pending';
        } elseif ($totalVerified >= $orderTotal) {
            $order->status = 'paid';
        } else {
            $order->status = 'partial_paid';
        }

        $order->save();
    }

    /**
     * Mark order as shipped — admin input kurir + nomor resi, transition status
     * 'paid' → 'shipped'. Trigger OrderShipped event untuk WA notif downstream
     * (listener belum diimplement, di-fire saja supaya wiring siap).
     *
     * Precondition: order.status harus salah satu dari self::SHIPPABLE_FROM.
     * Default: 'paid' saja — partial_paid / pending / cancelled / refunded /
     * shipped (sudah) / completed (terlalu lanjut) di-reject 422.
     */
    /**
     * Generate shipment via fulfillment API (Agenwebsite).
     * Requires order paid, + shipping_courier + shipping_service set.
     * If already has AWB (fulfillment_status=shipped + resi), skip with info.
     */
    public function generateShipment(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            ! in_array($order->status, self::SHIPPABLE_FROM, true),
            422,
            'Order belum siap kirim. Status sekarang: '.$order->status,
        );

        abort_if(
            ! $order->shipping_courier || ! $order->shipping_service,
            422,
            'Order belum memiliki kurir dan layanan pengiriman.',
        );

        if ($order->fulfillment_status === 'shipped' && $order->shipping_resi) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('info', 'Resi sudah tersedia: '.$order->shipping_resi.'.');
        }

        $service = app(FulfillmentService::class);
        $result = $service->createShipment($order);

        return match ($result['status']) {
            'awb_ready' => redirect()
                ->route('admin.orders.show', $order)
                ->with('status', 'Resi berhasil dibuat: '.$result['tracking_number'].'.'),
            'waiting_awb' => redirect()
                ->route('admin.orders.show', $order)
                ->with('status', 'Menunggu AWB dari sistem.'),
            'pending_payment' => redirect()
                ->route('admin.orders.show', $order)
                ->with('info', 'Menunggu pembayaran pengiriman.'),
            default => redirect()
                ->route('admin.orders.show', $order)
                ->with('error', $result['message'] ?? 'Gagal membuat resi.'),
        };
    }

    public function markShipped(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            ! in_array($order->status, self::SHIPPABLE_FROM, true),
            422,
            'Order belum siap kirim. Status sekarang: '.$order->status
                .'. Hanya status berikut yang bisa di-shipped: '.implode(', ', self::SHIPPABLE_FROM).'.',
        );

        $validated = $request->validate([
            'shipping_courier' => ['required', 'string', 'in:'.implode(',', self::COURIERS)],
            'shipping_resi' => ['required', 'string', 'min:4', 'max:64'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $order->shipping_courier = $validated['shipping_courier'];
            $order->shipping_resi = trim($validated['shipping_resi']);
            $order->shipped_at = now();
            $order->status = 'shipped';
            $order->save();
        });

        // Fire event AFTER commit — listener bisa baca persisted state.
        OrderShipped::dispatch($order->fresh());

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Resi berhasil di-input. Order ditandai sebagai dikirim.');
    }

    /**
     * Refund order: transition status ke 'refunded', fire OrderRefunded event
     * → DispatchAffiliateOrderRefunded listener kirim webhook ke Affiliate
     * untuk cancel komisi yang masih cooling / available.
     *
     * Precondition: order.status harus salah satu dari self::REFUNDABLE_FROM.
     */
    public function refund(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            ! in_array($order->status, self::REFUNDABLE_FROM, true),
            422,
            'Order tidak bisa di-refund. Status sekarang: '.$order->status
                .'. Hanya status berikut yang bisa di-refund: '.implode(', ', self::REFUNDABLE_FROM).'.',
        );

        $order->status = 'refunded';
        $order->save();

        OrderRefunded::dispatch($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order berhasil di-refund.');
    }
}
