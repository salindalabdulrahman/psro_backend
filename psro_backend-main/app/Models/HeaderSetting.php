<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeaderSetting extends Model
{
    protected $fillable = [
        'logo_url',
        'org_line1',
        'org_line2',
        'office_title',
        'nav_links_json',
        'social_links_json',
    ];

    protected function casts(): array
    {
        return [
            'nav_links_json' => 'array',
            'social_links_json' => 'array',
        ];
    }

    public static function current(): static
    {
        return static::firstOrCreate([], [
            'logo_url' => '/images/psro-logo.jpg',
            'org_line1' => 'Republic of the Philippines',
            'org_line2' => 'Bangsamoro Autonomous Region in Muslim Mindanao',
            'office_title' => 'Peace, Security & Reconciliation Office',
            'nav_links_json' => [
                ['label' => 'Home', 'url' => '/', 'children' => []],
                ['label' => 'About Us', 'url' => '#', 'children' => [
                    ['label' => 'Office of the Director', 'url' => '#'],
                    ['label' => 'History', 'url' => '#'],
                    ['label' => 'Mandate', 'url' => '#'],
                    ['label' => 'Organizational Structure', 'url' => '#'],
                ]],
                ['label' => 'Programs', 'url' => '#', 'children' => [
                    ['label' => 'Office of the Director', 'url' => '#'],
                    ['label' => 'History', 'url' => '#'],
                    ['label' => 'Mandate', 'url' => '#'],
                    ['label' => 'Organizational Structure', 'url' => '#'],
                ]],
                ['label' => 'Contact', 'url' => '#', 'children' => []],
            ],
            'social_links_json' => [
                ['platform' => 'facebook', 'url' => 'https://www.facebook.com/psro.ocm'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com'],
            ],
        ]);
    }
}
