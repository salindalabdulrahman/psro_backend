<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('headlines', function (Blueprint $table) {
            $table->id();
            $table->string('headline', 500)->default('');
            $table->string('sub_headline', 1000)->default('');
            $table->string('hero_image_url', 512)->default('/images/pic1.jpg');
            $table->timestamps();
        });

        Schema::create('mandates', function (Blueprint $table) {
            $table->id();
            $table->string('headline', 500)->default('');
            $table->string('subheadline', 1000)->default('');
            $table->text('director_quote')->nullable();
            $table->string('director_name', 255)->default('');
            $table->string('director_title', 255)->default('');
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('category', 80)->default('Program');
            $table->text('description')->nullable();
            $table->string('image', 512)->default('/images/pic1.jpg');
            $table->string('link', 512)->default('#');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_published');
        });

        // Seed defaults
        DB::table('headlines')->insert([
            'headline' => 'What makes peacebuilding sustainable?',
            'sub_headline' => 'Exploring community-led initiatives that foster long-term stability and social cohesion within the Bangsamoro region.',
            'hero_image_url' => '/images/pic1.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mandates')->insert([
            'headline' => 'Harnessing regional capacities to sustain peace through reconciliation',
            'subheadline' => 'Working horizontally across ministries and vertically from regional to community level.',
            'director_quote' => 'The new peace and security architecture is more integrated, with our office serving as a hinge between security and development.',
            'director_name' => 'Al-Rashid L. Kulayan, Ph.D.',
            'director_title' => 'Executive Director, PSRO-BARMM',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
        Schema::dropIfExists('mandates');
        Schema::dropIfExists('headlines');
    }
};
