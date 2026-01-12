<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
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
 * PurchaseOrder Model
 */
class PurchaseOrder extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'branch_id',
        'order_number',
        'supplier_id',
        'order_date',
        'expected_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'currency',
        'exchange_rate',
        'warehouse_id',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'approved_at' => 'datetime',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class)->orderBy('line_number');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total');
        $taxAmount = $this->lines()->sum('tax_amount');
        
        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $taxAmount - (float) ($this->discount_amount ?? 0),
        ]);
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    public function canApprove(): bool
    {
        return $this->status === PurchaseOrderStatus::DRAFT && $this->lines()->count() > 0;
    }

    public function canReceive(): bool
    {
        return $this->status->canReceive();
    }

    public function isFullyReceived(): bool
    {
        foreach ($this->lines as $line) {
            if ((float) $line->received_qty < (float) $line->quantity) {
                return false;
            }
        }
        return true;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeOfStatus($query, PurchaseOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            PurchaseOrderStatus::CONFIRMED,
            PurchaseOrderStatus::PARTIAL,
        ]);
    }
}
