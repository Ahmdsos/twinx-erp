<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'budget_id',
        'account_id',
        'period',
        'budgeted_amount',
        'actual_amount',
        'variance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'budgeted_amount' => 'decimal:2',
            'actual_amount' => 'decimal:2',
            'variance' => 'decimal:2',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getVariancePercentageAttribute(): float
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }
        return ((float) $this->variance / (float) $this->budgeted_amount) * 100;
    }
}
