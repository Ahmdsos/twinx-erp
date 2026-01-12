<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Journal Status Enum
 * 
 * Defines the lifecycle states of a journal entry.
 */
enum JournalStatus: string
{
    case DRAFT = 'draft';       // مسودة - قابل للتعديل
    case PENDING = 'pending';   // معلق - بانتظار الموافقة
    case POSTED = 'posted';     // مرحّل - مؤثر على الأرصدة
    case VOIDED = 'voided';     // ملغي - تم إلغاؤه بقيد عكسي

    /**
     * Get the Arabic name for the status.
     */
    public function nameAr(): string
    {
        return match($this) {
            self::DRAFT => 'مسودة',
            self::PENDING => 'معلق',
            self::POSTED => 'مرحّل',
            self::VOIDED => 'ملغي',
        };
    }

    /**
     * Check if the journal can be edited in this status.
     */
    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if the journal can be posted from this status.
     */
    public function canPost(): bool
    {
        return in_array($this, [self::DRAFT, self::PENDING]);
    }

    /**
     * Check if the journal can be voided from this status.
     */
    public function canVoid(): bool
    {
        return $this === self::POSTED;
    }

    /**
     * Check if the journal affects account balances.
     */
    public function affectsBalances(): bool
    {
        return $this === self::POSTED;
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::PENDING => 'yellow',
            self::POSTED => 'green',
            self::VOIDED => 'red',
        };
    }
}
