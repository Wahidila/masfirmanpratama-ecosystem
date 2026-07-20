<?php

namespace Tests\Feature\Admin;

use App\Events\PaymentVerified;
use App\Models\Admin;
use App\Models\Course;
use App\Models\CourseParticipant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Services\CourseParticipantSync;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
        $this->course = Course::factory()->create(['title' => 'Kelas AMC Reguler', 'price' => 1_000_000]);
    }

    /** Order kelas + pembayaran terverifikasi sejumlah $verified. */
    private function courseOrder(float $total, float $verified, array $meta = []): Order
    {
        $order = Order::factory()->create([
            'order_number' => 'COURSE-'.fake()->unique()->numerify('########'),
            'customer_name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'phone' => '08123456789',
            'total' => $total,
            'status' => 'pending',
            'order_meta' => $meta,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'course_id' => $this->course->id,
            'qty' => 1,
            'unit_price' => $total,
            'subtotal' => $total,
        ]);

        if ($verified > 0) {
            OrderPayment::factory()->create([
                'order_id' => $order->id,
                'amount' => $verified,
                'status' => 'verified',
            ]);
        }

        return $order->fresh();
    }

    // ─── Aturan sinkronisasi ────────────────────────────────────────────────

    public function test_lunas_course_order_becomes_participant_with_lunas_status(): void
    {
        $order = $this->courseOrder(1_000_000, 1_000_000, ['occupation' => 'Guru', 'motivation' => 'ingin berkembang']);

        $participant = app(CourseParticipantSync::class)->fromOrder($order);

        $this->assertNotNull($participant);
        $this->assertSame('lunas', $participant->payment_status);
        $this->assertSame('registered', $participant->status);
        $this->assertSame('Siti Aminah', $participant->name);
        $this->assertSame('Guru', $participant->occupation);
        $this->assertSame($this->course->id, $participant->course_id);
    }

    public function test_installment_course_order_becomes_participant_with_cicil_status(): void
    {
        $order = $this->courseOrder(1_000_000, 300_000);

        $participant = app(CourseParticipantSync::class)->fromOrder($order);

        $this->assertNotNull($participant);
        $this->assertSame('cicil', $participant->payment_status);
    }

    public function test_unpaid_course_order_does_not_become_participant(): void
    {
        $order = $this->courseOrder(1_000_000, 0);

        $this->assertNull(app(CourseParticipantSync::class)->fromOrder($order));
        $this->assertSame(0, CourseParticipant::count());
    }

    public function test_pending_payment_does_not_count_as_paid(): void
    {
        $order = $this->courseOrder(1_000_000, 0);
        OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 500_000,
            'status' => 'pending', // belum diverifikasi
        ]);

        $this->assertNull(app(CourseParticipantSync::class)->fromOrder($order->fresh()));
        $this->assertSame(0, CourseParticipant::count());
    }

    public function test_non_course_order_is_ignored(): void
    {
        $order = Order::factory()->create(['total' => 200_000]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id,
            'course_id' => null,
            'qty' => 1,
            'unit_price' => 200_000,
            'subtotal' => 200_000,
        ]);
        OrderPayment::factory()->create(['order_id' => $order->id, 'amount' => 200_000, 'status' => 'verified']);

        $this->assertNull(app(CourseParticipantSync::class)->fromOrder($order->fresh()));
    }

    public function test_installment_becomes_lunas_without_duplicating_participant(): void
    {
        $order = $this->courseOrder(1_000_000, 400_000);
        $sync = app(CourseParticipantSync::class);

        $first = $sync->fromOrder($order);
        $this->assertSame('cicil', $first->payment_status);

        // Sisa cicilan dibayar & diverifikasi.
        OrderPayment::factory()->create(['order_id' => $order->id, 'amount' => 600_000, 'status' => 'verified']);
        $second = $sync->fromOrder($order->fresh());

        $this->assertSame('lunas', $second->payment_status);
        $this->assertSame(1, CourseParticipant::count());
        $this->assertSame($first->id, $second->id);
    }

    public function test_listener_is_wired_to_payment_verified_event(): void
    {
        $order = $this->courseOrder(1_000_000, 1_000_000);
        $payment = $order->payments()->first();

        event(new PaymentVerified($order, $payment));

        $this->assertSame(1, CourseParticipant::where('order_id', $order->id)->count());
    }

    // ─── CRUD admin ─────────────────────────────────────────────────────────

    public function test_index_requires_authentication(): void
    {
        $this->get(route('admin.participants.index'))->assertRedirect(route('admin.login'));
    }

    public function test_index_lists_participants_and_filters(): void
    {
        $other = Course::factory()->create(['title' => 'Kelas Lain']);
        CourseParticipant::create(['course_id' => $this->course->id, 'name' => 'Andi Wijaya', 'status' => 'active', 'payment_status' => 'lunas']);
        CourseParticipant::create(['course_id' => $other->id, 'name' => 'Budi Cicil', 'status' => 'registered', 'payment_status' => 'cicil']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.participants.index'))
            ->assertOk()->assertSee('Andi Wijaya')->assertSee('Budi Cicil');

        // filter kelas
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.participants.index', ['course' => $this->course->id]))
            ->assertOk()->assertSee('Andi Wijaya')->assertDontSee('Budi Cicil');

        // filter status pembayaran
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.participants.index', ['payment' => 'cicil']))
            ->assertOk()->assertSee('Budi Cicil')->assertDontSee('Andi Wijaya');

        // pencarian
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.participants.index', ['q' => 'Andi']))
            ->assertOk()->assertSee('Andi Wijaya')->assertDontSee('Budi Cicil');
    }

    public function test_admin_can_create_manual_participant(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.participants.store'), [
                'course_id' => $this->course->id,
                'name' => 'Peserta Offline',
                'email' => 'offline@example.com',
                'phone' => '0899',
                'status' => 'active',
                'payment_status' => 'lunas',
            ])
            ->assertRedirect(route('admin.participants.index'));

        $participant = CourseParticipant::firstWhere('name', 'Peserta Offline');
        $this->assertNotNull($participant);
        $this->assertNull($participant->order_id, 'Peserta manual tidak terikat order.');
        $this->assertNotNull($participant->joined_at);
    }

    public function test_admin_can_update_participant(): void
    {
        $participant = CourseParticipant::create([
            'course_id' => $this->course->id, 'name' => 'Rina', 'status' => 'registered', 'payment_status' => 'cicil',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.participants.update', $participant), [
                'course_id' => $this->course->id,
                'name' => 'Rina Update',
                'status' => 'graduated',
                'payment_status' => 'lunas',
                'notes' => 'Sudah lulus batch 1',
            ])
            ->assertRedirect(route('admin.participants.index'));

        $participant->refresh();
        $this->assertSame('Rina Update', $participant->name);
        $this->assertSame('graduated', $participant->status);
        $this->assertSame('lunas', $participant->payment_status);
        $this->assertSame('Sudah lulus batch 1', $participant->notes);
    }

    public function test_admin_can_delete_participant(): void
    {
        $participant = CourseParticipant::create([
            'course_id' => $this->course->id, 'name' => 'Hapus Saya', 'status' => 'registered', 'payment_status' => 'lunas',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.participants.destroy', $participant))
            ->assertRedirect(route('admin.participants.index'));

        $this->assertSame(0, CourseParticipant::count());
    }

    // ─── Export XLSX ────────────────────────────────────────────────────────

    /** Ambil isi sheet hasil export sebagai array baris. */
    private function exportRows(array $query = []): array
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.participants.export', $query));

        $response->assertOk();
        $content = $response->streamedContent();

        // XLSX = arsip ZIP → wajib diawali signature "PK". Kalau ini CSV
        // yang cuma diberi ekstensi .xlsx, assertion ini gagal.
        $this->assertStringStartsWith('PK', $content, 'File export harus XLSX asli (arsip ZIP), bukan CSV.');

        $path = tempnam(sys_get_temp_dir(), 'export').'.xlsx';
        file_put_contents($path, $content);
        $rows = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet()->toArray();
        @unlink($path);

        return $rows;
    }

    public function test_export_downloads_real_xlsx_with_headers_and_rows(): void
    {
        CourseParticipant::create([
            'course_id' => $this->course->id, 'name' => 'Andi Export',
            'email' => 'andi@example.com', 'status' => 'active', 'payment_status' => 'lunas',
        ]);

        $rows = $this->exportRows();

        $this->assertSame('Nama', $rows[0][0]);
        $this->assertSame('Status Pembayaran', $rows[0][5]);
        $this->assertContains('Andi Export', array_column($rows, 0));
    }

    public function test_export_respects_active_filters(): void
    {
        CourseParticipant::create(['course_id' => $this->course->id, 'name' => 'Peserta Lunas', 'status' => 'active', 'payment_status' => 'lunas']);
        CourseParticipant::create(['course_id' => $this->course->id, 'name' => 'Peserta Cicil', 'status' => 'active', 'payment_status' => 'cicil']);

        $names = array_column($this->exportRows(['payment' => 'cicil']), 0);

        $this->assertContains('Peserta Cicil', $names);
        $this->assertNotContains('Peserta Lunas', $names);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->from(route('admin.participants.create'))
            ->post(route('admin.participants.store'), ['name' => ''])
            ->assertSessionHasErrors(['course_id', 'name', 'status', 'payment_status']);
    }
}
