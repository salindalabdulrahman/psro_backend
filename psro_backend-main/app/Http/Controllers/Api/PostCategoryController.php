<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PostCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostCategoryController extends Controller
{
    /**
     * List all categories (admin - includes inactive)
     */
    public function index(): JsonResponse
    {
        $categories = PostCategory::orderBy('display_order')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /**
     * Create a new category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:post_categories,name'],
            'icon' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['display_order'] = $validated['display_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = PostCategory::create($validated);

        return response()->json([
            'category' => $category,
            'message' => 'Category created successfully.',
        ], 201);
    }

    /**
     * Update a category
     */
    public function update(Request $request, PostCategory $postCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:80', 'unique:post_categories,name,' . $postCategory->id],
            'slug' => ['nullable', 'string', 'max:80'],
            'icon' => ['nullable', 'string', 'max:50'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (isset($validated['name']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $postCategory->update($validated);

        return response()->json([
            'category' => $postCategory->fresh(),
            'message' => 'Category updated successfully.',
        ]);
    }

    /**
     * Delete a category
     */
    public function destroy(PostCategory $postCategory): JsonResponse
    {
        $postCategory->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*.id' => ['required', 'integer', 'exists:post_categories,id'],
            'order.*.display_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['order'] as $item) {
            PostCategory::where('id', $item['id'])->update(['display_order' => $item['display_order']]);
        }

        return response()->json([
            'message' => 'Categories reordered successfully.',
        ]);
    }
}
