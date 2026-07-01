<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Product;
use App\Services\Settings;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function show(string $slug): View
    {
        $courseModel = Course::where('slug', $slug)->where('status', 'active')->firstOrFail();

        $price = (float) $courseModel->price;

        // Cicilan termurah per bulan dari skema AKTIF (jujur — dari data, bukan estimasi
        // ngarang). perMonth = (harga - DP) / jumlah cicilan; ambil yang paling ringan.
        $installmentFrom = null;
        $installmentTenor = null;
        if ($courseModel->installment_available) {
            foreach ($courseModel->installmentSchemes()->where('active', true)->get() as $scheme) {
                $n = (int) $scheme->n_installments;
                if ($n < 1) {
                    continue;
                }
                $dp = (float) ($scheme->dp_pct ?? 0) / 100 * $price;
                $perMonth = ($price - $dp) / $n;
                if ($installmentFrom === null || $perMonth < $installmentFrom) {
                    $installmentFrom = $perMonth;
                    $installmentTenor = $n;
                }
            }
            $installmentFrom = $installmentFrom !== null ? (int) round($installmentFrom) : null;
        }

        $data = [
            'slug' => $courseModel->slug,
            'type' => 'kelas',
            'title' => $courseModel->title,
            'subtitle' => $courseModel->subtitle ?? '',
            'badge' => $courseModel->badge,
            'badge_icon' => $courseModel->badge_icon,
            'category_label' => $courseModel->category_label ?? 'Kelas',
            'price' => $price,
            'original_price' => $courseModel->original_price ? (float) $courseModel->original_price : null,
            'image' => $courseModel->image_path ?? 'images/placeholder.webp',
            'image_alt' => $courseModel->meta_seo['image_alt'] ?? $courseModel->title,
            'cta_label' => 'Daftar Sekarang',
            'installment_available' => $courseModel->installment_available ?? false,
            'installment_from' => $installmentFrom,
            'installment_tenor' => $installmentTenor,
            'wa_number' => Settings::getWaAdmin()['number'] ?? null,
            // rating/student_count TANPA fallback palsu — tampil hanya kalau ada nilai asli.
            'rating' => $courseModel->rating,
            'student_count' => $courseModel->student_count,
            'tagline' => $courseModel->tagline,
            'description' => $courseModel->description ?? [],
            'syllabus' => $courseModel->syllabus ?? [],
            'schedule' => $courseModel->schedule ?? [],
            'benefits' => $courseModel->benefits ?? [],
            'testimonials' => $courseModel->testimonials ?? [],
        ];

        $related = [];
        if (! empty($courseModel->related) && is_array($courseModel->related)) {
            foreach ($courseModel->related as $relSlug) {
                $relProduct = Product::where('slug', $relSlug)->where('status', 'active')->first();
                if ($relProduct) {
                    $related[] = [
                        'slug' => $relProduct->slug,
                        'type' => $relProduct->type === 'course' ? 'kelas' : 'buku',
                        'title' => $relProduct->title,
                        'price' => (float) $relProduct->price,
                        'image' => $relProduct->image_path ?? 'images/placeholder.webp',
                        'subtitle' => $relProduct->meta_seo['subtitle'] ?? '',
                        'badge' => $relProduct->meta_seo['badge'] ?? null,
                        'category_label' => $relProduct->meta_seo['category_label'] ?? 'Buku',
                        'image_alt' => $relProduct->meta_seo['image_alt'] ?? $relProduct->title,
                    ];

                    continue;
                }
                $relCourse = Course::where('slug', $relSlug)->where('status', 'active')->first();
                if ($relCourse) {
                    $related[] = [
                        'slug' => $relCourse->slug,
                        'type' => 'kelas',
                        'title' => $relCourse->title,
                        'price' => (float) $relCourse->price,
                        'image' => $relCourse->image_path ?? 'images/placeholder.webp',
                        'subtitle' => $relCourse->subtitle ?? '',
                        'badge' => $relCourse->badge ?? null,
                        'category_label' => $relCourse->category_label ?? 'Kelas',
                        'image_alt' => $relCourse->meta_seo['image_alt'] ?? $relCourse->title,
                    ];
                }
            }
        }

        return view('pages.products.show', [
            'productModel' => null,
            'data' => $data,
            'related' => $related,
            'template' => 'pages.products.course',
        ]);
    }
}
