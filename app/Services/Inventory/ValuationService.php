<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Enums\ValuationMethod;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockItem;
use App\Models\Warehouse;

/**
 * ValuationService
 * 
 * Handles inventory valuation calculations.
 * Supports FIFO, LIFO, and Weighted Average methods.
 */
class ValuationService
{
    /**
     * Get cost for removing stock based on valuation method
     */
    public function getCostForRemoval(Product $product, Warehouse $warehouse, float $quantity): float
    {
        return match($product->valuation_method) {
            ValuationMethod::FIFO => $this->calculateFifoCost($product, $warehouse, $quantity),
            ValuationMethod::LIFO => $this->calculateLifoCost($product, $warehouse, $quantity),
            ValuationMethod::WEIGHTED_AVERAGE => $this->calculateWeightedAverageCost($product, $warehouse),
        };
    }

    /**
     * Calculate cost using FIFO (First In First Out)
     */
    public function calculateFifoCost(Product $product, Warehouse $warehouse, float $quantity): float
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('remaining_qty', '>', 0)
            ->orderBy('received_date', 'asc')
            ->get();

        $totalCost = 0;
        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $qtyFromBatch = min($remainingQty, (float) $batch->remaining_qty);
            $totalCost += $qtyFromBatch * (float) $batch->unit_cost;
            $remainingQty -= $qtyFromBatch;
        }

        return $quantity > 0 ? $totalCost / $quantity : 0;
    }

    /**
     * Calculate cost using LIFO (Last In First Out)
     */
    public function calculateLifoCost(Product $product, Warehouse $warehouse, float $quantity): float
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('remaining_qty', '>', 0)
            ->orderBy('received_date', 'desc')
            ->get();

        $totalCost = 0;
        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $qtyFromBatch = min($remainingQty, (float) $batch->remaining_qty);
            $totalCost += $qtyFromBatch * (float) $batch->unit_cost;
            $remainingQty -= $qtyFromBatch;
        }

        return $quantity > 0 ? $totalCost / $quantity : 0;
    }

    /**
     * Calculate Weighted Average Cost
     */
    public function calculateWeightedAverageCost(Product $product, Warehouse $warehouse): float
    {
        $stockItem = StockItem::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();

        return $stockItem ? (float) $stockItem->avg_cost : (float) $product->cost_price;
    }

    /**
     * Consume from batches (FIFO)
     */
    public function consumeFromBatches(Product $product, Warehouse $warehouse, float $quantity): void
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('remaining_qty', '>', 0)
            ->orderBy('received_date', 'asc')
            ->get();

        $remainingQty = $quantity;

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $consumed = $batch->consume($remainingQty);
            $remainingQty -= $consumed;
        }
    }

    /**
     * Get inventory value for a product in a warehouse
     */
    public function getInventoryValue(Product $product, ?Warehouse $warehouse = null): float
    {
        $query = StockItem::where('product_id', $product->id);
        
        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        return (float) $query->get()->sum(function ($item) {
            return (float) $item->quantity * (float) $item->avg_cost;
        });
    }

    /**
     * Get total inventory value for a warehouse
     */
    public function getWarehouseValue(Warehouse $warehouse): float
    {
        return (float) StockItem::where('warehouse_id', $warehouse->id)
            ->get()
            ->sum(function ($item) {
                return (float) $item->quantity * (float) $item->avg_cost;
            });
    }

    /**
     * Recalculate weighted average cost from batches
     */
    public function recalculateAverageCost(Product $product, Warehouse $warehouse): float
    {
        $batches = StockBatch::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('remaining_qty', '>', 0)
            ->get();

        $totalQty = $batches->sum('remaining_qty');
        $totalValue = $batches->sum(function ($batch) {
            return (float) $batch->remaining_qty * (float) $batch->unit_cost;
        });

        return $totalQty > 0 ? $totalValue / $totalQty : 0;
    }
}
