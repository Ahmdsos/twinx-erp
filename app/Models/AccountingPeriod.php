<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AccountingPeriod Model
 * 
 * Represents a fiscal period (usually monthly) for accounting transactions.
 * 
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property int $fiscal_year
 * @property int $period_number
 * @property PeriodStatus $status
 */
class AccountingPeriod extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = [
        'company_id',
        'name',
        'name_ar',
        'start_date',
        'end_date',
        'fiscal_year',
        'period_number',
        'status',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => PeriodStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'closed_at' => 'datetime',
            'fiscal_year' => 'integer',
            'period_number' => 'integer',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class, 'period_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountBalance::class, 'period_id');
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Check if a date falls within this period.
     */
    public function containsDate(\Carbon\Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Check if posting is allowed.
     */
    public function allowsPosting(): bool
    {
        return $this->status->allowsPosting();
    }

    /**
     * Get display name based on locale.
     */
    public function getDisplayNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeOpen($query)
    {
        return $query->where('status', PeriodStatus::OPEN);
    }

    public function scopeForFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeContainingDate($query, \Carbon\Carbon $date)
    {
        return $query->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);
    }

    // =========================================================================
    // STATIC HELPERS
    // =========================================================================

    /**
     * Find the period containing a specific date.
     */
    public static function findForDate(string $companyId, \Carbon\Carbon $date): ?self
    {
        return static::forCompany($companyId)
            ->containingDate($date)
            ->first();
    }

    /**
     * Get or create period for a date (useful for auto-creation).
     */
    public static function getOrCreateForDate(string $companyId, \Carbon\Carbon $date): self
    {
        $period = static::findForDate($companyId, $date);

        if (!$period) {
            $period = static::create([
                'company_id' => $companyId,
                'name' => $date->format('F Y'),
                'name_ar' => $date->locale('ar')->translatedFormat('F Y'),
                'start_date' => $date->copy()->startOfMonth(),
                'end_date' => $date->copy()->endOfMonth(),
                'fiscal_year' => $date->year,
                'period_number' => $date->month,
                'status' => PeriodStatus::OPEN,
            ]);
        }

        return $period;
    }
}
