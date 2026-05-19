<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();

        Storage::fake('public');
    }

    // -----------------------------------------------------------------
    // Auth guard
    // -----------------------------------------------------------------

    public function test_guest_redirected_from_products_index(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_guest_cannot_post_create(): void
    {
        $this->post(route('admin.products.store'), $this->validPayload())
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('products', 0);
    }

    // -----------------------------------------------------------------
    // Index page
    // -----------------------------------------------------------------

    public function test_index_renders_for_admin(): void
    {
        Product::factory()->count(3)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Produk')
            ->assertSee('Tambah Produk');
    }

    public function test_index_filters_by_status(): void
    {
        Product::factory()->create(['status' => 'active', 'title' => 'Buku Live']);
        Product::factory()->create(['status' => 'draft', 'title' => 'Buku Draft']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Buku Live')
            ->assertDontSee('Buku Draft');
    }

    public function test_index_search_by_title(): void
    {
        Product::factory()->create(['title' => 'Mind Power 101', 'slug' => 'mind-power-101']);
        Product::factory()->create(['title' => 'Other Buku', 'slug' => 'other-buku']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.index', ['q' => 'Mind']))
            ->assertOk()
            ->assertSee('Mind Power 101')
            ->assertDontSee('Other Buku');
    }

    // -----------------------------------------------------------------
    // Create / Store
    // -----------------------------------------------------------------

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Tambah Produk Baru')
            ->assertSee('Identitas produk');
    }

    public function test_store_creates_product_with_image(): void
    {
        $payload = $this->validPayload([
            'title' => 'Buku Mind Power 101',
            'slug' => 'buku-mind-power-101',
            'image' => UploadedFile::fake()->image('cover.jpg', 1000, 1000)->size(500),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('products', [
            'title' => 'Buku Mind Power 101',
            'slug' => 'buku-mind-power-101',
            'status' => 'draft',
        ]);

        $product = Product::where('slug', 'buku-mind-power-101')->first();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_store_auto_generates_slug_from_title(): void
    {
        $payload = $this->validPayload([
            'title' => 'Kelas Hipnoterapi Profesional!',
            'slug' => '', // kosong → auto
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'slug' => 'kelas-hipnoterapi-profesional',
        ]);
    }

    public function test_store_rejects_when_title_missing(): void
    {
        $payload = $this->validPayload(['title' => '']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['title']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_store_rejects_negative_price(): void
    {
        $payload = $this->validPayload(['price' => -1]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['price']);
    }

    public function test_store_rejects_invalid_status(): void
    {
        $payload = $this->validPayload(['status' => 'wonky']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['status']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Product::factory()->create(['slug' => 'taken-slug']);

        $payload = $this->validPayload(['slug' => 'taken-slug']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['slug']);
    }

    public function test_store_rejects_oversized_image(): void
    {
        $payload = $this->validPayload([
            'image' => UploadedFile::fake()->image('big.jpg', 1000, 1000)->size(3000), // 3 MB > 2 MB
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['image']);
    }

    public function test_store_rejects_too_small_image_dimensions(): void
    {
        $payload = $this->validPayload([
            'image' => UploadedFile::fake()->image('small.jpg', 400, 400)->size(100),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload)
            ->assertSessionHasErrors(['image']);
    }

    public function test_store_persists_meta_seo(): void
    {
        $payload = $this->validPayload([
            'meta_title' => 'Mind Power · Toko Resmi',
            'meta_description' => 'Beli buku Mind Power langsung dari toko resmi MasFirmanPratama.',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.products.store'), $payload);

        $product = Product::first();
        $this->assertSame('Mind Power · Toko Resmi', $product->meta_seo['title']);
        $this->assertStringStartsWith('Beli buku Mind Power', $product->meta_seo['description']);
    }

    // -----------------------------------------------------------------
    // Edit / Update
    // -----------------------------------------------------------------

    public function test_edit_form_renders(): void
    {
        $product = Product::factory()->create(['title' => 'Old Title']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Old Title');
    }

    public function test_update_changes_fields(): void
    {
        $product = Product::factory()->create([
            'title' => 'Old Title',
            'slug' => 'old-slug',
            'price' => 100000,
            'status' => 'draft',
        ]);

        $payload = $this->validPayload([
            'title' => 'New Title',
            'slug' => 'new-slug',
            'price' => 200000,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame('New Title', $product->title);
        $this->assertSame('new-slug', $product->slug);
        $this->assertSame('200000.00', $product->price);
        $this->assertSame('active', $product->status);
    }

    public function test_update_allows_keeping_own_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'keep-this']);

        $payload = $this->validPayload(['slug' => 'keep-this']);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.index'));
    }

    public function test_update_rejects_when_slug_taken_by_other(): void
    {
        Product::factory()->create(['slug' => 'taken-by-other']);
        $target = Product::factory()->create(['slug' => 'mine']);

        $payload = $this->validPayload(['slug' => 'taken-by-other']);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $target), $payload)
            ->assertSessionHasErrors(['slug']);
    }

    public function test_update_replaces_image_and_deletes_old(): void
    {
        $oldPath = 'products/old/cover.jpg';
        Storage::disk('public')->put($oldPath, 'old-image-bytes');

        $product = Product::factory()->create(['image_path' => $oldPath]);

        $payload = $this->validPayload([
            'image' => UploadedFile::fake()->image('new.jpg', 1000, 1000)->size(400),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertNotSame($oldPath, $product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_update_can_remove_image(): void
    {
        $oldPath = 'products/old/cover.jpg';
        Storage::disk('public')->put($oldPath, 'old-image-bytes');

        $product = Product::factory()->create(['image_path' => $oldPath]);

        $payload = $this->validPayload([
            'remove_image' => '1',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.products.update', $product), $payload)
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertNull($product->image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    // -----------------------------------------------------------------
    // Destroy (soft delete)
    // -----------------------------------------------------------------

    public function test_destroy_soft_deletes_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted($product);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Buku Test',
            'slug' => 'buku-test',
            'type' => 'book',
            'price' => 150000,
            'stock' => 10,
            'status' => 'draft',
            'description' => 'Deskripsi singkat produk.',
        ], $overrides);
    }
}
