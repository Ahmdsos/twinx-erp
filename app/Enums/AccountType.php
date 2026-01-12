<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Account Type Enum
 * 
 * Defines the fundamental types of accounts in the Chart of Accounts.
 * Based on GAAP/IFRS classification standards.
 */
enum AccountType: string
{
    case ASSET = 'asset';           // 1xxxxx - الأصول
    case LIABILITY = 'liability';   // 2xxxxx - الالتزامات
    case EQUITY = 'equity';         // 3xxxxx - حقوق الملكية
    case REVENUE = 'revenue';       // 4xxxxx - الإيرادات
    case COGS = 'cogs';             // 5xxxxx - تكلفة المبيعات
    case EXPENSE = 'expense';       // 6xxxxx - المصروفات

    /**
     * Get the Arabic name for the account type.
     */
    public function nameAr(): string
    {
        return match($this) {
            self::ASSET => 'الأصول',
            self::LIABILITY => 'الالتزامات',
            self::EQUITY => 'حقوق الملكية',
            self::REVENUE => 'الإيرادات',
            self::COGS => 'تكلفة المبيعات',
            self::EXPENSE => 'المصروفات',
        };
    }

    /**
     * Get the normal balance for this account type.
     */
    public function normalBalance(): string
    {
        return match($this) {
            self::ASSET, self::EXPENSE, self::COGS => 'debit',
            self::LIABILITY, self::EQUITY, self::REVENUE => 'credit',
        };
    }

    /**
     * Get the code prefix for this account type.
     */
    public function codePrefix(): string
    {
        return match($this) {
            self::ASSET => '1',
            self::LIABILITY => '2',
            self::EQUITY => '3',
            self::REVENUE => '4',
            self::COGS => '5',
            self::EXPENSE => '6',
        };
    }

    /**
     * Check if this type affects the Income Statement.
     */
    public function isIncomeStatement(): bool
    {
        return in_array($this, [self::REVENUE, self::COGS, self::EXPENSE]);
    }

    /**
     * Check if this type affects the Balance Sheet.
     */
    public function isBalanceSheet(): bool
    {
        return in_array($this, [self::ASSET, self::LIABILITY, self::EQUITY]);
    }

    /**
     * Get all account types as array for dropdowns.
     */
    public static function toArray(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'name' => $case->name,
            'name_ar' => $case->nameAr(),
            'normal_balance' => $case->normalBalance(),
        ], self::cases());
    }
}
