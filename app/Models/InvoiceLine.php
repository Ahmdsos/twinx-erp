<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InvoiceLine Model
 */
class InvoiceLine extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'unit_id',
        'sales_order_line_id',
        'line_number',
        'description',
        'quantity',
        'unit_price',
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
            'unit_price' => 'decimal:4',
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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function calculateTotals(): void
    {
        $subtotal = (float) $this->quantity * (float) $this->unit_price;
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
