<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_renders_and_shows_published_only(): void
    {
        Post::factory()->published()->create(['title' => 'Artikel Tayang', 'slug' => 'artikel-tayang']);
        Post::factory()->draft()->create(['title' => 'Artikel Draft', 'slug' => 'artikel-draft']);
        Post::factory()->scheduled()->create(['title' => 'Artikel Terjadwal', 'slug' => 'artikel-terjadwal']);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Artikel Tayang')
            ->assertDontSee('Artikel Draft')
            ->assertDontSee('Artikel Terjadwal');
    }

    /**
     * Regresi: post 'published' dengan published_at masa depan HARUS tetap tayang
     * (status 'published' = sumber kebenaran visibilitas). Untuk menunda tayang,
     * gunakan status 'scheduled'. Cegah bug "Published tapi hilang dari /blog".
     */
    public function test_blog_index_shows_published_even_with_future_date(): void
    {
        Post::factory()->published()->create([
            'title' => 'Published Tanggal Depan',
            'slug' => 'published-tanggal-depan',
            'published_at' => now()->addDays(10),
        ]);

        $this->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Published Tanggal Depan');
    }

    public function test_blog_index_filters_by_category(): void
    {
        // Unique excerpt is rendered only in the main article cards (not the
        // global "recent" sidebar), so it precisely tests the filtered listing.
        $cat = BlogCategory::create(['name' => 'Kekayaan', 'slug' => 'kekayaan']);
        $inCat = Post::factory()->published()->create(['title' => 'Tentang Rezeki', 'slug' => 'tentang-rezeki', 'excerpt' => 'excerpt-masuk-kategori']);
        $inCat->categories()->attach($cat);
        Post::factory()->published()->create(['title' => 'Topik Berbeda', 'slug' => 'topik-berbeda', 'excerpt' => 'excerpt-luar-kategori']);

        $this->get(route('blog.index', ['category' => 'kekayaan']))
            ->assertOk()
            ->assertSee('excerpt-masuk-kategori')
            ->assertDontSee('excerpt-luar-kategori');
    }

    public function test_blog_index_search(): void
    {
        Post::factory()->published()->create(['title' => 'Mindset Kaya', 'slug' => 'mindset-kaya', 'excerpt' => 'excerpt-cocok-cari']);
        Post::factory()->published()->create(['title' => 'Hal Lain', 'slug' => 'hal-lain', 'excerpt' => 'excerpt-tidak-cocok']);

        $this->get(route('blog.index', ['q' => 'Mindset']))
            ->assertOk()
            ->assertSee('excerpt-cocok-cari')
            ->assertDontSee('excerpt-tidak-cocok');
    }

    public function test_blog_show_renders_published_post(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Kekuatan Pikiran',
            'slug' => 'kekuatan-pikiran-artikel',
            'content' => '<p>Isi artikel yang bermanfaat.</p>',
        ]);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('Kekuatan Pikiran')
            ->assertSee('Isi artikel yang bermanfaat', false);
    }

    public function test_blog_show_404_for_draft(): void
    {
        $post = Post::factory()->draft()->create(['slug' => 'draft-post']);

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_blog_show_404_for_scheduled_future(): void
    {
        $post = Post::factory()->scheduled()->create(['slug' => 'scheduled-post']);

        $this->get(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_blog_show_increments_views(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'view-me', 'views' => 0]);

        $this->get(route('blog.show', $post->slug))->assertOk();

        $this->assertSame(1, $post->fresh()->views);
    }

    public function test_blog_show_lists_related_products_cta(): void
    {
        $product = \App\Models\Product::factory()->create(['title' => 'Buku Mind Power', 'slug' => 'buku-mind-power', 'type' => 'book', 'status' => 'active']);
        $post = Post::factory()->published()->create(['slug' => 'with-cta']);
        $post->products()->attach($product);

        $this->get(route('blog.show', $post->slug))
            ->assertOk()
            ->assertSee('Buku Mind Power');
    }

    public function test_navbar_has_blog_link(): void
    {
        $this->get(url('/'))
            ->assertOk()
            ->assertSee(route('blog.index'));
    }
}
