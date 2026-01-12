<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MovementType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StockMovement Model
 */
class StockMovement extends Model
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'branch_id',
        'reference',
        'type',
        'product_id',
        'warehouse_id',
        'unit_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'movement_date',
        'notes',
        'source_type',
        'source_id',
        'transfer_warehouse_id',
        'related_movement_id',
        'journal_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MovementType::class,
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'movement_date' => 'date',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

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

    public function transferWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'transfer_warehouse_id');
    }

    public function relatedMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'related_movement_id');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class, 'movement_id');
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Check if movement adds stock
     */
    public function isInbound(): bool
    {
        return in_array($this->type, [
            MovementType::PURCHASE,
            MovementType::TRANSFER_IN,
            MovementType::RETURN_IN,
            MovementType::OPENING,
        ]);
    }

    /**
     * Check if movement removes stock
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, [
            MovementType::SALE,
            MovementType::TRANSFER_OUT,
            MovementType::RETURN_OUT,
        ]);
    }

    /**
     * Get signed quantity (positive for inbound, negative for outbound)
     */
    public function getSignedQuantityAttribute(): float
    {
        return $this->isOutbound() ? -(float) $this->quantity : (float) $this->quantity;
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

    public function scopeOfType($query, MovementType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }

    public function scopeInbound($query)
    {
        return $query->whereIn('type', [
            MovementType::PURCHASE,
            MovementType::TRANSFER_IN,
            MovementType::RETURN_IN,
            MovementType::OPENING,
        ]);
    }

    public function scopeOutbound($query)
    {
        return $query->whereIn('type', [
            MovementType::SALE,
            MovementType::TRANSFER_OUT,
            MovementType::RETURN_OUT,
        ]);
    }
}
