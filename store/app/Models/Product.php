<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'type',
        'title',
        'price',
        'stock',
        'status',
        'image_path',
        'description',
        'meta_seo',
        'specs',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'is_shippable',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'meta_seo' => 'array',
            'specs' => 'array',
            'weight_kg' => 'decimal:2',
            'length_cm' => 'integer',
            'width_cm' => 'integer',
            'height_cm' => 'integer',
            'is_shippable' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Catatan: produk/buku TIDAK punya cicilan (checkout lunas-only) — relasi
    // installmentSchemes() sengaja dihapus. Cicilan hanya untuk kelas/kursus.

    /**
     * Resolve route binding by slug instead of id.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
