<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Models\Account;
use App\Models\Branch;
use App\Models\Budget;
use App\Models\Company;
use App\Models\User;
use App\Services\Finance\BudgetService;
use App\Services\Finance\CurrencyService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvancedFinanceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private CurrencyService $currencyService;
    private BudgetService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->currencyService = app(CurrencyService::class);
        $this->budgetService = app(BudgetService::class);
    }

    /**
     * Test currency conversion with same currency.
     */
    public function test_same_currency_returns_same_amount(): void
    {
        $result = $this->currencyService->convert(100, 'SAR', 'SAR');
        $this->assertEquals(100, $result);
    }

    /**
     * Test exchange rate setting and retrieval.
     */
    public function test_set_and_get_exchange_rate(): void
    {
        $this->currencyService->setExchangeRate('USD', 'SAR', 3.75);

        $rate = $this->currencyService->getExchangeRate('USD', 'SAR');
        $this->assertEquals(3.75, $rate);
    }

    /**
     * Test currency conversion.
     */
    public function test_currency_conversion(): void
    {
        $this->currencyService->setExchangeRate('USD', 'SAR', 3.75);

        $result = $this->currencyService->convert(100, 'USD', 'SAR');
        $this->assertEquals(375.00, $result);
    }

    /**
     * Test inverse exchange rate.
     */
    public function test_inverse_exchange_rate(): void
    {
        $this->currencyService->setExchangeRate('USD', 'SAR', 3.75);

        $rate = $this->currencyService->getExchangeRate('SAR', 'USD');
        $this->assertEqualsWithDelta(0.2667, $rate, 0.001);
    }

    /**
     * Test budget creation.
     */
    public function test_create_budget(): void
    {
        $budget = $this->budgetService->create([
            'name' => 'Operating Budget 2026',
            'fiscal_year' => 2026,
            'period_type' => 'monthly',
        ]);

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'name' => 'Operating Budget 2026',
            'fiscal_year' => 2026,
            'status' => 'draft',
        ]);
    }

    /**
     * Test add budget line.
     */
    public function test_add_budget_line(): void
    {
        $budget = $this->budgetService->create([
            'name' => 'Test Budget',
            'fiscal_year' => 2026,
        ]);

        $account = Account::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $line = $this->budgetService->addLine($budget, [
            'account_id' => $account->id,
            'period' => 1,
            'budgeted_amount' => 10000,
        ]);

        $this->assertEquals(10000, (float) $line->budgeted_amount);
        $this->assertEquals(-10000, (float) $line->variance); // No actual yet
        
        // Total should be updated
        $budget->refresh();
        $this->assertEquals(10000, (float) $budget->total_amount);
    }

    /**
     * Test budget approval.
     */
    public function test_approve_budget(): void
    {
        $budget = $this->budgetService->create([
            'name' => 'Approval Test',
            'fiscal_year' => 2026,
        ]);

        $this->assertTrue($budget->isDraft());

        $budget = $this->budgetService->approve($budget);

        $this->assertTrue($budget->isApproved());
        $this->assertNotNull($budget->approved_at);
    }
}
