<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('header_settings', function (Blueprint $table) {
            $table->id();
            $table->string('logo_url', 512)->default('/images/psro-logo.jpg');
            $table->string('org_line1', 255)->default('Republic of the Philippines');
            $table->string('org_line2', 255)->default('Bangsamoro Autonomous Region in Muslim Mindanao');
            $table->string('office_title', 255)->default('Peace, Security & Reconciliation Office');
            $table->json('nav_links_json')->nullable();
            $table->json('social_links_json')->nullable();
            $table->timestamps();
        });

        DB::table('header_settings')->insert([
            'logo_url' => '/images/psro-logo.jpg',
            'org_line1' => 'Republic of the Philippines',
            'org_line2' => 'Bangsamoro Autonomous Region in Muslim Mindanao',
            'office_title' => 'Peace, Security & Reconciliation Office',
            'nav_links_json' => json_encode([
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
            ]),
            'social_links_json' => json_encode([
                ['platform' => 'facebook', 'url' => 'https://facebook.com'],
                ['platform' => 'x', 'url' => 'https://twitter.com'],
                ['platform' => 'youtube', 'url' => 'https://youtube.com'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('header_settings');
    }
};
