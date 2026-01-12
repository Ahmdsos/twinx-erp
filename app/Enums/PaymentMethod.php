<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payment Method Enum
 */
enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case CHECK = 'check';
    case CREDIT = 'credit';

    public function nameAr(): string
    {
        return match($this) {
            self::CASH => 'نقدي',
            self::BANK_TRANSFER => 'تحويل بنكي',
            self::CARD => 'بطاقة',
            self::CHECK => 'شيك',
            self::CREDIT => 'آجل',
        };
    }

    public function requiresReference(): bool
    {
        return in_array($this, [self::BANK_TRANSFER, self::CHECK]);
    }
}
