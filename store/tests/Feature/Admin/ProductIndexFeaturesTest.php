<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur manajemen produk di /admin/products: toggle status cepat, sorting,
 * filter stok/tipe, dan bulk "pilih semua sesuai filter".
 */
class ProductIndexFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Quick status toggle ──────────────────────────────────────────────

    public function test_toggle_status_active_to_archived(): void
    {
        $p = Product::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.products.toggle-status', $p))
            ->assertRedirect();

        $this->assertSame('archived', $p->fresh()->status);
    }

    public function test_toggle_status_non_active_becomes_active(): void
    {
        foreach (['draft', 'archived'] as $status) {
            $p = Product::factory()->create(['status' => $status]);

            $this->actingAs($this->admin, 'admin')
                ->patch(route('admin.products.toggle-status', $p));

            $this->assertSame('active', $p->fresh()->status);
        }
    }

    public function test_toggle_status_requires_auth(): void
    {
        $p = Product::factory()->create(['status' => 'active']);

        $this->patch(route('admin.products.toggle-status', $p))
            ->assertRedirect(route('admin.login'));

        $this->assertSame('active', $p->fresh()->status);
    }

    // ── Sorting (whitelist) ──────────────────────────────────────────────

    public function test_index_sorts_by_price_ascending(): void
    {
        Product::factory()->create(['title' => 'Mahal', 'price' => 900000]);
        Product::factory()->create(['title' => 'Murah', 'price' => 100000]);

        $products = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['sort' => 'price', 'dir' => 'asc']))
            ->assertOk()
            ->viewData('products');

        $this->assertSame('Murah', $products->first()->title);
    }

    public function test_index_ignores_non_whitelisted_sort_column(): void
    {
        Product::factory()->count(2)->create();

        // ?sort=deleted_at bukan whitelist → tidak error, fallback ke default.
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['sort' => 'deleted_at', 'dir' => 'asc']))
            ->assertOk();
    }

    // ── Filter stok ──────────────────────────────────────────────────────

    public function test_stock_filter_out_of_stock(): void
    {
        $out = Product::factory()->create(['stock' => 0, 'is_shippable' => true]);
        $low = Product::factory()->create(['stock' => 3, 'is_shippable' => true]);
        $ok = Product::factory()->create(['stock' => 50, 'is_shippable' => true]);

        $ids = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['stock' => 'out']))
            ->assertOk()
            ->viewData('products')->pluck('id');

        $this->assertTrue($ids->contains($out->id));
        $this->assertFalse($ids->contains($low->id));
        $this->assertFalse($ids->contains($ok->id));
    }

    public function test_stock_filter_low_excludes_zero_and_healthy(): void
    {
        $out = Product::factory()->create(['stock' => 0, 'is_shippable' => true]);
        $low = Product::factory()->create(['stock' => 3, 'is_shippable' => true]);
        $ok = Product::factory()->create(['stock' => 50, 'is_shippable' => true]);

        $ids = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['stock' => 'low']))
            ->assertOk()
            ->viewData('products')->pluck('id');

        $this->assertTrue($ids->contains($low->id));
        $this->assertFalse($ids->contains($out->id)); // 0 = habis, bukan menipis
        $this->assertFalse($ids->contains($ok->id));
    }

    public function test_stock_filter_excludes_non_shippable_products(): void
    {
        // Produk non-fisik stok 0 TIDAK boleh muncul di filter stok (samakan dgn badge).
        $digitalOut = Product::factory()->create(['stock' => 0, 'is_shippable' => false]);
        $physicalOut = Product::factory()->create(['stock' => 0, 'is_shippable' => true]);

        $ids = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['stock' => 'out']))
            ->assertOk()
            ->viewData('products')->pluck('id');

        $this->assertTrue($ids->contains($physicalOut->id));
        $this->assertFalse($ids->contains($digitalOut->id));
    }

    // ── Filter tipe ──────────────────────────────────────────────────────

    public function test_type_filter_narrows_results(): void
    {
        $book = Product::factory()->create(['type' => 'book']);
        $course = Product::factory()->create(['type' => 'course']);

        $ids = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['type' => 'course']))
            ->assertOk()
            ->viewData('products')->pluck('id');

        $this->assertTrue($ids->contains($course->id));
        $this->assertFalse($ids->contains($book->id));
    }

    // ── Bulk "pilih semua sesuai filter" ─────────────────────────────────

    public function test_bulk_select_all_archives_entire_filtered_set_not_just_posted_ids(): void
    {
        // 25 draft (lebih dari 1 halaman @20) + 3 active di luar filter.
        Product::factory()->count(25)->create(['status' => 'draft']);
        Product::factory()->count(3)->create(['status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'archive',
                'select_all' => '1',
                'status' => 'draft', // filter dikirim sebagai body (hidden input)
                // sengaja TANPA ids[] — select_all harus meng-cover semua yang cocok filter
            ])
            ->assertRedirect();

        $this->assertSame(25, Product::where('status', 'archived')->count());
        // Yang di luar filter (active) TIDAK tersentuh.
        $this->assertSame(3, Product::where('status', 'active')->count());
    }

    public function test_bulk_without_ids_and_without_select_all_errors(): void
    {
        Product::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), ['action' => 'archive'])
            ->assertSessionHasErrors('ids');
    }

    // ── UI hooks ─────────────────────────────────────────────────────────

    public function test_index_renders_select_all_and_sortable_headers(): void
    {
        Product::factory()->create();

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('toggleAllOnPage', $html);   // header select-all
        $this->assertStringContainsString('sort=price', $html);        // header sortable
        $this->assertStringContainsString('name="select_all"', $html); // flag pilih-semua
    }
}
