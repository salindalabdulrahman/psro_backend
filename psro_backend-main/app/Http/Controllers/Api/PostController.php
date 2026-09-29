<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostCategory;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Get all posts (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::query();

        // Filter by publication status
        if ($request->has('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true);
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false)->whereNull('published_at');
            } elseif ($request->status === 'hide') {
                $query->where('is_published', false)->whereNotNull('published_at');
            }
        } elseif ($request->boolean('published_only', false)) {
            $query->published();
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by slider status
        if ($request->has('is_slider')) {
            $query->where('is_slider', $request->boolean('is_slider'));
        }

        // Search by title or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('title LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('description LIKE ?', ["%{$search}%"]);
            });
        }

        // Include author data
        $posts = $query->with('author:id,name,email')
                       ->orderBy('is_slider', 'desc')
                       ->orderBy('slider_order', 'asc')
                       ->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $posts->items(),
            'pagination' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
        ]);
    }

    /**
     * Get all post categories (for dropdowns)
     */
    public function categories(): JsonResponse
    {
        $categories = PostCategory::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get single post
     */
    public function show(Post $post): JsonResponse
    {
        $post->load('author:id,name,email');
        return response()->json($post);
    }

    /**
     * Create new post
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        if (trim((string) $request->input('activity_display_date', '')) === '') {
            $request->merge(['activity_display_date' => null]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:500'],
            'highlight_name' => ['nullable', 'string', 'max:255'],
            'highlight_role' => ['nullable', 'string', 'max:255'],
            'highlight_quote' => ['nullable', 'string', 'max:1000'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_link' => ['nullable', 'url', 'max:255'],
            'videos' => ['nullable', 'string'], // JSON string from FormData
            'is_slider' => ['boolean'],
            'slider_order' => ['integer', 'min:0'],
            'is_published' => ['boolean'],
            'activity_display_date' => ['nullable', 'string', 'date_format:Y-m-d'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['videos'] = $this->parseAndValidateVideos($request->input('videos'));
        $this->mergeUploadedVideoThumbnails($request, $validated['videos']);
        $validated = $this->normalizeHighlightFields($validated);

        // Generate slug
        $validated['slug'] = $this->generateUniqueSlug($request->title);

        // Handle image upload - store relative path for frontend resolution
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('posts', 'public');
            $validated['image_path'] = '/storage/' . $path;
        }

        // Set author and published date
        $validated['author_id'] = auth()->id();
        if ($request->boolean('is_published')) {
            $validated['published_at'] = now();
        }

        $post = Post::create($validated);
        $post->load('author:id,name,email');

        AuditLogService::record(
            $request,
            'post.created',
            auth()->id(),
            Post::class,
            $post->id,
            'Created post: '.$post->title,
            ['slug' => $post->slug],
            'success'
        );

        return response()->json([
            'post' => $post,
            'message' => 'Post created successfully.',
        ], 201);
    }

    /**
     * Update post
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);

        if ($request->has('activity_display_date') && trim((string) $request->input('activity_display_date')) === '') {
            $request->merge(['activity_display_date' => null]);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'category' => ['sometimes', 'string', 'max:80'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:500'],
            'highlight_name' => ['nullable', 'string', 'max:255'],
            'highlight_role' => ['nullable', 'string', 'max:255'],
            'highlight_quote' => ['nullable', 'string', 'max:1000'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'cta_link' => ['nullable', 'url', 'max:255'],
            'videos' => ['nullable', 'string'], // JSON string from FormData
            'is_slider' => ['boolean'],
            'slider_order' => ['integer', 'min:0'],
            'is_published' => ['boolean'],
            'save_as_draft' => ['boolean'],
            'activity_display_date' => ['nullable', 'string', 'date_format:Y-m-d'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        if ($request->has('videos')) {
            $validated['videos'] = $this->parseAndValidateVideos($request->input('videos'));
            $this->mergeUploadedVideoThumbnails($request, $validated['videos']);
        }

        $validated = $this->normalizeHighlightFields($validated);

        // Update slug if title changed
        if ($request->has('title')) {
            $validated['slug'] = $this->generateUniqueSlug($request->title, $post->id);
        }

        // Handle image upload - store relative path for frontend resolution
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($post->image_path) {
                $this->deleteImageFromUrl($post->image_path);
            }
            $path = $request->file('image')->store('posts', 'public');
            $validated['image_path'] = '/storage/' . $path;
        }

        // Handle published date
        if ($request->has('is_published')) {
            if ($request->boolean('is_published') && !$post->published_at) {
                $validated['published_at'] = now();
            }
            if (!$request->boolean('is_published') && $request->boolean('save_as_draft')) {
                $validated['published_at'] = null;
            }
        }

        $post->update($validated);
        $post->load('author:id,name,email');

        AuditLogService::record(
            $request,
            'post.updated',
            auth()->id(),
            Post::class,
            $post->id,
            'Updated post: '.$post->title,
            ['slug' => $post->slug],
            'success'
        );

        return response()->json([
            'post' => $post,
            'message' => 'Post updated successfully.',
        ]);
    }

    /**
     * Delete post
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $title = $post->title;
        $postId = $post->id;

        // Delete image file if exists
        if ($post->image_path) {
            $this->deleteImageFromUrl($post->image_path);
        }

        $post->delete();

        AuditLogService::record(
            $request,
            'post.deleted',
            auth()->id(),
            Post::class,
            $postId,
            'Deleted post: '.$title,
            [],
            'success'
        );

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * Reorder slider posts
     */
    public function reorderSlider(Request $request): JsonResponse
    {
        $this->authorize('reorderSlider', Post::class);

        $validated = $request->validate([
            'posts' => ['required', 'array'],
            'posts.*.id' => ['required', 'integer', 'exists:posts,id'],
            'posts.*.order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['posts'] as $item) {
            Post::where('id', $item['id'])->update(['slider_order' => $item['order']]);
        }

        AuditLogService::record(
            $request,
            'post.slider_reordered',
            auth()->id(),
            null,
            null,
            'Slider order updated',
            ['count' => count($validated['posts'])],
            'success'
        );

        return response()->json([
            'message' => 'Slider order updated successfully.',
        ]);
    }

    /**
     * Bulk delete posts
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorize('delete', Post::class);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:posts,id'],
        ]);

        $posts = Post::whereIn('id', $validated['ids'])->get();

        foreach ($posts as $post) {
            if ($post->image_path) {
                $this->deleteImageFromUrl($post->image_path);
            }
            $post->delete();
        }

        AuditLogService::record(
            $request,
            'post.bulk_deleted',
            auth()->id(),
            null,
            null,
            'Deleted '.count($validated['ids']).' post(s)',
            ['ids' => array_slice($validated['ids'], 0, 100)],
            'success'
        );

        return response()->json([
            'message' => 'Posts deleted successfully.',
            'count' => count($validated['ids']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeHighlightFields(array $validated): array
    {
        foreach (['highlight_name', 'highlight_role', 'highlight_quote'] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $value = trim((string) $validated[$key]);
            $validated[$key] = $value === '' ? null : $value;
        }

        return $validated;
    }

    /**
     * Generate unique slug
     */
    private function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $count = 0;
        $originalSlug = $slug;

        $query = Post::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $count++;
            $slug = "{$originalSlug}-{$count}";
            $query = Post::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
        }

        return $slug;
    }

    /**
     * Parse and validate videos from request (JSON string or null).
     * Returns array of {url, title, thumbnail?} with valid URLs only.
     * Fetches og:image thumbnail for each URL when possible (e.g. Facebook).
     *
     * @return array<int, array{url: string, title?: string, thumbnail?: string}>
     */
    private function parseAndValidateVideos(mixed $input): array
    {
        if ($input === null || $input === '') {
            return [];
        }
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (! is_array($decoded)) {
                return [];
            }
            $input = $decoded;
        }
        if (! is_array($input)) {
            return [];
        }
        $out = [];
        foreach ($input as $item) {
            if (! is_array($item) || empty($item['url']) || ! is_string($item['url'])) {
                continue;
            }
            $url = trim($item['url']);
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $entry = [
                'url' => $url,
                'title' => isset($item['title']) && is_string($item['title']) ? Str::limit(trim($item['title']), 200) : '',
            ];
            // Resolve thumbnail: manual URL, or fetch, then download and store locally (avoids hotlink/CORS/referrer blocks)
            $thumbnailUrl = null;
            $manualThumb = isset($item['thumbnail']) && is_string($item['thumbnail']) ? trim($item['thumbnail']) : '';
            if ($manualThumb !== '' && filter_var($manualThumb, FILTER_VALIDATE_URL)) {
                $thumbnailUrl = $manualThumb;
            } elseif (str_starts_with($manualThumb, '/storage/video-thumbnails/')) {
                $entry['thumbnail'] = $manualThumb;
                $out[] = $entry;
                continue;
            } else {
                $thumbnailUrl = $this->fetchVideoThumbnail($url);
            }
            if ($thumbnailUrl !== null) {
                $localPath = $this->downloadAndStoreThumbnail($thumbnailUrl);
                if ($localPath !== null) {
                    $entry['thumbnail'] = $localPath;
                }
            }
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Merge uploaded thumbnail files into the videos array (by index).
     * Request keys: thumbnail_0, thumbnail_1, ...
     *
     * @param  array<int, array<string, mixed>>  $videos
     */
    private function mergeUploadedVideoThumbnails(Request $request, array &$videos): void
    {
        foreach ($videos as $i => $video) {
            $key = 'thumbnail_' . $i;
            if (! $request->hasFile($key)) {
                continue;
            }
            $file = $request->file($key);
            if (! $file->isValid() || ! str_starts_with($file->getMimeType(), 'image/')) {
                continue;
            }
            $path = $file->store('video-thumbnails', 'public');
            $videos[$i]['thumbnail'] = '/storage/' . $path;
        }
    }

    /**
     * Download an image from URL and store it in our storage.
     * Returns the public path (e.g. /storage/video-thumbnails/xxx.jpg) or null.
     * This avoids hotlinking: we serve the image from our domain so there are no
     * CORS/referrer blocks from Facebook or other CDNs.
     *
     * @return string|null Public path to the stored file, or null on failure
     */
    private function downloadAndStoreThumbnail(string $imageUrl): ?string
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                ])
                ->get($imageUrl);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();
            if (strlen($body) < 100) {
                return null;
            }

            $contentType = $response->header('Content-Type') ?? '';
            $ext = 'jpg';
            if (str_contains($contentType, 'png')) {
                $ext = 'png';
            } elseif (str_contains($contentType, 'webp')) {
                $ext = 'webp';
            } elseif (str_contains($contentType, 'gif')) {
                $ext = 'gif';
            }

            $dir = 'video-thumbnails';
            $filename = Str::random(16) . '_' . time() . '.' . $ext;
            $path = $dir . '/' . $filename;

            Storage::disk('public')->put($path, $body);

            return '/storage/' . $path;
        } catch (\Throwable $e) {
            \Log::debug('Thumbnail download failed for ' . $imageUrl . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch thumbnail URL for a video link (e.g. Facebook).
     * Tries Microlink API first (works with Facebook), then direct og:image scrape.
     *
     * @return string|null Thumbnail URL or null on failure
     */
    private function fetchVideoThumbnail(string $url): ?string
    {
        $thumb = $this->fetchThumbnailViaMicrolink($url);
        if ($thumb !== null) {
            return $thumb;
        }
        return $this->fetchOgImageThumbnail($url);
    }

    /**
     * Fetch thumbnail via Microlink API (handles Facebook and most sites).
     * Free tier: 50 requests/day. No API key required for basic usage.
     *
     * @return string|null
     */
    private function fetchThumbnailViaMicrolink(string $url): ?string
    {
        try {
            $response = Http::timeout(8)
                ->get('https://api.microlink.io', [
                    'url' => $url,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            if (! is_array($json) || ($json['status'] ?? '') !== 'success') {
                return null;
            }

            $data = $json['data'] ?? [];
            $image = $data['image'] ?? null;
            if (is_string($image)) {
                return filter_var($image, FILTER_VALIDATE_URL) ? $image : null;
            }
            if (is_array($image) && ! empty($image['url'])) {
                $thumb = $image['url'];
                return filter_var($thumb, FILTER_VALIDATE_URL) ? $thumb : null;
            }

            return null;
        } catch (\Throwable $e) {
            \Log::debug('Microlink thumbnail failed for ' . $url . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch og:image from page HTML (fallback when Microlink fails or is rate-limited).
     *
     * @return string|null
     */
    private function fetchOgImageThumbnail(string $url): ?string
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $html = $response->body();
            if ($html === '') {
                return null;
            }

            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
                $thumb = trim(html_entity_decode($m[1]));
                return $thumb !== '' && filter_var($thumb, FILTER_VALIDATE_URL) ? $thumb : null;
            }
            if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
                $thumb = trim(html_entity_decode($m[1]));
                return $thumb !== '' && filter_var($thumb, FILTER_VALIDATE_URL) ? $thumb : null;
            }

            return null;
        } catch (\Throwable $e) {
            \Log::debug('og:image fetch failed for ' . $url . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete image from storage
     */
    private function deleteImageFromUrl(string $url): void
    {
        try {
            if (str_contains($url, '/storage/')) {
                $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
                Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::warning('Failed to delete image: ' . $e->getMessage());
        }
    }
}
