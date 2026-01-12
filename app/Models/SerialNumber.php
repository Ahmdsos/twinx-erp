<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SerialStatus;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialNumber extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'company_id',
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'purchase_line_id',
        'sale_line_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => SerialStatus::class,
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

    public function isAvailable(): bool
    {
        return $this->status === SerialStatus::AVAILABLE;
    }

    public function isSold(): bool
    {
        return $this->status === SerialStatus::SOLD;
    }
}
