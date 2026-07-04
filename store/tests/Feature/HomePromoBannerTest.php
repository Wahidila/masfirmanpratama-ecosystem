<?php

namespace Tests\Feature;

use App\Models\PromoBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section "Jadwal Terdekat" homepage dinamis dari CRUD banner promo admin.
 * Banner tayang hanya bila active + dalam jendela starts_at..ends_at.
 */
class HomePromoBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_active_banner_with_link(): void
    {
        PromoBanner::factory()->create([
            'title' => 'Kelas AMC Bandung Desember',
            'link_url' => 'https://wa.me/6281230633464?text=daftar',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Jadwal Terdekat')
            ->assertSee('Kelas AMC Bandung Desember')
            ->assertSee('https://wa.me/6281230633464?text=daftar', false);
    }

    public function test_hides_section_when_no_visible_banner(): void
    {
        // Tanpa banner sama sekali → section hilang total.
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Jadwal Terdekat');
    }

    public function test_hides_inactive_banner(): void
    {
        PromoBanner::factory()->inactive()->create(['title' => 'Banner Nonaktif']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Banner Nonaktif')
            ->assertDontSee('Jadwal Terdekat');
    }

    public function test_hides_expired_banner(): void
    {
        // Event sudah lewat → auto-hide.
        PromoBanner::factory()->expired()->create(['title' => 'Event Kemarin']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Event Kemarin');
    }

    public function test_hides_upcoming_banner_until_window_starts(): void
    {
        PromoBanner::factory()->upcoming()->create(['title' => 'Event Nanti']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Event Nanti');
    }

    public function test_banner_without_link_renders_as_plain_image(): void
    {
        PromoBanner::factory()->create([
            'title' => 'Banner Tanpa Link',
            'link_url' => null,
        ]);

        $response = $this->get('/')->assertOk();
        $response->assertSee('Banner Tanpa Link');
        // Tidak ada <a> promo — cek aria-label banner tak dibungkus anchor.
        $this->assertStringNotContainsString(
            'aria-label="Banner Tanpa Link"',
            $response->getContent(),
        );
    }

    public function test_multiple_banners_ordered_by_sort_order(): void
    {
        PromoBanner::factory()->create(['title' => 'Banner Kedua', 'sort_order' => 5]);
        PromoBanner::factory()->create(['title' => 'Banner Pertama', 'sort_order' => 1]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Banner Kedua'),
            strpos($html, 'Banner Pertama'),
            'Banner sort_order kecil harus tampil duluan',
        );
    }
}
