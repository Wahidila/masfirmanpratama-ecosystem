<?php

namespace Tests\Feature\Blog;

use App\Models\Admin;
use App\Models\Post;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin WordPress import endpoint (POST admin.posts.import).
 *
 * Guards the fix for the "Maximum execution time of 30s exceeded" fatal:
 * the request must run the importer to completion (incl. media downloads)
 * without dying, and media fetches must be faked/bounded in tests.
 */
class PostImportEndpointTest extends TestCase
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

    private function wxrUpload(): UploadedFile
    {
        // Reuse the shared WXR fixture as an uploaded .xml file (test mode = true
        // bypasses the is_uploaded_file() check).
        return new UploadedFile(
            base_path('tests/Fixtures/wxr-sample.xml'),
            'export.xml',
            'text/xml',
            null,
            true,
        );
    }

    public function test_import_requires_admin(): void
    {
        $this->post(route('admin.posts.import'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_import_with_media_download_completes_without_timeout(): void
    {
        Http::fake([
            'masfirmanpratama.com/wp-content/uploads/*' => Http::response('FAKEIMAGEBYTES', 200),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.import'), [
                'wxr' => $this->wxrUpload(),
                'download_media' => '1',
            ])
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('status');

        // publish + draft + future + trash = 4 posts imported.
        $this->assertSame(4, Post::withTrashed()->count());
        Storage::disk('public')->assertExists('blog/uploads/2026/06/berpikir-positif.jpg');
    }

    public function test_import_without_media_does_not_hit_network(): void
    {
        Http::fake(); // any outbound HTTP would record a request

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.import'), [
                'wxr' => $this->wxrUpload(),
            ])
            ->assertRedirect(route('admin.posts.index'));

        Http::assertNothingSent();
        $this->assertSame(4, Post::withTrashed()->count());
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.import'), [
                'wxr' => $this->wxrUpload(),
                'dry_run' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('import_result');

        $this->assertSame(0, Post::count());
    }

    public function test_rejects_non_xml_upload(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.posts.import'), [
                'wxr' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertSessionHasErrors('wxr');
    }
}
