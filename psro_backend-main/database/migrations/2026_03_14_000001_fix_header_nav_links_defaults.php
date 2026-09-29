<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update header nav_links with correct URLs for Home, About Us, Programs, Contact.
     */
    public function up(): void
    {
        $correctNavLinks = [
            ['label' => 'Home', 'url' => '/', 'children' => []],
            ['label' => 'About Us', 'url' => '#about', 'children' => []],
            ['label' => 'Programs', 'url' => '#section-activities', 'children' => []],
            ['label' => 'Contact', 'url' => '#contact', 'children' => []],
        ];

        DB::table('header_settings')->update([
            'nav_links_json' => json_encode($correctNavLinks),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $originalNavLinks = [
            ['label' => 'Home', 'url' => '/', 'children' => []],
            ['label' => 'About Us', 'url' => '#', 'children' => []],
            ['label' => 'Programs', 'url' => '#', 'children' => []],
            ['label' => 'Contact', 'url' => '#', 'children' => []],
        ];

        DB::table('header_settings')->update([
            'nav_links_json' => json_encode($originalNavLinks),
            'updated_at' => now(),
        ]);
    }
};
