<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Quotation Status
 * حالة عرض السعر
 */
enum QuotationStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case CONVERTED = 'converted';

    public function nameAr(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::SENT => 'مُرسل',
            self::ACCEPTED => 'مقبول',
            self::REJECTED => 'مرفوض',
            self::EXPIRED => 'منتهي الصلاحية',
            self::CONVERTED => 'تم التحويل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::ACCEPTED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'orange',
            self::CONVERTED => 'purple',
        };
    }

    public function canConvert(): bool
    {
        return $this === self::ACCEPTED;
    }
}
