<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'BARMM Holds Peace Summit 2026',
                'content' => 'The Bangsamoro Autonomous Region in Muslim Mindanao successfully hosted the annual Peace Summit, bringing together stakeholders from government, civil society, and international partners to discuss sustainable peace initiatives and reconciliation programs.',
                'category' => 'Peace',
                'image_path' => '/images/pic1.jpg',
                'is_slider' => true,
                'slider_order' => 1,
                'description' => 'Join us in building a peaceful BARMM through dialogue and collaboration',
                'cta_text' => 'Learn More',
                'cta_link' => '#',
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('BARMM Holds Peace Summit 2026'),
                'meta_title' => 'BARMM Peace Summit 2026 | PSRO',
                'meta_description' => 'Annual Peace Summit brings together peace stakeholders in BARMM',
            ],
            [
                'title' => 'Security Framework Implementation',
                'content' => 'The PSRO unveils a comprehensive security framework designed to ensure safety while maintaining the region\'s commitment to human rights and democratic principles.',
                'category' => 'Security',
                'image_path' => '/images/BARMM-BG.jpeg',
                'is_slider' => true,
                'slider_order' => 2,
                'description' => 'A comprehensive approach to regional security and stability',
                'cta_text' => 'View Programs',
                'cta_link' => '#',
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('Security Framework Implementation'),
                'meta_title' => 'Security Framework | PSRO',
                'meta_description' => 'Comprehensive security framework for BARMM',
            ],
            [
                'title' => 'Reconciliation Program Reaches Milestone',
                'content' => 'The Reconciliation Program celebrates reaching over 10,000 beneficiaries across the region, marking a significant achievement in healing and unity efforts.',
                'category' => 'Reconciliation',
                'image_path' => '/images/psro-logo.jpg',
                'is_slider' => true,
                'slider_order' => 3,
                'description' => 'Over 10,000 beneficiaries served in our reconciliation programs',
                'cta_text' => 'Our Impact',
                'cta_link' => '#',
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('Reconciliation Program Reaches Milestone'),
                'meta_title' => 'Reconciliation Program | PSRO',
                'meta_description' => 'Reconciliation program reaches 10,000 beneficiaries',
            ],
            // Regular posts (not in slider)
            [
                'title' => 'PSRO Launches Youth Peace Initiative',
                'content' => 'The Youth Peace Initiative aims to engage young leaders in peace-building activities across BARMM communities.',
                'category' => 'Programs',
                'image_path' => null,
                'is_slider' => false,
                'slider_order' => 0,
                'description' => null,
                'cta_text' => null,
                'cta_link' => null,
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('PSRO Launches Youth Peace Initiative'),
                'meta_title' => 'Youth Peace Initiative | PSRO',
                'meta_description' => 'Engaging young leaders in peace-building',
            ],
            [
                'title' => 'Community Dialogues Continue in Province',
                'content' => 'PSRO conducts community dialogues in various provinces to gather input for policy development.',
                'category' => 'Events',
                'image_path' => null,
                'is_slider' => false,
                'slider_order' => 0,
                'description' => null,
                'cta_text' => null,
                'cta_link' => null,
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('Community Dialogues Continue in Province'),
                'meta_title' => 'Community Dialogues | PSRO',
                'meta_description' => 'Community dialogues for policy development',
            ],
            [
                'title' => 'International Partners Visit PSRO Office',
                'content' => 'Delegation from international organizations visits the PSRO office to discuss collaboration opportunities.',
                'category' => 'Announcements',
                'image_path' => null,
                'is_slider' => false,
                'slider_order' => 0,
                'description' => null,
                'cta_text' => null,
                'cta_link' => null,
                'is_published' => true,
                'published_at' => now(),
                'slug' => Str::slug('International Partners Visit PSRO Office'),
                'meta_title' => 'International Collaboration | PSRO',
                'meta_description' => 'International partners discuss collaboration',
            ],
        ];

        foreach ($posts as $post) {
            Post::create($post);
        }
    }
}
