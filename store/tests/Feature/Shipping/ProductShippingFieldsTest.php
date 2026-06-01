<?php

namespace Tests\Feature\Shipping;

use App\Models\Course;
use App\Models\Product;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShippingFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_model_has_weight_kg_in_fillable(): void
    {
        $product = new Product;

        $this->assertContains('weight_kg', $product->getFillable());
        $this->assertContains('is_shippable', $product->getFillable());
        $this->assertContains('length_cm', $product->getFillable());
        $this->assertContains('width_cm', $product->getFillable());
        $this->assertContains('height_cm', $product->getFillable());
    }

    public function test_is_shippable_casts_to_boolean(): void
    {
        $product = new Product(['is_shippable' => 1]);
        $this->assertTrue($product->is_shippable);

        $product = new Product(['is_shippable' => 0]);
        $this->assertFalse($product->is_shippable);
    }

    public function test_seeded_book_products_have_weight_kg_greater_than_zero(): void
    {
        $this->seed(ProductSeeder::class);

        $books = Product::where('type', 'book')->get();

        $this->assertNotEmpty($books, 'No book products found after seeding');
        foreach ($books as $book) {
            $this->assertNotNull($book->weight_kg, "Book '{$book->slug}' has null weight_kg");
            $this->assertGreaterThan(0, $book->weight_kg, "Book '{$book->slug}' has weight_kg <= 0");
        }
    }

    /**
     * B1: courses are no longer in products table.
     * CourseSeeder seeds courses into the courses table — they have no shipping fields by design.
     */
    public function test_seeded_courses_exist_in_courses_table_not_products(): void
    {
        $this->seed(ProductSeeder::class);
        $this->seed(CourseSeeder::class);

        // No courses in products table
        $coursesInProducts = Product::where('type', 'course')->get();
        $this->assertEmpty($coursesInProducts, 'Products table should not contain courses after B1 split');

        // Course exists in courses table
        $courses = Course::all();
        $this->assertNotEmpty($courses, 'No courses found in courses table after CourseSeeder');
    }
}
