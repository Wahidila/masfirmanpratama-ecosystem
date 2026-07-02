<?php

namespace Tests\Feature\Blog;

use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Post;
use App\Services\Blog\WxrImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WxrImporterTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): string
    {
        return base_path('tests/Fixtures/wxr-sample.xml');
    }

    public function test_imports_categories_with_hierarchy(): void
    {
        (new WxrImporter)->import($this->fixture());

        $this->assertSame(2, BlogCategory::count());
        $parent = BlogCategory::where('slug', 'kekuatan-pikiran')->first();
        $child = BlogCategory::where('slug', 'sub-pikiran')->first();
        $this->assertNotNull($parent);
        $this->assertSame(11, $parent->wp_term_id);
        $this->assertSame($parent->id, $child->parent_id);
    }

    public function test_imports_tags(): void
    {
        (new WxrImporter)->import($this->fixture());

        $this->assertSame(2, BlogTag::count());
        $this->assertDatabaseHas('blog_tags', ['slug' => 'berpikir-positif']);
    }

    public function test_imports_posts_skipping_page_and_attachment(): void
    {
        (new WxrImporter)->import($this->fixture());

        // publish + draft + future + trash = 4 (page skipped, attachment is media)
        $this->assertSame(4, Post::withTrashed()->count());
    }

    public function test_publish_post_mapped_fully(): void
    {
        (new WxrImporter)->import($this->fixture());

        $post = Post::where('wp_post_id', 2006)->first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertSame('stop-berpikir-positif', $post->slug);
        $this->assertNotNull($post->published_at);
        $this->assertSame('firmanp', $post->wp_author_login);
        $this->assertSame('https://masfirmanpratama.com/stop-berpikir-positif/', $post->legacy_url);
        $this->assertSame('Stop Berpikir Positif - Expert in Mind Power', $post->meta_seo['title']);
        $this->assertSame('Kenapa berpikir positif saja tidak cukup.', $post->meta_seo['description']);
        // taxonomy
        $this->assertTrue($post->categories->pluck('slug')->contains('kekuatan-pikiran'));
        $this->assertEqualsCanonicalizing(['berpikir-positif', 'pikiran'], $post->tags->pluck('slug')->all());
        // primary category resolved from yoast term id 11
        $this->assertSame(BlogCategory::where('slug', 'kekuatan-pikiran')->value('id'), $post->primary_category_id);
        // dangerous html stripped
        $this->assertStringNotContainsString('<script', $post->content);
    }

    public function test_status_mapping(): void
    {
        (new WxrImporter)->import($this->fixture());

        $this->assertSame('draft', Post::where('wp_post_id', 2010)->value('status'));
        $this->assertSame('scheduled', Post::where('wp_post_id', 2011)->value('status'));

        $trash = Post::withTrashed()->where('wp_post_id', 2012)->first();
        $this->assertSame('draft', $trash->status);
        $this->assertTrue($trash->trashed());
    }

    public function test_import_is_idempotent(): void
    {
        (new WxrImporter)->import($this->fixture());
        $firstCount = Post::withTrashed()->count();
        $postId = Post::where('wp_post_id', 2006)->value('id');

        (new WxrImporter)->import($this->fixture());

        $this->assertSame($firstCount, Post::withTrashed()->count());
        // same row updated, not duplicated
        $this->assertSame($postId, Post::where('wp_post_id', 2006)->value('id'));
        $this->assertSame(1, Post::where('wp_post_id', 2006)->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $result = (new WxrImporter(dryRun: true))->import($this->fixture());

        $this->assertSame(0, Post::count());
        $this->assertSame(0, BlogCategory::count());
        $this->assertSame(4, $result['summary']['posts_created']);
        $this->assertSame(2, $result['summary']['categories']);
    }

    public function test_media_download_and_inline_rewrite(): void
    {
        Storage::fake('public');
        Http::fake([
            'masfirmanpratama.com/wp-content/uploads/*' => Http::response('FAKEIMAGEBYTES', 200),
        ]);

        (new WxrImporter(downloadMedia: true))->import($this->fixture());

        // featured image rehosted to public disk + pointed at storage path
        Storage::disk('public')->assertExists('blog/uploads/2026/06/berpikir-positif.jpg');
        $post = Post::where('wp_post_id', 2006)->first();
        $this->assertSame('storage/blog/uploads/2026/06/berpikir-positif.jpg', $post->image_path);

        // inline image URL rewritten (size suffix stripped, rehosted path)
        $this->assertStringContainsString('storage/blog/uploads/2026/06/berpikir-positif.jpg', $post->content);
        $this->assertStringNotContainsString('wp-content/uploads', $post->content);
    }

    public function test_without_media_keeps_old_url(): void
    {
        (new WxrImporter(downloadMedia: false))->import($this->fixture());

        $post = Post::where('wp_post_id', 2006)->first();
        // featured image keeps old absolute URL until rehosted
        $this->assertSame('https://masfirmanpratama.com/wp-content/uploads/2026/06/berpikir-positif.jpg', $post->image_path);
        $this->assertStringStartsWith('http', $post->imageUrl());
    }

    public function test_invalid_file_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        (new WxrImporter)->import(base_path('composer.json'));
    }
}
