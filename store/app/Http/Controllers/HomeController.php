<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\VideoTestimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $products = Product::where('status', 'active')
            ->where('type', 'book')
            ->orderBy('id')
            ->get()
            ->map(fn (Product $p) => [
                'image' => asset($p->image_path ?? 'images/placeholder.webp'),
                'title' => $p->title,
                'price' => (float) $p->price,
                'originalPrice' => $p->meta_seo['original_price'] ?? null,
                'category' => $p->meta_seo['category_label'] ?? 'Buku',
                'badge' => $p->meta_seo['badge'] ?? null,
                'href' => url('/produk/'.$p->slug),
            ]);

        $slugs = ['10-keajaiban-pikiran', 'alpha-telepathy', 'instan-hypnosis', 'kitab-101-kalimat-sugesti-ajaib', 'kitab-kunci-penarik-rezeki'];
        $welcomeBooks = Product::whereIn('slug', $slugs)->get()->sortBy(fn ($p) => array_search($p->slug, $slugs))->values();

        $classFormats = Course::where('status', 'active')
            ->where('show_on_homepage', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (Course $c) {
                // Harga coret hanya bila original_price valid & lebih tinggi dari harga jual.
                $hasDiscount = $c->original_price !== null
                    && (float) $c->original_price > (float) $c->price;

                return [
                    'name' => $c->title,
                    'slug' => $c->slug,
                    'tagline' => $c->tagline ?? $c->subtitle ?? '',
                    'price' => 'Rp '.number_format((float) $c->price, 0, ',', '.'),
                    'originalPrice' => $hasDiscount
                        ? 'Rp '.number_format((float) $c->original_price, 0, ',', '.')
                        : null,
                    'discountPercent' => $hasDiscount
                        ? (int) round((1 - (float) $c->price / (float) $c->original_price) * 100)
                        : null,
                    'priceNote' => $c->installment_available ? '*Bisa dicicil sampai lunas.' : '',
                    'iconAccent' => $c->card_icon ?: 'sparkles',
                    'iconColor' => $c->card_icon_color ?: 'text-primary-500',
                    'features' => is_array($c->card_features) ? $c->card_features : [],
                    'badge' => $c->badge,
                    'highlight' => $c->card_style === 'highlight',
                    'dark' => $c->card_style === 'dark',
                    'ctaLabel' => $c->cta_label ?: 'Lihat Detail',
                    'ctaHref' => route('products.show', $c->slug),
                ];
            })->all();

        $videoTestimonials = VideoTestimonial::visibleOnHomepage()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VideoTestimonial $testimonial) => [
                'video' => $testimonial->video_url,
                'poster' => $testimonial->poster_url,
                'title' => $testimonial->title,
                'name' => $testimonial->participant_name,
                'role' => $testimonial->role ?: 'Alumni AMC',
            ])
            ->all();

        // Banner promo/jadwal terdekat — dikelola admin (aktif + dalam jendela tayang).
        $promoBanners = PromoBanner::visible()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('pages.home', compact('products', 'welcomeBooks', 'classFormats', 'videoTestimonials', 'promoBanners'));
    }
}
