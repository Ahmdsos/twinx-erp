<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Invoice Status Enum
 */
enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function nameAr(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::ISSUED => 'صادرة',
            self::PARTIALLY_PAID => 'مدفوعة جزئياً',
            self::PAID => 'مدفوعة',
            self::OVERDUE => 'متأخرة',
            self::CANCELLED => 'ملغاة',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::ISSUED => 'blue',
            self::PARTIALLY_PAID => 'yellow',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canReceivePayment(): bool
    {
        return in_array($this, [self::ISSUED, self::PARTIALLY_PAID, self::OVERDUE]);
    }

    public function affectsBalance(): bool
    {
        return in_array($this, [self::ISSUED, self::PARTIALLY_PAID, self::PAID, self::OVERDUE]);
    }
}
