<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mandate extends Model
{
    protected $fillable = [
        'headline',
        'subheadline',
        'director_quote',
        'director_name',
        'director_title',
        'director_image_url',
    ];

    /**
     * Get the singleton row, creating it with defaults if missing.
     */
    public static function current(): static
    {
        return static::firstOrCreate([], [
            'headline' => 'Harnessing regional capacities to sustain peace through reconciliation',
            'subheadline' => 'Working horizontally across ministries and vertically from regional to community level.',
            'director_quote' => 'The new peace and security architecture is more integrated, with our office serving as a hinge between security and development.',
            'director_name' => 'Al-Rashid L. Kulayan, Ph.D.',
            'director_title' => 'Executive Director, PSRO-BARMM',
        ]);
    }
}
