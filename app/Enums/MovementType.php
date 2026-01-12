<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Movement Type Enum
 */
enum MovementType: string
{
    case PURCHASE = 'purchase';         // شراء
    case SALE = 'sale';                 // بيع
    case TRANSFER_IN = 'transfer_in';   // تحويل وارد
    case TRANSFER_OUT = 'transfer_out'; // تحويل صادر
    case ADJUSTMENT = 'adjustment';     // تسوية
    case RETURN_IN = 'return_in';       // مرتجع وارد
    case RETURN_OUT = 'return_out';     // مرتجع صادر
    case OPENING = 'opening';           // رصيد افتتاحي

    public function nameAr(): string
    {
        return match($this) {
            self::PURCHASE => 'شراء',
            self::SALE => 'بيع',
            self::TRANSFER_IN => 'تحويل وارد',
            self::TRANSFER_OUT => 'تحويل صادر',
            self::ADJUSTMENT => 'تسوية',
            self::RETURN_IN => 'مرتجع وارد',
            self::RETURN_OUT => 'مرتجع صادر',
            self::OPENING => 'رصيد افتتاحي',
        };
    }

    public function referencePrefix(): string
    {
        return match($this) {
            self::PURCHASE => 'PO',
            self::SALE => 'SO',
            self::TRANSFER_IN => 'TI',
            self::TRANSFER_OUT => 'TO',
            self::ADJUSTMENT => 'ADJ',
            self::RETURN_IN => 'RI',
            self::RETURN_OUT => 'RO',
            self::OPENING => 'OB',
        };
    }

    public function affectsQuantity(): int
    {
        return match($this) {
            self::PURCHASE, self::TRANSFER_IN, self::RETURN_IN, self::OPENING => 1,
            self::SALE, self::TRANSFER_OUT, self::RETURN_OUT => -1,
            self::ADJUSTMENT => 0, // Can be positive or negative
        };
    }
}
