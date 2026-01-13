<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    /**
     * List brands (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Brand::query()
            ->where('company_id', $request->user()->current_company_id);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%");
            });
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $brands = $query->paginate($request->get('per_page', 50));

        return $this->paginated($brands);
    }

    /**
     * List all brands (without pagination for dropdowns)
     */
    public function all(Request $request): JsonResponse
    {
        $brands = Brand::where('company_id', $request->user()->current_company_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'name_ar', 'logo']);

        return $this->success($brands, 'All brands retrieved');
    }

    /**
     * Show single brand
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $brand = Brand::where('company_id', $request->user()->current_company_id)
            ->withCount('products')
            ->findOrFail($id);

        return $this->success($brand);
    }

    /**
     * Create brand
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->current_company_id;
        $validated['slug'] = $validated['slug'] ?? \Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;

        $brand = Brand::create($validated);

        return $this->success($brand, 'تم إنشاء الماركة بنجاح', 201);
    }

    /**
     * Update brand
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $brand = Brand::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255',
            'logo' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $brand->update($validated);

        return $this->success($brand->fresh(), 'تم تحديث الماركة بنجاح');
    }

    /**
     * Delete brand
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $brand = Brand::where('company_id', $request->user()->current_company_id)
            ->withCount('products')
            ->findOrFail($id);

        // Check for products
        if ($brand->products_count > 0) {
            return $this->error('لا يمكن حذف ماركة مرتبطة بمنتجات', 422);
        }

        $brand->delete();

        return $this->success(null, 'تم حذف الماركة بنجاح');
    }
}
