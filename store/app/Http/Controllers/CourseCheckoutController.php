<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\WaNotification;
use App\Services\Installment\InstallmentReminder;
use App\Services\Settings;
use App\Services\XSenderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        return view('pages.courses.checkout', [
            'course' => $course,
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
            // Email opsional, KECUALI pendaftaran datang dari link referral (form
            // atau cookie) — sisi affiliate butuh email untuk atribusi komisi.
            'customer_email' => [
                Rule::requiredIf(fn () => filled($request->input('ref_code')) || filled($request->cookie('referral_code'))),
                'nullable', 'email', 'max:120',
            ],
            'customer_phone' => ['required', 'string', 'max:30'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'motivation' => ['nullable', 'string', 'max:500'],
            'payment_type' => ['required', 'in:lunas,cicilan'],
            // Cicilan bebas: customer isi nominal DP (bebas), sisa dicicil kapan saja.
            'dp_amount' => ['nullable', 'required_if:payment_type,cicilan', 'integer', 'min:1', 'max:'.(int) $course->price],
            'ref_code' => ['nullable', 'string', 'max:64'],
        ], [
            'customer_name.required' => 'Nama lengkap wajib diisi.',
            'customer_email.required' => 'Email wajib diisi untuk pendaftaran via link referral.',
            'customer_email.email' => 'Format email tidak valid.',
            'customer_phone.required' => 'Nomor WhatsApp wajib diisi.',
            'payment_type.required' => 'Pilih metode pembayaran.',
            'dp_amount.required_if' => 'Isi jumlah DP yang dibayar sekarang.',
            'dp_amount.min' => 'DP minimal Rp 1.',
            'dp_amount.max' => 'DP tidak boleh melebihi harga kelas.',
        ]);

        // Cicilan bebas: nominal DP dari customer (0 untuk lunas).
        $dpAmount = $validated['payment_type'] === 'cicilan' ? (int) $validated['dp_amount'] : 0;

        $order = DB::transaction(function () use ($validated, $course, $dpAmount, $request) {
            // Referral code: input form override, fallback ke cookie affiliate
            $refCode = $validated['ref_code'] ?? $request->cookie('referral_code');

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'email' => $validated['customer_email'] ?? null,
                'address' => '',
                'total' => (int) $course->price,
                'unique_code' => Order::generateUniqueCode((int) $course->price),
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

            // Cicilan bebas: buat pembayaran DP saja (kode unik dibebankan ke DP).
            $this->generatePaymentSchedule($order, (int) $course->price, $validated['payment_type'], $dpAmount);

            // Data tambahan + penanda cicilan bebas di order_meta (dipakai halaman
            // upload/track & InstallmentReminder untuk mode free-form tanpa jadwal).
            $meta = [
                'occupation' => $validated['occupation'] ?? '',
                'motivation' => $validated['motivation'] ?? '',
            ];
            if ($validated['payment_type'] === 'cicilan') {
                $meta['installment'] = [
                    'free_form' => true,
                    'dp' => $dpAmount,
                ];
            }
            $order->update(['order_meta' => $meta]);

            return $order;
        });

        // Kirim notifikasi WhatsApp ke customer
        $uploadUrl = $this->generateUploadUrl($order);
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
        $isFreeForm = (bool) data_get($orderModel->order_meta, 'installment.free_form');
        $isCicilan = $payments->count() > 1 || $isFreeForm;
        $firstPayment = $payments->first();

        // Yang HARUS ditransfer sekarang: DP (cicilan) atau total penuh (lunas).
        $totalTransfer = (int) ($firstPayment->amount ?? $orderModel->total);

        // Jadwal tetap HANYA untuk model lama (>1 payment terjadwal). Cicilan bebas
        // (free-form): tanpa jadwal / jatuh tempo — sisa dibayar bebas kapan saja.
        $schedule = [];
        if ($isCicilan && ! $isFreeForm) {
            $interval = (int) data_get($orderModel->order_meta, 'installment.interval_days', 30);
            foreach ($payments->values() as $i => $payment) {
                $schedule[] = [
                    'label' => $i === 0 ? 'DP — bayar sekarang' : 'Cicilan ke-'.$i,
                    'due_label' => $i === 0 ? 'Sekarang' : 'H+'.($i * $interval),
                    'amount' => (int) $payment->amount,
                ];
            }
        }

        // Sisa tagihan untuk cicilan bebas (payableTotal − terverifikasi).
        $verified = (int) $payments->where('status', 'verified')->sum('amount');
        $remaining = max(0, $orderModel->payableTotal() - $verified);

        return view('pages.courses.checkout-success', [
            'course' => $course,
            'order' => $orderModel,
            'bankAccounts' => Settings::getBankAccounts(),
            'waAdmin' => Settings::getWaAdmin(),
            'uploadUrl' => session('upload_url', $this->generateUploadUrl($orderModel)),
            'trackUrl' => $this->generateTrackUrl($orderModel->order_number),
            'isCicilan' => $isCicilan,
            'isFreeForm' => $isFreeForm,
            'paymentType' => $isCicilan ? 'cicilan' : 'lunas',
            'totalTransfer' => $totalTransfer,
            'schedule' => $schedule,
            'remaining' => $remaining,
        ]);
    }

    /**
     * Generate pembayaran awal berdasarkan payment_type.
     * Lunas   = 1 record sebesar payableTotal (total + kode unik).
     * Cicilan = 1 record DP (nominal bebas dari customer + kode unik). Sisa
     *           dibayar bebas kapan saja lewat halaman upload (tanpa jadwal/tempo).
     */
    protected function generatePaymentSchedule(Order $order, int $total, string $paymentType, int $dpAmount): void
    {
        if ($paymentType === 'cicilan') {
            // DP saja — kode unik dibebankan ke DP (transfer pertama) supaya
            // nominal khas & gampang dicocokkan admin.
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $dpAmount + (int) $order->unique_code,
                'method' => 'transfer',
                'status' => 'pending',
            ]);
        } else {
            // Lunas — single payment sebesar total + kode unik (payableTotal).
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $order->payableTotal(),
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
     * Generate signed upload URL untuk customer upload bukti bayar. TTL
     * schedule-aware: untuk order cicilan, link hidup sampai angsuran terakhir
     * jatuh tempo (+ grace) — bukan cuma 7 hari yang keburu mati sebelum
     * angsuran ditagih. Order lunas/buku tetap pakai TTL default.
     */
    protected function generateUploadUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'upload.show',
            app(InstallmentReminder::class)->uploadUrlExpiry($order),
            ['order_number' => $order->order_number],
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
