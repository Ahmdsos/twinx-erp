<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Invoice Type Enum
 */
enum InvoiceType: string
{
    case SALES = 'sales';
    case RETURN = 'return';

    public function nameAr(): string
    {
        return match($this) {
            self::SALES => 'فاتورة مبيعات',
            self::RETURN => 'فاتورة مرتجع',
        };
    }

    public function affectsReceivable(): int
    {
        return match($this) {
            self::SALES => 1,
            self::RETURN => -1,
        };
    }
}
