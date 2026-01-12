<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $query = Product::where('company_id', $this->tenantContext->companyId())
            ->with(['category:id,name', 'unit:id,name,symbol']);
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        // Filter by category
        if ($categoryId = $request->input('category')) {
            $query->where('category_id', $categoryId);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }
        
        $products = $query->orderBy('name')->paginate(20)->withQueryString();
        
        $categories = ProductCategory::where('company_id', $this->tenantContext->companyId())
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'semi_wholesale_price' => 'nullable|numeric|min:0',
            'quarter_wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'distributor_price' => 'nullable|numeric|min:0',
            'min_retail_qty' => 'nullable|integer|min:1',
            'min_semi_wholesale_qty' => 'nullable|integer|min:1',
            'min_quarter_wholesale_qty' => 'nullable|integer|min:1',
            'min_wholesale_qty' => 'nullable|integer|min:1',
            'min_distributor_qty' => 'nullable|integer|min:1',
            'stock_count' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);
        
        $validated['company_id'] = $this->tenantContext->companyId();
        
        Product::create($validated);
        
        return redirect()->route('admin.products.index')
            ->with('success', 'تم إضافة المنتج بنجاح');
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'sku' => 'required|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'nullable|uuid',
            'description' => 'nullable|string',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'semi_wholesale_price' => 'nullable|numeric|min:0',
            'quarter_wholesale_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'distributor_price' => 'nullable|numeric|min:0',
            'min_retail_qty' => 'nullable|integer|min:1',
            'min_semi_wholesale_qty' => 'nullable|integer|min:1',
            'min_quarter_wholesale_qty' => 'nullable|integer|min:1',
            'min_wholesale_qty' => 'nullable|integer|min:1',
            'min_distributor_qty' => 'nullable|integer|min:1',
            'stock_count' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);
        
        $product->update($validated);
        
        return redirect()->route('admin.products.index')
            ->with('success', 'تم تحديث المنتج بنجاح');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);
        
        $product->delete();
        
        return redirect()->route('admin.products.index')
            ->with('success', 'تم حذف المنتج بنجاح');
    }
}
