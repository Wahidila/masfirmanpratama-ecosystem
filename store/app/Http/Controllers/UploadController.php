<?php

namespace App\Http\Controllers;

use App\Events\PaymentSubmitted;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * UploadController — customer upload bukti bayar (task t_c0616c67).
 *
 * Flow:
 *   1. show() — render upload page. Kalau order_number ada di DB, hydrate
 *      dengan real Order + list pending payments. Kalau ngga, fallback ke
 *      M1 stub view (dummy state) supaya prototype lama tetep jalan.
 *   2. store() — validate file (image, max 2MB), match payment by sequence
 *      ke pending payment di order, save file ke storage, update payment
 *      record (proof_path + paid_at), recompute order.status berdasarkan
 *      verifikasi existing.
 *
 * Schema decision (pitfall ref #11): order_payments.status enum =
 * pending|verified|rejected. Task body sebut 'pending_verification' yang
 * ngga ada — pakai schema source-of-truth: payment yang udah upload tetep
 * status='pending' (waiting admin verify) — bukti exist via proof_path
 * not null + paid_at not null. Order.status awalnya 'pending', bisa
 * stay 'pending' (waiting verify) atau 'partial_paid' kalau ada payment
 * verified sebelumnya. Task body sebut 'payment_review' yang ngga ada di
 * orders enum — order.status TIDAK transition saat upload, hanya saat
 * admin verify (logic existing di OrderController::approvePayment).
 *
 * Auth: signed URL dari CheckoutController (TTL 24h). show() & store()
 * di-protect via 'signed' middleware. Task t_8a063559 bakal harden lebih
 * lanjut (token-based, expiry policy).
 */
class UploadController extends Controller
{
    /**
     * Render upload page. Resolve real Order kalau ada di DB.
     */
    public function show(Request $request, string $order_number): View
    {
        $order = Order::with(['payments' => fn ($q) => $q->orderBy('id')])
            ->where('order_number', $order_number)
            ->first();

        if (! $order) {
            // Fallback ke M1 stub view (dummy state, query string-driven).
            return $this->m1StubView($request, $order_number);
        }

        $pendingPayments = $order->payments
            ->where('status', 'pending')
            ->whereNull('proof_path')
            ->values();

        // Determine payment context dari real Order.
        $totalPayments = $order->payments->count();
        $isInstallment = $totalPayments > 1;
        $paymentType = $isInstallment ? 'cicilan' : 'lunas';

        // Default ke pending payment pertama (atau seq dari query string kalau valid).
        $defaultSequence = (int) $request->query('seq', 0);
        $defaultSequence = max(0, min($totalPayments - 1, $defaultSequence));

        // Total transfer = nominal payment yang sedang di-target.
        $targetPayment = $order->payments[$defaultSequence] ?? null;
        $totalTransfer = $targetPayment ? (int) $targetPayment->amount : 0;

        return view('pages.upload', [
            'orderNumber' => $order_number,
            'paymentType' => $paymentType,
            'totalTransfer' => $totalTransfer,
            'totalPayments' => $totalPayments,
            'defaultSequence' => $defaultSequence,
            'dbOrder' => $order,
            'pendingPayments' => $pendingPayments,
        ]);
    }

    /**
     * Save bukti bayar — file upload + DB update.
     */
    public function store(Request $request, string $order_number): RedirectResponse
    {
        $validated = $request->validate([
            'proof_file' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // KB → 2 MB
            ],
            'installment_sequence' => ['nullable', 'integer', 'min:0', 'max:23'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'proof_file.required' => 'Pilih file bukti transfer dulu sebelum mengirim.',
            'proof_file.image' => 'File harus berupa gambar (JPG, PNG, atau WebP).',
            'proof_file.mimes' => 'Format tidak didukung. Pakai JPG, PNG, atau WebP.',
            'proof_file.max' => 'Ukuran file terlalu besar. Maksimal 2 MB.',
        ]);

        $order = Order::where('order_number', $order_number)->first();

        if (! $order) {
            // Order ngga ada di DB — bisa jadi prototype-flow lama (signed URL
            // tanpa real order). Fallback ke behavior M1 stub: success flash
            // tanpa save, supaya mockup tetep work.
            return $this->m1StubFlashRedirect($request, $order_number);
        }

        $sequence = (int) ($validated['installment_sequence'] ?? 0);

        // Match payment by sequence (0-indexed sesuai FE) ke pending payment.
        // payments udah ke-order by id (insertion order = sequence).
        $payments = $order->payments()->orderBy('id')->get();

        $payment = $payments[$sequence] ?? null;

        if (! $payment) {
            throw ValidationException::withMessages([
                'installment_sequence' => 'Pembayaran cicilan ke-'.($sequence + 1).' tidak ditemukan untuk order ini.',
            ]);
        }

        if ($payment->status !== 'pending') {
            throw ValidationException::withMessages([
                'installment_sequence' => 'Pembayaran ini sudah diproses sebelumnya (status: '.$payment->status.').',
            ]);
        }

        if ($payment->proof_path) {
            throw ValidationException::withMessages([
                'proof_file' => 'Bukti untuk pembayaran ini sudah pernah diupload. Hubungi admin kalau perlu ganti.',
            ]);
        }

        // Save file ke storage/app/public/payment-proofs/<order_number>/<payment_id>-<random>.<ext>
        // Random suffix biar ngga overwrite kalau ada race condition + filename
        // unpredictable (anti-enumeration via storage URL).
        $file = $request->file('proof_file');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = sprintf(
            '%d-%s.%s',
            $payment->id,
            Str::random(8),
            $ext,
        );
        $path = $file->storeAs(
            'payment-proofs/'.$order_number,
            $filename,
            'public',
        );

        DB::transaction(function () use ($payment, $path) {
            $payment->proof_path = $path;
            $payment->paid_at = now();
            // Note disimpan ke proof metadata kalau ada; column terpisah belum ada
            // di schema, jadi append ke end of proof_path metadata? Untuk M2,
            // cukup kalau note ngga di-persist (out-of-scope schema change).
            // Status TETEP 'pending' — admin verify yang transition ke 'verified'.
            $payment->save();
        });

        // Fire event AFTER commit — listener bisa baca persisted state.
        PaymentSubmitted::dispatch($order->fresh(), $payment->fresh());

        return redirect()
            ->route('upload.show', ['order_number' => $order_number])
            ->with('upload.success', true)
            ->with('upload.sequence', $sequence);
    }

    /**
     * M1 stub view fallback — order_number ngga ada di DB, render dummy state
     * berdasarkan query string. Backward compat dengan prototype lama.
     */
    protected function m1StubView(Request $request, string $order_number): View
    {
        $paymentType = in_array($request->query('type'), ['lunas', 'cicilan'], true)
            ? $request->query('type')
            : 'lunas';

        $totalTransfer = max(0, (int) $request->query('total', 0));

        $totalPayments = (int) $request->query('n', $paymentType === 'cicilan' ? 2 : 1);
        if ($paymentType === 'cicilan') {
            $totalPayments = max(2, min(24, $totalPayments));
        } else {
            $totalPayments = 1;
        }

        $defaultSequence = (int) $request->query('seq', 0);
        $defaultSequence = max(0, min($totalPayments - 1, $defaultSequence));

        return view('pages.upload', [
            'orderNumber' => $order_number,
            'paymentType' => $paymentType,
            'totalTransfer' => $totalTransfer,
            'totalPayments' => $totalPayments,
            'defaultSequence' => $defaultSequence,
            'dbOrder' => null,
            'pendingPayments' => collect(),
        ]);
    }

    /**
     * M1 stub flash redirect — order ngga ada di DB, redirect dengan success
     * flash tanpa save apa pun.
     */
    protected function m1StubFlashRedirect(Request $request, string $order_number): RedirectResponse
    {
        return redirect()
            ->route('upload.show', array_filter([
                'order_number' => $order_number,
                'type' => $request->query('type'),
                'total' => $request->query('total'),
                'n' => $request->query('n'),
                'seq' => $request->query('seq'),
            ]))
            ->with('upload.success', true)
            ->with('upload.sequence', (int) $request->input('installment_sequence', 0));
    }
}
