<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    public function test_destroy_soft_deletes_product(): void
    {
        $product = Product::factory()->create(['title' => 'Test Buku']);

        $response = $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_default_index_excludes_soft_deleted(): void
    {
        $active = Product::factory()->create(['title' => 'Active Product', 'slug' => 'active-prod']);
        $trashed = Product::factory()->create(['title' => 'Trashed Product', 'slug' => 'trashed-prod']);
        $trashed->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Active Product');
        $response->assertDontSee('Trashed Product');
    }

    public function test_trashed_view_shows_only_soft_deleted(): void
    {
        $active = Product::factory()->create(['title' => 'Active Product', 'slug' => 'active-prod-2']);
        $trashed = Product::factory()->create(['title' => 'Trashed Product', 'slug' => 'trashed-prod-2']);
        $trashed->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['view' => 'trashed']));

        $response->assertStatus(200);
        $response->assertSee('Trashed Product');
        $response->assertDontSee('Active Product');
    }

    public function test_restore_returns_product_to_active_view(): void
    {
        $product = Product::factory()->create(['slug' => 'restore-me']);
        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.restore', $product->slug));

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);
    }

    public function test_restore_404_for_active_product(): void
    {
        $product = Product::factory()->create(['slug' => 'active-only']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.restore', $product->slug));

        $response->assertNotFound();
    }

    public function test_bulk_archive_updates_status(): void
    {
        $a = Product::factory()->create(['status' => 'active', 'slug' => 'p-a']);
        $b = Product::factory()->create(['status' => 'active', 'slug' => 'p-b']);
        $c = Product::factory()->create(['status' => 'active', 'slug' => 'p-c']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'archive',
                'ids' => [$a->id, $b->id],
            ]);

        $response->assertRedirect();

        $this->assertSame('archived', $a->fresh()->status);
        $this->assertSame('archived', $b->fresh()->status);
        $this->assertSame('active', $c->fresh()->status);
    }

    public function test_bulk_activate_updates_status(): void
    {
        $a = Product::factory()->create(['status' => 'draft', 'slug' => 'd-a']);
        $b = Product::factory()->create(['status' => 'archived', 'slug' => 'd-b']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'activate',
                'ids' => [$a->id, $b->id],
            ]);

        $response->assertRedirect();
        $this->assertSame('active', $a->fresh()->status);
        $this->assertSame('active', $b->fresh()->status);
    }

    public function test_bulk_soft_delete(): void
    {
        $a = Product::factory()->create(['slug' => 'sd-a']);
        $b = Product::factory()->create(['slug' => 'sd-b']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'soft_delete',
                'ids' => [$a->id, $b->id],
            ]);

        $response->assertRedirect();
        $this->assertSoftDeleted('products', ['id' => $a->id]);
        $this->assertSoftDeleted('products', ['id' => $b->id]);
    }

    public function test_bulk_restore(): void
    {
        $a = Product::factory()->create(['slug' => 'r-a']);
        $b = Product::factory()->create(['slug' => 'r-b']);
        $a->delete();
        $b->delete();

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'restore',
                'ids' => [$a->id, $b->id],
            ]);

        $response->assertRedirect();
        $this->assertNull($a->fresh()->deleted_at);
        $this->assertNull($b->fresh()->deleted_at);
    }

    public function test_bulk_force_delete_removes_from_db(): void
    {
        $a = Product::factory()->create(['slug' => 'fd-a']);
        $a->delete(); // soft delete first (force_delete only operates on trashed)
        $aId = $a->id;

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'force_delete',
                'ids' => [$aId],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('products', ['id' => $aId]);
    }

    public function test_bulk_validates_action_enum(): void
    {
        $a = Product::factory()->create(['slug' => 'v-a']);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'totally-invalid-action',
                'ids' => [$a->id],
            ]);

        $response->assertSessionHasErrors('action');
    }

    public function test_bulk_validates_ids_required(): void
    {
        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.bulk'), [
                'action' => 'archive',
                'ids' => [],
            ]);

        $response->assertSessionHasErrors('ids');
    }

    public function test_bulk_unauthenticated_redirects_to_login(): void
    {
        $a = Product::factory()->create(['slug' => 'u-a']);

        $response = $this->post(route('admin.products.bulk'), [
            'action' => 'archive',
            'ids' => [$a->id],
        ]);

        $response->assertRedirect(route('admin.login'));
    }
}
