<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Bill Status Enum
 */
enum BillStatus: string
{
    case DRAFT = 'draft';
    case POSTED = 'posted';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function nameAr(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::POSTED => 'مرحّلة',
            self::PARTIALLY_PAID => 'مدفوعة جزئياً',
            self::PAID => 'مدفوعة',
            self::CANCELLED => 'ملغاة',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::POSTED => 'blue',
            self::PARTIALLY_PAID => 'yellow',
            self::PAID => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canPay(): bool
    {
        return in_array($this, [self::POSTED, self::PARTIALLY_PAID]);
    }

    public function affectsBalance(): bool
    {
        return in_array($this, [self::POSTED, self::PARTIALLY_PAID, self::PAID]);
    }
}
