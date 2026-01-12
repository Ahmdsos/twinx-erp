<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Employment Type
 * نوع التوظيف
 */
enum EmploymentType: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';

    public function nameAr(): string
    {
        return match ($this) {
            self::FULL_TIME => 'دوام كامل',
            self::PART_TIME => 'دوام جزئي',
            self::CONTRACT => 'عقد مؤقت',
        };
    }
}
