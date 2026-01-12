<?php

declare(strict_types=1);

namespace App\Enums;

enum CustomerType: string
{
    case RETAIL = 'retail';
    case SEMI_WHOLESALE = 'semi_wholesale';
    case QUARTER_WHOLESALE = 'quarter_wholesale';
    case WHOLESALE = 'wholesale';
    case DISTRIBUTOR = 'distributor';
    
    public function nameAr(): string
    {
        return match($this) {
            self::RETAIL => 'تجزئة',
            self::SEMI_WHOLESALE => 'نصف جملة',
            self::QUARTER_WHOLESALE => 'ربع جملة',
            self::WHOLESALE => 'جملة',
            self::DISTRIBUTOR => 'موزع',
        };
    }
    
    public function priceField(): string
    {
        return match($this) {
            self::RETAIL => 'selling_price',
            self::SEMI_WHOLESALE => 'semi_wholesale_price',
            self::QUARTER_WHOLESALE => 'quarter_wholesale_price',
            self::WHOLESALE => 'wholesale_price',
            self::DISTRIBUTOR => 'distributor_price',
        };
    }
    
    public function minQtyField(): string
    {
        return match($this) {
            self::RETAIL => 'min_retail_qty',
            self::SEMI_WHOLESALE => 'min_semi_wholesale_qty',
            self::QUARTER_WHOLESALE => 'min_quarter_wholesale_qty',
            self::WHOLESALE => 'min_wholesale_qty',
            self::DISTRIBUTOR => 'min_distributor_qty',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::RETAIL => 'blue',
            self::SEMI_WHOLESALE => 'green',
            self::QUARTER_WHOLESALE => 'yellow',
            self::WHOLESALE => 'purple',
            self::DISTRIBUTOR => 'red',
        };
    }
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
