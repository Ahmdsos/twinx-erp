<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountBalance Model
 * 
 * Pre-calculated balance for performance optimization.
 * Updated when journals are posted.
 * 
 * @property string $id
 * @property string $account_id
 * @property string $period_id
 * @property string|null $branch_id
 */
class AccountBalance extends Model
{
    use HasUuid;

    protected $fillable = [
        'account_id',
        'period_id',
        'branch_id',
        'opening_debit',
        'opening_credit',
        'period_debit',
        'period_credit',
        'closing_debit',
        'closing_credit',
        'ytd_debit',
        'ytd_credit',
    ];

    protected function casts(): array
    {
        return [
            'opening_debit' => 'decimal:4',
            'opening_credit' => 'decimal:4',
            'period_debit' => 'decimal:4',
            'period_credit' => 'decimal:4',
            'closing_debit' => 'decimal:4',
            'closing_credit' => 'decimal:4',
            'ytd_debit' => 'decimal:4',
            'ytd_credit' => 'decimal:4',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get net opening balance.
     */
    public function getOpeningBalanceAttribute(): float
    {
        return (float) $this->opening_debit - (float) $this->opening_credit;
    }

    /**
     * Get net period movement.
     */
    public function getPeriodMovementAttribute(): float
    {
        return (float) $this->period_debit - (float) $this->period_credit;
    }

    /**
     * Get net closing balance.
     */
    public function getClosingBalanceAttribute(): float
    {
        return (float) $this->closing_debit - (float) $this->closing_credit;
    }

    // =========================================================================
    // METHODS
    // =========================================================================

    /**
     * Recalculate closing balances.
     */
    public function recalculateClosing(): void
    {
        $this->closing_debit = (float) $this->opening_debit + (float) $this->period_debit;
        $this->closing_credit = (float) $this->opening_credit + (float) $this->period_credit;
        $this->save();
    }

    /**
     * Add movement to period totals.
     */
    public function addMovement(float $debit, float $credit): void
    {
        $this->period_debit = (float) $this->period_debit + $debit;
        $this->period_credit = (float) $this->period_credit + $credit;
        $this->recalculateClosing();
    }
}
