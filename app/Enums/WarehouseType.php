<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Warehouse Type Enum
 */
enum WarehouseType: string
{
    case MAIN = 'main';         // مخزن رئيسي
    case TRANSIT = 'transit';   // مخزن عبور
    case DAMAGED = 'damaged';   // مخزن تالف
    case VIRTUAL = 'virtual';   // مخزن افتراضي

    public function nameAr(): string
    {
        return match($this) {
            self::MAIN => 'مخزن رئيسي',
            self::TRANSIT => 'مخزن عبور',
            self::DAMAGED => 'مخزن تالف',
            self::VIRTUAL => 'مخزن افتراضي',
        };
    }
}
