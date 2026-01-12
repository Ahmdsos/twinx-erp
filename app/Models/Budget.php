<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'fiscal_year',
        'period_type',
        'status',
        'total_amount',
        'approved_by',
        'approved_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getTotalVarianceAttribute(): float
    {
        return $this->lines->sum('variance');
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }
        return ($this->total_variance / (float) $this->total_amount) * 100;
    }
}
