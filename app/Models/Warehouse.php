<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WarehouseType;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Warehouse Model
 */
class Warehouse extends Model
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'code',
        'name',
        'name_ar',
        'type',
        'address',
        'phone',
        'is_active',
        'allow_negative_stock',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => WarehouseType::class,
            'is_active' => 'boolean',
            'allow_negative_stock' => 'boolean',
            'sort_order' => 'integer',
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

    public function stockItems(): HasMany
    {
        return $this->hasMany(StockItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }

    public function getCodeNameAttribute(): string
    {
        return "{$this->code} - {$this->display_name}";
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Get stock level for a product
     */
    public function stockForProduct(Product $product): float
    {
        $item = $this->stockItems()->where('product_id', $product->id)->first();
        return $item ? (float) $item->quantity : 0;
    }

    /**
     * Check if negative stock is allowed
     */
    public function canGoNegative(): bool
    {
        return $this->allow_negative_stock || $this->type === WarehouseType::VIRTUAL;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeOfType($query, WarehouseType $type)
    {
        return $query->where('type', $type);
    }
}
