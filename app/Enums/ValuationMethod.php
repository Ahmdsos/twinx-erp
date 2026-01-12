<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Valuation Method Enum
 */
enum ValuationMethod: string
{
    case FIFO = 'fifo';                         // First In First Out
    case LIFO = 'lifo';                         // Last In First Out
    case WEIGHTED_AVERAGE = 'weighted_average'; // المتوسط المرجح

    public function nameAr(): string
    {
        return match($this) {
            self::FIFO => 'الوارد أولاً صادر أولاً',
            self::LIFO => 'الوارد أخيراً صادر أولاً',
            self::WEIGHTED_AVERAGE => 'المتوسط المرجح',
        };
    }
}
