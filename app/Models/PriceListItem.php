<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PriceListItem Model
 */
class PriceListItem extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'price_list_id',
        'product_id',
        'unit_id',
        'price',
        'min_qty',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:4',
            'min_qty' => 'decimal:4',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
