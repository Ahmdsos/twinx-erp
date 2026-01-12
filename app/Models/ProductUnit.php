<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductUnit Model - Multi-UOM support
 */
class ProductUnit extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion_factor',
        'is_base_unit',
        'is_purchase_unit',
        'is_sale_unit',
        'barcode',
        'sale_price',
        'cost_price',
    ];

    protected function casts(): array
    {
        return [
            'conversion_factor' => 'decimal:6',
            'is_base_unit' => 'boolean',
            'is_purchase_unit' => 'boolean',
            'is_sale_unit' => 'boolean',
            'sale_price' => 'decimal:4',
            'cost_price' => 'decimal:4',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // =========================================================================
    // CONVERSION METHODS
    // =========================================================================

    /**
     * Convert quantity to base unit
     */
    public function toBaseUnit(float $qty): float
    {
        return $qty * (float) $this->conversion_factor;
    }

    /**
     * Convert quantity from base unit
     */
    public function fromBaseUnit(float $qty): float
    {
        return $qty / (float) $this->conversion_factor;
    }

    /**
     * Get effective sale price (unit price or product price)
     */
    public function getEffectiveSalePriceAttribute(): float
    {
        return $this->sale_price ?? ($this->product->sale_price * (float) $this->conversion_factor);
    }
}
