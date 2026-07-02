<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\InstallmentScheme;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\WaNotification;
use App\Services\Settings;
use App\Services\XSenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * CourseCheckoutController — checkout flow khusus pendaftaran kelas.
 *
 * Berbeda dengan book checkout:
 *   - Form = formulir pendaftaran kelas (nama, email, phone, alamat, pekerjaan, motivasi)
 *   - Tidak ada cart (single course per checkout)
 *   - Order number format: COURSE-YYYYMMDD-XXX-XXXXXX
 *   - Setelah checkout → kirim WA otomatis (detail kelas + info pembayaran + rekening)
 *   - Tidak ada shipping
 */
class CourseCheckoutController extends Controller
{
    /**
     * Tampilkan form pendaftaran kelas.
     */
    public function create(string $slug): View
    {
        $course = Course::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        // Hanya skema CICILAN nyata: harus ada sisa yang dicicil (dp_pct < 100).
        // Skema "lunas" (dp 100%) sudah diwakili opsi "Bayar Lunas" terpisah —
        // jangan tampil lagi di daftar cicilan (membingungkan).
        $schemes = InstallmentScheme::active()
            ->forCourse($course->id)
            ->where('dp_pct', '<', 100)
            ->orderBy('n_installments')
            ->get();

        return view('pages.courses.checkout', [
            'course' => $course,
            'schemes' => $schemes,
        ]);
    }

    /**
     * Proses pendaftaran kelas.
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $course = Course::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'motivation' => ['nullable', 'string', 'max:500'],
            'payment_type' => ['required', 'in:lunas,cicilan'],
            'installment_scheme_id' => ['nullable', 'required_if:payment_type,cicilan', 'integer', 'exists:installment_schemes,id'],
            'ref_code' => ['nullable', 'string', 'max:64'],
        ], [
            'customer_name.required' => 'Nama lengkap wajib diisi.',
            'customer_email.required' => 'Email wajib diisi.',
            'customer_email.email' => 'Format email tidak valid.',
            'customer_phone.required' => 'Nomor WhatsApp wajib diisi.',
            'payment_type.required' => 'Pilih metode pembayaran.',
            'installment_scheme_id.required_if' => 'Pilih skema cicilan.',
        ]);

        // Resolve installment scheme if cicilan
        $scheme = null;
        if ($validated['payment_type'] === 'cicilan' && ! empty($validated['installment_scheme_id'])) {
            $scheme = InstallmentScheme::active()
                ->forCourse($course->id)
                ->where('dp_pct', '<', 100) // hanya skema cicilan nyata (lihat create())
                ->where('id', $validated['installment_scheme_id'])
                ->firstOrFail();
        }

        $order = DB::transaction(function () use ($validated, $course, $scheme, $request) {
            // Referral code: input form override, fallback ke cookie affiliate
            $refCode = $validated['ref_code'] ?? $request->cookie('referral_code');

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'email' => $validated['customer_email'],
                'address' => '',
                'total' => (int) $course->price,
                'status' => 'pending',
                'ref_code' => $refCode ?: null,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'course_id' => $course->id,
                'product_id' => null,
                'qty' => 1,
                'unit_price' => (int) $course->price,
                'subtotal' => (int) $course->price,
            ]);

            // Generate payment schedule
            $this->generatePaymentSchedule($order, (int) $course->price, $validated['payment_type'], $scheme);

            // Simpan data tambahan + snapshot skema cicilan di order_meta.
            // Snapshot dipakai halaman success untuk menampilkan jadwal (interval)
            // tanpa bergantung pada scheme yang bisa berubah/terhapus nanti.
            $meta = [
                'occupation' => $validated['occupation'] ?? '',
                'motivation' => $validated['motivation'] ?? '',
            ];
            if ($scheme) {
                $meta['installment'] = [
                    'scheme_name' => $scheme->name,
                    'dp_pct' => (float) $scheme->dp_pct,
                    'n_installments' => (int) $scheme->n_installments,
                    'interval_days' => (int) $scheme->interval_days,
                ];
            }
            $order->update(['order_meta' => $meta]);

            return $order;
        });

        // Kirim notifikasi WhatsApp ke customer
        $uploadUrl = $this->generateUploadUrl($order->order_number);
        $this->sendWhatsAppNotification($order, $course, $validated, $uploadUrl);

        return redirect()
            ->route('courses.checkout.success', ['slug' => $course->slug, 'order' => $order->order_number])
            ->with('status', 'Pendaftaran berhasil! Cek WhatsApp untuk detail pembayaran.')
            ->with('upload_url', $uploadUrl);
    }

    /**
     * Halaman sukses setelah checkout kelas.
     */
    public function success(string $slug, string $order): View
    {
        $course = Course::where('slug', $slug)->firstOrFail();
        $orderModel = Order::where('order_number', $order)->firstOrFail();
        $orderModel->load(['payments' => fn ($q) => $q->orderBy('id')]);

        $payments = $orderModel->payments;
        $isCicilan = $payments->count() > 1;
        $firstPayment = $payments->first();

        // Yang HARUS ditransfer sekarang: DP (cicilan) atau total penuh (lunas).
        $totalTransfer = (int) ($firstPayment->amount ?? $orderModel->total);

        // Jadwal pembayaran cicilan (DP + tiap angsuran) — interval dari snapshot
        // skema di order_meta (default 30 hari bila tak ada).
        $schedule = [];
        if ($isCicilan) {
            $interval = (int) data_get($orderModel->order_meta, 'installment.interval_days', 30);
            foreach ($payments->values() as $i => $payment) {
                $schedule[] = [
                    'label' => $i === 0 ? 'DP — bayar sekarang' : 'Cicilan ke-'.$i,
                    'due_label' => $i === 0 ? 'Sekarang' : 'H+'.($i * $interval),
                    'amount' => (int) $payment->amount,
                ];
            }
        }

        return view('pages.courses.checkout-success', [
            'course' => $course,
            'order' => $orderModel,
            'bankAccounts' => Settings::getBankAccounts(),
            'waAdmin' => Settings::getWaAdmin(),
            'uploadUrl' => session('upload_url', $this->generateUploadUrl($order)),
            'trackUrl' => $this->generateTrackUrl($orderModel->order_number),
            'isCicilan' => $isCicilan,
            'paymentType' => $isCicilan ? 'cicilan' : 'lunas',
            'totalTransfer' => $totalTransfer,
            'schedule' => $schedule,
        ]);
    }

