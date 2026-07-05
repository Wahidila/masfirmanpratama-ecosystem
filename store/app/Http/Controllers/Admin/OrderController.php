<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderCompleted;
use App\Events\OrderRefunded;
use App\Events\OrderShipped;
use App\Events\PaymentRejected;
use App\Events\PaymentVerified;
use App\Http\Controllers\Controller;
use App\Http\Controllers\TrackController;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Installment\InstallmentReminder;
use App\Services\Settings;
use App\Services\Shipping\AgenwebsiteClient;
use App\Services\WhatsappNotifier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
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
     * Status precondition yang valid untuk transition ke 'shipped'.
     * Schema enum source-of-truth: 'paid' = lunas terverifikasi, siap kirim.
     */
    public const SHIPPABLE_FROM = ['paid'];

    /**
     * Status precondition yang valid untuk transition ke 'refunded'.
     * Order bisa di-refund dari paid, partial_paid, shipped, atau completed.
     */
    public const REFUNDABLE_FROM = ['paid', 'partial_paid', 'shipped', 'completed'];

    /**
     * Status precondition yang valid untuk transition ke 'completed'.
     * Hanya order yang sudah 'shipped' yang bisa ditandai selesai (paket harus
     * dikirim dulu). Untuk alur resi-manual yang tak dapat callback AWB, ini
     * jalan admin menutup siklus order secara eksplisit.
     */
    public const COMPLETABLE_FROM = ['shipped'];

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
    public function show(Order $order, InstallmentReminder $reminder): View
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

        // Ringkasan cicilan untuk kartu "Cicilan" + tombol Reminder Cicilan.
        $installment = null;
        if ($reminder->isInstallment($order)) {
            $installment = [
                'schedule' => $reminder->schedule($order),
                'next_due' => $reminder->nextDue($order),
                'remaining' => $reminder->remaining($order),
                'paid_count' => $reminder->paidCount($order),
                'total_count' => $reminder->totalCount($order),
                'can_remind' => $reminder->hasOutstanding($order)
                    && ! in_array($order->status, ['cancelled', 'refunded'], true),
            ];
        }

        return view('admin.orders.show', [
            'order' => $order,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'totalRejected' => $totalRejected,
            'remaining' => $remaining,
            'installment' => $installment,
            'statuses' => self::STATUSES,
            'couriers' => $this->courierOptions($order),
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
            // Backfill paid_at bila belum ada (mis. admin approve tanpa customer
            // upload bukti) — laporan revenue di-bucket by paid_at; NULL = tak
            // pernah terhitung di Total Revenue/chart selamanya.
            $payment->paid_at ??= now();
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
    public function markShipped(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            ! in_array($order->status, self::SHIPPABLE_FROM, true),
            422,
            'Order belum siap kirim. Status sekarang: '.$order->status
                .'. Hanya status berikut yang bisa di-shipped: '.implode(', ', self::SHIPPABLE_FROM).'.',
        );

        $validated = $request->validate([
            'shipping_courier' => ['required', 'string', Rule::in(array_keys($this->courierOptions($order)))],
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
     * Tandai order 'shipped' → 'completed' secara manual oleh admin.
     *
     * Alasan: sejak resi diinput manual (auto-shipment mati), order tak menerima
     * callback AWB 'delivered' → tanpa aksi ini order macet di 'shipped' selamanya.
     * Tombol ini menutup siklus + memicu OrderCompleted (WA terima kasih ke pembeli).
     *
     * Precondition: status harus salah satu dari self::COMPLETABLE_FROM ('shipped').
     * pending / paid (belum kirim) / completed (sudah) / cancelled / refunded → 422.
     */
    public function markCompleted(Request $request, Order $order): RedirectResponse
    {
        abort_if(
            ! in_array($order->status, self::COMPLETABLE_FROM, true),
            422,
            'Order belum bisa diselesaikan. Status sekarang: '.$order->status
                .'. Hanya status berikut yang bisa diselesaikan: '.implode(', ', self::COMPLETABLE_FROM).'.',
        );

        $justCompleted = false;

        DB::transaction(function () use ($order, $request, &$justCompleted) {
            // Audit jejak penyelesaian manual di order_meta (tanpa perlu kolom baru).
            $meta = $order->order_meta ?? [];
            $meta['completed_at'] = now()->toIso8601String();
            $meta['completed_by'] = $request->user('admin')?->id;
            $meta['completed_manually'] = true;
            $order->order_meta = $meta;

            // markCompleted() mem-persist status, fulfillment_status, + order_meta
            // di atas sekaligus (save menyertakan semua atribut dirty).
            $justCompleted = $order->markCompleted();
        });

        // WA terima kasih ke pembeli — hanya sekali saat transisi ke completed.
        if ($justCompleted) {
            OrderCompleted::dispatch($order->fresh());
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order ditandai selesai. Notifikasi WhatsApp terima kasih dikirim ke pembeli.');
    }

    /**
     * Opsi B (non-blocking): cek apakah resi manual sudah terdeteksi di sistem
     * kurir lewat endpoint tracking Agenwebsite. TIDAK menolak/mengubah resi —
     * hanya indikator terdeteksi/belum + refresh tracking_status (order manual
     * tak dapat callback AWB, jadi ini satu-satunya cara status trackingnya ke-update).
     *
     * Catatan: API tidak punya validator resi khusus; "belum terdeteksi" bisa
     * berarti resi salah ATAU benar tapi belum discan kurir — makanya jangan
     * dijadikan hard-block.
     */
    public function checkResi(Order $order): JsonResponse
    {
        if (! $order->shipping_resi || ! $order->shipping_courier) {
            return response()->json([
                'ok' => false,
                'detected' => false,
                'message' => 'Order belum memiliki kurir & nomor resi.',
            ], 422);
        }

        try {
            $history = AgenwebsiteClient::fromConfig()->tracking(
                $order->shipping_resi,
                strtolower(trim((string) $order->shipping_courier)),
                TrackController::phoneVerification($order->phone),
            );
        } catch (\Throwable) {
            $history = [];
        }

        $history = is_array($history) ? array_values($history) : [];
        $detected = $history !== [];

        $latestStatus = null;
        if ($detected) {
            $last = $history[count($history) - 1];
            $latestStatus = is_array($last)
                ? ($last['status'] ?? $last['desc'] ?? $last['description'] ?? null)
                : null;

            // Simpan status terbaru → tampil di detail admin & halaman tracking customer.
            if (is_string($latestStatus) && $latestStatus !== '') {
                $order->forceFill(['tracking_status' => $latestStatus])->save();
            }
        }

        // Auto-complete: kalau order masih 'shipped' dan status kurir sudah
        // "delivered", tutup siklus otomatis (sama seperti webhook AWB, tapi untuk
        // resi manual yang tak dapat callback). Guard COMPLETABLE_FROM cegah
        // transisi dari status yang tak valid.
        $autoCompleted = false;
        if (in_array($order->status, self::COMPLETABLE_FROM, true)
            && stripos((string) $latestStatus, 'deliver') !== false) {
            $autoCompleted = $order->markCompleted();
            if ($autoCompleted) {
                OrderCompleted::dispatch($order->fresh());
            }
        }

        return response()->json([
            'ok' => true,
            'detected' => $detected,
            'status' => $latestStatus,
            'completed' => $autoCompleted,
            'history_count' => count($history),
            'message' => $autoCompleted
                ? 'Resi terdeteksi DELIVERED — order otomatis ditandai selesai.'
                : ($detected
                    ? 'Resi terdeteksi di sistem kurir.'
                    : 'Resi belum terdeteksi di sistem kurir — kemungkinan belum discan. Coba lagi nanti.'),
        ]);
    }

    /**
     * Opsi kurir untuk dropdown "Tandai Dikirim", SINKRON dengan kurir aktif
     * (Settings 'shipping.couriers', fallback config('shipping.couriers')).
     * Nilai = courier_id (mis. 'jne') supaya konsisten dengan yang disimpan saat
     * customer checkout & dipakai halaman tracking; label dari config
     * 'shipping.courier_labels' (id tak dikenal → strtoupper).
     *
     * Kurir yang SUDAH tersimpan di order (pilihan customer) selalu disertakan
     * walau tidak lagi aktif — supaya bisa jadi default terpilih & tetap valid
     * saat form disubmit.
     *
     * @return array<string, string> [courier_id => label]
     */
    protected function courierOptions(Order $order): array
    {
        $active = Settings::get('shipping.couriers', config('shipping.couriers', []));
        $active = is_array($active) ? $active : [];
        $labels = (array) config('shipping.courier_labels', []);

        $options = [];
        foreach ($active as $id) {
            $id = (string) $id;
            if ($id === '') {
                continue;
            }
            $options[$id] = $labels[$id] ?? strtoupper($id);
        }

        // Kurir pilihan customer harus selalu ada sebagai opsi (default select).
        $current = trim((string) ($order->shipping_courier ?? ''));
        if ($current !== '' && ! isset($options[$current])) {
            $options[$current] = $labels[$current] ?? strtoupper($current);
        }

        return $options;
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

    /**
     * Kirim pengingat cicilan ke customer via WhatsApp: status tiap angsuran,
     * tagihan berikutnya + jatuh tempo, sisa, rekening, dan link upload bukti
     * bayar yang baru (signed). Manual dari tombol "Reminder Cicilan" di detail
     * order — tiap klik membuat notifikasi baru (upload URL selalu segar).
     *
     * Guard: hanya untuk order cicilan yang masih ada angsuran belum lunas.
     */
    public function remindInstallment(Order $order, InstallmentReminder $reminder, WhatsappNotifier $notifier): RedirectResponse
    {
        $order->loadMissing(['items.course', 'payments']);

        abort_if(! $reminder->isInstallment($order), 422, 'Order ini bukan pesanan cicilan.');
        // Mirror the view-side can_remind guard: never dun a cancelled/refunded
        // order (refund() flips status but leaves angsuran pending, so a direct
        // POST would otherwise still pass hasOutstanding()).
        abort_if(in_array($order->status, ['cancelled', 'refunded'], true), 422,
            'Order sudah '.$order->status.' — reminder cicilan tidak dikirim.');
        abort_if(! $reminder->hasOutstanding($order), 422, 'Semua cicilan sudah lunas — tidak ada yang perlu diingatkan.');

        if (trim((string) $order->phone) === '') {
            return back()->with('error', 'Order tidak punya nomor WhatsApp — reminder tidak bisa dikirim.');
        }

        $notification = $notifier->send(
            'customer_installment_reminder',
            (string) $order->phone,
            $this->installmentReminderPayload($order, $reminder),
            $order,
        );

        $message = match ($notification->status) {
            'sent' => 'Reminder cicilan berhasil dikirim via WhatsApp.',
            'failed' => 'Gagal mengirim reminder: '.($notification->error ?: 'error tidak diketahui').'.',
            default => 'Reminder cicilan masuk antrean kirim (gateway WhatsApp belum dikonfigurasi).',
        };

        return redirect()
            ->route('admin.orders.show', $order)
            ->with($notification->status === 'failed' ? 'error' : 'status', $message);
    }

    /**
     * Susun payload template `customer_installment_reminder`: rincian tiap
     * angsuran, tagihan berikutnya, sisa, rekening, dan upload URL baru.
     *
     * @return array<string, string>
     */
    protected function installmentReminderPayload(Order $order, InstallmentReminder $reminder): array
    {
        $courseTitle = $order->items->first(fn ($item) => $item->course_id !== null)?->course?->title
            ?? 'Kelas';

        $statusText = [
            'verified' => '✅ Lunas',
            'pending' => '⏳ Belum bayar',
            'rejected' => '❌ Ditolak — upload ulang',
        ];

        $progress = collect($reminder->schedule($order))
            ->map(fn (array $s) => $s['label'].': Rp '.number_format($s['amount'], 0, ',', '.')
                .' — '.($statusText[$s['status']] ?? $s['status']))
            ->implode("\n");

        $next = $reminder->nextDue($order);
        $nextDue = '—';
        if ($next) {
            $nextDue = $next['label'].' — Rp '.number_format($next['amount'], 0, ',', '.');
            if ($next['due_date']) {
                $nextDue .= "\nJatuh tempo: ".$next['due_date']->translatedFormat('d M Y');
                if ($next['overdue_days'] > 0) {
                    $nextDue .= ' (⚠️ lewat '.$next['overdue_days'].' hari)';
                }
            }
        }

        $banks = collect(Settings::getBankAccounts())
            ->map(fn (array $a) => '• '.($a['bank'] ?? '').' - '.($a['number'] ?? '').' (a.n '.($a['holder'] ?? '').')')
            ->implode("\n");
        if ($banks === '') {
            $banks = '(Rekening belum dikonfigurasi)';
        }

        return [
            'customer_name' => (string) $order->customer_name,
            'course_title' => (string) $courseTitle,
            'order_number' => (string) $order->order_number,
            'progress' => $progress,
            'next_due' => $nextDue,
            'remaining' => number_format($reminder->remaining($order), 0, ',', '.'),
            'bank_accounts' => $banks,
            'upload_url' => $this->generateUploadUrl($order->order_number),
        ];
    }

    /**
     * Signed upload URL untuk customer upload bukti bayar (TTL default 7 hari),
     * konsisten dengan CourseCheckoutController::generateUploadUrl().
     */
    protected function generateUploadUrl(string $orderNumber): string
    {
        $ttlDays = max(1, (int) config('checkout.upload_url_ttl_days', 7));

        return URL::temporarySignedRoute(
            'upload.show',
            now()->addDays($ttlDays),
            ['order_number' => $orderNumber],
        );
    }
}
