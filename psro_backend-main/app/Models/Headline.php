<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Headline extends Model
{
    protected $fillable = [
        'headline',
        'sub_headline',
        'hero_image_url',
    ];

    /**
     * Get the singleton row, creating it with defaults if missing.
     */
    public static function current(): static
    {
        return static::firstOrCreate([], [
            'headline' => 'What makes peacebuilding sustainable?',
            'sub_headline' => 'Exploring community-led initiatives that foster long-term stability and social cohesion within the Bangsamoro region.',
            'hero_image_url' => '/images/pic1.jpg',
        ]);
    }
}
