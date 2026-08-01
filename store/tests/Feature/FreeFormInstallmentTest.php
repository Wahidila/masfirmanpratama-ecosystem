<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Course;
use App\Models\Order;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Cicilan bebas (free-form): DP nominal bebas saat checkout, lalu customer
 * menambah pembayaran bebas kapan saja & berapa saja lewat halaman upload —
 * tanpa jadwal/jatuh tempo. Peserta baru enroll saat lunas.
 */
class FreeFormInstallmentTest extends TestCase
{
    use RefreshDatabase;

    private function course(int $price = 1_000_000): Course
    {
        return Course::factory()->active()->create(['slug' => 'kelas-x', 'title' => 'Kelas X', 'price' => $price]);
    }

    private function checkoutCicilan(int $dp = 300_000): Order
    {
        $this->post('/kelas/kelas-x/checkout', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'payment_type' => 'cicilan',
            'dp_amount' => $dp,
        ]);

        return Order::firstOrFail();
    }

    public function test_checkout_cicilan_creates_single_dp_payment_marked_free_form(): void
    {
        $this->course();
        $order = $this->checkoutCicilan(300_000);

        $this->assertTrue((bool) data_get($order->order_meta, 'installment.free_form'));
        $this->assertSame(300_000, (int) data_get($order->order_meta, 'installment.dp'));
        $this->assertSame(1, $order->payments()->count());
        $this->assertSame(300_000 + $order->unique_code, (int) $order->payments()->first()->amount);
    }

    public function test_cicilan_requires_dp_amount(): void
    {
        $this->course();

        $this->post('/kelas/kelas-x/checkout', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'payment_type' => 'cicilan',
            // dp_amount hilang
        ])->assertSessionHasErrors('dp_amount');

        $this->assertSame(0, Order::count());
    }

    public function test_customer_can_add_freeform_payment_of_any_amount(): void
    {
        Storage::fake('public');
        $this->course();
        $order = $this->checkoutCicilan(300_000);

        $url = URL::temporarySignedRoute('upload.store', now()->addDay(), ['order_number' => $order->order_number]);
        $this->post($url, [
            'new_payment_amount' => 250_000,
            'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $order->refresh();
        $this->assertSame(2, $order->payments()->count());
        $new = $order->payments()->orderByDesc('id')->first();
        $this->assertSame('250000.00', $new->amount);
        $this->assertSame('pending', $new->status);
        $this->assertNotNull($new->proof_path);
    }

    public function test_admin_can_remind_when_freeform_still_has_remaining(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        $this->course(1_000_000);
        $order = $this->checkoutCicilan(300_000);

        // Verifikasi DP → masih ada sisa (belum lunas).
        $dp = $order->payments()->first();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.payments.approve', [$order, $dp]));

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Kirim Reminder Cicilan')            // tombol reminder muncul
            ->assertDontSee('Semua cicilan sudah lunas');    // BUKAN "sudah lunas"
    }

    public function test_admin_order_page_has_track_button(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        $this->course(1_000_000);
        $order = $this->checkoutCicilan(300_000);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Halaman Lacak');
    }

    public function test_track_page_shows_cicilan_remaining_after_approval(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        $this->course(1_000_000);
        $order = $this->checkoutCicilan(300_000);

        // Admin verifikasi DP → status & sisa terupdate.
        $dp = $order->payments()->first();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.payments.approve', [$order, $dp]));

        // Halaman lacak menampilkan ringkasan cicilan + sisa (Rp 700.000).
        $this->get(URL::signedRoute('track.show', ['order_number' => $order->order_number]))
            ->assertOk()
            ->assertSee('Total Tagihan')
            ->assertSee('Sisa')
            ->assertSee('Rp '.number_format(700_000, 0, ',', '.'));
    }

    public function test_participant_enrolls_only_after_freeform_paid_in_full(): void
    {
        $this->seed(AdminSeeder::class);
        $admin = Admin::first();
        Storage::fake('public');
        $this->course(1_000_000);
        $order = $this->checkoutCicilan(400_000);

        // Verifikasi DP (400rb + kode) → belum lunas → belum jadi peserta.
        $dp = $order->payments()->first();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.payments.approve', [$order, $dp]));
        $this->assertSame(0, \App\Models\CourseParticipant::count());

        // Customer tambah pembayaran sisa (600rb) → admin verifikasi → lunas.
        $url = URL::temporarySignedRoute('upload.store', now()->addDay(), ['order_number' => $order->order_number]);
        $this->post($url, [
            'new_payment_amount' => 600_000,
            'proof_file' => UploadedFile::fake()->image('bukti2.jpg'),
        ]);
        $second = $order->payments()->orderByDesc('id')->first();
        $this->actingAs($admin, 'admin')->post(route('admin.orders.payments.approve', [$order, $second]));

        $this->assertSame(1, \App\Models\CourseParticipant::count());
        $this->assertSame('lunas', \App\Models\CourseParticipant::first()->payment_status);
    }
}
