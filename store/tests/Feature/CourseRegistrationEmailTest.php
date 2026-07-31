<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Email pendaftaran kelas: opsional, kecuali order via link referral
 * (mirror CheckoutController — atribusi komisi affiliate butuh email).
 */
class CourseRegistrationEmailTest extends TestCase
{
    use RefreshDatabase;

    private function course(): Course
    {
        return Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas AMC Reguler',
            'price' => 4_500_000,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'payment_type' => 'lunas',
            'ref_code' => null,
        ], $overrides);
    }

    public function test_email_optional_for_non_referral_course_registration(): void
    {
        $this->course();
        $payload = $this->payload();
        unset($payload['customer_email']);

        $this->post('/kelas/kelas-amc-reguler/checkout', $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Order::count());
        $this->assertNull(Order::first()->email);
    }

    public function test_email_required_for_referral_course_registration(): void
    {
        $this->course();
        $payload = $this->payload();
        unset($payload['customer_email']);

        $this->withUnencryptedCookie('referral_code', 'HUQJUMKG')
            ->post('/kelas/kelas-amc-reguler/checkout', $payload)
            ->assertSessionHasErrors(['customer_email']);

        $this->assertSame(0, Order::count());
    }
}
