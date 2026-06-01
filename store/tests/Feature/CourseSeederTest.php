<?php

namespace Tests\Feature;

use App\Models\Course;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_seeder_creates_one_course(): void
    {
        $this->seed(CourseSeeder::class);

        $this->assertSame(1, Course::count());
    }

    public function test_seeded_course_has_correct_slug_and_title(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertSame('kelas-amc-reguler', $course->slug);
        $this->assertSame('Kelas Reguler Alpha Mind Control', $course->title);
    }

    public function test_seeded_course_has_syllabus_array(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertIsArray($course->syllabus);
        $this->assertCount(20, $course->syllabus);
    }

    public function test_seeded_course_has_price_and_status(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertSame('4500000.00', $course->price);
        $this->assertSame('active', $course->status);
    }

    public function test_seeded_course_has_description_schedule_benefits_testimonials(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertIsArray($course->description);
        $this->assertNotEmpty($course->description);
        $this->assertIsArray($course->schedule);
        $this->assertNotEmpty($course->schedule);
        $this->assertIsArray($course->benefits);
        $this->assertNotEmpty($course->benefits);
        $this->assertIsArray($course->testimonials);
        $this->assertNotEmpty($course->testimonials);
    }

    public function test_seeded_course_is_idempotent(): void
    {
        $this->seed(CourseSeeder::class);
        $this->seed(CourseSeeder::class);

        $this->assertSame(1, Course::count());
    }

    public function test_seeded_course_has_installment_available_true(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertTrue($course->installment_available);
    }

    public function test_seeded_course_has_related_array(): void
    {
        $this->seed(CourseSeeder::class);

        $course = Course::first();

        $this->assertIsArray($course->related);
        $this->assertNotEmpty($course->related);
    }

    public function test_course_model_has_active_scope(): void
    {
        $this->seed(CourseSeeder::class);

        $active = Course::active()->get();

        $this->assertCount(1, $active);
    }
}
