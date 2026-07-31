<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoTestimonial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'participant_name',
        'role',
        'video_url',
        'poster_url',
        'status',
        'show_on_homepage',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'show_on_homepage' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeVisibleOnHomepage(Builder $query): Builder
    {
        return $query
            ->where('status', 'active')
            ->where('show_on_homepage', true);
    }
}
