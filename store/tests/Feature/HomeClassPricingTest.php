<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Section "Pilih Format Kelas" di homepage dinamis dari Course CRUD.
 * Harga coret (original_price) hanya tampil bila lebih tinggi dari harga jual.
 */
class HomeClassPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_strikethrough_and_savings_when_course_discounted(): void
    {
        Course::factory()->create([
            'title' => 'Kelas Diskon AMC',
            'status' => 'active',
            'show_on_homepage' => true,
            'price' => 4_500_000,
            'original_price' => 6_750_000, // 33% off
        ]);

        $res = $this->get('/')->assertOk();
        $res->assertSee('Kelas Diskon AMC');
        $res->assertSee('Rp 6.750.000');       // harga coret
        $res->assertSee('line-through', false); // benar-benar dicoret
        $res->assertSee('Hemat 33%');
    }

    public function test_no_strikethrough_when_original_price_absent(): void
    {
        Course::factory()->create([
            'title' => 'Kelas Normal AMC',
            'status' => 'active',
            'show_on_homepage' => true,
            'price' => 4_500_000,
            'original_price' => null,
        ]);

        $res = $this->get('/')->assertOk();
        $res->assertSee('Kelas Normal AMC');
        $res->assertDontSee('Hemat');
    }

    public function test_no_strikethrough_when_original_price_not_higher_than_price(): void
    {
        // original_price <= price → bukan diskon, jangan tampilkan coret.
        Course::factory()->create([
            'title' => 'Kelas Sama AMC',
            'status' => 'active',
            'show_on_homepage' => true,
            'price' => 4_500_000,
            'original_price' => 4_500_000,
        ]);

        $res = $this->get('/')->assertOk();
        $res->assertSee('Kelas Sama AMC');
        $res->assertDontSee('Hemat');
    }
}
