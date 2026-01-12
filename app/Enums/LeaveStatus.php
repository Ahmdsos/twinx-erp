<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Leave Request Status
 * حالة طلب الإجازة
 */
enum LeaveStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function nameAr(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::APPROVED => 'معتمد',
            self::REJECTED => 'مرفوض',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
