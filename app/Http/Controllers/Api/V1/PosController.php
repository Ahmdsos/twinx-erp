<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Customer;
use App\Models\Product;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends ApiController
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Get products for POS
     */
    public function products(Request $request): JsonResponse
    {
        $query = Product::where('company_id', $this->tenantContext->companyId())
            ->where('is_active', true)
            ->where('is_sellable', true);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%")
                  ->orWhere('barcode', $search);
            });
        }

        // Category filter
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->select([
            'id', 'name', 'sku', 'barcode', 'selling_price', 'cost_price',
            'category_id', 'unit_id', 'tax_rate', 'image_url'
        ])->limit(50)->get();

        return $this->success($products, 'POS products retrieved');
    }

    /**
     * Search customers for POS
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $search = $request->input('search', '');

        $customers = Customer::where('company_id', $this->tenantContext->companyId())
            ->where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('customer_number', 'like', "%{$search}%");
            })
            ->select(['id', 'name', 'customer_number', 'phone', 'email'])
            ->limit(10)
            ->get();

        return $this->success($customers, 'Customers found');
    }

    /**
     * Quick sale (simplified invoice creation)
     */
    public function quickSale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|uuid',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'customer_id' => 'nullable|uuid',
        ]);

        // This would integrate with InvoiceService
        // For now, return success structure
        return $this->success([
            'message' => 'Quick sale endpoint ready',
            'items_count' => count($request->input('items')),
        ], 'Sale processed');
    }
}
