<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\JournalLine;
use App\Services\TenantContext;
use Carbon\Carbon;

/**
 * Balance Sheet Report
 * الميزانية العمومية
 */
class BalanceSheetReport
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Generate balance sheet as of a date
     */
    public function generate(Carbon $asOf): array
    {
        $companyId = $this->tenantContext->companyId();

        // Assets
        $currentAssets = $this->getAccountTypeBalance(AccountType::ASSET, $asOf, true);
        $fixedAssets = $this->getAccountTypeBalance(AccountType::ASSET, $asOf, false);
        $totalAssets = $currentAssets['total'] + $fixedAssets['total'];

        // Liabilities
        $currentLiabilities = $this->getAccountTypeBalance(AccountType::LIABILITY, $asOf, true);
        $longTermLiabilities = $this->getAccountTypeBalance(AccountType::LIABILITY, $asOf, false);
        $totalLiabilities = $currentLiabilities['total'] + $longTermLiabilities['total'];

        // Equity
        $equity = $this->getAccountTypeBalance(AccountType::EQUITY, $asOf);

        // Retained Earnings (Net Income to date)
        $retainedEarnings = $this->calculateRetainedEarnings($asOf);

        $totalEquity = $equity['total'] + $retainedEarnings;
        $totalLiabilitiesEquity = $totalLiabilities + $totalEquity;

        return [
            'as_of' => $asOf->format('Y-m-d'),
            'assets' => [
                'current' => $currentAssets,
                'fixed' => $fixedAssets,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'current' => $currentLiabilities,
                'long_term' => $longTermLiabilities,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'capital' => $equity,
                'retained_earnings' => $retainedEarnings,
                'total' => $totalEquity,
            ],
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'is_balanced' => abs($totalAssets - $totalLiabilitiesEquity) < 0.01,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get balance for account type
     */
    private function getAccountTypeBalance(AccountType $type, Carbon $asOf, ?bool $isCurrent = null): array
    {
        $query = Account::where('company_id', $this->tenantContext->companyId())
            ->where('type', $type);

        // For assets/liabilities, filter by current/non-current based on code pattern
        // Current accounts typically start with 11xxxx, 21xxxx
        // Fixed/Long-term start with 12xxxx, 22xxxx
        if ($isCurrent !== null) {
            $prefix = $type === AccountType::ASSET ? '11' : '21';
            $nonCurrentPrefix = $type === AccountType::ASSET ? '12' : '22';
            
            if ($isCurrent) {
                $query->where('code', 'like', $prefix . '%');
            } else {
                $query->where('code', 'like', $nonCurrentPrefix . '%');
            }
        }

        $accounts = $query->orderBy('code')->get();
        $total = 0;
        $details = [];

        foreach ($accounts as $account) {
            $balance = $this->getAccountBalance($account, $asOf);
            if (abs($balance) > 0.01) {
                $details[] = [
                    'code' => $account->code,
                    'name' => $account->display_name,
                    'balance' => $balance,
                ];
                $total += $balance;
            }
        }

        return [
            'total' => $total,
            'details' => $details,
        ];
    }

    /**
     * Get account balance as of date
     */
    private function getAccountBalance(Account $account, Carbon $asOf): float
    {
        $debits = JournalLine::where('account_id', $account->id)
            ->whereHas('journal', fn($q) => $q
                ->where('transaction_date', '<=', $asOf)
                ->where('status', 'posted')
            )
            ->sum('debit');

        $credits = JournalLine::where('account_id', $account->id)
            ->whereHas('journal', fn($q) => $q
                ->where('transaction_date', '<=', $asOf)
                ->where('status', 'posted')
            )
            ->sum('credit');

        // Assets/Expenses: Debit - Credit
        // Liabilities/Equity/Revenue: Credit - Debit
        if (in_array($account->type, [AccountType::ASSET, AccountType::EXPENSE, AccountType::COGS])) {
            return (float) $debits - (float) $credits;
        }

        return (float) $credits - (float) $debits;
    }

    /**
     * Calculate retained earnings (cumulative net income)
     */
    private function calculateRetainedEarnings(Carbon $asOf): float
    {
        $incomeReport = app(IncomeStatementReport::class);
        
        // Get net income from beginning of company to asOf date
        $result = $incomeReport->generate(
            Carbon::create(2000, 1, 1), // Far past date
            $asOf
        );

        return $result['net_income'] ?? 0;
    }
}
