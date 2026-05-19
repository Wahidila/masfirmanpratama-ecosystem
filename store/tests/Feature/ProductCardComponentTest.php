<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * Regression test untuk komponen <x-product-card>.
 *
 * Konteks: fix aspect-square + object-contain + p-4 sempat hilang waktu
 * VPS migration karena working tree numpuk belum di-commit. Test ini guard
 * biar varian image container/fit class ngga regress lagi ke default 4/5 +
 * cover (yang dipakai showcase di components-gallery, bukan card produksi).
 *
 * Lihat juga: skill mc-project-context § Disaster Recovery.
 */
class ProductCardComponentTest extends TestCase
{
    /**
     * Image container harus pakai aspect-square + padding p-4 supaya
     * cover/foto produk konsisten 1:1 di semua entry point user-facing
     * (home #katalog, /produk, related di book/course detail).
     */
    public function test_image_container_uses_aspect_square_with_padding(): void
    {
        $html = Blade::render(
            '<x-product-card title="Buku X" price="100000" image="/img/test.jpg" />'
        );

        $this->assertStringContainsString(
            'aspect-square',
            $html,
            'Product card harus pakai aspect-square (bukan aspect-[4/5])'
        );
        $this->assertStringContainsString(
            'p-4',
            $html,
            'Container harus punya padding p-4 untuk breathing room'
        );
        $this->assertStringNotContainsString(
            'aspect-[4/5]',
            $html,
            'aspect-[4/5] sudah deprecated untuk product card user-facing'
        );
    }

    /**
     * Image fit harus object-contain biar full produk kelihatan
     * (cover akan crop top/bottom buku/kitab — bukan yang kita mau).
     */
    public function test_image_uses_object_contain_fit(): void
    {
        $html = Blade::render(
            '<x-product-card title="Buku X" price="100000" image="/img/test.jpg" />'
        );

        $this->assertStringContainsString(
            'object-contain',
            $html,
            'Image harus pakai object-contain biar full produk kelihatan tanpa crop'
        );
        $this->assertStringNotContainsString(
            'object-cover',
            $html,
            'object-cover crop bagian penting buku/kitab — sudah diganti object-contain'
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
