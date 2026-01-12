<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PriceList Model
 */
class PriceList extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'name_ar',
        'is_default',
        'markup_percent',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'markup_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    public function getPriceForProduct(Product $product, ?Unit $unit = null, float $qty = 1): ?float
    {
        $query = $this->items()
            ->where('product_id', $product->id)
            ->where('min_qty', '<=', $qty)
            ->orderBy('min_qty', 'desc');

        if ($unit) {
            $query->where('unit_id', $unit->id);
        }

        $item = $query->first();

        if ($item) {
            return (float) $item->price;
        }

        // Fallback to product price with markup
        $basePrice = (float) $product->sale_price;
        return $basePrice * (1 + ((float) $this->markup_percent / 100));
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