    /**
     * Generate payment schedule berdasarkan payment_type.
     * Lunas = 1 record full amount.
     * Cicilan = DP + n_installments sesuai scheme.
     */
    protected function generatePaymentSchedule(Order $order, int $total, string $paymentType, ?InstallmentScheme $scheme): void
    {
        if ($paymentType === 'cicilan' && $scheme) {
            // DP amount
            $dpAmount = (int) ceil($total * ((float) $scheme->dp_pct / 100));
            $remaining = $total - $dpAmount;
            $perInstallment = (int) ceil($remaining / $scheme->n_installments);

            // DP payment
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $dpAmount,
                'method' => 'transfer',
                'status' => 'pending',
            ]);

            // Installment payments
            for ($i = 1; $i <= $scheme->n_installments; $i++) {
                // Last installment absorbs rounding difference
                $amount = ($i === $scheme->n_installments)
                    ? $remaining - ($perInstallment * ($scheme->n_installments - 1))
                    : $perInstallment;

                OrderPayment::create([
                    'order_id' => $order->id,
                    'amount' => max(0, $amount),
                    'method' => 'transfer',
                    'status' => 'pending',
                ]);
            }
        } else {
            // Lunas — single payment
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'method' => 'transfer',
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Generate order number format: COURSE-YYYYMMDD-XXX-XXXXXX.
     * XXX = 3 char random, XXXXXX = 6 char random uppercase.
     */
    protected function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $prefix = strtoupper(Str::random(3));
            $suffix = strtoupper(Str::random(6));
            $candidate = 'COURSE-'.now()->format('Ymd').'-'.$prefix.'-'.$suffix;

            if (! Order::where('order_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'COURSE-'.now()->format('Ymd').'-'.strtoupper(Str::random(10));
    }

    /**
     * Kirim WA ke customer: detail kelas + info pembayaran + rekening + link upload.
     */
    protected function sendWhatsAppNotification(Order $order, Course $course, array $data, string $uploadUrl): void
    {
        $bankAccounts = Settings::getBankAccounts();

        // Format rekening
        $rekeningText = '';
        foreach ($bankAccounts as $acc) {
            $rekeningText .= "• {$acc['bank']} - {$acc['number']} (a.n {$acc['holder']})\n";
        }

        if (empty($rekeningText)) {
            $rekeningText = "(Rekening belum dikonfigurasi)\n";
        }

        // Payment info based on type
        $payments = $order->payments()->orderBy('id')->get();
        $isCicilan = $payments->count() > 1;
        $firstPayment = $payments->first();

        $paymentInfoText = "💰 *Detail Pembayaran*\n"
            .'Total: Rp '.number_format((int) $course->price, 0, ',', '.')."\n";

        if ($isCicilan) {
            $paymentInfoText .= "Metode: Cicilan ({$payments->count()}x pembayaran)\n"
                .'DP (Bayar Sekarang): Rp '.number_format((int) $firstPayment->amount, 0, ',', '.')."\n"
                ."Status: Menunggu DP\n";
        } else {
            $paymentInfoText .= "Metode: Transfer Bank (Lunas)\n"
                ."Status: Menunggu Pembayaran\n";
        }

        $message = "🎓 *PENDAFTARAN KELAS BERHASIL*\n\n"
            ."Halo {$data['customer_name']},\n"
            ."Terima kasih sudah mendaftar! Berikut detail pesanan kamu:\n\n"
            ."━━━━━━━━━━━━━━━━━━━━\n"
            ."📋 *Detail Kelas*\n"
            ."Kelas: {$course->title}\n"
            ."Order ID: {$order->order_number}\n\n"
            .$paymentInfoText."\n"
            ."🏦 *Rekening Pembayaran*\n"
            .$rekeningText
            ."\n━━━━━━━━━━━━━━━━━━━━\n\n"
            ."📤 *Upload Bukti Bayar:*\n"
            .$uploadUrl."\n\n"
            ."⚠️ *Penting:*\n"
            ."• Lakukan pembayaran dalam 1x24 jam.\n"
            ."• Upload bukti transfer via link di atas.\n"
            ."• Konfirmasi otomatis akan dikirim setelah diverifikasi.\n\n"
            .'Terima kasih! 🙏';

        try {
            // Record ke DB via WhatsappNotifier (akan otomatis kirim via XSender)
            // Override: kita kirim langsung pakai custom message yang lebih lengkap
            $xsender = app(XSenderService::class);
            $result = $xsender->send($data['customer_phone'], $message);

            // Record ke wa_notifications untuk tracking
            WaNotification::create([
                'order_id' => $order->id,
                'recipient' => $data['customer_phone'],
                'template' => 'course_registration_success',
                'payload_json' => [
                    'customer_name' => $data['customer_name'],
                    'order_number' => $order->order_number,
                    'course_title' => $course->title,
                    'amount' => number_format((int) $course->price, 0, ',', '.'),
                ],
                'status' => $result['ok'] ? 'sent' : 'failed',
                'sent_at' => $result['ok'] ? now() : null,
                'error' => $result['ok'] ? null : mb_substr($result['body'] ?? '', 0, 500),
            ]);
        } catch (\Throwable $e) {
            // Don't fail checkout if WA fails — log only
            Log::warning('[CourseCheckout] WA notification failed', [
                'order' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate signed upload URL untuk customer upload bukti bayar.
     * TTL = 7 hari (sama dengan book checkout).
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

    /**
     * Signed URL untuk halaman lacak order (TTL lebih panjang, default 30 hari).
     */
    protected function generateTrackUrl(string $orderNumber): string
    {
        $ttlDays = max(1, (int) config('checkout.track_url_ttl_days', 30));

        return URL::temporarySignedRoute(
            'track.show',
            now()->addDays($ttlDays),
            ['order_number' => $orderNumber],
        );
    }
}
