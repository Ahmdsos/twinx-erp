<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillLine Model
 */
class BillLine extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'bill_id',
        'product_id',
        'unit_id',
        'purchase_order_line_id',
        'line_number',
        'description',
        'quantity',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function calculateTotals(): void
    {
        $subtotal = (float) $this->quantity * (float) $this->unit_cost;
        $discountAmount = $subtotal * ((float) $this->discount_percent / 100);
        $netAmount = $subtotal - $discountAmount;
        $taxAmount = $netAmount * ((float) $this->tax_rate / 100);

        $this->update([
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'line_total' => $netAmount + $taxAmount,
        ]);
    }
}
