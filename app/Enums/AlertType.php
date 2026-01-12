<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Alert Type
 * نوع التنبيه
 */
enum AlertType: string
{
    case LOW_STOCK = 'low_stock';
    case EXPIRING_BATCH = 'expiring_batch';
    case OVERDUE_INVOICE = 'overdue_invoice';
    case NEW_ORDER = 'new_order';
    case PAYMENT_RECEIVED = 'payment_received';
    case DELIVERY_STATUS = 'delivery_status';

    public function nameAr(): string
    {
        return match ($this) {
            self::LOW_STOCK => 'مخزون منخفض',
            self::EXPIRING_BATCH => 'دفعة قاربت الانتهاء',
            self::OVERDUE_INVOICE => 'فاتورة متأخرة',
            self::NEW_ORDER => 'طلب جديد',
            self::PAYMENT_RECEIVED => 'دفعة مستلمة',
            self::DELIVERY_STATUS => 'حالة التوصيل',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOW_STOCK => '📦',
            self::EXPIRING_BATCH => '⏰',
            self::OVERDUE_INVOICE => '⚠️',
            self::NEW_ORDER => '🛒',
            self::PAYMENT_RECEIVED => '💰',
            self::DELIVERY_STATUS => '🚚',
        };
    }
}
