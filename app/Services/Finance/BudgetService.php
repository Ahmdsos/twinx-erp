<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Budget Service
 * خدمة الميزانيات
 */
class BudgetService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create budget
     */
    public function create(array $data): Budget
    {
        return Budget::create([
            'company_id' => $this->tenantContext->companyId(),
            'name' => $data['name'],
            'fiscal_year' => $data['fiscal_year'],
            'period_type' => $data['period_type'] ?? 'monthly',
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add budget line
     */
    public function addLine(Budget $budget, array $data): BudgetLine
    {
        $line = BudgetLine::create([
            'budget_id' => $budget->id,
            'account_id' => $data['account_id'],
            'period' => $data['period'],
            'budgeted_amount' => $data['budgeted_amount'],
            'actual_amount' => 0,
            'variance' => -$data['budgeted_amount'],
        ]);

        $this->recalculateTotal($budget);

        return $line;
    }

    /**
     * Update actual amount
     */
    public function updateActual(BudgetLine $line, float $actual): BudgetLine
    {
        $line->update([
            'actual_amount' => $actual,
            'variance' => $actual - (float) $line->budgeted_amount,
        ]);

        return $line->fresh();
    }

    /**
     * Approve budget
     */
    public function approve(Budget $budget): Budget
    {
        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $budget->fresh();
    }

    /**
     * Get budget vs actual report
     */
    public function getBudgetVsActual(Budget $budget): array
    {
        $lines = $budget->lines()
            ->with('account')
            ->get()
            ->groupBy('account_id');

        $report = [];
        foreach ($lines as $accountId => $accountLines) {
            $account = $accountLines->first()->account;
            $report[] = [
                'account_id' => $accountId,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'budgeted' => $accountLines->sum('budgeted_amount'),
                'actual' => $accountLines->sum('actual_amount'),
                'variance' => $accountLines->sum('variance'),
            ];
        }

        return $report;
    }

    /**
     * Recalculate budget total
     */
    private function recalculateTotal(Budget $budget): void
    {
        $total = BudgetLine::where('budget_id', $budget->id)
            ->sum('budgeted_amount');

        $budget->update(['total_amount' => $total]);
    }
}
