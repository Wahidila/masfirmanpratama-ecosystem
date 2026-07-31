<?php

namespace App\Http\Controllers;

use App\Events\PaymentSubmitted;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\Installment\InstallmentReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

        $payments = $order->payments; // sudah orderBy('id') = urutan sequence

        $pendingPayments = $payments
            ->where('status', 'pending')
            ->whereNull('proof_path')
            ->values();

        // Determine payment context dari real Order.
        $totalPayments = $payments->count();
        $isInstallment = $totalPayments > 1;
        $paymentType = $isInstallment ? 'cicilan' : 'lunas';

        // Default ke angsuran BERIKUTNYA yang bisa diupload (pending + belum ada
        // bukti) — bukan selalu DP. Kalau customer buka link reminder setelah DP
        // lunas, langsung ke-arah cicilan yang benar. ?seq= eksplisit tetap
        // dihormati (mis. redirect balik setelah upload).
        $explicitSeq = $request->query('seq');
        if ($explicitSeq !== null && $explicitSeq !== '') {
            $defaultSequence = max(0, min($totalPayments - 1, (int) $explicitSeq));
        } else {
            $idx = $payments->search(fn ($p) => $p->status === 'pending' && $p->proof_path === null);
            $defaultSequence = $idx === false ? 0 : (int) $idx;
        }

        // Total transfer = nominal payment yang sedang di-target.
        $targetPayment = $payments[$defaultSequence] ?? null;
        $totalTransfer = $targetPayment ? (int) $targetPayment->amount : 0;

        [$installmentOptions, $installmentAmounts] = $this->installmentOptions($payments, $totalPayments);

        return view('pages.upload', [
            'orderNumber' => $order_number,
            'paymentType' => $paymentType,
            'totalTransfer' => $totalTransfer,
            'totalPayments' => $totalPayments,
            'defaultSequence' => $defaultSequence,
            'dbOrder' => $order,
            'pendingPayments' => $pendingPayments,
            'installmentOptions' => $installmentOptions,
            'installmentAmounts' => $installmentAmounts,
            'uploadStoreUrl' => $this->signedStoreUrl($order_number, $order),
            'trackUrl' => $this->signedTrackUrl($order_number),
        ]);
    }

    /**
     * Build opsi dropdown cicilan + nominal per-sequence untuk halaman upload.
     *
     * Real order → state-aware: tandai mana yang sudah lunas / menunggu verifikasi
     * / ditolak, dan hanya yang 'pending + belum ada bukti' yang bisa dipilih.
     * Stub M1 (payments null) → count-based, semua bisa dipilih (backward compat).
     *
     * @param  Collection<int, OrderPayment>|null  $payments
     * @return array{0: list<array<string, mixed>>, 1: array<int, int>}
     */
    protected function installmentOptions(?Collection $payments, int $totalPayments): array
    {
        $options = [];
        $amounts = [];
        $installmentCount = max(0, $totalPayments - 1);

        for ($i = 0; $i < $totalPayments; $i++) {
            $payment = $payments?->get($i);
            $status = $payment?->status;
            $hasProof = $payment && $payment->proof_path !== null;
            $uploadable = $payment ? ($status === 'pending' && ! $hasProof) : true;

            if ($payment) {
                $amounts[$i] = (int) $payment->amount;
            }

            $statusNote = match (true) {
                $status === 'verified' => 'sudah lunas',
                $status === 'rejected' => 'ditolak — hubungi admin',
                $status === 'pending' && $hasProof => 'menunggu verifikasi',
                default => '',
            };

            $options[] = [
                'value' => $i,
                'label' => $i === 0 ? 'Down Payment (DP)' : 'Cicilan ke-'.$i.' dari '.$installmentCount,
                'note' => $i === 0
                    ? 'Pembayaran pertama dari '.$totalPayments
                    : ($i === $totalPayments - 1 ? 'Cicilan terakhir' : ''),
                'status_note' => $statusNote,
                'uploadable' => $uploadable,
            ];
        }

        return [$options, $amounts];
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
        PaymentSubmitted::dispatch($order->fresh(), $payment->fresh(), $sequence);

        return redirect($this->signedShowUrl($order_number, ['seq' => $sequence], $order))
            ->with('upload.success', true)
            ->with('upload.sequence', $sequence);
    }

    /**
     * Generate signed POST URL ke /upload/{order_number} buat form action.
     * TTL schedule-aware (InstallmentReminder::uploadUrlExpiry) sehingga form
     * expire konsisten dengan show URL — untuk order cicilan, form tetap bisa
     * disubmit sampai angsuran terakhir jatuh tempo, bukan cuma 7 hari.
     */
    protected function signedStoreUrl(string $order_number, ?Order $order = null): string
    {
        return URL::temporarySignedRoute(
            'upload.store',
            app(InstallmentReminder::class)->uploadUrlExpiry($order),
            ['order_number' => $order_number],
        );
    }

    /**
     * Generate signed GET URL kembali ke upload page (redirect after store).
     * TTL schedule-aware, konsisten dengan signedStoreUrl().
     */
    protected function signedShowUrl(string $order_number, array $extraParams = [], ?Order $order = null): string
    {
        return URL::temporarySignedRoute(
            'upload.show',
            app(InstallmentReminder::class)->uploadUrlExpiry($order),
            array_merge(['order_number' => $order_number], $extraParams),
        );
    }

    /**
     * Generate signed GET URL ke /track/{order_number}. TTL config-driven
     * (default 30 days, lebih panjang dari upload supaya customer bisa
     * monitor sampai delivered).
     */
    protected function signedTrackUrl(string $order_number): string
    {
        $ttlDays = max(1, (int) config('checkout.track_url_ttl_days', 30));

        return URL::temporarySignedRoute(
            'track.show',
            now()->addDays($ttlDays),
            ['order_number' => $order_number],
        );
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

        [$installmentOptions, $installmentAmounts] = $this->installmentOptions(null, $totalPayments);

        return view('pages.upload', [
            'orderNumber' => $order_number,
            'paymentType' => $paymentType,
            'totalTransfer' => $totalTransfer,
            'totalPayments' => $totalPayments,
            'defaultSequence' => $defaultSequence,
            'dbOrder' => null,
            'pendingPayments' => collect(),
            'installmentOptions' => $installmentOptions,
            'installmentAmounts' => $installmentAmounts,
            'uploadStoreUrl' => $this->signedStoreUrl($order_number),
            'trackUrl' => $this->signedTrackUrl($order_number),
        ]);
    }

    /**
     * M1 stub flash redirect — order ngga ada di DB, redirect dengan success
     * flash tanpa save apa pun. Pakai signed URL juga supaya consistent
     * dengan flow utama (kalau /upload/{order_number} udah signed-only,
     * fallback redirect ngga boleh balik plain).
     */
    protected function m1StubFlashRedirect(Request $request, string $order_number): RedirectResponse
    {
        $extra = array_filter([
            'type' => $request->query('type'),
            'total' => $request->query('total'),
            'n' => $request->query('n'),
            'seq' => $request->query('seq'),
        ]);

        return redirect($this->signedShowUrl($order_number, $extra))
            ->with('upload.success', true)
            ->with('upload.sequence', (int) $request->input('installment_sequence', 0));
    }
}
