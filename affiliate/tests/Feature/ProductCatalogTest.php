<?php

namespace Tests\Feature;

use App\Models\Affiliator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function activeAffiliator(): Affiliator
    {
        return Affiliator::factory()->create([
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function fakeCatalog(): void
    {
        Http::fake([
            '*/api/affiliate/products' => Http::response([
                'products' => [
                    ['type' => 'course', 'slug' => 'kelas-amc', 'title' => 'Kelas AMC Reguler', 'price' => 4_500_000, 'image_url' => null, 'url' => 'https://store.test/kelas/kelas-amc'],
                    ['type' => 'book', 'slug' => 'buku-mpl', 'title' => 'Buku MPL', 'price' => 185_000, 'image_url' => null, 'url' => 'https://store.test/produk/buku-mpl'],
                ],
            ], 200),
        ]);
    }

    public function test_products_page_lists_catalog(): void
    {
        $this->fakeCatalog();

        $this->actingAs($this->activeAffiliator(), 'affiliator')
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Kelas AMC Reguler')
            ->assertSee('Buku MPL');
    }

    public function test_referral_create_page_shows_product_picker(): void
    {
        $this->fakeCatalog();

        $this->actingAs($this->activeAffiliator(), 'affiliator')
            ->get(route('referrals.create'))
            ->assertOk()
            ->assertSee('Pilih produk')
            ->assertSee('URL custom');
    }

    public function test_referral_created_with_selected_product_url(): void
    {
        $affiliator = $this->activeAffiliator();

        $this->actingAs($affiliator, 'affiliator')
            ->post(route('referrals.store'), [
                'label' => 'Instagram',
                'target_url' => 'https://store.test/kelas/kelas-amc',
            ])
            ->assertRedirect(route('referrals.index'));

        $this->assertDatabaseHas('referral_codes', [
            'affiliator_id' => $affiliator->id,
            'target_url' => 'https://store.test/kelas/kelas-amc',
        ]);
    }

    public function test_referrals_index_shows_product_name_and_store_link(): void
    {
        $this->fakeCatalog();
        $affiliator = $this->activeAffiliator();
        $affiliator->referralCodes()->create([
            'code' => 'ABCD1234',
            'label' => 'IG Bio',
            'target_url' => 'https://store.test/kelas/kelas-amc',
        ]);

        $this->actingAs($affiliator, 'affiliator')
            ->get(route('referrals.index'))
            ->assertOk()
            ->assertSee('Kelas AMC Reguler')      // nama produk dari katalog
            ->assertSee('Lihat produk di store'); // aria-label ikon eye
    }

    public function test_products_page_handles_unavailable_catalog(): void
    {
        Http::fake(['*/api/affiliate/products' => Http::response('', 500)]);

        $this->actingAs($this->activeAffiliator(), 'affiliator')
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('belum tersedia');
    }
}
