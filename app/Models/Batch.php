<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'batch_number',
        'manufacture_date',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'cost_per_unit',
        'is_expired',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'manufacture_date' => 'date',
            'expiry_date' => 'date',
            'initial_quantity' => 'decimal:3',
            'current_quantity' => 'decimal:3',
            'cost_per_unit' => 'decimal:2',
            'is_expired' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now()->toDateString();
    }

    public function daysUntilExpiry(): int|float|null
    {
        return $this->expiry_date
            ? (int) now()->diffInDays($this->expiry_date, false)
            : null;
    }

    public function isNearExpiry(int $days = 30): bool
    {
        $daysLeft = $this->daysUntilExpiry();
        return $daysLeft !== null && $daysLeft <= $days && $daysLeft >= 0;
    }
}
