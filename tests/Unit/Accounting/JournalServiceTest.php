<?php

declare(strict_types=1);

namespace Tests\Unit\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\JournalType;
use App\Enums\PeriodStatus;
use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\InactiveAccountException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\Accounting\AccountBalanceService;
use App\Services\Accounting\JournalService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for JournalService
 * 
 * يتحقق من أن:
 * - UnbalancedJournalException يُطرح إذا Debits != Credits
 * - لا يمكن الترحيل في فترة مغلقة
 * - لا يمكن الترحيل لحسابات الأب (Group)
 */
class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $journalService;
    private TenantContext $tenantContext;
    private Company $company;
    private Branch $branch;
    private AccountingPeriod $period;
    private Account $cashAccount;
    private Account $revenueAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company and branch
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create period
        $this->period = AccountingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'status' => PeriodStatus::OPEN,
        ]);

        // Create accounts
        $this->cashAccount = Account::factory()->asset()->create([
            'company_id' => $this->company->id,
            'code' => '1101',
            'name' => 'Cash',
        ]);

        $this->revenueAccount = Account::factory()->revenue()->create([
            'company_id' => $this->company->id,
            'code' => '4101',
            'name' => 'Sales Revenue',
        ]);

        // Create user and authenticate
        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        // Set tenant context
        $this->tenantContext = app(TenantContext::class);
        $this->tenantContext->set($this->company, $this->branch);

        // Initialize service
        $this->journalService = app(JournalService::class);
    }

    /**
     * Test creating a balanced journal entry.
     */
    public function test_create_balanced_journal(): void
    {
        $journal = $this->journalService->create([
            'transaction_date' => now(),
            'type' => JournalType::GENERAL->value,
            'description' => 'Test journal',
            'lines' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                    'description' => 'Cash received',
                ],
                [
                    'account_id' => $this->revenueAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                    'description' => 'Revenue',
                ],
            ],
        ]);

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertEquals(JournalStatus::DRAFT, $journal->status);
        $this->assertEquals(1000, $journal->total_debit);
        $this->assertEquals(1000, $journal->total_credit);
        $this->assertTrue($journal->isBalanced());
    }

    /**
     * Test throwing UnbalancedJournalException when debits != credits.
     */
    public function test_throws_unbalanced_exception_when_debits_not_equal_credits(): void
    {
        $journal = $this->journalService->create([
            'transaction_date' => now(),
            'type' => JournalType::GENERAL->value,
            'lines' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->revenueAccount->id,
                    'debit' => 0,
                    'credit' => 500, // Unbalanced!
                ],
            ],
        ]);

        $this->assertFalse($journal->isBalanced());

        $this->expectException(UnbalancedJournalException::class);
        $this->journalService->post($journal);
    }

    /**
     * Test throwing ClosedPeriodException when posting to closed period.
     */
    public function test_throws_closed_period_exception(): void
    {
        // Close the period
        $this->period->update(['status' => PeriodStatus::CLOSED]);

        $journal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
            'status' => JournalStatus::DRAFT,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        // Add balanced lines
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

        $this->expectException(ClosedPeriodException::class);
        $this->journalService->post($journal);
    }

    /**
     * Test throwing InactiveAccountException when posting to group account.
     */
    public function test_throws_exception_when_posting_to_group_account(): void
    {
        // Create a group account (does not allow direct posting)
        $groupAccount = Account::factory()->group()->asset()->create([
            'company_id' => $this->company->id,
            'code' => '1000',
            'name' => 'Assets',
            'is_group' => true,
            'allow_direct_posting' => false,
        ]);

        $journal = Journal::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'period_id' => $this->period->id,
            'status' => JournalStatus::DRAFT,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        // Try to post to group account
        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $groupAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        JournalLine::factory()->create([
            'journal_id' => $journal->id,
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $this->expectException(InactiveAccountException::class);
        $this->journalService->post($journal);
    }

    /**
     * Test successful journal posting.
     */
    public function test_successful_journal_posting(): void
    {
        $journal = $this->journalService->create([
            'transaction_date' => now(),
            'type' => JournalType::GENERAL->value,
            'lines' => [
                [
                    'account_id' => $this->cashAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $this->revenueAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                ],
            ],
        ]);

        $this->journalService->post($journal);

        $journal->refresh();
        $this->assertEquals(JournalStatus::POSTED, $journal->status);
        $this->assertNotNull($journal->posted_at);
    }

    /**
     * Test journal reference generation.
     */
    public function test_reference_number_generation(): void
    {
        $ref1 = $this->journalService->generateReference(JournalType::GENERAL);
        $ref2 = $this->journalService->generateReference(JournalType::SALES);

        $this->assertStringStartsWith('JE-', $ref1);
        $this->assertStringStartsWith('SJ-', $ref2);
        $this->assertStringContainsString(date('Y'), $ref1);
    }
}
