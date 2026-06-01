<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseStorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_courses_and_books(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas Reguler Alpha Mind Control',
            'price' => 4500000,
            'status' => 'active',
        ]);

        $book1 = Product::factory()->active()->book()->create([
            'title' => 'Buku Alpha Mind Control',
            'slug' => 'buku-amc',
            'price' => 185000,
        ]);

        $book2 = Product::factory()->active()->book()->create([
            'title' => 'Buku Hypno Mind Control',
            'slug' => 'buku-hypno',
            'price' => 150000,
        ]);

        $response = $this->get('/produk');
        $response->assertStatus(200);
        $response->assertSee('Kelas Reguler Alpha Mind Control');
        $response->assertSee('Buku Alpha Mind Control');
        $response->assertSee('Buku Hypno Mind Control');
    }

    public function test_course_detail_page_renders_from_db(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas Reguler Alpha Mind Control',
            'price' => 4500000,
            'syllabus' => ['Topik A', 'Topik B'],
            'benefits' => [
                ['icon' => 'star', 'title' => 'Benefit X', 'desc' => 'Desc X'],
            ],
            'description' => ['Paragraf 1'],
            'schedule' => [
                ['title' => 'Sabtu', 'detail' => '09:00 WIB'],
            ],
        ]);

        $response = $this->get('/produk/kelas-amc-reguler');
        $response->assertStatus(200);
        $response->assertSee('Kelas Reguler Alpha Mind Control');
        $response->assertSee('Topik A');
        $response->assertSee('Benefit X');
        $response->assertSee('Paragraf 1');
        $response->assertSee('Sabtu');
    }

    public function test_course_detail_uses_db_not_config(): void
    {
        Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'DB Course Title Unique',
            'price' => 4500000,
        ]);

        $response = $this->get('/produk/kelas-amc-reguler');
        $response->assertStatus(200);
        $response->assertSee('DB Course Title Unique');
    }
}
