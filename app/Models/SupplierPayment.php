<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * SupplierPayment Model
 */
class SupplierPayment extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'branch_id',
        'payment_number',
        'supplier_id',
        'bill_id',
        'payment_date',
        'payment_method',
        'amount',
        'currency',
        'exchange_rate',
        'reference',
        'bank_account_id',
        'notes',
        'journal_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeByMethod($query, PaymentMethod $method)
    {
        return $query->where('payment_method', $method);
    }
}
