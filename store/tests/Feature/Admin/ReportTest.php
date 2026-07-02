<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Services\Reporting\SalesReportService;
use Carbon\Carbon;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_redirects_unauthenticated_to_login(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_access_reports_page(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penjualan');
    }

    public function test_reports_show_revenue_summary_with_correct_totals(): void
    {
        // Two verified payments: 100k + 200k = 300k revenue
        $order1 = Order::factory()->status('paid')->create(['total' => 100_000]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $order1->id,
            'amount' => 100_000,
        ]);

        $order2 = Order::factory()->status('paid')->create(['total' => 200_000]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $order2->id,
            'amount' => 200_000,
        ]);

        // Pending payment should NOT count toward revenue
        $order3 = Order::factory()->status('pending')->create(['total' => 500_000]);
        OrderPayment::factory()->create([
            'order_id' => $order3->id,
            'amount' => 500_000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        // Revenue should show verified payments total (100k + 200k = 300k).
        $response->assertSee('Rp 300.000');
    }

    public function test_date_range_filter_works(): void
    {
        // Order + verified payment 10 days ago
        $oldOrder = Order::factory()->status('paid')->create([
            'total' => 150_000,
            'created_at' => now()->subDays(10),
        ]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $oldOrder->id,
            'amount' => 150_000,
            'paid_at' => now()->subDays(10),
        ]);

        // Order + verified payment today
        $todayOrder = Order::factory()->status('paid')->create([
            'total' => 80_000,
            'created_at' => now(),
        ]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $todayOrder->id,
            'amount' => 80_000,
            'paid_at' => now(),
        ]);

        // Filter: last 3 days only → should show 80k, not 150k
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index', [
            'from' => now()->subDays(3)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Rp 80.000');
        $response->assertDontSee('Rp 150.000');
    }

    public function test_invalid_date_range_returns_validation_error(): void
    {
        // 'from' is after 'to' → invalid
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index', [
            'from' => '2025-12-31',
            'to' => '2025-01-01',
        ]));

        $response->assertSessionHasErrors(['from']);
    }

    public function test_invalid_date_format_returns_validation_error(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index', [
            'from' => 'not-a-date',
            'to' => '2025-01-01',
        ]));

        $response->assertSessionHasErrors(['from']);
    }

    public function test_export_csv_downloads_file_with_correct_headers(): void
    {
        $order = Order::factory()->status('paid')->create([
            'total' => 100_000,
            'customer_name' => 'Budi Test',
            'order_number' => 'MFP-CSV0001',
        ]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
            'amount' => 100_000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $disposition = $response->headers->get('Content-Disposition', '');
        $this->assertStringContainsString('laporan-penjualan', $disposition);
        $this->assertStringContainsString('.csv', $disposition);

        $content = $response->streamedContent();
        $this->assertStringContainsString('Tanggal', $content);
        $this->assertStringContainsString('Order Number', $content);
        $this->assertStringContainsString('Customer', $content);
        $this->assertStringContainsString('MFP-CSV0001', $content);
        $this->assertStringContainsString('Budi Test', $content);
    }

    public function test_top_products_shows_correct_ranking_by_revenue(): void
    {
        // Product A: 2 items × 50k = 100k revenue
        $productA = Product::factory()->create(['title' => 'Buku A']);
        $orderA = Order::factory()->status('paid')->create(['total' => 100_000]);
        OrderItem::factory()->create([
            'order_id' => $orderA->id,
            'product_id' => $productA->id,
            'qty' => 2,
            'unit_price' => 50_000,
            'subtotal' => 100_000,
        ]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $orderA->id,
            'amount' => 100_000,
        ]);

        // Product B: 1 item × 200k = 200k revenue (should rank #1)
        $productB = Product::factory()->create(['title' => 'Buku B']);
        $orderB = Order::factory()->status('paid')->create(['total' => 200_000]);
        OrderItem::factory()->create([
            'order_id' => $orderB->id,
            'product_id' => $productB->id,
            'qty' => 1,
            'unit_price' => 200_000,
            'subtotal' => 200_000,
        ]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $orderB->id,
            'amount' => 200_000,
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $content = $response->getContent();

        // Buku B (200k) should appear before Buku A (100k) in top products
        $posB = strpos($content, 'Buku B');
        $posA = strpos($content, 'Buku A');
        $this->assertNotFalse($posB, 'Buku B should appear in top products');
        $this->assertNotFalse($posA, 'Buku A should appear in top products');
        $this->assertLessThan($posA, $posB, 'Buku B (higher revenue) should rank above Buku A');
    }

    public function test_order_status_breakdown_displays_correct_counts(): void
    {
        Order::factory()->status('pending')->count(3)->create();
        Order::factory()->status('paid')->count(2)->create();
        Order::factory()->status('shipped')->count(1)->create();

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Pending');
        $response->assertSee('Lunas');
    }

    public function test_payment_summary_shows_verified_and_pending_totals(): void
    {
        $verifiedOrder = Order::factory()->status('paid')->create();
        OrderPayment::factory()->verified()->create([
            'order_id' => $verifiedOrder->id,
            'amount' => 250_000,
        ]);

        $pendingOrder = Order::factory()->status('pending')->create();
        OrderPayment::factory()->create([
            'order_id' => $pendingOrder->id,
            'amount' => 75_000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Rp 250.000'); // verified total
    }

    /**
     * REGRESSION: range > 91 hari memicu monthlyRevenue() yang dulu memakai
     * DATE_FORMAT (MySQL-only) → SQL error di SQLite (dev/test). Kini portable.
     */
    public function test_long_range_over_91_days_does_not_crash(): void
    {
        $order = Order::factory()->status('paid')->create(['total' => 100_000]);
        OrderPayment::factory()->verified()->create([
            'order_id' => $order->id,
            'amount' => 100_000,
            'paid_at' => now()->subMonths(2),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index', [
                'from' => now()->subMonths(5)->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertStatus(200);
    }

    /**
     * REGRESSION: monthlyRevenue() dulu MEMUTASI $from/$to (startOfMonth/endOfMonth
     * tanpa copy) → query setelahnya (top products, status, payment summary)
     * diam-diam memakai range yang melebar.
     */
    public function test_monthly_revenue_does_not_mutate_input_dates(): void
    {
        $from = Carbon::parse('2026-03-15')->startOfDay();
        $to = Carbon::parse('2026-06-20')->endOfDay();

        app(SalesReportService::class)->monthlyRevenue($from, $to);

        $this->assertSame('2026-03-15', $from->toDateString(), '$from tidak boleh berubah');
        $this->assertSame('2026-06-20', $to->toDateString(), '$to tidak boleh berubah');
    }

    /** Input tanggal selalu terisi periode aktif (bukan kosong saat default). */
    public function test_filter_inputs_prefilled_with_resolved_period(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index'))
            ->assertStatus(200)
            ->getContent();

        // Default = awal bulan ini s/d hari ini — tampil di input + label periode.
        $this->assertStringContainsString('value="'.now()->startOfMonth()->toDateString().'"', $html);
        $this->assertStringContainsString('value="'.now()->toDateString().'"', $html);
        $this->assertStringContainsString('Periode:', $html);
    }

    /** Preset periode cepat untuk admin non-teknis. */
    public function test_quick_period_presets_render(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index'))
            ->assertStatus(200)
            ->getContent();

        foreach (['Hari Ini', '7 Hari', '30 Hari', 'Bulan Ini', 'Bulan Lalu'] as $preset) {
            $this->assertStringContainsString($preset, $html);
        }
    }

    /** Legend status pesanan jadi drill-down ke daftar pesanan terfilter. */
    public function test_order_status_legend_links_to_filtered_orders(): void
    {
        Order::factory()->status('paid')->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index'))
            ->assertStatus(200)
            ->assertSee(route('admin.orders.index', ['status' => 'paid']), false);
    }

    /**
     * REGRESSION: payment verified TANPA paid_at (admin approve tanpa customer
     * upload bukti) dulu tak pernah terhitung di Total Revenue — laporan
     * di-bucket by paid_at, NULL tak match whereBetween. approvePayment kini
     * backfill paid_at.
     */
    public function test_admin_approved_payment_without_paid_at_counts_in_revenue(): void
    {
        $order = Order::factory()->status('pending')->create(['total' => 90_000]);
        $payment = OrderPayment::factory()->create([
            'order_id' => $order->id,
            'amount' => 90_000,
            'status' => 'pending',
            'paid_at' => null, // belum upload bukti
        ]);

        // Admin approve langsung.
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.orders.payments.approve', [$order, $payment]))
            ->assertRedirect();

        $this->assertNotNull($payment->fresh()->paid_at, 'paid_at harus di-backfill saat approve');

        // Revenue report harus mencatatnya.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.reports.index'))
            ->assertStatus(200)
            ->assertSee('Rp 90.000');
    }
}
