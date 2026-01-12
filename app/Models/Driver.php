<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DriverStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, HasUuid, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'driver_number',
        'name',
        'name_ar',
        'phone',
        'email',
        'license_number',
        'license_expiry',
        'id_number',
        'status',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'status' => DriverStatus::class,
            'commission_rate' => 'decimal:2',
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

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class, 'assigned_driver_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function activeDeliveries(): HasMany
    {
        return $this->deliveries()->whereNotIn('status', ['delivered', 'failed']);
    }

    public function isAvailable(): bool
    {
        return $this->status === DriverStatus::AVAILABLE && $this->is_active;
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->getLocale() === 'ar' && $this->name_ar 
            ? $this->name_ar 
            : $this->name;
    }
}
