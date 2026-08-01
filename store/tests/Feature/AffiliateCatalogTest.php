<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_returns_active_products_and_courses(): void
    {
        Product::factory()->active()->book()->create(['slug' => 'buku-x', 'title' => 'Buku X', 'price' => 185000]);
        Course::factory()->active()->create(['slug' => 'kelas-amc', 'title' => 'Kelas AMC', 'price' => 4_500_000]);

        $response = $this->getJson('/api/affiliate/products');

        $response->assertOk()
            ->assertJsonStructure(['products' => [['type', 'slug', 'title', 'price', 'image_url', 'url']]]);

        $products = collect($response->json('products'));
        $this->assertTrue($products->contains(fn ($p) => $p['slug'] === 'buku-x' && $p['type'] === 'book'));
        $this->assertTrue($products->contains(fn ($p) => $p['slug'] === 'kelas-amc' && $p['type'] === 'course'));

        $book = $products->firstWhere('slug', 'buku-x');
        $this->assertStringContainsString('/produk/buku-x', $book['url']);
        $course = $products->firstWhere('slug', 'kelas-amc');
        $this->assertStringContainsString('/kelas/kelas-amc', $course['url']);
    }

    public function test_catalog_excludes_inactive_products(): void
    {
        Product::factory()->book()->create(['slug' => 'buku-draft', 'status' => 'draft']);

        $products = collect($this->getJson('/api/affiliate/products')->json('products'));

        $this->assertFalse($products->contains(fn ($p) => $p['slug'] === 'buku-draft'));
    }
}
