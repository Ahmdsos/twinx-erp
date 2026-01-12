<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Delivery Status
 * حالة التوصيل
 */
enum DeliveryStatus: string
{
    case PENDING = 'pending';
    case ASSIGNED = 'assigned';
    case PICKED_UP = 'picked_up';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function nameAr(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::ASSIGNED => 'تم التعيين',
            self::PICKED_UP => 'تم الاستلام',
            self::IN_TRANSIT => 'في الطريق',
            self::DELIVERED => 'تم التوصيل',
            self::FAILED => 'فشل التوصيل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ASSIGNED => 'yellow',
            self::PICKED_UP => 'blue',
            self::IN_TRANSIT => 'purple',
            self::DELIVERED => 'green',
            self::FAILED => 'red',
        };
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::DELIVERED, self::FAILED]);
    }
}
