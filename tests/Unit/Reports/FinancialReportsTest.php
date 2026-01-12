<?php

declare(strict_types=1);

namespace Tests\Unit\Reports;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\JournalType;
use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\AccountingPeriod;
use App\Models\User;
use App\Services\Reports\TrialBalanceReport;
use App\Services\Reports\IncomeStatementReport;
use App\Services\Reports\BalanceSheetReport;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Financial Reports
 */
class FinancialReportsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private AccountingPeriod $period;
    private Account $cashAccount;
    private Account $revenueAccount;
    private Account $expenseAccount;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $this->period = AccountingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Q1 2026',
            'fiscal_year' => 2026,
            'period_number' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-03-31',
            'status' => 'open',
        ]);

        // Create accounts using factory
        $this->cashAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '110001',
            'name' => 'Cash',
            'type' => AccountType::ASSET,
            'normal_balance' => 'debit',
        ]);

        $this->revenueAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '410001',
            'name' => 'Sales Revenue',
            'type' => AccountType::REVENUE,
            'normal_balance' => 'credit',
        ]);

        $this->expenseAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'code' => '610001',
            'name' => 'Rent Expense',
            'type' => AccountType::EXPENSE,
            'normal_balance' => 'debit',
        ]);

        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($this->user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);
    }

    private function createJournal(string $reference, ?string $date = null): Journal
    {
        return Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
            'reference' => $reference,
            'type' => JournalType::GENERAL,
            'transaction_date' => $date ?? now(),
            'status' => JournalStatus::POSTED,
            'created_by' => $this->user->id,
        ]);
    }

    /**
     * Test trial balance is balanced.
     */
    public function test_trial_balance_is_balanced(): void
    {
        $journal = $this->createJournal('JV-001');

        // Debit Cash 1000, Credit Revenue 1000
        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        // Create account balances
        AccountBalance::create([
            'account_id' => $this->cashAccount->id,
            'period_id' => $this->period->id,
            'opening_debit' => 0,
            'opening_credit' => 0,
            'current_debit' => 1000,
            'current_credit' => 0,
        ]);

        AccountBalance::create([
            'account_id' => $this->revenueAccount->id,
            'period_id' => $this->period->id,
            'opening_debit' => 0,
            'opening_credit' => 0,
            'current_debit' => 0,
            'current_credit' => 1000,
        ]);

        $report = app(TrialBalanceReport::class)->generate($this->period);

        $this->assertTrue($report['is_balanced']);
        $this->assertEquals($report['totals']['debit'], $report['totals']['credit']);
    }

    /**
     * Test income statement calculates net income.
     */
    public function test_income_statement_calculates_net_income(): void
    {
        // Revenue entry
        $journal1 = $this->createJournal('JV-002', '2026-01-15');

        JournalLine::factory()->create([
            'journal_id' => $journal1->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 5000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal1->id,
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        // Expense entry
        $journal2 = $this->createJournal('JV-003', '2026-01-20');

        JournalLine::factory()->create([
            'journal_id' => $journal2->id,
            'account_id' => $this->expenseAccount->id,
            'debit' => 2000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal2->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 0,
            'credit' => 2000,
        ]);

        $report = app(IncomeStatementReport::class)->generate(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31')
        );

        $this->assertEquals(5000, $report['revenue']['total']);
        $this->assertEquals(2000, $report['expenses']['total']);
        $this->assertEquals(3000, $report['net_income']);
    }

    /**
     * Test balance sheet equation (A = L + E).
     */
    public function test_balance_sheet_equation(): void
    {
        $journal = $this->createJournal('JV-004', '2026-01-10');

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $this->cashAccount->id,
            'debit' => 10000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 10000,
        ]);

        $report = app(BalanceSheetReport::class)->generate(Carbon::parse('2026-01-31'));

        // Assets should equal Liabilities + Equity
        $this->assertTrue($report['is_balanced']);
    }
}
