<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Regression test untuk komponen <x-product-card>.
 *
 * Image container: aspect-square, no padding, object-cover (full bleed).
 * Decision 2026-06-05: user wants no padding on image container.
 */
class ProductCardComponentTest extends TestCase
{
    /**
     * Image container harus pakai aspect-square tanpa padding supaya
     * gambar full bleed di semua entry point user-facing
     * (home #katalog, /produk, related di book/course detail).
     */
    public function test_image_container_uses_aspect_square_without_padding(): void
    {
        $html = Blade::render(
            '<x-product-card title="Buku X" price="100000" image="/img/test.jpg" />'
        );

        $this->assertStringContainsString(
            'aspect-square',
            $html,
            'Product card harus pakai aspect-square (bukan aspect-[4/5])'
        );
        $this->assertStringNotContainsString(
            'p-4',
            $html,
            'Container tidak boleh punya padding — gambar harus full bleed'
        );
        $this->assertStringNotContainsString(
            'aspect-[4/5]',
            $html,
            'aspect-[4/5] sudah deprecated untuk product card user-facing'
        );
    }

    /**
     * Image fit harus object-cover supaya gambar fill full container
     * tanpa whitespace.
     */
    public function test_image_uses_object_cover_fit(): void
    {
        $html = Blade::render(
            '<x-product-card title="Buku X" price="100000" image="/img/test.jpg" />'
        );

        $this->assertStringContainsString(
            'object-cover',
            $html,
            'Image harus pakai object-cover biar fill full tanpa whitespace'
        );
        $this->assertStringNotContainsString(
            'object-contain',
            $html,
            'object-contain sudah diganti object-cover — no padding, full bleed'
        );
    }

    /**
     * Smoke test: komponen render tanpa error untuk shape data minimal.
     */
    public function test_renders_without_image_fallback(): void
    {
        $html = Blade::render(
            '<x-product-card title="Tanpa Gambar" price="50000" />'
        );

        $this->assertStringContainsString('aspect-square', $html);
        $this->assertStringContainsString('Tanpa Gambar', $html);
        // Fallback icon container tetap ada
        $this->assertStringContainsString('data-lucide="image"', $html);
    }
}
