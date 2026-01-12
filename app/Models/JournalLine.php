<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * JournalLine Model
 * 
 * Represents a single line/entry in a journal.
 * Each line must have either a debit or credit amount (not both).
 * 
 * @property string $id
 * @property string $journal_id
 * @property string $account_id
 * @property string|null $cost_center_id
 * @property float $debit
 * @property float $credit
 */
class JournalLine extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'journal_id',
        'account_id',
        'cost_center_id',
        'debit',
        'credit',
        'debit_fc',
        'credit_fc',
        'currency',
        'exchange_rate',
        'description',
        'line_number',
        'reference_type',
        'reference_id',
        'due_date',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'debit_fc' => 'decimal:4',
            'credit_fc' => 'decimal:4',
            'exchange_rate' => 'decimal:6',
            'due_date' => 'date',
            'metadata' => 'array',
            'line_number' => 'integer',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    /**
     * Polymorphic relationship to sub-ledger entity.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get the net amount (debit - credit or credit - debit based on context).
     */
    public function getNetAmountAttribute(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }

    /**
     * Get the absolute amount.
     */
    public function getAmountAttribute(): float
    {
        return max((float) $this->debit, (float) $this->credit);
    }

    /**
     * Check if this is a debit entry.
     */
    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    /**
     * Check if this is a credit entry.
     */
    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }
}
