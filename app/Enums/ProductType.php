<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Product Type Enum
 */
enum ProductType: string
{
    case PRODUCT = 'product';   // منتج ملموس
    case SERVICE = 'service';   // خدمة
    case BUNDLE = 'bundle';     // حزمة منتجات

    public function nameAr(): string
    {
        return match($this) {
            self::PRODUCT => 'منتج',
            self::SERVICE => 'خدمة',
            self::BUNDLE => 'حزمة',
        };
    }

    public function isTrackable(): bool
    {
        return $this === self::PRODUCT;
    }
}
