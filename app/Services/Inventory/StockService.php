<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\MovementType;
use App\Enums\ValuationMethod;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * StockService
 * 
 * Core service for inventory management.
 * Handles stock additions, removals, transfers.
 */
class StockService
{
    public function __construct(
        private TenantContext $tenantContext,
        private ValuationService $valuationService
    ) {}

    /**
     * Add stock (purchase, return, adjustment+, opening)
     */
    public function addStock(
        Product $product,
        Warehouse $warehouse,
        Unit $unit,
        float $quantity,
        float $unitCost,
        MovementType $type,
        ?string $batchNumber = null,
        ?\Carbon\Carbon $expiryDate = null,
        ?string $notes = null,
        ?string $sourceType = null,
        ?string $sourceId = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product, $warehouse, $unit, $quantity, $unitCost, $type,
            $batchNumber, $expiryDate, $notes, $sourceType, $sourceId
        ) {
            // Create movement
            $movement = StockMovement::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'reference' => $this->generateReference($type),
                'type' => $type,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'unit_id' => $unit->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'movement_date' => now(),
                'notes' => $notes,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => auth()->id(),
            ]);

            // Update stock item
            $stockItem = $this->getOrCreateStockItem($product, $warehouse, $unit);
            $stockItem->addStock($quantity, $unitCost);

            // Create batch for FIFO tracking
            if ($product->valuation_method === ValuationMethod::FIFO) {
                StockBatch::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'batch_number' => $batchNumber,
                    'received_date' => now(),
                    'expiry_date' => $expiryDate,
                    'initial_qty' => $quantity,
                    'remaining_qty' => $quantity,
                    'unit_cost' => $unitCost,
                    'movement_id' => $movement->id,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Remove stock (sale, return out, adjustment-)
     */
    public function removeStock(
        Product $product,
        Warehouse $warehouse,
        Unit $unit,
        float $quantity,
        MovementType $type,
        ?string $notes = null,
        ?string $sourceType = null,
        ?string $sourceId = null
    ): StockMovement {
        return DB::transaction(function () use (
            $product, $warehouse, $unit, $quantity, $type,
            $notes, $sourceType, $sourceId
        ) {
            // Get cost based on valuation method
            $unitCost = $this->valuationService->getCostForRemoval(
                $product, $warehouse, $quantity
            );

            // Create movement
            $movement = StockMovement::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'reference' => $this->generateReference($type),
                'type' => $type,
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'unit_id' => $unit->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'movement_date' => now(),
                'notes' => $notes,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => auth()->id(),
            ]);

            // Update stock item
            $stockItem = $this->getOrCreateStockItem($product, $warehouse, $unit);
            $stockItem->removeStock($quantity);

            // Consume from batches for FIFO
            if ($product->valuation_method === ValuationMethod::FIFO) {
                $this->valuationService->consumeFromBatches($product, $warehouse, $quantity);
            }

            return $movement;
        });
    }

    /**
     * Transfer stock between warehouses
     */
    public function transfer(
        Product $product,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        Unit $unit,
        float $quantity,
        ?string $notes = null
    ): array {
        return DB::transaction(function () use (
            $product, $fromWarehouse, $toWarehouse, $unit, $quantity, $notes
        ) {
            // Get current cost
            $unitCost = $this->valuationService->getCostForRemoval(
                $product, $fromWarehouse, $quantity
            );

            // Create outbound movement
            $outbound = StockMovement::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'reference' => $this->generateReference(MovementType::TRANSFER_OUT),
                'type' => MovementType::TRANSFER_OUT,
                'product_id' => $product->id,
                'warehouse_id' => $fromWarehouse->id,
                'unit_id' => $unit->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'movement_date' => now(),
                'notes' => $notes,
                'transfer_warehouse_id' => $toWarehouse->id,
                'created_by' => auth()->id(),
            ]);

            // Create inbound movement
            $inbound = StockMovement::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'reference' => $this->generateReference(MovementType::TRANSFER_IN),
                'type' => MovementType::TRANSFER_IN,
                'product_id' => $product->id,
                'warehouse_id' => $toWarehouse->id,
                'unit_id' => $unit->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'movement_date' => now(),
                'notes' => $notes,
                'transfer_warehouse_id' => $fromWarehouse->id,
                'related_movement_id' => $outbound->id,
                'created_by' => auth()->id(),
            ]);

            // Link outbound to inbound
            $outbound->update(['related_movement_id' => $inbound->id]);

            // Update stock items
            $fromItem = $this->getOrCreateStockItem($product, $fromWarehouse, $unit);
            $fromItem->removeStock($quantity);

            $toItem = $this->getOrCreateStockItem($product, $toWarehouse, $unit);
            $toItem->addStock($quantity, $unitCost);

            return ['outbound' => $outbound, 'inbound' => $inbound];
        });
    }

    /**
     * Get or create stock item
     */
    public function getOrCreateStockItem(Product $product, Warehouse $warehouse, Unit $unit): StockItem
    {
        return StockItem::firstOrCreate(
            [
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'unit_id' => $unit->id,
            ],
            [
                'quantity' => 0,
                'reserved_qty' => 0,
                'avg_cost' => 0,
            ]
        );
    }

    /**
     * Get stock level for product
     */
    public function getStockLevel(Product $product, ?Warehouse $warehouse = null): float
    {
        $query = StockItem::where('product_id', $product->id);
        
        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get available stock (not reserved)
     */
    public function getAvailableStock(Product $product, ?Warehouse $warehouse = null): float
    {
        $query = StockItem::where('product_id', $product->id);
        
        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        $items = $query->get();
        return (float) $items->sum('quantity') - (float) $items->sum('reserved_qty');
    }

    /**
     * Generate unique reference number
     */
    public function generateReference(MovementType $type): string
    {
        $prefix = $type->referencePrefix();
        $date = now()->format('Ymd');
        
        $lastRef = StockMovement::where('company_id', $this->tenantContext->companyId())
            ->where('reference', 'like', "{$prefix}-{$date}-%")
            ->orderBy('reference', 'desc')
            ->value('reference');

        if ($lastRef) {
            $lastNumber = (int) substr($lastRef, -5);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $newNumber);
    }
}
