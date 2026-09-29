<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'content',
        'category',
        'image_path',
        'is_slider',
        'slider_order',
        'description',
        'highlight_name',
        'highlight_role',
        'highlight_quote',
        'cta_text',
        'cta_link',
        'videos',
        'is_published',
        'published_at',
        'activity_display_date',
        'meta_title',
        'meta_description',
        'slug',
        'author_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_slider' => 'boolean',
            'is_published' => 'boolean',
            'slider_order' => 'integer',
            'published_at' => 'datetime',
            'activity_display_date' => 'date',
            'videos' => 'array',
        ];
    }

    /**
     * Scope to get only slider posts
     */
    public function scopeSlider($query)
    {
        return $query->where('is_slider', true)
                     ->orderBy('slider_order', 'asc');
    }

    /**
     * Scope to get only published posts
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Relationship: Get the author of the post
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
