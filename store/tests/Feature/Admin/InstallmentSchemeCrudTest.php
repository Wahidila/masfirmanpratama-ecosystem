<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\InstallmentScheme;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallmentSchemeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Auth ────────────────────────────────────────────────

    public function test_index_redirects_unauthenticated(): void
    {
        $this->get(route('admin.installment-schemes.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_create_redirects_unauthenticated(): void
    {
        $this->get(route('admin.installment-schemes.create'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_store_redirects_unauthenticated(): void
    {
        $this->post(route('admin.installment-schemes.store'))
            ->assertRedirect(route('admin.login'));
    }

    // ── Index ───────────────────────────────────────────────

    public function test_index_renders_for_admin(): void
    {
        InstallmentScheme::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee('Skema Cicilan');
        $response->assertSee('Skema Baru');
    }

    public function test_index_shows_empty_state(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada skema');
    }

    public function test_index_filters_by_global_scope(): void
    {
        InstallmentScheme::factory()->global()->create(['name' => 'GLOBAL-A']);
        $product = Product::factory()->create();
        InstallmentScheme::factory()->create([
            'name' => 'PRODUCT-B',
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.index', ['scope' => 'global']));

        $response->assertStatus(200);
        $response->assertSee('GLOBAL-A');
        $response->assertDontSee('PRODUCT-B');
    }

    public function test_index_filters_by_product_scope(): void
    {
        InstallmentScheme::factory()->global()->create(['name' => 'GLOBAL-X']);
        $product = Product::factory()->create();
        InstallmentScheme::factory()->create([
            'name' => 'PRODUCT-Y',
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.index', ['scope' => 'product']));

        $response->assertStatus(200);
        $response->assertSee('PRODUCT-Y');
        $response->assertDontSee('GLOBAL-X');
    }

    public function test_index_search_by_name(): void
    {
        InstallmentScheme::factory()->create(['name' => 'Cicilan Khusus 12x']);
        InstallmentScheme::factory()->create(['name' => 'Lunas Promo']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.index', ['q' => 'Khusus']));

        $response->assertStatus(200);
        $response->assertSee('Cicilan Khusus 12x');
        $response->assertDontSee('Lunas Promo');
    }

    // ── Create / Store ──────────────────────────────────────

    public function test_create_form_renders(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.create'));

        $response->assertStatus(200);
        $response->assertSee('Skema Cicilan Baru');
        $response->assertSee('Nama Skema');
        $response->assertSee('Berlaku untuk');
    }

    public function test_store_creates_global_scheme(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => '6x Cicilan Test',
                'product_id' => '',
                'dp_pct' => 25,
                'n_installments' => 6,
                'interval_days' => 30,
                'active' => '1',
            ]);

        $response->assertRedirect(route('admin.installment-schemes.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('installment_schemes', [
            'name' => '6x Cicilan Test',
            'product_id' => null,
            'n_installments' => 6,
            'active' => true,
        ]);
    }

    public function test_store_creates_product_specific_scheme(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => '12x Khusus',
                'product_id' => $product->id,
                'dp_pct' => 15,
                'n_installments' => 12,
                'interval_days' => 30,
                'active' => '1',
            ])->assertRedirect();

        $this->assertDatabaseHas('installment_schemes', [
            'name' => '12x Khusus',
            'product_id' => $product->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [])
            ->assertSessionHasErrors(['name', 'dp_pct', 'n_installments', 'interval_days']);
    }

    public function test_store_validates_dp_pct_range(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => 'Test',
                'dp_pct' => 150, // > 100
                'n_installments' => 3,
                'interval_days' => 30,
            ])->assertSessionHasErrors('dp_pct');

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => 'Test',
                'dp_pct' => -10, // < 0
                'n_installments' => 3,
                'interval_days' => 30,
            ])->assertSessionHasErrors('dp_pct');
    }

    public function test_store_validates_n_installments_minimum(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => 'Test',
                'dp_pct' => 30,
                'n_installments' => 0, // < 1
                'interval_days' => 30,
            ])->assertSessionHasErrors('n_installments');
    }

    public function test_store_validates_product_id_exists(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => 'Test',
                'product_id' => 999999,
                'dp_pct' => 30,
                'n_installments' => 3,
                'interval_days' => 30,
            ])->assertSessionHasErrors('product_id');
    }

    public function test_store_defaults_inactive_when_active_unchecked(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.store'), [
                'name' => 'Inactive Test',
                'dp_pct' => 30,
                'n_installments' => 3,
                'interval_days' => 30,
                // active not provided
            ])->assertRedirect();

        $this->assertDatabaseHas('installment_schemes', [
            'name' => 'Inactive Test',
            'active' => false,
        ]);
    }

    // ── Edit / Update ───────────────────────────────────────

    public function test_edit_form_renders(): void
    {
        $scheme = InstallmentScheme::factory()->create(['name' => 'Edit Me']);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.installment-schemes.edit', $scheme));

        $response->assertStatus(200);
        $response->assertSee('Edit Skema: Edit Me');
        $response->assertSee('Edit Me');
    }

    public function test_update_modifies_scheme(): void
    {
        $scheme = InstallmentScheme::factory()->create([
            'name' => 'Old Name',
            'dp_pct' => 30,
            'n_installments' => 3,
            'interval_days' => 30,
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.installment-schemes.update', $scheme), [
                'name' => 'New Name',
                'product_id' => '',
                'dp_pct' => 50,
                'n_installments' => 6,
                'interval_days' => 14,
                'active' => '1',
            ])->assertRedirect(route('admin.installment-schemes.index'));

        $scheme->refresh();
        $this->assertSame('New Name', $scheme->name);
        $this->assertSame('50.00', $scheme->dp_pct);
        $this->assertSame(6, $scheme->n_installments);
        $this->assertSame(14, $scheme->interval_days);
    }

    public function test_update_can_change_scope_to_product(): void
    {
        $scheme = InstallmentScheme::factory()->global()->create();
        $product = Product::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.installment-schemes.update', $scheme), [
                'name' => $scheme->name,
                'product_id' => $product->id,
                'dp_pct' => 30,
                'n_installments' => 3,
                'interval_days' => 30,
                'active' => '1',
            ])->assertRedirect();

        $scheme->refresh();
        $this->assertSame($product->id, $scheme->product_id);
    }

    // ── Destroy ─────────────────────────────────────────────

    public function test_destroy_deletes_scheme(): void
    {
        $scheme = InstallmentScheme::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.installment-schemes.destroy', $scheme))
            ->assertRedirect(route('admin.installment-schemes.index'));

        $this->assertDatabaseMissing('installment_schemes', ['id' => $scheme->id]);
    }

    // ── Toggle active ───────────────────────────────────────

    public function test_toggle_flips_active(): void
    {
        $scheme = InstallmentScheme::factory()->create(['active' => true]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.toggle', $scheme))
            ->assertRedirect();

        $scheme->refresh();
        $this->assertFalse($scheme->active);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.installment-schemes.toggle', $scheme))
            ->assertRedirect();

        $scheme->refresh();
        $this->assertTrue($scheme->active);
    }

    // ── Model scopes ────────────────────────────────────────

    public function test_active_scope_filters_inactive(): void
    {
        InstallmentScheme::factory()->global()->create(['active' => true, 'name' => 'A']);
        InstallmentScheme::factory()->global()->inactive()->create(['name' => 'B']);

        $names = InstallmentScheme::active()->pluck('name')->toArray();
        $this->assertContains('A', $names);
        $this->assertNotContains('B', $names);
    }

    public function test_for_product_scope_returns_global_plus_specific(): void
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        InstallmentScheme::factory()->global()->create(['name' => 'GLOBAL']);
        InstallmentScheme::factory()->create(['product_id' => $product1->id, 'name' => 'P1']);
        InstallmentScheme::factory()->create(['product_id' => $product2->id, 'name' => 'P2']);

        $names = InstallmentScheme::forProduct($product1->id)->pluck('name')->toArray();
        sort($names);
        $this->assertSame(['GLOBAL', 'P1'], $names);
    }

    public function test_for_product_scope_with_null_returns_global_only(): void
    {
        $product = Product::factory()->create();
        InstallmentScheme::factory()->global()->create(['name' => 'GLOBAL']);
        InstallmentScheme::factory()->create(['product_id' => $product->id, 'name' => 'PROD']);

        $names = InstallmentScheme::forProduct(null)->pluck('name')->toArray();
        $this->assertSame(['GLOBAL'], $names);
    }

    // ── Sidebar nav ─────────────────────────────────────────

    public function test_sidebar_links_to_installment_schemes_index(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.installment-schemes.index'));
        $response->assertSee('Skema Cicilan');
    }
}
