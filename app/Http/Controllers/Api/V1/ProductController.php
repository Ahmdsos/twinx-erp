<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends ApiController
{
    /**
     * List products (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('company_id', $request->user()->current_company_id);

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->paginate($request->get('per_page', 15));

        return $this->paginated($products);
    }

    /**
     * Show single product
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        return $this->success(new ProductResource($product));
    }

    /**
     * Create product
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'unit_id' => 'nullable|uuid|exists:units,id',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'barcode' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['company_id'] = $request->user()->current_company_id;
        $validated['created_by'] = $request->user()->id;

        $product = Product::create($validated);

        return $this->success(
            new ProductResource($product),
            'تم إنشاء المنتج بنجاح',
            201
        );
    }

    /**
     * Update product
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'sku' => 'sometimes|string|max:50',
            'name' => 'sometimes|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'unit_id' => 'nullable|uuid|exists:units,id',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'barcode' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return $this->success(
            new ProductResource($product->fresh()),
            'تم تحديث المنتج بنجاح'
        );
    }

    /**
     * Delete product
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $product->delete();

        return $this->success(null, 'تم حذف المنتج بنجاح');
    }
}
