<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\PromoBanner;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CRUD banner promo/jadwal terdekat homepage (upload gambar + jendela tayang).
 */
class PromoBannerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Auth ────────────────────────────────────────────────

    public function test_index_redirects_unauthenticated(): void
    {
        $this->get(route('admin.promo-banners.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_store_redirects_unauthenticated(): void
    {
        $this->post(route('admin.promo-banners.store'))
            ->assertRedirect(route('admin.login'));
    }

    // ── Index ───────────────────────────────────────────────

    public function test_index_renders_with_banners(): void
    {
        PromoBanner::factory()->create(['title' => 'Banner Surabaya']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index'))
            ->assertStatus(200)
            ->assertSee('Banner Promo')
            ->assertSee('Banner Surabaya');
    }

    public function test_index_shows_empty_state(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index'))
            ->assertStatus(200)
            ->assertSee('Belum ada banner');
    }

    public function test_index_marks_active_but_out_of_window_banner(): void
    {
        PromoBanner::factory()->expired()->create(['title' => 'Banner Lewat']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index'))
            ->assertStatus(200)
            ->assertSee('Di luar jadwal');
    }

    public function test_index_shows_stat_cards(): void
    {
        PromoBanner::factory()->count(2)->create();
        PromoBanner::factory()->inactive()->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index'))
            ->assertStatus(200)
            ->assertSee('Tampil Sekarang')
            ->assertSee('Aktif')
            ->assertSee('Nonaktif');
    }

    public function test_index_search_by_title(): void
    {
        PromoBanner::factory()->create(['title' => 'Event Bandung Spesial']);
        PromoBanner::factory()->create(['title' => 'Event Jakarta']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index', ['q' => 'Bandung']))
            ->assertStatus(200)
            ->assertSee('Event Bandung Spesial')
            ->assertDontSee('Event Jakarta');
    }

    public function test_index_filters_by_status(): void
    {
        PromoBanner::factory()->create(['title' => 'Banner Aktif Ini']);
        PromoBanner::factory()->inactive()->create(['title' => 'Banner Mati Ini']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.index', ['status' => 'inactive']))
            ->assertStatus(200)
            ->assertSee('Banner Mati Ini')
            ->assertDontSee('Banner Aktif Ini');
    }

    // ── Create / Store ──────────────────────────────────────

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.promo-banners.create'))
            ->assertStatus(200)
            ->assertSee('Banner Promo Baru')
            ->assertSee('Gambar Banner')
            ->assertSee('Mulai Tayang')
            ->assertSee('Akhir Tayang');
    }

    public function test_store_uploads_image_and_creates_banner(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.promo-banners.store'), [
                'title' => 'Kelas AMC Jakarta 2026',
                'image' => UploadedFile::fake()->image('banner.webp', 1280, 312),
                'link_url' => 'https://wa.me/6281230633464',
                'active' => '1',
                'sort_order' => 0,
            ])
            ->assertRedirect(route('admin.promo-banners.index'))
            ->assertSessionHas('status');

        $banner = PromoBanner::firstOrFail();
        $this->assertSame('Kelas AMC Jakarta 2026', $banner->title);
        $this->assertStringStartsWith('banners/', $banner->image_path);
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_store_requires_image(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.promo-banners.store'), [
                'title' => 'Tanpa Gambar',
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_store_rejects_non_image_file(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.promo-banners.store'), [
                'title' => 'File Aneh',
                'image' => UploadedFile::fake()->create('file.pdf', 100, 'application/pdf'),
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_store_rejects_ends_at_before_starts_at(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.promo-banners.store'), [
                'title' => 'Jendela Terbalik',
                'image' => UploadedFile::fake()->image('banner.webp'),
                'starts_at' => '2026-08-01T10:00',
                'ends_at' => '2026-07-01T10:00', // sebelum starts_at
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors('ends_at');
    }

    // ── Update ──────────────────────────────────────────────

    public function test_update_without_new_image_keeps_existing(): void
    {
        $banner = PromoBanner::factory()->create(['title' => 'Lama']);
        $oldPath = $banner->image_path;

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.promo-banners.update', $banner), [
                'title' => 'Baru',
                'link_url' => '',
                'active' => '1',
                'sort_order' => 2,
            ])
            ->assertRedirect(route('admin.promo-banners.index'));

        $banner->refresh();
        $this->assertSame('Baru', $banner->title);
        $this->assertSame($oldPath, $banner->image_path);
        $this->assertSame(2, $banner->sort_order);
    }

    public function test_update_with_new_image_replaces_old_upload(): void
    {
        // Banner dgn gambar hasil upload (folder banners/) — file lama harus terhapus.
        Storage::disk('public')->put('banners/old.webp', 'x');
        $banner = PromoBanner::factory()->create(['image_path' => 'banners/old.webp']);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.promo-banners.update', $banner), [
                'title' => $banner->title,
                'image' => UploadedFile::fake()->image('new.webp', 1280, 312),
                'active' => '1',
                'sort_order' => 0,
            ])
            ->assertRedirect();

        $banner->refresh();
        $this->assertNotSame('banners/old.webp', $banner->image_path);
        Storage::disk('public')->assertMissing('banners/old.webp');
        Storage::disk('public')->assertExists($banner->image_path);
    }

    // ── Toggle & Destroy ────────────────────────────────────

    public function test_toggle_flips_active(): void
    {
        $banner = PromoBanner::factory()->create(['active' => true]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.promo-banners.toggle', $banner))
            ->assertRedirect();

        $this->assertFalse($banner->fresh()->active);
    }

    public function test_destroy_deletes_banner_and_uploaded_file(): void
    {
        Storage::disk('public')->put('banners/gone.webp', 'x');
        $banner = PromoBanner::factory()->create(['image_path' => 'banners/gone.webp']);

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.promo-banners.destroy', $banner))
            ->assertRedirect(route('admin.promo-banners.index'));

        $this->assertDatabaseMissing('promo_banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing('banners/gone.webp');
    }

    // ── Sidebar nav ─────────────────────────────────────────

    public function test_sidebar_links_to_promo_banners(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee(route('admin.promo-banners.index'))
            ->assertSee('Banner Promo');
    }
}
