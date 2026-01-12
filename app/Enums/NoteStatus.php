<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Credit/Debit Note Status
 * حالة الإشعار
 */
enum NoteStatus: string
{
    case DRAFT = 'draft';
    case ISSUED = 'issued';
    case APPLIED = 'applied';

    public function nameAr(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::ISSUED => 'صادر',
            self::APPLIED => 'مُطبق',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ISSUED => 'blue',
            self::APPLIED => 'green',
        };
    }
}
