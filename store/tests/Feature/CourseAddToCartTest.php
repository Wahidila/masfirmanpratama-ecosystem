<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAddToCartTest extends TestCase
{
    use RefreshDatabase;

    private function createCourse(array $overrides = []): Course
    {
        return Course::factory()->active()->create(array_merge([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas Reguler Alpha Mind Control',
            'price' => 4500000,
            'benefits' => [
                ['icon' => 'star', 'title' => 'Benefit A', 'desc' => 'Desc A'],
            ],
        ], $overrides));
    }

    public function test_course_cta_links_to_course_checkout(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler');
        $response->assertStatus(200);

        $content = $response->getContent();

        // CTA harus link ke /kelas/{slug}/checkout, bukan /checkout (book checkout)
        $this->assertStringContainsString(
            route('courses.checkout', 'kelas-amc-reguler'),
            $content,
            'CTA must link to course checkout, not book checkout'
        );

        // Tidak boleh ada addToCartAndCheckout (kelas tidak masuk cart)
        $this->assertStringNotContainsString(
            'addToCartAndCheckout',
            $content,
            'Course page must not have addToCartAndCheckout function'
        );
    }

    public function test_course_page_does_not_add_to_cart(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Tidak boleh ada $store.cart.add di halaman kelas
        $this->assertStringNotContainsString(
            'store.cart.add',
            $content,
            'Course page must not add items to cart'
        );
    }

    public function test_course_checkout_page_renders(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler/checkout');
        $response->assertStatus(200);

        // Form pendaftaran harus ada
        $response->assertSee('Selesaikan Pendaftaran');
        $response->assertSee('Data Pendaftar');
        $response->assertSee('customer_name', false);
        $response->assertSee('customer_email', false);
        $response->assertSee('customer_phone', false);
    }

    /** Checkout cicilan bebas → success menonjolkan DP (nominal bebas), 1 payment DP, tanpa jadwal. */
    public function test_cicilan_checkout_freeform_dp(): void
    {
        $course = $this->createCourse();

        $redirect = $this->post(route('courses.checkout.store', $course->slug), [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@contoh.com',
            'customer_phone' => '08123456789',
            'payment_type' => 'cicilan',
            'dp_amount' => 1_000_000, // DP nominal bebas
        ]);
        $redirect->assertRedirect();

        $success = $this->get($redirect->headers->get('Location'));
        $success->assertStatus(200);
        $success->assertSee('Pendaftaran berhasil', false);

        $order = Order::where('email', 'budi@contoh.com')->firstOrFail();
        // DP nominal bebas (1jt) + kode unik ditransfer sekarang (bukan 4.5jt penuh).
        $success->assertSee('Rp '.number_format(1_000_000 + $order->unique_code, 0, ',', '.'));
        $success->assertSee('Transfer sekarang (DP)');
        // Cicilan bebas: tanpa jadwal / jatuh tempo.
        $success->assertDontSee('Jadwal pembayaran');

        // Free-form ditandai di order_meta; hanya 1 payment (DP) yang dibuat.
        $this->assertTrue((bool) data_get($order->order_meta, 'installment.free_form'));
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(1_000_000 + $order->unique_code, (int) $order->payments()->first()->amount);
    }

    /** Checkout lunas → success page transfer = total penuh, tanpa jadwal cicilan. */
    public function test_lunas_checkout_success_shows_full_total(): void
    {
        $course = $this->createCourse();

        $redirect = $this->post(route('courses.checkout.store', $course->slug), [
            'customer_name' => 'Siti Aminah',
            'customer_email' => 'siti@contoh.com',
            'customer_phone' => '08987654321',
            'payment_type' => 'lunas',
        ]);
        $redirect->assertRedirect();

        $success = $this->get($redirect->headers->get('Location'));
        $success->assertStatus(200);
        $success->assertSee('Transfer sekarang (Lunas)');

        $order = Order::where('email', 'siti@contoh.com')->firstOrFail();
        // Nominal transfer lunas = total + kode unik.
        $success->assertSee('Rp '.number_format($order->payableTotal(), 0, ',', '.'));
        $success->assertDontSee('Jadwal pembayaran');

        $this->assertSame(1, $order->payments()->count());
    }
}
