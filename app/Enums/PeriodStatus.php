<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Period Status Enum
 * 
 * Defines the states of an accounting period.
 */
enum PeriodStatus: string
{
    case OPEN = 'open';       // مفتوحة - يمكن الترحيل
    case CLOSED = 'closed';   // مغلقة - يمكن إعادة الفتح
    case LOCKED = 'locked';   // مقفلة - لا يمكن التعديل

    /**
     * Get the Arabic name for the status.
     */
    public function nameAr(): string
    {
        return match($this) {
            self::OPEN => 'مفتوحة',
            self::CLOSED => 'مغلقة',
            self::LOCKED => 'مقفلة',
        };
    }

    /**
     * Check if posting is allowed in this period status.
     */
    public function allowsPosting(): bool
    {
        return $this === self::OPEN;
    }

    /**
     * Check if the period can be reopened.
     */
    public function canReopen(): bool
    {
        return $this === self::CLOSED;
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match($this) {
            self::OPEN => 'green',
            self::CLOSED => 'yellow',
            self::LOCKED => 'red',
        };
    }
}
