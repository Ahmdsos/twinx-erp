<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
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
 * Invoice Model
 */
class Invoice extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'branch_id',
        'invoice_number',
        'type',
        'customer_id',
        'sales_order_id',
        'invoice_date',
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
        'issued_by',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'issued_at' => 'datetime',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total');
        $taxAmount = $this->lines()->sum('tax_amount');
        $total = $subtotal + $taxAmount - (float) $this->discount_amount;
        
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
            ? InvoiceStatus::PAID 
            : InvoiceStatus::PARTIALLY_PAID;

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

    public function canReceivePayment(): bool
    {
        return $this->status->canReceivePayment();
    }

    public function isOverdue(): bool
    {
        return $this->status !== InvoiceStatus::PAID 
            && $this->due_date->isPast();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeOfStatus($query, InvoiceStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', [
            InvoiceStatus::ISSUED,
            InvoiceStatus::PARTIALLY_PAID,
            InvoiceStatus::OVERDUE,
        ]);
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereIn('status', [
                InvoiceStatus::ISSUED,
                InvoiceStatus::PARTIALLY_PAID,
            ]);
    }
}
