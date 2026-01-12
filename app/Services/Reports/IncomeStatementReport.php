<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalLine;
use App\Services\TenantContext;
use Carbon\Carbon;

/**
 * Income Statement Report
 * قائمة الدخل (Profit & Loss)
 */
class IncomeStatementReport
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Generate income statement for date range
     */
    public function generate(Carbon $from, Carbon $to): array
    {
        $companyId = $this->tenantContext->companyId();

        // Get revenue accounts total
        $revenue = $this->getAccountTypeTotal(AccountType::REVENUE, $from, $to);

        // Get COGS accounts total
        $cogs = $this->getAccountTypeTotal(AccountType::COGS, $from, $to);

        // Gross Profit
        $grossProfit = $revenue - $cogs;

        // Get expense accounts total
        $expenses = $this->getAccountTypeTotal(AccountType::EXPENSE, $from, $to);

        // Net Income
        $netIncome = $grossProfit - $expenses;

        return [
            'period' => [
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
            ],
            'revenue' => [
                'total' => $revenue,
                'details' => $this->getAccountTypeDetails(AccountType::REVENUE, $from, $to),
            ],
            'cost_of_goods_sold' => [
                'total' => $cogs,
                'details' => $this->getAccountTypeDetails(AccountType::COGS, $from, $to),
            ],
            'gross_profit' => $grossProfit,
            'gross_margin' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0,
            'expenses' => [
                'total' => $expenses,
                'details' => $this->getAccountTypeDetails(AccountType::EXPENSE, $from, $to),
            ],
            'net_income' => $netIncome,
            'net_margin' => $revenue > 0 ? round(($netIncome / $revenue) * 100, 2) : 0,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get total for account type in period
     */
    private function getAccountTypeTotal(AccountType $type, Carbon $from, Carbon $to): float
    {
        $accountIds = Account::where('company_id', $this->tenantContext->companyId())
            ->where('type', $type)
            ->pluck('id');

        $credits = JournalLine::whereIn('account_id', $accountIds)
            ->whereHas('journal', fn($q) => $q
                ->whereBetween('transaction_date', [$from, $to])
                ->where('status', 'posted')
            )
            ->sum('credit');

        $debits = JournalLine::whereIn('account_id', $accountIds)
            ->whereHas('journal', fn($q) => $q
                ->whereBetween('transaction_date', [$from, $to])
                ->where('status', 'posted')
            )
            ->sum('debit');

        // Revenue/Income = Credits - Debits
        // Expenses/COGS = Debits - Credits
        if (in_array($type, [AccountType::REVENUE])) {
            return (float) $credits - (float) $debits;
        }

        return (float) $debits - (float) $credits;
    }

    /**
     * Get detailed breakdown by account
     */
    private function getAccountTypeDetails(AccountType $type, Carbon $from, Carbon $to): array
    {
        $accounts = Account::where('company_id', $this->tenantContext->companyId())
            ->where('type', $type)
            ->orderBy('code')
            ->get();

        $details = [];
        foreach ($accounts as $account) {
            $credits = JournalLine::where('account_id', $account->id)
                ->whereHas('journal', fn($q) => $q
                    ->whereBetween('transaction_date', [$from, $to])
                    ->where('status', 'posted')
                )
                ->sum('credit');

            $debits = JournalLine::where('account_id', $account->id)
                ->whereHas('journal', fn($q) => $q
                    ->whereBetween('transaction_date', [$from, $to])
                    ->where('status', 'posted')
                )
                ->sum('debit');

            $amount = in_array($type, [AccountType::REVENUE])
                ? (float) $credits - (float) $debits
                : (float) $debits - (float) $credits;

            if (abs($amount) > 0.01) {
                $details[] = [
                    'code' => $account->code,
                    'name' => $account->display_name,
                    'amount' => $amount,
                ];
            }
        }

        return $details;
    }
}
