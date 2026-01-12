<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockBatch Model - For FIFO tracking
 */
class StockBatch extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_number',
        'received_date',
        'expiry_date',
        'initial_qty',
        'remaining_qty',
        'unit_cost',
        'movement_id',
    ];

    protected function casts(): array
    {
        return [
            'received_date' => 'date',
            'expiry_date' => 'date',
            'initial_qty' => 'decimal:4',
            'remaining_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
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

    public function movement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'movement_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Check if batch is depleted
     */
    public function isDepleted(): bool
    {
        return (float) $this->remaining_qty <= 0;
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Get remaining value
     */
    public function getRemainingValueAttribute(): float
    {
        return (float) $this->remaining_qty * (float) $this->unit_cost;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Consume from this batch (FIFO)
     */
    public function consume(float $qty): float
    {
        $consumed = min($qty, (float) $this->remaining_qty);
        $this->remaining_qty = (float) $this->remaining_qty - $consumed;
        $this->save();
        return $consumed;
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
        return $query->where('remaining_qty', '>', 0);
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('received_date', 'asc');
    }

    public function scopeNewestFirst($query)
    {
        return $query->orderBy('received_date', 'desc');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', now());
        });
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                     ->where('expiry_date', '>', now());
    }
}
