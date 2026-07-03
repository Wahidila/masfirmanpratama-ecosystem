<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_published_posts_only(): void
    {
        Post::factory()->published()->create(['slug' => 'tayang-sitemap']);
        Post::factory()->draft()->create(['slug' => 'draft-sitemap']);

        $response = $this->get('/sitemap-blog.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee(route('blog.show', 'tayang-sitemap'), false);
        $response->assertDontSee(route('blog.show', 'draft-sitemap'), false);
    }

    public function test_rss_feed_renders(): void
    {
        Post::factory()->published()->create(['title' => 'Judul RSS', 'slug' => 'judul-rss']);

        $response = $this->get(route('blog.feed'));

        $response->assertOk();
        $this->assertStringContainsString('application/rss+xml', $response->headers->get('Content-Type'));
        $response->assertSee('<rss', false);
        $response->assertSee('Judul RSS', false);
    }

    public function test_publish_scheduled_command_flips_due_posts(): void
    {
        $due = Post::factory()->create(['status' => 'scheduled', 'slug' => 'due', 'published_at' => now()->subMinute()]);
        $future = Post::factory()->create(['status' => 'scheduled', 'slug' => 'future', 'published_at' => now()->addDay()]);

        $this->artisan('posts:publish-scheduled')->assertSuccessful();

        $this->assertSame('published', $due->fresh()->status);
        $this->assertSame('scheduled', $future->fresh()->status);
    }

    public function test_due_scheduled_post_visible_before_cron_flip(): void
    {
        // Safety net: scope should surface a scheduled post whose time passed even
        // if posts:publish-scheduled hasn't run yet.
        $post = Post::factory()->create(['status' => 'scheduled', 'slug' => 'due-visible', 'published_at' => now()->subMinute()]);

        $this->get(route('blog.show', $post->slug))->assertOk();
        $this->assertTrue(Post::published()->whereKey($post->id)->exists());
    }

    public function test_future_scheduled_post_hidden(): void
    {
        $post = Post::factory()->create(['status' => 'scheduled', 'slug' => 'future-hidden', 'published_at' => now()->addWeek()]);

        $this->get(route('blog.show', $post->slug))->assertNotFound();
        $this->assertFalse(Post::published()->whereKey($post->id)->exists());
    }
}
