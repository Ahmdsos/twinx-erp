<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Traits\BelongsToTenant;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use HasFactory, HasUuid, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'delivery_number',
        'sales_order_id',
        'invoice_id',
        'customer_id',
        'customer_name',
        'delivery_address',
        'contact_phone',
        'driver_id',
        'vehicle_id',
        'zone_id',
        'status',
        'failure_reason',
        'scheduled_at',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'receiver_name',
        'delivery_notes',
        'signature_path',
        'photo_path',
        'delivery_fee',
        'cod_amount',
        'cod_collected',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'scheduled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_fee' => 'decimal:2',
            'cod_amount' => 'decimal:2',
            'cod_collected' => 'boolean',
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

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function isComplete(): bool
    {
        return $this->status->isComplete();
    }

    public function canAssign(): bool
    {
        return $this->status === DeliveryStatus::PENDING;
    }

    public function canDeliver(): bool
    {
        return in_array($this->status, [
            DeliveryStatus::ASSIGNED,
            DeliveryStatus::PICKED_UP,
            DeliveryStatus::IN_TRANSIT,
        ]);
    }
}
