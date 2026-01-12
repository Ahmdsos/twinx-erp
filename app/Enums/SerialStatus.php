<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Serial Number Status
 * حالة الرقم التسلسلي
 */
enum SerialStatus: string
{
    case AVAILABLE = 'available';
    case RESERVED = 'reserved';
    case SOLD = 'sold';
    case RETURNED = 'returned';
    case DAMAGED = 'damaged';

    public function nameAr(): string
    {
        return match ($this) {
            self::AVAILABLE => 'متاح',
            self::RESERVED => 'محجوز',
            self::SOLD => 'مباع',
            self::RETURNED => 'مرتجع',
            self::DAMAGED => 'تالف',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'green',
            self::RESERVED => 'yellow',
            self::SOLD => 'blue',
            self::RETURNED => 'orange',
            self::DAMAGED => 'red',
        };
    }
}
