<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends ApiController
{
    /**
     * List categories (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->where('company_id', $request->user()->current_company_id);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        // Filter by parent
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } elseif ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Sorting
        $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc');

        $categories = $query->paginate($request->get('per_page', 50));

        return $this->paginated($categories);
    }

    /**
     * Get category tree structure
     */
    public function tree(Request $request): JsonResponse
    {
        $categories = Category::where('company_id', $request->user()->current_company_id)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['children' => function ($q) {
                        $q->where('is_active', true)->orderBy('sort_order');
                    }]);
            }])
            ->orderBy('sort_order')
            ->get();

        return $this->success($categories, 'Category tree retrieved');
    }

    /**
     * Show single category
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $category = Category::where('company_id', $request->user()->current_company_id)
            ->with('parent', 'children')
            ->findOrFail($id);

        return $this->success($category);
    }

    /**
     * Create category
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->current_company_id;
        $validated['slug'] = $validated['slug'] ?? \Str::slug($validated['name']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = Category::create($validated);

        return $this->success($category, 'تم إنشاء التصنيف بنجاح', 201);
    }

    /**
     * Update category
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => 'nullable|uuid|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Prevent setting itself as parent
        if (isset($validated['parent_id']) && $validated['parent_id'] === $id) {
            return $this->error('لا يمكن تعيين التصنيف كأب لنفسه', 422);
        }

        $category->update($validated);

        return $this->success($category->fresh(), 'تم تحديث التصنيف بنجاح');
    }

    /**
     * Delete category
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $category = Category::where('company_id', $request->user()->current_company_id)
            ->withCount('children', 'products')
            ->findOrFail($id);

        // Check for children
        if ($category->children_count > 0) {
            return $this->error('لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية', 422);
        }

        // Check for products
        if ($category->products_count > 0) {
            return $this->error('لا يمكن حذف تصنيف يحتوي على منتجات', 422);
        }

        $category->delete();

        return $this->success(null, 'تم حذف التصنيف بنجاح');
    }

    /**
     * Reorder categories
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|uuid|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
            'categories.*.parent_id' => 'nullable|uuid|exists:categories,id',
        ]);

        foreach ($validated['categories'] as $item) {
            Category::where('id', $item['id'])
                ->where('company_id', $request->user()->current_company_id)
                ->update([
                    'sort_order' => $item['sort_order'],
                    'parent_id' => $item['parent_id'] ?? null,
                ]);
        }

        return $this->success(null, 'تم إعادة ترتيب التصنيفات بنجاح');
    }
}
