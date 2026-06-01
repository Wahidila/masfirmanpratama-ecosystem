<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::where('status', 'active')
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $p) => [
                'slug' => $p->slug,
                'name' => $p->title,
                'type' => $p->type === 'course' ? 'kelas' : 'buku',
                'price' => (float) $p->price,
                'image' => asset($p->image_path ?? 'images/placeholder.webp'),
                'description' => $p->meta_seo['tagline'] ?? $p->description,
                'badge' => $p->meta_seo['badge'] ?? null,
            ]);

        $courses = Course::where('status','active')->orderBy('id')->get()->map(fn(Course $c) => [
            'slug' => $c->slug,
            'name' => $c->title,
            'type' => 'kelas',
            'price' => (float) $c->price,
            'image' => asset($c->image_path ?? 'images/placeholder.webp'),
            'description' => $c->tagline ?? ($c->subtitle ?? null),
            'badge' => $c->badge ?? null,
        ]);

        $products = $courses->concat($products);

        $productCounts = [
            'kelas' => $products->where('type', 'kelas')->count(),
            'buku' => $products->where('type', 'buku')->count(),
        ];

        $productIndex = $products->map(fn ($p) => [
            'type' => $p['type'],
            'name' => mb_strtolower($p['name']),
        ])->values()->all();

        $productTotal = $products->count();

        return view('pages.products.index', compact('products', 'productCounts', 'productIndex', 'productTotal'));
    }

    public function show(string $slug): View
    {
        $courseModel = Course::where('slug', $slug)->where('status', 'active')->first();
        if ($courseModel) {
            $data = [
                'slug' => $courseModel->slug,
                'type' => 'kelas',
                'title' => $courseModel->title,
                'subtitle' => $courseModel->subtitle ?? '',
                'badge' => $courseModel->badge,
                'badge_icon' => $courseModel->badge_icon,
                'category_label' => $courseModel->category_label ?? 'Kelas',
                'price' => (float) $courseModel->price,
                'original_price' => $courseModel->original_price ? (float) $courseModel->original_price : null,
                'image' => $courseModel->image_path ?? 'images/placeholder.webp',
                'image_alt' => $courseModel->meta_seo['image_alt'] ?? $courseModel->title,
                'cta_label' => 'Daftar Sekarang',
                'installment_available' => $courseModel->installment_available ?? false,
                'rating' => $courseModel->rating ?? '4.9/5',
                'student_count' => $courseModel->student_count ?? '1000+',
                'tagline' => $courseModel->tagline,
                'description' => $courseModel->description ?? [],
                'syllabus' => $courseModel->syllabus ?? [],
                'schedule' => $courseModel->schedule ?? [],
                'benefits' => $courseModel->benefits ?? [],
                'testimonials' => $courseModel->testimonials ?? [],
            ];

            $related = [];
            foreach (($courseModel->related ?? []) as $relSlug) {
                $relProduct = Product::where('slug', $relSlug)->where('status','active')->first();
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
                $relCourse = Course::where('slug', $relSlug)->where('status','active')->first();
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

            return view('pages.products.show', [
                'productModel' => null,
                'data' => $data,
                'related' => $related,
                'template' => 'pages.products.course',
            ]);
        }

        $productModel = Product::where('slug', $slug)->where('status', 'active')->first();
        $configProduct = config('products.items.'.$slug, []);

        // If product not in DB and not in config, show placeholder
        if (! $productModel && empty($configProduct)) {
            return view('pages.products.show', [
                'productModel' => null,
                'data' => null,
                'related' => [],
                'template' => null,
                'slug' => $slug,
            ]);
        }

        // Build product array: DB takes precedence, config as fallback
        if ($productModel) {
            $data = array_merge($configProduct, [
                'slug' => $productModel->slug,
                'type' => $productModel->type === 'course' ? 'kelas' : 'buku',
                'title' => $productModel->title,
                'price' => (float) $productModel->price,
                'image' => asset($productModel->image_path ?? 'images/placeholder.webp'),
                'subtitle' => $productModel->meta_seo['subtitle'] ?? ($configProduct['subtitle'] ?? ''),
                'tagline' => $productModel->meta_seo['tagline'] ?? ($configProduct['tagline'] ?? null),
                'badge' => $productModel->meta_seo['badge'] ?? ($configProduct['badge'] ?? null),
                'category_label' => $productModel->meta_seo['category_label'] ?? ($configProduct['category_label'] ?? 'Buku'),
                'image_alt' => $productModel->meta_seo['image_alt'] ?? ($configProduct['image_alt'] ?? $productModel->title),
                'rating' => $productModel->meta_seo['rating'] ?? ($configProduct['rating'] ?? '4.9/5'),
                'student_count' => $productModel->meta_seo['student_count'] ?? ($configProduct['student_count'] ?? '1000+'),
                'description' => is_string($productModel->description) ? [$productModel->description] : ($productModel->description ?? ($configProduct['description'] ?? [])),
                'original_price' => $productModel->meta_seo['original_price'] ?? ($configProduct['original_price'] ?? null),
            ]);
        } else {
            // Config-only fallback (DB not seeded, e.g. test env)
            $type = $configProduct['type'] ?? 'buku';
            $data = $configProduct;
            $data['type'] = $type === 'course' ? 'kelas' : $type;
            $data['image'] = isset($configProduct['image']) ? asset($configProduct['image']) : null;
        }

        // Resolve related products
        $related = [];
        if (! empty($configProduct['related'])) {
            foreach ((array) $configProduct['related'] as $relatedSlug) {
                $relProduct = Product::where('slug', $relatedSlug)->where('status', 'active')->first();
                $relConfig = config('products.items.'.$relatedSlug, []);
                if ($relProduct) {
                    $related[] = array_merge($relConfig, [
                        'slug' => $relProduct->slug,
                        'type' => $relProduct->type === 'course' ? 'kelas' : 'buku',
                        'title' => $relProduct->title,
                        'price' => (float) $relProduct->price,
                        'image' => asset($relProduct->image_path ?? 'images/placeholder.webp'),
                        'subtitle' => $relProduct->meta_seo['subtitle'] ?? ($relConfig['subtitle'] ?? ''),
                        'badge' => $relProduct->meta_seo['badge'] ?? ($relConfig['badge'] ?? null),
                        'category_label' => $relProduct->meta_seo['category_label'] ?? ($relConfig['category_label'] ?? 'Buku'),
                        'image_alt' => $relProduct->meta_seo['image_alt'] ?? ($relConfig['image_alt'] ?? $relProduct->title),
                    ]);
                } elseif (! empty($relConfig)) {
                    $relConfig['image'] = isset($relConfig['image']) ? asset($relConfig['image']) : null;
                    $relConfig['type'] = ($relConfig['type'] ?? 'buku') === 'course' ? 'kelas' : ($relConfig['type'] ?? 'buku');
                    $related[] = array_merge(['slug' => $relatedSlug], $relConfig);
                }
            }
        }

        $type = $data['type'] ?? 'buku';
        $templateMap = [
            'kelas' => 'pages.products.course',
            'buku' => 'pages.products.book',
        ];
        $template = isset($templateMap[$type]) ? $templateMap[$type] : null;

        return view('pages.products.show', compact('productModel', 'data', 'related', 'template'));
    }
}
