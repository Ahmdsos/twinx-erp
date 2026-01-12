<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JournalStatus;
use App\Enums\JournalType;
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
 * Journal Model
 * 
 * Represents a journal entry (accounting transaction).
 * Contains multiple journal lines that must balance (debits = credits).
 * 
 * @property string $id
 * @property string $company_id
 * @property string $branch_id
 * @property string $period_id
 * @property string $reference
 * @property JournalType $type
 * @property \Carbon\Carbon $transaction_date
 * @property JournalStatus $status
 * @property float $total_debit
 * @property float $total_credit
 */
class Journal extends Model implements Auditable
{
    use HasFactory;
    use HasUuid;
    use BelongsToTenant;
    use SoftDeletes;
    use AuditableTrait;

    protected $fillable = [
        'company_id',
        'branch_id',
        'period_id',
        'reference',
        'type',
        'transaction_date',
        'posting_date',
        'status',
        'total_debit',
        'total_credit',
        'currency',
        'exchange_rate',
        'description',
        'notes',
        'source_type',
        'source_id',
        'reversed_by_id',
        'reversal_of_id',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => JournalType::class,
            'status' => JournalStatus::class,
            'transaction_date' => 'date',
            'posting_date' => 'date',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
            'total_debit' => 'decimal:4',
            'total_credit' => 'decimal:4',
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

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('line_number');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversed_by_id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversal_of_id');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Check if the journal is balanced (debits = credits).
     */
    public function isBalanced(): bool
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return bccomp(
            (string) ($totals->total_debit ?? 0),
            (string) ($totals->total_credit ?? 0),
            4
        ) === 0;
    }

    /**
     * Recalculate totals from lines.
     */
    public function recalculateTotals(): void
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $this->update([
            'total_debit' => $totals->total_debit ?? 0,
            'total_credit' => $totals->total_credit ?? 0,
        ]);
    }

    /**
     * Check if journal can be edited.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if journal can be posted.
     */
    public function canPost(): bool
    {
        return $this->status->canPost()
            && $this->isBalanced()
            && $this->lines()->count() > 0
            && $this->period->status->allowsPosting();
    }

    /**
     * Check if journal can be voided.
     */
    public function canVoid(): bool
    {
        return $this->status->canVoid();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeDraft($query)
    {
        return $query->where('status', JournalStatus::DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', JournalStatus::POSTED);
    }

    public function scopeOfType($query, JournalType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function scopeBetweenDates($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}
