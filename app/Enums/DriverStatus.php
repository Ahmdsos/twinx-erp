<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Driver Status
 * حالة السائق
 */
enum DriverStatus: string
{
    case AVAILABLE = 'available';
    case ON_DELIVERY = 'on_delivery';
    case OFF_DUTY = 'off_duty';

    public function nameAr(): string
    {
        return match ($this) {
            self::AVAILABLE => 'متاح',
            self::ON_DELIVERY => 'في توصيل',
            self::OFF_DUTY => 'خارج الخدمة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'green',
            self::ON_DELIVERY => 'blue',
            self::OFF_DUTY => 'gray',
        };
    }
}
