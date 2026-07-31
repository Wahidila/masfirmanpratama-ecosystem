<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Course;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur manajemen kelas di /admin/courses — paritas dengan /admin/products:
 * toggle status cepat, sorting, dan bulk "pilih semua sesuai filter".
 */
class CourseIndexFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminSeeder::class);
        $this->admin = Admin::first();
    }

    // ── Quick status toggle ──────────────────────────────────────────────

    public function test_toggle_status_active_to_archived(): void
    {
        $c = Course::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->patch(route('admin.courses.toggle-status', $c))
            ->assertRedirect();

        $this->assertSame('archived', $c->fresh()->status);
    }

    public function test_toggle_status_non_active_becomes_active(): void
    {
        foreach (['draft', 'archived'] as $status) {
            $c = Course::factory()->create(['status' => $status]);

            $this->actingAs($this->admin, 'admin')
                ->patch(route('admin.courses.toggle-status', $c));

            $this->assertSame('active', $c->fresh()->status);
        }
    }

    public function test_toggle_status_requires_auth(): void
    {
        $c = Course::factory()->create(['status' => 'active']);

        $this->patch(route('admin.courses.toggle-status', $c))
            ->assertRedirect(route('admin.login'));

        $this->assertSame('active', $c->fresh()->status);
    }

    // ── Sorting (whitelist) ──────────────────────────────────────────────

    public function test_index_sorts_by_price_ascending(): void
    {
        Course::factory()->create(['title' => 'Mahal', 'price' => 9_000_000]);
        Course::factory()->create(['title' => 'Murah', 'price' => 1_000_000]);

        $courses = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.index', ['sort' => 'price', 'dir' => 'asc']))
            ->assertOk()
            ->viewData('courses');

        $this->assertSame('Murah', $courses->first()->title);
    }

    public function test_index_ignores_non_whitelisted_sort_column(): void
    {
        Course::factory()->count(2)->create();

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.index', ['sort' => 'deleted_at', 'dir' => 'asc']))
            ->assertOk();
    }

    // ── Bulk "pilih semua sesuai filter" ─────────────────────────────────

    public function test_bulk_select_all_archives_entire_filtered_set_not_just_posted_ids(): void
    {
        Course::factory()->count(25)->create(['status' => 'draft']);
        Course::factory()->count(3)->create(['status' => 'active']);

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.bulk'), [
                'action' => 'archive',
                'select_all' => '1',
                'status' => 'draft', // filter dikirim sebagai body (hidden input)
            ])
            ->assertRedirect();

        $this->assertSame(25, Course::where('status', 'archived')->count());
        $this->assertSame(3, Course::where('status', 'active')->count());
    }

    public function test_bulk_without_ids_and_without_select_all_errors(): void
    {
        Course::factory()->create();

        $this->actingAs($this->admin, 'admin')
            ->post(route('admin.courses.bulk'), ['action' => 'archive'])
            ->assertSessionHasErrors('ids');
    }

    // ── UI hooks ─────────────────────────────────────────────────────────

    public function test_index_renders_select_all_sortable_headers_and_toggle(): void
    {
        Course::factory()->create(['status' => 'active']);

        $html = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('toggleAllOnPage', $html);   // header select-all
        $this->assertStringContainsString('sort=price', $html);        // header sortable
        $this->assertStringContainsString('name="select_all"', $html); // flag pilih-semua
        $this->assertStringContainsString('toggle-status', $html);     // quick status toggle
    }
}
