<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'account_id',
        'code',
        'bank_name',
        'account_number',
        'account_name',
        'iban',
        'swift_code',
        'currency_code',
        'opening_balance',
        'current_balance',
        'last_reconciled_date',
        'last_reconciled_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'last_reconciled_date' => 'date',
            'last_reconciled_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function getUnreconciledBalanceAttribute(): float
    {
        return (float) $this->current_balance - (float) ($this->last_reconciled_balance ?? 0);
    }
}
