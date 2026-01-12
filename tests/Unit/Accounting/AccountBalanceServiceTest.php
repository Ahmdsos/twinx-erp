<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Accounting\AccountBalanceService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for AccountBalanceService
 * 
 * يتحقق من أن:
 * - الأرصدة تُحدّث بشكل صحيح بعد الترحيل
 * - الرصيد الافتتاحي يُنقل من الفترة السابقة
 */
class AccountBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountBalanceService $balanceService;
    private Company $company;
    private Branch $branch;
    private AccountingPeriod $period;
    private Account $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->period = AccountingPeriod::factory()->create(['company_id' => $this->company->id]);
        $this->cashAccount = Account::factory()->asset()->create([
            'company_id' => $this->company->id,
            'code' => '1101',
            'name' => 'Cash',
        ]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->balanceService = app(AccountBalanceService::class);
    }

    /**
     * Test balance is created when it doesn't exist.
     */
    public function test_get_or_create_balance_creates_new(): void
    {
        $balance = $this->balanceService->getOrCreateBalance(
            $this->cashAccount->id,
            $this->period->id,
            $this->branch->id
        );

        $this->assertInstanceOf(AccountBalance::class, $balance);
        $this->assertEquals($this->cashAccount->id, $balance->account_id);
        $this->assertEquals($this->period->id, $balance->period_id);
        $this->assertEquals(0, $balance->period_debit);
        $this->assertEquals(0, $balance->period_credit);
    }

    /**
     * Test balance updates correctly from journal line.
     */
    public function test_update_from_journal_line(): void
    {
        $journal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
        ]);

        $line = JournalLine::factory()->debit(1000)->create([
            'journal_id' => $journal->id,
            'account_id' => $this->cashAccount->id,
        ]);

        $this->balanceService->updateFromJournalLine($line, $journal);

        $balance = AccountBalance::where('account_id', $this->cashAccount->id)
            ->where('period_id', $this->period->id)
            ->first();

        $this->assertEquals(1000, $balance->period_debit);
        $this->assertEquals(0, $balance->period_credit);
        $this->assertEquals(1000, $balance->closing_debit);
    }

    /**
     * Test multiple updates accumulate correctly.
     */
    public function test_multiple_updates_accumulate(): void
    {
        $journal1 = Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
        ]);

        $journal2 = Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
        ]);

        $line1 = JournalLine::factory()->debit(500)->create([
            'journal_id' => $journal1->id,
            'account_id' => $this->cashAccount->id,
        ]);

        $line2 = JournalLine::factory()->debit(300)->create([
            'journal_id' => $journal2->id,
            'account_id' => $this->cashAccount->id,
        ]);

        $this->balanceService->updateFromJournalLine($line1, $journal1);
        $this->balanceService->updateFromJournalLine($line2, $journal2);

        $balance = AccountBalance::where('account_id', $this->cashAccount->id)
            ->where('period_id', $this->period->id)
            ->first();

        $this->assertEquals(800, $balance->period_debit); // 500 + 300
        $this->assertEquals(800, $balance->closing_debit);
    }

    /**
     * Test get balance at date.
     */
    public function test_get_balance_at_date(): void
    {
        // Create posted journal with lines
        $journal = Journal::factory()->posted()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
            'transaction_date' => now()->subDays(5),
        ]);

        JournalLine::factory()->debit(1500)->create([
            'journal_id' => $journal->id,
            'account_id' => $this->cashAccount->id,
        ]);

        $balance = $this->balanceService->getBalanceAtDate(
            $this->cashAccount,
            now()
        );

        $this->assertArrayHasKey('debit', $balance);
        $this->assertArrayHasKey('credit', $balance);
        $this->assertArrayHasKey('balance', $balance);
        $this->assertEquals(1500, $balance['debit']);
    }
}
