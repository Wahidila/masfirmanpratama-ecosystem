<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackOrderPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Generate signed URL ke /track/{order_number} buat test.
     * Track route protected via 'signed' middleware (task t_8a063559).
     * Pakai dummy order_number (suffix-based heuristic) — Order DB lookup
     * di route closure return null, fallback ke dummy view path (M1 backward
     * compat). Real DB hydrate path di-test di TrackOrderDbHydrateTest.
     */
    private function signedTrack(string $orderNumber): string
    {
        return URL::temporarySignedRoute(
            'track.show',
            now()->addDays(30),
            ['order_number' => $orderNumber],
        );
    }

    // ─── Smoke ─────────────────────────────────────────────────────────────

    public function test_track_page_returns_200(): void
    {
        $this->get($this->signedTrack('MFP-20260516-ABC123'))->assertStatus(200);
    }

    public function test_track_page_uses_store_layout_assets(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-ABC123'));

        $response->assertStatus(200);
        // Vite-injected CSS + JS markers
        $response->assertSeeInOrder(['/build/assets/app-', '.css'], false);
        $response->assertSeeInOrder(['/build/assets/app-', '.js'], false);
        $response->assertSee('csrf-token', false);
        $response->assertSee('unpkg.com/lucide', false);
        // Page chrome (judul tab)
        $response->assertSee('Lacak Pesanan — MFP-20260516-ABC123', false);
    }

    // ─── Header (order number + status badge) ──────────────────────────────

    public function test_track_page_renders_order_number_in_header(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-ABC123'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="order-number"', false);
        $response->assertSee('MFP-20260516-ABC123', false);
        $response->assertSee('Salin nomor pesanan', false);
    }

    public function test_track_page_renders_status_badge(): void
    {
        // 'A' suffix → unpaid
        $response = $this->get($this->signedTrack('MFP-20260516-NEWXA'));
        $response->assertStatus(200);
        $response->assertSee('data-testid="status-badge"', false);
        $response->assertSee('data-status="unpaid"', false);
        $response->assertSee('Menunggu Pembayaran', false);
    }

    public function test_status_badge_changes_per_order_suffix(): void
    {
        $cases = [
            [$this->signedTrack('MFP-DUMMYA'), 'unpaid', 'Menunggu Pembayaran'],
            [$this->signedTrack('MFP-DUMMYC'), 'waiting_confirmation', 'Menunggu Verifikasi'],
            [$this->signedTrack('MFP-DUMMYE'), 'paid', 'Lunas'],
            [$this->signedTrack('MFP-DUMMYG'), 'partial_paid', 'Cicilan Berjalan'],
            [$this->signedTrack('MFP-DUMMYJ'), 'processing', 'Sedang Diproses'],
            [$this->signedTrack('MFP-DUMMYZ'), 'completed', 'Pesanan Selesai'],
        ];

        foreach ($cases as [$url, $status, $needle]) {
            $response = $this->get($url);
            $response->assertStatus(200);
            $response->assertSee('data-status="'.$status.'"', false);
            $response->assertSee($needle, false);
        }
    }

    // ─── Timeline 6 step ───────────────────────────────────────────────────

    public function test_track_page_renders_six_step_timeline(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-ABC123'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="status-timeline"', false);
        // 6 step keys ada di markup (desktop ol + mobile ol = 12 occurrences,
        // tapi assertSee cuma butuh ada).
        $response->assertSee('data-step="created"', false);
        $response->assertSee('data-step="uploaded"', false);
        $response->assertSee('data-step="verified"', false);
        $response->assertSee('data-step="processing"', false);
        $response->assertSee('data-step="shipped"', false);
        $response->assertSee('data-step="completed"', false);
        // Label step
        $response->assertSee('Pesanan Dibuat', false);
        $response->assertSee('Bukti Diupload', false);
        $response->assertSee('Pembayaran Diverifikasi', false);
        $response->assertSee('Pesanan Diproses', false);
        $response->assertSee('Dikirim', false);
        $response->assertSee('Selesai', false);
    }

    // ─── Order items ───────────────────────────────────────────────────────

    public function test_track_page_renders_order_items_block(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-ABC123'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="order-items"', false);
        $response->assertSee('Kelas Reguler AMC', false);
        $response->assertSee('Buku — Mind Power &amp; Life Mastery', false);
        // Total ditampilkan dalam locale Indonesia
        $response->assertSee('Total Pesanan', false);
        $response->assertSee('Rp 4.710.000', false); // 4500000 + 185000 + 25000 ongkir
    }

    // ─── Payment history ───────────────────────────────────────────────────

    public function test_track_page_renders_payment_history_table_for_paid_order(): void
    {
        // 'E' suffix → paid → ada 1 row payment history confirmed
        $response = $this->get($this->signedTrack('MFP-20260516-PAIDE'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="payment-history"', false);
        $response->assertSee('data-testid="payment-history-table"', false);
        $response->assertSee('Pembayaran Lunas', false);
        $response->assertSee('Diverifikasi', false);
        $response->assertSee('Lihat bukti', false);
    }

    public function test_track_page_renders_installment_rows_for_partial_paid_order(): void
    {
        // 'G' suffix → partial_paid → DP confirmed + 2 cicilan pending
        $response = $this->get($this->signedTrack('MFP-20260516-PARTG'));

        $response->assertStatus(200);
        $response->assertSee('Down Payment (30%)', false);
        $response->assertSee('Cicilan ke-1 dari 2', false);
        $response->assertSee('Cicilan ke-2 dari 2', false);
        // Tone untuk pending row
        $response->assertSee('data-status="pending"', false);
        $response->assertSee('data-status="confirmed"', false);
    }

    public function test_track_page_renders_empty_state_when_no_payment(): void
    {
        // 'A' suffix → unpaid → belum ada payment history
        $response = $this->get($this->signedTrack('MFP-20260516-NEWXA'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada bukti bayar yang diupload.', false);
        // CTA upload muncul
        $response->assertSee('/upload/MFP-20260516-NEWXA', false);
        $response->assertSee('Upload bukti bayar', false);
    }

    // ─── Pengiriman (shipment) ─────────────────────────────────────────────

    public function test_track_page_renders_shipment_card_for_processing_order(): void
    {
        // 'J' suffix → processing → shipment card muncul (item fisik = buku)
        $response = $this->get($this->signedTrack('MFP-20260516-PROCJ'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="shipment-card"', false);
        $response->assertSee('JNE Reguler', false);
        $response->assertSee('data-testid="resi-number"', false);
        $response->assertSee('data-testid="tracking-link"', false);
        // Tracking URL ke situs kurir
        $response->assertSee('jne.co.id/id/tracking/trace/awb/JNE', false);
    }

    public function test_track_page_omits_shipment_card_for_unpaid_order(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-NEWXA'));

        $response->assertStatus(200);
        $response->assertDontSee('data-testid="shipment-card"', false);
    }

    // ─── CTA conditional (upload only when payable) ────────────────────────

    public function test_track_page_shows_upload_cta_when_unpaid(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-NEWXA'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="cta-upload"', false);
    }

    public function test_track_page_shows_upload_cta_when_partial_paid(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-PARTG'));

        $response->assertStatus(200);
        $response->assertSee('data-testid="cta-upload"', false);
    }

    public function test_track_page_hides_upload_cta_when_completed(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-DONEZ'));

        $response->assertStatus(200);
        $response->assertDontSee('data-testid="cta-upload"', false);
    }

    // ─── Wa admin link ─────────────────────────────────────────────────────

    public function test_track_page_renders_wa_admin_link(): void
    {
        $response = $this->get($this->signedTrack('MFP-20260516-ABC123'));

        $response->assertStatus(200);
        $waNumber = config('store.wa_admin.number');
        $response->assertSee('https://wa.me/'.$waNumber, false);
        $response->assertSee('Tanya Admin', false);
    }

    // ─── DB-driven status (order nyata) ─────────────────────────────────────

    private function seedTrackOrder(string $status, bool $withProof, string $proofStatus = 'pending'): Order
    {
        $order = Order::create([
            'order_number' => 'MFP-20260701-'.strtoupper(Str::random(6)),
            'customer_name' => 'Track Real',
            'phone' => '081234567890',
            'address' => 'Jl. Test, Malang, Jawa Timur',
            'total' => 165000,
            'status' => $status,
            'shipping_city' => 'Malang',
            'shipping_province' => 'Jawa Timur',
        ]);
        $payment = OrderPayment::create([
            'order_id' => $order->id,
            'amount' => 165000,
            'method' => 'transfer',
            'status' => $proofStatus,
        ]);
        if ($withProof) {
            $payment->proof_path = 'payment-proofs/'.$order->order_number.'/1.jpg';
            $payment->paid_at = now();
            $payment->save();
        }

        return $order;
    }

    public function test_track_status_reflects_real_order_pending_unpaid(): void
    {
        $order = $this->seedTrackOrder('pending', withProof: false);

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('data-status="unpaid"', false)
            ->assertSee('Menunggu Pembayaran', false);
    }

    public function test_track_status_reflects_real_order_proof_uploaded(): void
    {
        $order = $this->seedTrackOrder('pending', withProof: true);

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('data-status="waiting_confirmation"', false)
            ->assertSee('Menunggu Verifikasi', false);
    }

    public function test_track_status_reflects_real_order_paid(): void
    {
        $order = $this->seedTrackOrder('paid', withProof: true, proofStatus: 'verified');

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('data-status="paid"', false);
    }

    public function test_track_status_reflects_real_order_completed(): void
    {
        $order = $this->seedTrackOrder('completed', withProof: true, proofStatus: 'verified');

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('data-status="completed"', false)
            ->assertSee('Pesanan Selesai', false);
    }

    public function test_track_status_reflects_real_order_cancelled(): void
    {
        $order = $this->seedTrackOrder('cancelled', withProof: false);

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('data-status="cancelled"', false)
            ->assertSee('Pesanan Dibatalkan', false);
    }

    public function test_track_items_and_totals_come_from_real_order(): void
    {
        $product = Product::factory()->create([
            'slug' => 'buku-track', 'title' => 'Buku Tracking Test',
            'type' => 'book', 'price' => 150000, 'is_shippable' => true, 'status' => 'active',
        ]);
        $order = Order::create([
            'order_number' => 'MFP-20260701-ITEMS1',
            'customer_name' => 'Item Test', 'phone' => '0812', 'address' => 'Jl. Test',
            'total' => 165000, 'shipping_cost' => 15000, 'status' => 'paid',
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'qty' => 1, 'unit_price' => 150000, 'subtotal' => 150000,
        ]);
        OrderPayment::create([
            'order_id' => $order->id, 'amount' => 165000, 'method' => 'transfer', 'status' => 'verified',
        ]);

        $this->get($this->signedTrack($order->order_number))
            ->assertStatus(200)
            ->assertSee('Buku Tracking Test', false)   // item nyata, bukan dummy "Kelas Reguler AMC"
            ->assertDontSee('Kelas Reguler AMC', false)
            ->assertSee('Rp 165.000', false);           // total nyata
    }
}
