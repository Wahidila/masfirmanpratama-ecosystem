<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    /**
     * Seed dev/demo blog content. Real content is migrated from WordPress via
     * `php artisan blog:import-wordpress`. This seeder mirrors the 6 live
     * categories + a few tags + sample posts (published/draft/scheduled) so the
     * /blog + /admin/posts screens have something to render during development.
     */

    /** @var array<int, array{name: string, slug: string}> Live categories from the old site. */
    private const CATEGORIES = [
        ['name' => 'Mindset dan Spiritualitas', 'slug' => 'mindset-dan-spiritualitas'],
        ['name' => 'Kekuatan Pikiran', 'slug' => 'kekuatan-pikiran'],
        ['name' => 'Pengembangan Diri', 'slug' => 'kualitas-diri'],
        ['name' => 'Kekayaan', 'slug' => 'kekayaan'],
        ['name' => 'Alpha Mind Control', 'slug' => 'alpha-mind-control'],
        ['name' => 'Keluarga Bahagia', 'slug' => 'keluarga-bahagia'],
    ];

    private const TAGS = ['pikiran', 'mindset', 'berpikir-positif', 'hidup-kaya', 'keluarga-bahagia'];

    /** @var array<int, array{title: string, category: string, status: string}> */
    private const POSTS = [
        ['title' => 'Stop Berpikir Positif: Mengapa Pikiran Positif Saja Tidak Cukup', 'category' => 'kekuatan-pikiran', 'status' => 'published'],
        ['title' => '3 Proses Keluar dari Zona Miskin yang Jarang Diajarkan', 'category' => 'kekayaan', 'status' => 'published'],
        ['title' => '5 Alasan Suami Istri Harus Memiliki Pikiran yang Selaras', 'category' => 'keluarga-bahagia', 'status' => 'published'],
        ['title' => 'Melatih Mindset Kaya Bukan dengan Afirmasi Semata', 'category' => 'mindset-dan-spiritualitas', 'status' => 'draft'],
        ['title' => 'Rahasia Alpha Mind Control untuk Fokus Total (Segera Terbit)', 'category' => 'alpha-mind-control', 'status' => 'scheduled'],
    ];

    public function run(): void
    {
        $categories = collect(self::CATEGORIES)->mapWithKeys(function (array $cat) {
            $model = BlogCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                ['name' => $cat['name']],
            );

            return [$cat['slug'] => $model];
        });

        $tags = collect(self::TAGS)->map(fn (string $slug) => BlogTag::firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::title(str_replace('-', ' ', $slug))],
        ));

        foreach (self::POSTS as $item) {
            $body = "<p>".fake()->paragraph(8)."</p>\n<h3>Poin Penting</h3>\n<ul><li>".fake()->sentence()."</li><li>".fake()->sentence()."</li></ul>\n<p>".fake()->paragraph(6)."</p>";

            $post = Post::factory()
                ->{$item['status']}()
                ->create([
                    'title' => $item['title'],
                    'slug' => Str::slug($item['title']),
                    'excerpt' => fake()->sentence(16),
                    'content' => $body,
                    'reading_minutes' => Post::estimateReadingMinutes($body),
                    'wp_author_login' => 'firmanp',
                ]);

            $category = $categories[$item['category']];
            $post->categories()->syncWithoutDetaching([$category->id]);
            $post->primary_category_id = $category->id;
            $post->save();

            $post->tags()->syncWithoutDetaching($tags->random(2)->pluck('id')->all());
        }
    }
}
