<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Post;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCrudTest extends TestCase
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

    // ---- Auth guard ----

    public function test_guest_redirected_from_posts_index(): void
    {
        $this->get(route('admin.posts.index'))->assertRedirect(route('admin.login'));
    }

    public function test_guest_cannot_store_post(): void
    {
        $this->post(route('admin.posts.store'), $this->validPayload())
            ->assertRedirect(route('admin.login'));
        $this->assertDatabaseCount('posts', 0);
    }

    // ---- Index ----

    public function test_index_renders_for_admin(): void
    {
        Post::factory()->count(3)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('Blog')
            ->assertSee('Tambah Artikel');
    }

    public function test_index_filters_by_status(): void
    {
        Post::factory()->published()->create(['title' => 'Artikel Tayang', 'slug' => 'artikel-tayang']);
        Post::factory()->draft()->create(['title' => 'Artikel Draft', 'slug' => 'artikel-draft']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.posts.index', ['status' => 'published']))
            ->assertOk()
            ->assertSee('Artikel Tayang')
            ->assertDontSee('Artikel Draft');
    }

    public function test_index_filters_by_category(): void
    {
        $cat = BlogCategory::create(['name' => 'Kekuatan Pikiran', 'slug' => 'kekuatan-pikiran']);
        $inCat = Post::factory()->create(['title' => 'Punya Kategori', 'slug' => 'punya-kategori']);
        $inCat->categories()->attach($cat);
        Post::factory()->create(['title' => 'Tanpa Kategori', 'slug' => 'tanpa-kategori']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.posts.index', ['category' => 'kekuatan-pikiran']))
            ->assertOk()
            ->assertSee('Punya Kategori')
            ->assertDontSee('Tanpa Kategori');
    }

    public function test_index_search_by_title(): void
    {
        Post::factory()->create(['title' => 'Mindset Kaya', 'slug' => 'mindset-kaya']);
        Post::factory()->create(['title' => 'Topik Lain', 'slug' => 'topik-lain']);

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.posts.index', ['q' => 'Mindset']))
            ->assertOk()
            ->assertSee('Mindset Kaya')
            ->assertDontSee('Topik Lain');
    }

    // ---- Create / Store ----

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertSee('Tambah Artikel Baru')
            ->assertSee('Identitas artikel');
    }

    public function test_store_creates_post_with_categories_and_tags(): void
    {
        $cat = BlogCategory::create(['name' => 'Kekayaan', 'slug' => 'kekayaan']);

        $payload = $this->validPayload([
            'title' => 'Rahasia Rezeki',
            'slug' => 'rahasia-rezeki',
            'status' => 'published',
            'category_ids' => [$cat->id],
            'tags' => 'rezeki, mindset',
            'image' => UploadedFile::fake()->image('hero.jpg', 1200, 630)->size(400),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $payload)
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('status');

        $post = Post::where('slug', 'rahasia-rezeki')->first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertStringStartsWith('storage/', $post->image_path);
        $this->assertTrue($post->categories->contains($cat));
        $this->assertSame($cat->id, $post->primary_category_id);
        $this->assertEqualsCanonicalizing(['rezeki', 'mindset'], $post->tags->pluck('slug')->all());
        $this->assertSame(2, BlogTag::count());
    }

    public function test_store_auto_generates_slug(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $this->validPayload(['title' => 'Judul Artikel Keren!', 'slug' => '']))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseHas('posts', ['slug' => 'judul-artikel-keren']);
    }

    public function test_store_sanitizes_dangerous_html(): void
    {
        $payload = $this->validPayload([
            'slug' => 'xss-test',
            'content' => '<p>Aman</p><script>alert(1)</script><a href="javascript:alert(2)">klik</a>',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $payload)
            ->assertRedirect(route('admin.posts.index'));

        $post = Post::where('slug', 'xss-test')->first();
        $this->assertStringContainsString('<p>Aman</p>', $post->content);
        $this->assertStringNotContainsString('<script', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);
    }

    public function test_store_requires_published_at_for_scheduled(): void
    {
        $payload = $this->validPayload(['status' => 'scheduled', 'published_at' => '']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $payload)
            ->assertSessionHasErrors(['published_at']);
    }

    public function test_store_rejects_missing_title(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $this->validPayload(['title' => '']))
            ->assertSessionHasErrors(['title']);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_store_rejects_invalid_status(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $this->validPayload(['status' => 'wonky']))
            ->assertSessionHasErrors(['status']);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        Post::factory()->create(['slug' => 'taken']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.store'), $this->validPayload(['slug' => 'taken']))
            ->assertSessionHasErrors(['slug']);
    }

    // ---- Update ----

    public function test_update_changes_fields_and_syncs_categories(): void
    {
        $post = Post::factory()->draft()->create(['title' => 'Lama', 'slug' => 'lama']);
        $catA = BlogCategory::create(['name' => 'A', 'slug' => 'a']);
        $post->categories()->attach($catA);
        $catB = BlogCategory::create(['name' => 'B', 'slug' => 'b']);

        $payload = $this->validPayload([
            'title' => 'Baru',
            'slug' => 'baru',
            'status' => 'published',
            'category_ids' => [$catB->id],
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.posts.update', $post), $payload)
            ->assertRedirect(route('admin.posts.index'));

        $post->refresh();
        $this->assertSame('Baru', $post->title);
        $this->assertSame('published', $post->status);
        $this->assertEqualsCanonicalizing([$catB->id], $post->categories()->pluck('blog_categories.id')->all());
    }

    // ---- Destroy / Restore / Bulk ----

    public function test_destroy_soft_deletes(): void
    {
        $post = Post::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.posts.destroy', $post))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertSoftDeleted($post);
    }

    public function test_restore_brings_back_post(): void
    {
        $post = Post::factory()->create();
        $post->delete();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.restore', $post->slug))
            ->assertRedirect(route('admin.posts.index'));

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    public function test_bulk_publish_sets_status_and_published_at(): void
    {
        $a = Post::factory()->draft()->create();
        $b = Post::factory()->draft()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.bulk'), ['action' => 'publish', 'ids' => [$a->id, $b->id]])
            ->assertRedirect();

        $this->assertSame('published', $a->fresh()->status);
        $this->assertNotNull($a->fresh()->published_at);
        $this->assertSame('published', $b->fresh()->status);
    }

    // ---- Helpers ----

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Artikel Test',
            'slug' => 'artikel-test',
            'excerpt' => 'Ringkasan singkat.',
            'content' => '<p>Isi artikel yang cukup panjang untuk diuji.</p>',
            'status' => 'draft',
        ], $overrides);
    }
}
