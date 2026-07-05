<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Halaman upload bukti bayar state-aware untuk order cicilan: default ke
 * angsuran berikutnya yang belum lunas (bukan DP yang sudah dibayar), tandai
 * yang lunas, dan tampilkan nominal yang benar.
 */
class UploadInstallmentPageTest extends TestCase
{
    use RefreshDatabase;

    private function signedShow(string $orderNumber, array $query = []): string
    {
        return URL::temporarySignedRoute(
            'upload.show',
            now()->addDays(7),
            array_merge(['order_number' => $orderNumber], $query),
        );
    }

    /** @param list<string> $statuses */
    private function cicilanOrder(array $statuses): Order
    {
        $order = Order::create([
            'order_number' => 'COURSE-20260705-ABC-DEF123',
            'customer_name' => 'Customer Test',
            'phone' => '081234567890',
            'address' => '',
            'total' => 7_500_000,
            'status' => 'partial_paid',
        ]);

        foreach ($statuses as $i => $status) {
            OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $i === 0 ? 2_250_000 : 1_750_000, // DP 30% + 3×
                'method' => 'transfer',
                'status' => $status,
                'proof_path' => $status === 'verified' ? 'payment-proofs/x.jpg' : null,
                'paid_at' => $status === 'verified' ? now() : null,
            ]);
        }

        return $order->fresh();
    }

    public function test_defaults_to_next_unpaid_installment_after_dp_paid(): void
    {
        $order = $this->cicilanOrder(['verified', 'pending', 'pending', 'pending']);

        $response = $this->get($this->signedShow($order->order_number))->assertOk();

        // Alpine default = seq 1 (Cicilan ke-1), bukan 0 (DP).
        $response->assertSee('defaultSequence: 1', false);
        // Nominal yang ditampilkan = angsuran (1.75jt), bukan DP (2.25jt).
        $response->assertSee('Rp 1.750.000');
        // DP ditandai lunas + tidak bisa dipilih.
        $response->assertSee('sudah lunas', false);
        $response->assertSee('value="0" disabled', false);
        // Teks stub M2 lama sudah hilang.
        $response->assertDontSee('login admin di M2');
        $response->assertSee('Terpilih otomatis ke pembayaran berikutnya');
    }

    public function test_fresh_order_defaults_to_dp(): void
    {
        $order = $this->cicilanOrder(['pending', 'pending', 'pending', 'pending']);

        $response = $this->get($this->signedShow($order->order_number))->assertOk();

        $response->assertSee('defaultSequence: 0', false);
        $response->assertSee('Rp 2.250.000'); // DP amount
        // DP masih bisa dipilih (belum lunas).
        $response->assertDontSee('value="0" disabled', false);
    }

    public function test_explicit_seq_query_is_respected(): void
    {
        $order = $this->cicilanOrder(['verified', 'pending', 'pending', 'pending']);

        $this->get($this->signedShow($order->order_number, ['seq' => 2]))
            ->assertOk()
            ->assertSee('defaultSequence: 2', false);
    }
}
