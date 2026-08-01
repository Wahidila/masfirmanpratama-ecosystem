<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * Katalog produk (buku + kelas) untuk app Affiliate.
 *
 * App Affiliate dan Store adalah 2 aplikasi/DB terpisah, jadi Affiliate
 * mengambil daftar produk lewat endpoint JSON ini (di-cache di sisi Affiliate)
 * untuk halaman "Produk" affiliator + dropdown pilih produk saat buat link
 * referral. Data ini sama dengan yang sudah publik di /produk & /kelas.
 */
class AffiliateCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $courses = Course::where('status', 'active')
            ->orderBy('title')
            ->get()
            ->map(fn (Course $c) => [
                'type' => 'course',
                'slug' => $c->slug,
                'title' => $c->title,
                'price' => (int) $c->price,
                'image_url' => $c->image_path ? asset($c->image_path) : null,
                'url' => route('courses.show', $c->slug),
            ]);

        $books = Product::where('status', 'active')
            ->orderBy('title')
            ->get()
            ->map(fn (Product $p) => [
                'type' => 'book',
                'slug' => $p->slug,
                'title' => $p->title,
                'price' => (int) $p->price,
                'image_url' => $p->image_path ? asset($p->image_path) : null,
                'url' => route('products.show', $p->slug),
            ]);

        return response()->json([
            'products' => $courses->concat($books)->values(),
        ]);
    }
}
