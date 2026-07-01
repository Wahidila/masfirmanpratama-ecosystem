<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Course;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseManagementTest extends TestCase
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

    // -----------------------------------------------------------------
    // Auth guard
    // -----------------------------------------------------------------

    public function test_guest_redirected_from_courses_index(): void
    {
        $this->get(route('admin.courses.index'))
            ->assertRedirect(route('admin.login'));
    }

    // -----------------------------------------------------------------
    // Index page
    // -----------------------------------------------------------------

    public function test_admin_can_view_courses_index(): void
    {
        Course::factory()->count(2)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertSee('Kelas')
            ->assertSee('Tambah Kelas');
    }

    // -----------------------------------------------------------------
    // Create / Store
    // -----------------------------------------------------------------

    public function test_create_form_renders(): void
    {
        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->assertSee('Tambah Kelas Baru')
            ->assertSee('Identitas kelas');
    }

    public function test_create_form_uses_redesigned_two_column_layout(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->getContent();

        // Sidebar Publikasi + save bar lengket + layout 2 kolom (samakan dgn form produk).
        $this->assertStringContainsString('Publikasi', $html);
        $this->assertStringContainsString('sticky bottom-0', $html);
        $this->assertStringContainsString('lg:grid-cols-3', $html);
        // Status dikirim lewat hidden input (segmented control), bukan <select>.
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringNotContainsString('<select id="status"', $html);
    }

    public function test_create_form_uses_visual_icon_picker(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->getContent();

        // Icon picker (Alpine + daftar Lucide) menggantikan input teks nama ikon.
        $this->assertStringContainsString('iconPicker(', $html);
        $this->assertStringContainsString('MFP_ICON_LIST', $html);
        // Hidden input tetap membawa nama ikon ke server (kontrak controller sama).
        $this->assertStringContainsString('name="badge_icon"', $html);
        $this->assertStringContainsString('name="card_icon"', $html);
        // Input teks lama untuk badge_icon sudah tidak ada.
        $this->assertStringNotContainsString('id="badge_icon"', $html);
    }

    public function test_create_form_uses_visual_color_picker(): void
    {
        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.create'))
            ->assertOk()
            ->getContent();

        // Color picker (swatch) menggantikan input teks class Tailwind.
        $this->assertStringContainsString('colorPicker(', $html);
        // Hidden input tetap membawa kelas warna ke server.
        $this->assertStringContainsString('name="card_icon_color"', $html);
        // Input teks lama sudah tidak ada.
        $this->assertStringNotContainsString('id="card_icon_color"', $html);
        $this->assertStringNotContainsString('class Tailwind', $html);
    }

    public function test_admin_can_create_course(): void
    {
        $payload = $this->validPayload([
            'title' => 'Kelas Mind Power Reguler',
            'slug' => 'kelas-mind-power-reguler',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('courses', [
            'title' => 'Kelas Mind Power Reguler',
            'slug' => 'kelas-mind-power-reguler',
            'status' => 'draft',
        ]);

        // Verify syllabus stored as JSON array
        $course = Course::where('slug', 'kelas-mind-power-reguler')->first();
        $this->assertIsArray($course->syllabus);
        $this->assertCount(2, $course->syllabus);
        $this->assertSame('Point 1', $course->syllabus[0]);

        // Verify description stored as JSON array
        $this->assertIsArray($course->description);
        $this->assertCount(1, $course->description);
    }

    public function test_store_creates_course_with_image(): void
    {
        $payload = $this->validPayload([
            'image' => UploadedFile::fake()->image('cover.jpg', 800, 800)->size(500),
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload)
            ->assertRedirect(route('admin.courses.index'));

        $course = Course::first();
        $this->assertNotNull($course->image_path);
        $this->assertStringStartsWith('storage/', $course->image_path);
        $diskPath = str_starts_with($course->image_path, 'storage/') ? substr($course->image_path, 8) : $course->image_path;
        Storage::disk('public')->assertExists($diskPath);
    }

    // -----------------------------------------------------------------
    // Edit / Update
    // -----------------------------------------------------------------

    public function test_admin_can_update_course(): void
    {
        $course = Course::factory()->create([
            'title' => 'Old Title',
            'slug' => 'old-slug',
            'price' => 100000,
            'status' => 'draft',
        ]);

        $payload = $this->validPayload([
            'title' => 'New Title',
            'slug' => 'new-slug',
            'price' => 250000,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.courses.update', $course), $payload)
            ->assertRedirect(route('admin.courses.index'));

        $course->refresh();
        $this->assertSame('New Title', $course->title);
        $this->assertSame('new-slug', $course->slug);
        $this->assertSame('250000.00', $course->price);
        $this->assertSame('active', $course->status);
    }

    // -----------------------------------------------------------------
    // Destroy (soft delete)
    // -----------------------------------------------------------------

    public function test_admin_can_soft_delete_course(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->delete(route('admin.courses.destroy', $course))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSoftDeleted($course);
    }

    // -----------------------------------------------------------------
    // Restore
    // -----------------------------------------------------------------

    public function test_admin_can_restore_course(): void
    {
        $course = Course::factory()->create(['slug' => 'restore-me']);
        $course->delete();
        $this->assertSoftDeleted('courses', ['id' => $course->id]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.restore', $course->slug))
            ->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'deleted_at' => null,
        ]);
    }

    // -----------------------------------------------------------------
    // Bulk actions
    // -----------------------------------------------------------------

    public function test_bulk_archive(): void
    {
        $a = Course::factory()->create(['status' => 'active', 'slug' => 'c-a']);
        $b = Course::factory()->create(['status' => 'active', 'slug' => 'c-b']);
        $c = Course::factory()->create(['status' => 'active', 'slug' => 'c-c']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.bulk'), [
                'action' => 'archive',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertSame('archived', $a->fresh()->status);
        $this->assertSame('archived', $b->fresh()->status);
        $this->assertSame('active', $c->fresh()->status);
    }

    public function test_bulk_activate(): void
    {
        $a = Course::factory()->create(['status' => 'draft', 'slug' => 'd-a']);
        $b = Course::factory()->create(['status' => 'archived', 'slug' => 'd-b']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.bulk'), [
                'action' => 'activate',
                'ids' => [$a->id, $b->id],
            ])
            ->assertRedirect();

        $this->assertSame('active', $a->fresh()->status);
        $this->assertSame('active', $b->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------

    public function test_validation_title_required(): void
    {
        $payload = $this->validPayload(['title' => '']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload)
            ->assertSessionHasErrors(['title']);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_validation_slug_unique(): void
    {
        Course::factory()->create(['slug' => 'taken-slug']);

        $payload = $this->validPayload(['slug' => 'taken-slug']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload)
            ->assertSessionHasErrors(['slug']);
    }

    // -----------------------------------------------------------------
    // Homepage card fields (sync-c2)
    // -----------------------------------------------------------------

    public function test_admin_can_store_course_with_card_fields(): void
    {
        $payload = $this->validPayload([
            'title' => 'Kelas Platinum Card Test',
            'slug' => 'kelas-platinum-card-test',
            'sort_order' => 2,
            'show_on_homepage' => '1',
            'card_style' => 'highlight',
            'card_icon' => 'gem',
            'card_icon_color' => 'text-amber-500',
            'cta_label' => 'Daftar Platinum',
            'card_features_raw' => "Akses seumur hidup\nMentoring 1-on-1\nSertifikat premium",
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload)
            ->assertRedirect(route('admin.courses.index'));

        $course = Course::where('slug', 'kelas-platinum-card-test')->first();
        $this->assertNotNull($course);
        $this->assertSame(2, $course->sort_order);
        $this->assertTrue((bool) $course->show_on_homepage);
        $this->assertSame('highlight', $course->card_style);
        $this->assertSame('gem', $course->card_icon);
        $this->assertSame('text-amber-500', $course->card_icon_color);
        $this->assertSame('Daftar Platinum', $course->cta_label);
        $this->assertIsArray($course->card_features);
        $this->assertCount(3, $course->card_features);
        $this->assertSame('Akses seumur hidup', $course->card_features[0]);
        $this->assertSame('Mentoring 1-on-1', $course->card_features[1]);
        $this->assertSame('Sertifikat premium', $course->card_features[2]);
    }

    public function test_admin_can_update_card_fields(): void
    {
        $course = Course::factory()->create([
            'title' => 'Kelas Card Update',
            'slug' => 'kelas-card-update',
            'card_style' => 'default',
            'sort_order' => 0,
            'show_on_homepage' => false,
        ]);

        $payload = $this->validPayload([
            'title' => 'Kelas Card Update',
            'slug' => 'kelas-card-update',
            'sort_order' => 5,
            'show_on_homepage' => '1',
            'card_style' => 'highlight',
            'card_icon' => 'video',
            'card_icon_color' => 'text-blue-600',
            'cta_label' => 'Mulai Belajar',
            'card_features_raw' => "Fitur A\nFitur B\nFitur C\nFitur D",
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.courses.update', $course), $payload)
            ->assertRedirect(route('admin.courses.index'));

        $course->refresh();
        $this->assertSame(5, $course->sort_order);
        $this->assertTrue((bool) $course->show_on_homepage);
        $this->assertSame('highlight', $course->card_style);
        $this->assertSame('video', $course->card_icon);
        $this->assertSame('text-blue-600', $course->card_icon_color);
        $this->assertSame('Mulai Belajar', $course->cta_label);
        $this->assertIsArray($course->card_features);
        $this->assertCount(4, $course->card_features);
        $this->assertSame('Fitur A', $course->card_features[0]);
        $this->assertSame('Fitur D', $course->card_features[3]);
    }

    public function test_validation_card_style_invalid(): void
    {
        $payload = $this->validPayload([
            'card_style' => 'rainbow',
        ]);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.store'), $payload)
            ->assertSessionHasErrors('card_style');
    }

    public function test_show_on_homepage_unchecked_stores_false(): void
    {
        $course = Course::factory()->create([
            'title' => 'Kelas Uncheck Test',
            'slug' => 'kelas-uncheck-test',
            'show_on_homepage' => true,
        ]);

        $payload = $this->validPayload([
            'title' => 'Kelas Uncheck Test',
            'slug' => 'kelas-uncheck-test',
            // show_on_homepage NOT sent (checkbox unchecked)
        ]);

        $this->actingAs($this->admin, 'admin')
            ->put(route('admin.courses.update', $course), $payload)
            ->assertRedirect(route('admin.courses.index'));

        $course->refresh();
        $this->assertFalse((bool) $course->show_on_homepage);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Kelas Test',
            'slug' => 'kelas-test',
            'price' => 150000,
            'status' => 'draft',
            'installment_available' => '1',
            'description_raw' => 'Paragraph one',
            'syllabus_raw' => "Point 1\nPoint 2",
        ], $overrides);
    }
}
