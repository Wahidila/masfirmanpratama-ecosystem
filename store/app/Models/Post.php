<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'content',
        'image_path',
        'status',
        'published_at',
        'meta_seo',
        'reading_minutes',
        'views',
        'wp_post_id',
        'wp_author_login',
        'wp_guid',
        'canonical_url',
        'legacy_url',
        'primary_category_id',
    ];

    protected function casts(): array
    {
        return [
            'meta_seo' => 'array',
            'published_at' => 'datetime',
            'reading_minutes' => 'integer',
            'views' => 'integer',
        ];
    }

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(BlogCategory::class, 'category_post');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'tag_post');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'post_product');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'primary_category_id');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    /**
     * Publicly-visible posts: published AND publish time has arrived.
     * Covers scheduled posts automatically once published_at passes now().
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    // -----------------------------------------------------------------
    // Behaviour
    // -----------------------------------------------------------------

    protected static function booted(): void
    {
        // Auto-slug from title when slug left empty (mirrors Product/Course).
        static::saving(function (Post $post) {
            if (blank($post->slug) && filled($post->title)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    /**
     * Resolve route binding by slug instead of id.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Estimated reading time (minutes) from the body word count, ~200 wpm.
     */
    public static function estimateReadingMinutes(?string $html): int
    {
        $words = str_word_count(trim(strip_tags((string) $html)));

        return max(1, (int) ceil($words / 200));
    }
}
