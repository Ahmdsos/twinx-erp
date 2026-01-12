<?php

declare(strict_types=1);

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountingPeriod;
use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

/**
 * AccountBalanceService
 * 
 * Manages account balance calculations and updates.
 */
class AccountBalanceService
{
    /**
     * Update balance from a journal line.
     */
    public function updateFromJournalLine(JournalLine $line, Journal $journal): void
    {
        $balance = $this->getOrCreateBalance(
            $line->account_id,
            $journal->period_id,
            $journal->branch_id
        );

        $balance->addMovement(
            (float) $line->debit,
            (float) $line->credit
        );
    }

    /**
     * Get or create a balance record.
     */
    public function getOrCreateBalance(
        string $accountId,
        string $periodId,
        ?string $branchId = null
    ): AccountBalance {
        return AccountBalance::firstOrCreate(
            [
                'account_id' => $accountId,
                'period_id' => $periodId,
                'branch_id' => $branchId,
            ],
            [
                'opening_debit' => 0,
                'opening_credit' => 0,
                'period_debit' => 0,
                'period_credit' => 0,
                'closing_debit' => 0,
                'closing_credit' => 0,
                'ytd_debit' => 0,
                'ytd_credit' => 0,
            ]
        );
    }

    /**
     * Get account balance at a specific date.
     */
    public function getBalanceAtDate(
        Account $account,
        \Carbon\Carbon $date,
        ?string $branchId = null
    ): array {
        // Sum all posted journal lines up to the date
        $query = JournalLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($date, $branchId) {
                $q->where('status', 'posted')
                    ->where('transaction_date', '<=', $date);

                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        $totals = $query->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        $debit = (float) ($totals->total_debit ?? 0);
        $credit = (float) ($totals->total_credit ?? 0);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $account->calculateNetBalance($debit, $credit),
        ];
    }

    /**
     * Close period and carry forward balances.
     */
    public function closePeriod(AccountingPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            // Get next period
            $nextPeriod = AccountingPeriod::where('company_id', $period->company_id)
                ->where('start_date', '>', $period->end_date)
                ->orderBy('start_date')
                ->first();

            if (!$nextPeriod) {
                return;
            }

            // Carry forward all balances
            $balances = AccountBalance::where('period_id', $period->id)->get();

            foreach ($balances as $balance) {
                $nextBalance = $this->getOrCreateBalance(
                    $balance->account_id,
                    $nextPeriod->id,
                    $balance->branch_id
                );

                $nextBalance->update([
                    'opening_debit' => $balance->closing_debit,
                    'opening_credit' => $balance->closing_credit,
                ]);

                $nextBalance->recalculateClosing();
            }
        });
    }

    /**
     * Rebuild all balances for a period (data repair).
     */
    public function rebuildPeriodBalances(AccountingPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            // Delete existing balances
            AccountBalance::where('period_id', $period->id)->delete();

            // Rebuild from journal lines
            $lines = JournalLine::whereHas('journal', function ($q) use ($period) {
                $q->where('period_id', $period->id)
                    ->where('status', 'posted');
            })->get();

            foreach ($lines as $line) {
                $journal = $line->journal;
                $balance = $this->getOrCreateBalance(
                    $line->account_id,
                    $period->id,
                    $journal->branch_id
                );

                $balance->addMovement(
                    (float) $line->debit,
                    (float) $line->credit
                );
            }
        });
    }
}
