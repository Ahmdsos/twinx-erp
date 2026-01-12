<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BillStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

/**
 * Bill Model (Supplier Invoice)
 */
class Bill extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'branch_id',
        'bill_number',
        'supplier_ref',
        'supplier_id',
        'purchase_order_id',
        'bill_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'amount_paid',
        'balance_due',
        'currency',
        'exchange_rate',
        'journal_id',
        'notes',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BillStatus::class,
            'bill_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'posted_at' => 'datetime',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BillLine::class)->orderBy('line_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total');
        $taxAmount = $this->lines()->sum('tax_amount');
        $total = $subtotal + $taxAmount - (float) ($this->discount_amount ?? 0);
        
        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance_due' => $total - (float) $this->amount_paid,
        ]);
    }

    public function applyPayment(float $amount): void
    {
        $newPaid = (float) $this->amount_paid + $amount;
        $newBalance = (float) $this->total - $newPaid;

        $status = $newBalance <= 0 
            ? BillStatus::PAID 
            : BillStatus::PARTIALLY_PAID;

        $this->update([
            'amount_paid' => $newPaid,
            'balance_due' => max(0, $newBalance),
            'status' => $status,
        ]);
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    public function canPay(): bool
    {
        return $this->status->canPay();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeOfStatus($query, BillStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            BillStatus::POSTED,
            BillStatus::PARTIALLY_PAID,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', [
                BillStatus::POSTED,
                BillStatus::PARTIALLY_PAID,
            ]);
    }
}
