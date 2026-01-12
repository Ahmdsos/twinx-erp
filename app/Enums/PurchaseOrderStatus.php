<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Purchase Order Status Enum
 */
enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PARTIAL = 'partial';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';

    public function nameAr(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::CONFIRMED => 'مؤكد',
            self::PARTIAL => 'مستلم جزئياً',
            self::RECEIVED => 'مستلم بالكامل',
            self::CANCELLED => 'ملغي',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::CONFIRMED => 'blue',
            self::PARTIAL => 'yellow',
            self::RECEIVED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canReceive(): bool
    {
        return in_array($this, [self::CONFIRMED, self::PARTIAL]);
    }
}
