<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\ProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends ApiController
{
    /**
     * List products (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->current_company_id;
        
        $query = Product::query()
            ->where('company_id', $companyId)
            ->with(['category:id,name,name_ar,slug', 'brand:id,name,name_ar,logo', 'unit:id,name,short_name']);

        // Search (name, name_ar, sku, barcode)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_ar', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by brand
        if ($request->has('brand_id') && !empty($request->brand_id)) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'low_stock':
                    $query->where('track_stock', true)
                          ->whereColumn('stock_quantity', '<=', 'reorder_level')
                          ->where('stock_quantity', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('track_stock', true)
                          ->where('stock_quantity', '<=', 0);
                    break;
                case 'in_stock':
                    $query->where(function ($q) {
                        $q->where('track_stock', false)
                          ->orWhere(function ($q2) {
                              $q2->where('track_stock', true)
                                 ->whereColumn('stock_quantity', '>', 'reorder_level');
                          });
                    });
                    break;
            }
        }

        // Sorting
        $sortField = $request->get('sort_field', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['name', 'sku', 'cost_price', 'selling_price', 'stock_quantity', 'created_at', 'updated_at'];
        
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->get('per_page', 20), 100);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Show single product
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->current_company_id)
            ->with(['category', 'brand', 'unit'])
            ->findOrFail($id);

        return $this->success(new ProductResource($product));
    }

    /**
     * Create product
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        // Set company and user
        $validated['company_id'] = $request->user()->current_company_id;
        $validated['created_by'] = $request->user()->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        // Auto-generate SKU if empty
        if (empty($validated['sku'])) {
            $validated['sku'] = $this->generateSku($validated['company_id']);
        }

        // Auto-generate barcode if empty
        if (empty($validated['barcode'])) {
            $validated['barcode'] = $this->generateBarcode();
        }

        $product = Product::create($validated);

        return $this->success(
            new ProductResource($product->load(['category', 'brand', 'unit'])),
            'تم إنشاء المنتج بنجاح',
            201
        );
    }

    /**
     * Update product
     */
    public function update(ProductRequest $request, string $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = $path;
        }

        $product->update($validated);

        return $this->success(
            new ProductResource($product->fresh()->load(['category', 'brand', 'unit'])),
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

        // Delete image
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        $product->delete();

        return $this->success(null, 'تم حذف المنتج بنجاح');
    }

    /**
     * Upload product image
     */
    public function uploadImage(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $product = Product::where('company_id', $request->user()->current_company_id)
            ->findOrFail($id);

        // Delete old image
        if ($product->image_url) {
            Storage::disk('public')->delete($product->image_url);
        }

        // Store new image
        $path = $request->file('image')->store('products', 'public');
        $product->update(['image_url' => $path]);

        return $this->success([
            'image_url' => url('storage/' . $path),
        ], 'تم رفع صورة المنتج بنجاح');
    }

    /**
     * Import products from Excel
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120',
        ]);

        $companyId = $request->user()->current_company_id;
        $userId = $request->user()->id;
        
        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            
            // Use PhpSpreadsheet or simple CSV parsing
            $imported = 0;
            $errors = [];
            
            if ($extension === 'csv') {
                $handle = fopen($file->getPathname(), 'r');
                $header = fgetcsv($handle); // Skip header row
                
                while (($row = fgetcsv($handle)) !== false) {
                    try {
                        if (count($row) < 4) continue;
                        
                        Product::create([
                            'company_id' => $companyId,
                            'created_by' => $userId,
                            'sku' => $row[0] ?: $this->generateSku($companyId),
                            'name' => $row[1],
                            'name_ar' => $row[2] ?? null,
                            'barcode' => $row[3] ?? null,
                            'cost_price' => (float) ($row[4] ?? 0),
                            'selling_price' => (float) ($row[5] ?? 0),
                            'retail_price' => (float) ($row[5] ?? 0),
                            'stock_quantity' => (int) ($row[6] ?? 0),
                            'is_active' => true,
                        ]);
                        $imported++;
                    } catch (\Exception $e) {
                        $errors[] = "صف " . ($imported + 1) . ": " . $e->getMessage();
                    }
                }
                fclose($handle);
            } else {
                // For xlsx/xls, you would use PhpSpreadsheet here
                // This is a placeholder - implement with PhpSpreadsheet if needed
                return $this->error('استيراد ملفات Excel غير مدعوم حالياً، استخدم CSV', 422);
            }
            
            return $this->success([
                'imported' => $imported,
                'errors' => $errors,
            ], "تم استيراد {$imported} منتج بنجاح");
            
        } catch (\Exception $e) {
            return $this->error('حدث خطأ أثناء استيراد الملف: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export products to CSV
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $companyId = $request->user()->current_company_id;
        
        $products = Product::where('company_id', $companyId)
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name,short_name'])
            ->orderBy('name')
            ->get();

        $filename = 'products_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        return response()->stream(function () use ($products) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");
            
            // Header row
            fputcsv($handle, [
                'SKU',
                'الاسم (إنجليزي)',
                'الاسم (عربي)',
                'الباركود',
                'التصنيف',
                'الماركة',
                'الوحدة',
                'سعر التكلفة',
                'سعر البيع',
                'سعر التجزئة',
                'سعر الجملة',
                'سعر الموزع',
                'المخزون',
                'حد إعادة الطلب',
                'نسبة الضريبة',
                'الحالة',
            ]);
            
            // Data rows
            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->sku,
                    $product->name,
                    $product->name_ar,
                    $product->barcode,
                    $product->category?->name,
                    $product->brand?->name,
                    $product->unit?->name,
                    $product->cost_price,
                    $product->selling_price,
                    $product->retail_price,
                    $product->wholesale_price,
                    $product->distributor_price,
                    $product->stock_quantity,
                    $product->reorder_level,
                    $product->tax_rate,
                    $product->is_active ? 'نشط' : 'معطل',
                ]);
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid',
        ]);

        $companyId = $request->user()->current_company_id;
        
        $products = Product::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->get();

        $deleted = 0;
        foreach ($products as $product) {
            if ($product->image_url) {
                Storage::disk('public')->delete($product->image_url);
            }
            $product->delete();
            $deleted++;
        }

        return $this->success([
            'deleted' => $deleted,
        ], "تم حذف {$deleted} منتج بنجاح");
    }

    /**
     * Bulk update products
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid',
            'data' => 'required|array',
            'data.category_id' => 'nullable|uuid',
            'data.brand_id' => 'nullable|uuid',
            'data.is_active' => 'nullable|boolean',
            'data.price_adjustment' => 'nullable|numeric',
            'data.price_adjustment_type' => 'nullable|in:fixed,percentage',
        ]);

        $companyId = $request->user()->current_company_id;
        $data = $request->data;
        
        $updateData = [];
        
        if (isset($data['category_id'])) {
            $updateData['category_id'] = $data['category_id'];
        }
        if (isset($data['brand_id'])) {
            $updateData['brand_id'] = $data['brand_id'];
        }
        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        $updated = Product::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->update($updateData);

        // Handle price adjustment if specified
        if (isset($data['price_adjustment']) && isset($data['price_adjustment_type'])) {
            $products = Product::where('company_id', $companyId)
                ->whereIn('id', $request->ids)
                ->get();
                
            foreach ($products as $product) {
                $adjustment = $data['price_adjustment'];
                
                if ($data['price_adjustment_type'] === 'percentage') {
                    $newPrice = $product->selling_price * (1 + ($adjustment / 100));
                } else {
                    $newPrice = $product->selling_price + $adjustment;
                }
                
                $product->update([
                    'selling_price' => max(0, $newPrice),
                    'retail_price' => max(0, $newPrice),
                ]);
            }
        }

        return $this->success([
            'updated' => $updated,
        ], "تم تحديث {$updated} منتج بنجاح");
    }

    /**
     * Generate unique SKU
     */
    protected function generateSku(string $companyId): string
    {
        $prefix = 'PRD-';
        $number = Product::where('company_id', $companyId)->count() + 1;
        
        do {
            $sku = $prefix . str_pad((string) $number, 6, '0', STR_PAD_LEFT);
            $exists = Product::where('company_id', $companyId)
                ->where('sku', $sku)
                ->exists();
            $number++;
        } while ($exists);
        
        return $sku;
    }

    /**
     * Generate unique barcode (EAN-13 format)
     */
    protected function generateBarcode(): string
    {
        $prefix = '200'; // Internal use prefix
        $random = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $code = $prefix . $random;
        
        // Calculate check digit (EAN-13)
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $code[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $code . $checkDigit;
    }
}
