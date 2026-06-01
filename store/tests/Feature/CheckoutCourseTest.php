<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\InstallmentScheme;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutCourseTest extends TestCase
{
    use RefreshDatabase;

    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Budi Santoso',
            'customer_email' => 'budi@example.com',
            'customer_phone' => '081234567890',
            'address_line' => 'Jl. Merdeka No. 12',
            'address_city' => 'Malang',
            'address_province' => 'Jawa Timur',
            'address_postal' => '65111',
            'shipping_method' => null,
            'payment_type' => 'lunas',
            'installment_scheme_id' => null,
            'cart_json' => json_encode([
                ['slug' => 'kelas-amc-reguler', 'name' => 'Kelas AMC', 'price' => 4500000, 'qty' => 1],
            ]),
            'cart_total' => 4500000,
            'ref_code' => null,
        ], $overrides);
    }

    public function test_checkout_course_creates_order_with_course_id(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas AMC',
            'price' => 4500000,
        ]);

        $this->post('/checkout', $this->validPayload());

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame('4500000.00', $order->total);

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertSame($course->id, $item->course_id);
        $this->assertNull($item->product_id);

        $this->assertDatabaseHas('order_items', [
            'course_id' => $course->id,
            'product_id' => null,
        ]);
    }

    public function test_checkout_book_regression_product_id_set_course_id_null(): void
    {
        $product = Product::factory()->create([
            'slug' => 'buku-test',
            'title' => 'Buku Test',
            'price' => 185000,
            'status' => 'active',
            'type' => 'book',
        ]);

        $this->post('/checkout', $this->validPayload([
            'cart_json' => json_encode([
                ['slug' => 'buku-test', 'name' => 'Buku Test', 'price' => 185000, 'qty' => 1],
            ]),
            'cart_total' => 185000,
        ]));

        $this->assertSame(1, Order::count());
        $order = Order::first();

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertSame($product->id, $item->product_id);
        $this->assertNull($item->course_id);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'course_id' => null,
        ]);
    }

    public function test_checkout_mixed_course_and_book(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas AMC',
            'price' => 4500000,
        ]);

        $product = Product::factory()->active()->book()->create([
            'slug' => 'buku-mpl',
            'title' => 'Buku MPL',
            'price' => 185000,
        ]);

        $this->post('/checkout', $this->validPayload([
            'cart_json' => json_encode([
                ['slug' => 'kelas-amc-reguler', 'name' => 'Kelas AMC', 'price' => 4500000, 'qty' => 1],
                ['slug' => 'buku-mpl', 'name' => 'Buku MPL', 'price' => 185000, 'qty' => 1],
            ]),
            'cart_total' => 4500000 + 185000,
        ]));

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame('4685000.00', $order->total);

        $items = OrderItem::where('order_id', $order->id)->orderBy('id')->get();
        $this->assertCount(2, $items);

        $courseItem = $items->firstWhere('course_id', $course->id);
        $this->assertNotNull($courseItem);
        $this->assertSame($course->id, $courseItem->course_id);
        $this->assertNull($courseItem->product_id);

        $productItem = $items->firstWhere('product_id', $product->id);
        $this->assertNotNull($productItem);
        $this->assertSame($product->id, $productItem->product_id);
        $this->assertNull($productItem->course_id);
    }

    public function test_checkout_unknown_course_slug_fails(): void
    {
        $this->post('/checkout', $this->validPayload([
            'cart_json' => json_encode([
                ['slug' => 'kelas-ghost', 'name' => 'Kelas Ghost', 'price' => 500000, 'qty' => 1],
            ]),
            'cart_total' => 500000,
        ]))->assertSessionHasErrors('cart_json');

        $this->assertSame(0, Order::count());
    }

    public function test_checkout_course_with_cicilan(): void
    {
        $course = Course::factory()->active()->create([
            'slug' => 'kelas-amc-reguler',
            'title' => 'Kelas AMC',
            'price' => 4500000,
        ]);

        $scheme = InstallmentScheme::create([
            'course_id' => null,
            'name' => 'Cicilan 3x',
            'dp_pct' => 30,
            'n_installments' => 3,
            'interval_days' => 30,
            'active' => true,
        ]);

        $this->post('/checkout', $this->validPayload([
            'payment_type' => 'cicilan',
            'installment_scheme_id' => $scheme->id,
        ]));

        $this->assertSame(1, Order::count());
        $order = Order::first();
        $this->assertSame('4500000.00', $order->total);

        $payments = OrderPayment::where('order_id', $order->id)->orderBy('id')->get();
        $this->assertCount(3, $payments);
        $this->assertSame('1350000.00', $payments[0]->amount);
        $this->assertSame('1575000.00', $payments[1]->amount);
        $this->assertSame('1575000.00', $payments[2]->amount);

        $sum = (float) $payments->sum('amount');
        $this->assertEquals(4500000.0, $sum);

        $item = OrderItem::where('order_id', $order->id)->first();
        $this->assertSame($course->id, $item->course_id);
        $this->assertNull($item->product_id);
    }
}
