<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Batch;
use App\Models\ReorderRule;
use App\Services\TenantContext;
use Illuminate\Support\Collection;

/**
 * Advanced Inventory Service
 * خدمة المخزون المتقدم
 */
class AdvancedInventoryService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create batch for product
     */
    public function createBatch(array $data): Batch
    {
        return Batch::create([
            'company_id' => $this->tenantContext->companyId(),
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'],
            'batch_number' => $data['batch_number'],
            'manufacture_date' => $data['manufacture_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'initial_quantity' => $data['quantity'],
            'current_quantity' => $data['quantity'],
            'cost_per_unit' => $data['cost_per_unit'] ?? null,
        ]);
    }

    /**
     * Get expiring batches
     */
    public function getExpiringBatches(int $daysAhead = 30): Collection
    {
        $dateLimit = now()->addDays($daysAhead)->toDateString();

        return Batch::where('company_id', $this->tenantContext->companyId())
            ->where('current_quantity', '>', 0)
            ->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $dateLimit)
            ->where('expiry_date', '>=', now()->toDateString())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get expired batches
     */
    public function getExpiredBatches(): Collection
    {
        return Batch::where('company_id', $this->tenantContext->companyId())
            ->where('current_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->toDateString())
            ->get();
    }

    /**
     * Create reorder rule
     */
    public function createReorderRule(array $data): ReorderRule
    {
        return ReorderRule::create([
            'company_id' => $this->tenantContext->companyId(),
            'product_id' => $data['product_id'],
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'min_quantity' => $data['min_quantity'],
            'reorder_quantity' => $data['reorder_quantity'],
            'max_quantity' => $data['max_quantity'] ?? null,
            'preferred_supplier_id' => $data['preferred_supplier_id'] ?? null,
            'lead_time_days' => $data['lead_time_days'] ?? 0,
        ]);
    }

    /**
     * Get products needing reorder
     */
    public function getProductsNeedingReorder(): array
    {
        $rules = ReorderRule::where('company_id', $this->tenantContext->companyId())
            ->where('is_active', true)
            ->with(['product', 'warehouse', 'preferredSupplier'])
            ->get();

        $needsReorder = [];

        foreach ($rules as $rule) {
            // Get current stock
            $currentStock = $rule->product->inventoryLevels()
                ->when($rule->warehouse_id, fn ($q) => $q->where('warehouse_id', $rule->warehouse_id))
                ->sum('quantity');

            if ($rule->needsReorder($currentStock)) {
                $needsReorder[] = [
                    'product_id' => $rule->product_id,
                    'product_name' => $rule->product->name,
                    'warehouse' => $rule->warehouse?->name,
                    'current_stock' => $currentStock,
                    'min_quantity' => $rule->min_quantity,
                    'suggested_quantity' => $rule->getSuggestedQuantity($currentStock),
                    'supplier' => $rule->preferredSupplier?->name,
                ];
            }
        }

        return $needsReorder;
    }
}
