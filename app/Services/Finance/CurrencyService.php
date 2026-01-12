<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Currency;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Currency Service
 * خدمة العملات
 */
class CurrencyService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Get exchange rate between two currencies
     */
    public function getExchangeRate(string $from, string $to, ?string $date = null): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ?? now()->toDateString();

        // Try direct rate
        $rate = DB::table('exchange_rates')
            ->where('company_id', $this->tenantContext->companyId())
            ->where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->value('rate');

        if ($rate) {
            return (float) $rate;
        }

        // Try inverse rate
        $inverseRate = DB::table('exchange_rates')
            ->where('company_id', $this->tenantContext->companyId())
            ->where('from_currency', $to)
            ->where('to_currency', $from)
            ->where('effective_date', '<=', $date)
            ->orderBy('effective_date', 'desc')
            ->value('rate');

        if ($inverseRate) {
            return 1 / (float) $inverseRate;
        }

        // No rate found - return 1 (same value)
        return 1.0;
    }

    /**
     * Convert amount between currencies
     */
    public function convert(float $amount, string $from, string $to, ?string $date = null): float
    {
        $rate = $this->getExchangeRate($from, $to, $date);
        return round($amount * $rate, 2);
    }

    /**
     * Set exchange rate
     */
    public function setExchangeRate(string $from, string $to, float $rate, ?string $date = null): void
    {
        $date = $date ?? now()->toDateString();

        DB::table('exchange_rates')->updateOrInsert(
            [
                'company_id' => $this->tenantContext->companyId(),
                'from_currency' => $from,
                'to_currency' => $to,
                'effective_date' => $date,
            ],
            [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'rate' => $rate,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get base currency
     */
    public function getBaseCurrency(): ?Currency
    {
        return Currency::where('company_id', $this->tenantContext->companyId())
            ->where('is_base', true)
            ->first();
    }

    /**
     * Get active currencies
     */
    public function getActiveCurrencies(): \Illuminate\Database\Eloquent\Collection
    {
        return Currency::where('company_id', $this->tenantContext->companyId())
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
}
