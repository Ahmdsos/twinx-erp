<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockItem Model - Inventory balances per warehouse
 */
class StockItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'unit_id',
        'quantity',
        'reserved_qty',
        'avg_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_qty' => 'decimal:4',
            'avg_cost' => 'decimal:4',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQtyAttribute(): float
    {
        return (float) $this->quantity - (float) $this->reserved_qty;
    }

    /**
     * Get total value (quantity * avg cost)
     */
    public function getTotalValueAttribute(): float
    {
        return (float) $this->quantity * (float) $this->avg_cost;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Add stock
     */
    public function addStock(float $qty, float $unitCost): void
    {
        // Update weighted average cost
        $totalQty = (float) $this->quantity + $qty;
        if ($totalQty > 0) {
            $totalValue = ((float) $this->quantity * (float) $this->avg_cost) + ($qty * $unitCost);
            $this->avg_cost = $totalValue / $totalQty;
        }
        
        $this->quantity = $totalQty;
        $this->save();
    }

    /**
     * Remove stock
     */
    public function removeStock(float $qty): void
    {
        $this->quantity = (float) $this->quantity - $qty;
        $this->save();
    }

    /**
     * Reserve stock
     */
    public function reserve(float $qty): void
    {
        $this->reserved_qty = (float) $this->reserved_qty + $qty;
        $this->save();
    }

    /**
     * Release reserved stock
     */
    public function release(float $qty): void
    {
        $this->reserved_qty = max(0, (float) $this->reserved_qty - $qty);
        $this->save();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeForProduct($query, string $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForWarehouse($query, string $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
