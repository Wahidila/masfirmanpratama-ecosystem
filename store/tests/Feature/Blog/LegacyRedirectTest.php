<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_root_url_301_redirects_to_blog(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'stop-berpikir-positif']);

        $this->get('/stop-berpikir-positif')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', 'stop-berpikir-positif'));
    }

    public function test_old_root_url_with_trailing_slash_redirects(): void
    {
        Post::factory()->published()->create(['slug' => 'punya-trailing']);

        $this->get('/punya-trailing/')
            ->assertStatus(301)
            ->assertRedirect(route('blog.show', 'punya-trailing'));
    }

    public function test_unknown_root_slug_404s(): void
    {
        $this->get('/slug-yang-tidak-ada')->assertNotFound();
    }

    public function test_draft_post_root_url_not_redirected(): void
    {
        Post::factory()->draft()->create(['slug' => 'draft-root']);

        // draft is not "published", so the catch-all must 404 (not leak a redirect)
        $this->get('/draft-root')->assertNotFound();
    }

    public function test_reserved_prefix_not_shadowed(): void
    {
        // /produk must still hit the products index, not the legacy catch-all
        $this->get('/produk')->assertOk();
    }

    public function test_blog_route_not_shadowed(): void
    {
        Post::factory()->published()->create(['slug' => 'sebuah-artikel']);

        // /blog/{slug} still serves the article directly (200), not a redirect
        $this->get(route('blog.show', 'sebuah-artikel'))->assertOk();
    }

    public function test_legacy_category_redirects(): void
    {
        BlogCategory::create(['name' => 'Kekayaan', 'slug' => 'kekayaan']);

        $this->get('/category/kekayaan')
            ->assertStatus(301)
            ->assertRedirect(route('blog.index', ['category' => 'kekayaan']));
    }

    public function test_legacy_tag_redirects_to_blog(): void
    {
        $this->get('/tag/pikiran')
            ->assertStatus(301)
            ->assertRedirect(route('blog.index'));
    }
}
