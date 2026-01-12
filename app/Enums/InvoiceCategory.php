<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Invoice Category for ZATCA
 * تصنيف الفاتورة لهيئة الزكاة
 */
enum InvoiceCategory: string
{
    case STANDARD = 'standard';     // فاتورة ضريبية (B2B/B2G)
    case SIMPLIFIED = 'simplified'; // فاتورة مبسطة (B2C)

    public function nameAr(): string
    {
        return match ($this) {
            self::STANDARD => 'فاتورة ضريبية',
            self::SIMPLIFIED => 'فاتورة ضريبية مبسطة',
        };
    }

    public function nameEn(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard Tax Invoice',
            self::SIMPLIFIED => 'Simplified Tax Invoice',
        };
    }

    /**
     * Standard invoices need real-time clearance
     * Simplified invoices need reporting within 24h
     */
    public function requiresClearance(): bool
    {
        return $this === self::STANDARD;
    }
}
