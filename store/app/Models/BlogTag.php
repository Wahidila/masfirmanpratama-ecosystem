<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class BlogTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'wp_term_id',
    ];

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'tag_post');
    }

    protected static function booted(): void
    {
        static::saving(function (BlogTag $tag) {
            if (blank($tag->slug) && filled($tag->name)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
