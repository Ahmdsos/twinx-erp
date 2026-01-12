<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Supplier Model
 */
class Supplier extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'name_ar',
        'email',
        'phone',
        'mobile',
        'vat_number',
        'cr_number',
        'address',
        'city',
        'country',
        'payment_terms',
        'payable_account_id',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms' => 'integer',
            'is_active' => 'boolean',
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

    public function payableAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payable_account_id');
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
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

    public function getTotalBalanceAttribute(): float
    {
        return (float) $this->bills()
            ->whereIn('status', ['posted', 'partially_paid'])
            ->sum('balance_due');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
