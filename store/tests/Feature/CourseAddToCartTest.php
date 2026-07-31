<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstallmentScheme;
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

    /** Checkout cicilan → success page menonjolkan DP (bukan total penuh) + jadwal. */
    public function test_cicilan_checkout_success_shows_dp_and_schedule(): void
    {
        $course = $this->createCourse();
        $scheme = InstallmentScheme::factory()->forCourse($course)->create([
            'name' => '12x Cicilan',
            'dp_pct' => 15,
            'n_installments' => 12,
            'interval_days' => 30,
            'active' => true,
        ]);

        $redirect = $this->post(route('courses.checkout.store', $course->slug), [
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@contoh.com',
            'customer_phone' => '08123456789',
            'payment_type' => 'cicilan',
            'installment_scheme_id' => $scheme->id,
        ]);
        $redirect->assertRedirect();

        $success = $this->get($redirect->headers->get('Location'));
        $success->assertStatus(200);
        $success->assertSee('Pendaftaran berhasil', false);
        // DP 15% dari 4.5jt = 675.000 ditransfer sekarang (bukan 4.500.000 penuh).
        $success->assertSee('Rp 675.000');
        $success->assertSee('Transfer sekarang (DP)');
        // Jadwal cicilan tampil.
        $success->assertSee('Jadwal pembayaran');
        $success->assertSee('Cicilan ke-1');
        $success->assertSee('H+30');

        // Snapshot skema tersimpan di order_meta (dipakai success untuk interval).
        $order = Order::where('email', 'budi@contoh.com')->firstOrFail();
        $this->assertSame(30, (int) data_get($order->order_meta, 'installment.interval_days'));
        $this->assertSame(12, (int) data_get($order->order_meta, 'installment.n_installments'));
        // DP + 12 cicilan = 13 payment records.
        $this->assertSame(13, $order->payments()->count());
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
        $success->assertSee('Rp 4.500.000');
        $success->assertDontSee('Jadwal pembayaran');

        $order = Order::where('email', 'siti@contoh.com')->firstOrFail();
        $this->assertSame(1, $order->payments()->count());
    }
}
