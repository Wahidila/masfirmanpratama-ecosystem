<?php

namespace Tests\Feature\Blog;

use App\Models\Post;
use App\Services\Blog\InternalLinkRewriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rewriting legacy masfirmanpratama.com cross-post links to /blog/{slug},
 * both via the service directly and the blog:relink command.
 */
class InternalLinkRewriterTest extends TestCase
{
    use RefreshDatabase;

    private function rewrite(string $html): string
    {
        return (new InternalLinkRewriter)->rewrite($html);
    }

    public function test_rewrites_date_permalink_to_blog_slug(): void
    {
        Post::factory()->create(['slug' => 'sumber-kekuatan-doa']);

        $html = 'Baca: <a href="https://masfirmanpratama.com/2025/03/01/sumber-kekuatan-doa/">doa</a>';

        $this->assertStringContainsString('href="/blog/sumber-kekuatan-doa"', $this->rewrite($html));
    }

    public function test_rewrites_bare_slug_permalink(): void
    {
        Post::factory()->create(['slug' => 'mindset-kaya']);

        $html = '<a href="https://masfirmanpratama.com/mindset-kaya/">x</a>';

        $this->assertStringContainsString('href="/blog/mindset-kaya"', $this->rewrite($html));
    }

    public function test_resolves_collision_suffixed_slug_via_legacy_url(): void
    {
        // Stored slug got a -wp suffix on import, but inline links use the
        // original post_name (captured in legacy_url).
        Post::factory()->create([
            'slug' => 'berpikir-positif-wp2006',
            'legacy_url' => 'https://masfirmanpratama.com/berpikir-positif/',
        ]);

        $html = '<a href="https://masfirmanpratama.com/2024/11/24/berpikir-positif/">x</a>';

        $this->assertStringContainsString('href="/blog/berpikir-positif-wp2006"', $this->rewrite($html));
    }

    public function test_preserves_fragment(): void
    {
        Post::factory()->create(['slug' => 'artikel-a']);

        $html = '<a href="https://masfirmanpratama.com/2025/01/02/artikel-a/#bagian-2">x</a>';

        $this->assertStringContainsString('href="/blog/artikel-a#bagian-2"', $this->rewrite($html));
    }

    public function test_leaves_non_article_links_untouched(): void
    {
        Post::factory()->create(['slug' => 'ada-post']);

        $html = implode("\n", [
            '<img src="https://masfirmanpratama.com/wp-content/uploads/2026/06/a.jpg">',
            '<a href="https://masfirmanpratama.com/category/mindset/">kategori</a>',
            '<a href="https://masfirmanpratama.com/kelas/amc/">kelas</a>',
            '<a href="https://masfirmanpratama.com/">home</a>',
            '<a href="https://google.com/artikel-a/">luar</a>',
        ]);

        $this->assertSame($html, $this->rewrite($html));
    }

    public function test_bare_slug_without_matching_post_is_untouched(): void
    {
        // No post named tentang-saya → likely an old page, leave it alone.
        $html = '<a href="https://masfirmanpratama.com/tentang-saya/">tentang</a>';

        $this->assertSame($html, $this->rewrite($html));
    }

    public function test_unmatched_date_link_is_normalised_and_reported(): void
    {
        // Date permalink is unambiguously a blog article even if not imported yet.
        $rewriter = new InternalLinkRewriter;
        $out = $rewriter->rewrite('<a href="https://masfirmanpratama.com/2025/10/15/diam-yang-mengubah-hidup/">x</a>');

        $this->assertStringContainsString('href="/blog/diam-yang-mengubah-hidup"', $out);
        $this->assertContains('diam-yang-mengubah-hidup', $rewriter->unmatchedTargets());
    }

    public function test_command_rewrites_and_is_idempotent(): void
    {
        Post::factory()->create(['slug' => 'target-post']);
        $author = Post::factory()->create([
            'slug' => 'author-post',
            'content' => 'Baca ini juga: <a href="https://masfirmanpratama.com/2025/03/01/target-post/">Target</a>',
        ]);

        $this->artisan('blog:relink')
            ->assertSuccessful();

        $author->refresh();
        $this->assertStringContainsString('href="/blog/target-post"', $author->content);
        $this->assertStringNotContainsString('masfirmanpratama.com/2025', $author->content);

        // Second run finds nothing to change.
        $before = $author->content;
        $this->artisan('blog:relink')->assertSuccessful();
        $this->assertSame($before, $author->fresh()->content);
    }

    public function test_command_dry_run_writes_nothing(): void
    {
        $author = Post::factory()->create([
            'slug' => 'author-post',
            'content' => '<a href="https://masfirmanpratama.com/author-post/">self</a>',
        ]);
        Post::factory()->create(['slug' => 'author-post-2']); // ensure map has entries

        $original = $author->content;

        $this->artisan('blog:relink --dry-run')->assertSuccessful();

        $this->assertSame($original, $author->fresh()->content);
    }

    public function test_command_preserves_updated_at(): void
    {
        $stamp = now()->subYear();
        $author = Post::factory()->create([
            'slug' => 'author-post',
            'content' => '<a href="https://masfirmanpratama.com/author-post/">self</a>',
        ]);
        // Force a known updated_at without touching content.
        Post::withoutTimestamps(fn () => $author->forceFill(['updated_at' => $stamp])->save());

        $this->artisan('blog:relink')->assertSuccessful();

        $this->assertSame(
            $stamp->toDateTimeString(),
            $author->fresh()->updated_at->toDateTimeString(),
        );
    }
}
