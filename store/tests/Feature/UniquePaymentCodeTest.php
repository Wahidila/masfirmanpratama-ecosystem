<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstallmentScheme;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kode unik pembayaran: nominal transfer = total + kode unik (1–999), dibebankan
 * ke pembayaran pertama (lunas: satu-satunya row; cicilan: DP).
 */
class UniquePaymentCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lunas_checkout_bakes_unique_code_into_payment(): void
    {
        Course::factory()->active()->create(['slug' => 'kelas-amc-reguler', 'price' => 4_500_000]);

        $this->post('/checkout', [
            'customer_name' => 'Budi',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 12',
            'address_city' => 'Malang',
            'address_province' => 'Jawa Timur',
            'payment_type' => 'lunas',
            'cart_json' => json_encode([['slug' => 'kelas-amc-reguler', 'name' => 'Kelas', 'price' => 4_500_000, 'qty' => 1]]),
            'cart_total' => 4_500_000,
        ]);

        $order = Order::first();
        $this->assertGreaterThanOrEqual(1, $order->unique_code);
        $this->assertLessThanOrEqual(999, $order->unique_code);
        $this->assertSame((int) $order->total + $order->unique_code, $order->payableTotal());

        $payment = OrderPayment::where('order_id', $order->id)->first();
        $this->assertSame(number_format($order->payableTotal(), 2, '.', ''), $payment->amount);
    }

    public function test_course_cicilan_dp_carries_unique_code_and_plan_sums_to_payable(): void
    {
        $course = Course::factory()->active()->create(['slug' => 'kelas-amc-reguler', 'price' => 1_000_000]);
        $scheme = InstallmentScheme::create([
            'course_id' => $course->id,
            'name' => 'DP 30% + 2x',
            'dp_pct' => 30,
            'n_installments' => 2,
            'interval_days' => 30,
            'active' => true,
        ]);

        $this->post('/kelas/kelas-amc-reguler/checkout', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'payment_type' => 'cicilan',
            'installment_scheme_id' => $scheme->id,
        ]);

        $order = Order::first();
        $payments = $order->payments()->orderBy('id')->get();

        // DP = ceil(1jt * 30%) + kode unik.
        $this->assertSame(300_000 + $order->unique_code, (int) $payments->first()->amount);
        // Total seluruh row = total + kode unik (payableTotal).
        $this->assertSame($order->payableTotal(), (int) $payments->sum('amount'));
    }

    public function test_generate_unique_code_returns_valid_range(): void
    {
        $code = Order::generateUniqueCode(1_000_000);
        $this->assertGreaterThanOrEqual(1, $code);
        $this->assertLessThanOrEqual(999, $code);
    }

    public function test_payable_total_falls_back_to_total_when_no_code(): void
    {
        $order = Order::factory()->create(['total' => 500_000, 'unique_code' => null]);
        $this->assertSame(500_000, $order->payableTotal());
    }
}
