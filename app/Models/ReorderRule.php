<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReorderRule extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'min_quantity',
        'reorder_quantity',
        'max_quantity',
        'preferred_supplier_id',
        'lead_time_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'decimal:3',
            'reorder_quantity' => 'decimal:3',
            'max_quantity' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function preferredSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'preferred_supplier_id');
    }

    /**
     * Check if product needs reorder based on current stock
     */
    public function needsReorder(float $currentStock): bool
    {
        return $currentStock <= (float) $this->min_quantity;
    }

    /**
     * Get suggested order quantity
     */
    public function getSuggestedQuantity(float $currentStock): float
    {
        if ($this->max_quantity) {
            return (float) $this->max_quantity - $currentStock;
        }
        return (float) $this->reorder_quantity;
    }
}
