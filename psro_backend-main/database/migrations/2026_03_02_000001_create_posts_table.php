<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration creates the posts table with all slider-related fields,
     * foreign keys, and indexes for the PSRO-BARMM website.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Basic post fields
            $table->string('title');
            $table->text('content');
            $table->string('category')->comment('Peace, Security, or Reconciliation');
            $table->string('image_path')->nullable()->comment('Path to featured image');
            
            // Slider-specific fields
            $table->boolean('is_slider')->default(false)->comment('Whether to show in hero slider');
            $table->integer('slider_order')->default(0)->comment('Order in the slider');
            $table->text('description')->nullable()->comment('Short description for slider caption');
            $table->string('cta_text')->nullable()->comment('Call-to-action button text');
            $table->string('cta_link')->nullable()->comment('Link for CTA button');
            
            // Publication status
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            
            // SEO fields
            $table->string('meta_title')->nullable()->comment('SEO title');
            $table->text('meta_description')->nullable()->comment('SEO description');
            $table->string('slug')->unique()->comment('URL slug');
            
            // Author relationship
            $table->foreignId('author_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('Foreign key to users table');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('is_slider');
            $table->index('slider_order');
            $table->index(['is_slider', 'slider_order']);
            $table->index('category');
            $table->index('is_published');
            $table->index('published_at');
            $table->index('author_id');
            $table->index('created_at');
            $table->index('updated_at');
        });
        
        // Create post categories table
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color')->default('#004d2c')->comment('Category color for UI');
            $table->string('icon')->nullable()->comment('Category icon class');
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        
        // Create site settings table for dynamic content
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Setting key');
            $table->text('value')->nullable()->comment('Setting value');
            $table->string('type')->default('text')->comment('Value type: text, number, boolean, json');
            $table->string('group')->default('general')->comment('Settings group');
            $table->text('description')->nullable()->comment('Setting description');
            $table->timestamps();
            
            $table->index('group');
            $table->index('key');
        });
        
        // Create slider settings table for more flexible slider configuration
        Schema::create('slider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Slider name');
            $table->string('slug')->unique();
            $table->integer('autoplay_interval')->default(6000)->comment('Autoplay interval in milliseconds');
            $table->boolean('autoplay_enabled')->default(true);
            $table->boolean('show_arrows')->default(true);
            $table->boolean('show_dots')->default(true);
            $table->boolean('show_counter')->default(true);
            $table->boolean('pause_on_hover')->default(true);
            $table->string('transition_effect')->default('slide')->comment('slide, fade, zoom');
            $table->integer('height_desktop')->default(80)->comment('Height in vh for desktop');
            $table->integer('height_tablet')->default(70)->comment('Height in vh for tablet');
            $table->integer('height_mobile')->default(60)->comment('Height in vh for mobile');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('slug');
        });
        
        // Insert default site settings
        DB::table('site_settings')->insert([
            // Homepage settings
            [
                'key' => 'homepage_headline',
                'value' => 'Peace, Security and Reconciliation Office',
                'type' => 'text',
                'group' => 'homepage',
                'description' => 'Main headline on homepage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'homepage_subheadline',
                'value' => 'Bangsamoro Autonomous Region in Muslim Mindanao',
                'type' => 'text',
                'group' => 'homepage',
                'description' => 'Subheadline on homepage',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'homepage_hero_background',
                'value' => '/images/BARMM-BG.jpeg',
                'type' => 'text',
                'group' => 'homepage',
                'description' => 'Hero section background image',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Organization settings
            [
                'key' => 'org_name',
                'value' => 'Peace, Security and Reconciliation Office',
                'type' => 'text',
                'group' => 'organization',
                'description' => 'Organization name',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'org_abbreviation',
                'value' => 'PSRO',
                'type' => 'text',
                'group' => 'organization',
                'description' => 'Organization abbreviation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'org_region',
                'value' => 'Bangsamoro Autonomous Region in Muslim Mindanao',
                'type' => 'text',
                'group' => 'organization',
                'description' => 'Organization region',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Contact settings
            [
                'key' => 'contact_email',
                'value' => 'info@psro-barmm.gov.ph',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Contact email address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_phone',
                'value' => '',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Contact phone number',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_address',
                'value' => '',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Physical address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Insert default post categories
        DB::table('post_categories')->insert([
            [
                'name' => 'Peace',
                'slug' => 'peace',
                'color' => '#004d2c',
                'icon' => 'fa-handshake',
                'display_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Security',
                'slug' => 'security',
                'color' => '#1e40af',
                'icon' => 'fa-shield-alt',
                'display_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reconciliation',
                'slug' => 'reconciliation',
                'color' => '#b38e44',
                'icon' => 'fa-balance-scale',
                'display_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Announcements',
                'slug' => 'announcements',
                'color' => '#dc2626',
                'icon' => 'fa-bullhorn',
                'display_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Programs',
                'slug' => 'programs',
                'color' => '#7c3aed',
                'icon' => 'fa-project-diagram',
                'display_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Events',
                'slug' => 'events',
                'color' => '#0891b2',
                'icon' => 'fa-calendar-alt',
                'display_order' => 6,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        
        // Insert default slider settings
        DB::table('slider_settings')->insert([
            [
                'name' => 'Hero Slider',
                'slug' => 'hero-slider',
                'autoplay_interval' => 6000,
                'autoplay_enabled' => true,
                'show_arrows' => true,
                'show_dots' => true,
                'show_counter' => true,
                'pause_on_hover' => true,
                'transition_effect' => 'slide',
                'height_desktop' => 80,
                'height_tablet' => 70,
                'height_mobile' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slider_settings');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('posts');
    }
};
