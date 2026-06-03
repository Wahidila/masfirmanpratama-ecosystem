<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseAddToCartTest extends TestCase
{
    use RefreshDatabase;

    private function createCourse(array $overrides = []): Course
    {
        return Course::factory()->active()->create(array_merge([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas Reguler Alpha Mind Control',
            'price' => 4500000,
            'benefits' => [
                ['icon' => 'star', 'title' => 'Benefit A', 'desc' => 'Desc A'],
            ],
        ], $overrides));
    }

    public function test_course_cta_is_button_not_checkout_link(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler');
        $response->assertStatus(200);

        // CTA must NOT be a direct <a href> to checkout
        $content = $response->getContent();
        // The old pattern was <a href=\"/checkout\"> — must no longer exist as a CTA link
        $this->assertStringNotContainsString('href=\"/checkout\"', $content,
            'CTA should not be a direct link to /checkout — must use addToCartAndCheckout');
    }

    public function test_course_page_contains_is_shippable_false(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler');
        $response->assertStatus(200);

        $content = $response->getContent();
        // Alpine data should contain is_shippable: false for digital course
        // Js::from renders as JSON.parse('...') with unicode escapes for quotes
        $this->assertMatchesRegularExpression(
            '/is_shippable.{0,10}false/',
            $content,
            'Course Alpine data must contain is_shippable: false'
        );
    }

    public function test_course_page_contains_add_to_cart_and_checkout_function(): void
    {
        $this->createCourse();

        $response = $this->get('/kelas/kelas-amc-reguler');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('addToCartAndCheckout', $content,
            'Course page must contain addToCartAndCheckout function');
    }
}
