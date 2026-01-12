<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\ValuationMethod;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Product Model
 */
class Product extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'name_ar',
        'description',
        'type',
        'is_trackable',
        'is_purchasable',
        'is_sellable',
        'cost_price',
        'sale_price',
        'min_sale_price',
        'tax_rate',
        'is_tax_inclusive',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'reorder_qty',
        'valuation_method',
        'inventory_account_id',
        'cogs_account_id',
        'revenue_account_id',
        'base_unit_id',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'valuation_method' => ValuationMethod::class,
            'is_trackable' => 'boolean',
            'is_purchasable' => 'boolean',
            'is_sellable' => 'boolean',
            'is_tax_inclusive' => 'boolean',
            'is_active' => 'boolean',
            'cost_price' => 'decimal:4',
            'sale_price' => 'decimal:4',
            'min_sale_price' => 'decimal:4',
            'tax_rate' => 'decimal:2',
            'min_stock_level' => 'decimal:4',
            'max_stock_level' => 'decimal:4',
            'reorder_point' => 'decimal:4',
            'reorder_qty' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
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

    public function getSkuNameAttribute(): string
    {
        return "{$this->sku} - {$this->display_name}";
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Get total stock across all warehouses
     */
    public function getTotalStockAttribute(): float
    {
        return (float) $this->stockItems()->sum('quantity');
    }

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStockAttribute(): float
    {
        $items = $this->stockItems()->get();
        return (float) $items->sum('quantity') - (float) $items->sum('reserved_qty');
    }

    /**
     * Check if stock is low
     */
    public function isLowStock(): bool
    {
        return $this->total_stock <= $this->reorder_point;
    }

    /**
     * Get stock in specific warehouse
     */
    public function stockInWarehouse(Warehouse $warehouse): float
    {
        $item = $this->stockItems()->where('warehouse_id', $warehouse->id)->first();
        return $item ? (float) $item->quantity : 0;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrackable($query)
    {
        return $query->where('is_trackable', true);
    }

    public function scopeSellable($query)
    {
        return $query->where('is_sellable', true);
    }

    public function scopePurchasable($query)
    {
        return $query->where('is_purchasable', true);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeInCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
