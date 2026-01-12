<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Journal Type Enum
 * 
 * Defines the types of journal entries in the system.
 */
enum JournalType: string
{
    case GENERAL = 'general';           // قيد عام
    case SALES = 'sales';               // قيد مبيعات
    case PURCHASE = 'purchase';         // قيد مشتريات
    case RECEIPT = 'receipt';           // قيد قبض
    case PAYMENT = 'payment';           // قيد صرف
    case TRANSFER = 'transfer';         // قيد تحويل
    case ADJUSTMENT = 'adjustment';     // قيد تسوية
    case OPENING = 'opening';           // قيد افتتاحي
    case CLOSING = 'closing';           // قيد إقفال
    case REVERSAL = 'reversal';         // قيد عكسي

    /**
     * Get the Arabic name for the journal type.
     */
    public function nameAr(): string
    {
        return match($this) {
            self::GENERAL => 'قيد عام',
            self::SALES => 'قيد مبيعات',
            self::PURCHASE => 'قيد مشتريات',
            self::RECEIPT => 'قيد قبض',
            self::PAYMENT => 'قيد صرف',
            self::TRANSFER => 'قيد تحويل',
            self::ADJUSTMENT => 'قيد تسوية',
            self::OPENING => 'قيد افتتاحي',
            self::CLOSING => 'قيد إقفال',
            self::REVERSAL => 'قيد عكسي',
        };
    }

    /**
     * Get the reference prefix for this journal type.
     */
    public function referencePrefix(): string
    {
        return match($this) {
            self::GENERAL => 'JE',
            self::SALES => 'SJ',
            self::PURCHASE => 'PJ',
            self::RECEIPT => 'RV',
            self::PAYMENT => 'PV',
            self::TRANSFER => 'TR',
            self::ADJUSTMENT => 'AJ',
            self::OPENING => 'OB',
            self::CLOSING => 'CL',
            self::REVERSAL => 'RJ',
        };
    }
}
