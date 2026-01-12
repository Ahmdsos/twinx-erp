<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Services\TenantContext;

/**
 * Trial Balance Report
 * ميزان المراجعة
 */
class TrialBalanceReport
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Generate trial balance for a period
     */
    public function generate(AccountingPeriod $period): array
    {
        $accounts = Account::where('company_id', $this->tenantContext->companyId())
            ->with(['balances' => fn($q) => $q->where('period_id', $period->id)])
            ->whereHas('balances', fn($q) => $q->where('period_id', $period->id))
            ->orderBy('code')
            ->get();

        $totals = ['debit' => 0, 'credit' => 0];
        $lines = [];

        foreach ($accounts as $account) {
            $balance = $account->balances->first();
            if (!$balance) continue;

            $debit = (float) $balance->opening_debit + (float) $balance->current_debit;
            $credit = (float) $balance->opening_credit + (float) $balance->current_credit;

            // Net balance
            $netDebit = max(0, $debit - $credit);
            $netCredit = max(0, $credit - $debit);

            if ($netDebit == 0 && $netCredit == 0) continue;

            $lines[] = [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->display_name,
                'debit' => $netDebit,
                'credit' => $netCredit,
            ];

            $totals['debit'] += $netDebit;
            $totals['credit'] += $netCredit;
        }

        return [
            'period' => [
                'id' => $period->id,
                'name' => $period->name,
                'start_date' => $period->start_date->format('Y-m-d'),
                'end_date' => $period->end_date->format('Y-m-d'),
            ],
            'lines' => $lines,
            'totals' => $totals,
            'is_balanced' => abs($totals['debit'] - $totals['credit']) < 0.01,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generate trial balance as of a specific date
     */
    public function generateAsOf(\Carbon\Carbon $date): array
    {
        $period = AccountingPeriod::where('company_id', $this->tenantContext->companyId())
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if (!$period) {
            return [
                'error' => 'No period found for date',
                'lines' => [],
                'totals' => ['debit' => 0, 'credit' => 0],
            ];
        }

        return $this->generate($period);
    }
}
