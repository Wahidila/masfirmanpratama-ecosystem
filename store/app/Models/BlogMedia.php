<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogMedia extends Model
{
    use HasFactory;

    protected $table = 'blog_media';

    protected $fillable = [
        'wp_post_id',
        'disk_path',
        'original_url',
        'original_path',
        'mime_type',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'wp_post_id' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Public asset path (prefixed with storage/ so asset() resolves under /storage).
     */
    public function assetPath(): string
    {
        return 'storage/'.ltrim($this->disk_path, '/');
    }
}
