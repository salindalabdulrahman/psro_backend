<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicController extends Controller
{
    public function index()
    {
        // Fetch slider posts (marked as is_slider = true)
        $sliderPosts = Post::slider()
            ->whereNotNull('image_path')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'image' => $post->image_path,
                    'title' => $post->title,
                    'description' => $post->description,
                    'category' => $post->category,
                    'cta_text' => $post->cta_text,
                ];
            });

        // Fetch the latest 3 posts to show on the landing page
        $posts = Post::latest()->take(3)->get();

        return Inertia::render('Web/Home', [
            'slides' => $sliderPosts,
            'posts' => $posts,
            'headline' => 'PSRO-BARMM',
            'subheadline' => 'Building lasting peace through community engagement, conflict transformation, and coordinated regional security initiatives across the Bangsamoro region.'
        ]);
    }
}
