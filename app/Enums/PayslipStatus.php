<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Payslip Status
 * حالة كشف الراتب
 */
enum PayslipStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function nameAr(): string
    {
        return match ($this) {
            self::DRAFT => 'مسودة',
            self::APPROVED => 'معتمد',
            self::PAID => 'مدفوع',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::APPROVED => 'blue',
            self::PAID => 'green',
        };
    }
}
