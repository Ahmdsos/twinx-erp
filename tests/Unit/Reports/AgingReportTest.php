<?php

declare(strict_types=1);

namespace Tests\Unit\Reports;

use App\Enums\InvoiceStatus;
use App\Enums\BillStatus;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Reports\AgingReport;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Aging Report
 */
class AgingReportTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Supplier $supplier;
    private AgingReport $agingReport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->agingReport = app(AgingReport::class);
    }

    /**
     * Test receivables aging buckets.
     */
    public function test_receivables_aging_buckets(): void
    {
        $today = Carbon::parse('2026-01-12');

        // Current (not overdue)
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => InvoiceStatus::ISSUED,
            'due_date' => $today->copy()->addDays(10),
            'total' => 1000,
            'balance_due' => 1000,
            'created_by' => auth()->id(),
        ]);

        // 1-30 days overdue
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => InvoiceStatus::OVERDUE,
            'due_date' => $today->copy()->subDays(15),
            'total' => 2000,
            'balance_due' => 2000,
            'created_by' => auth()->id(),
        ]);

        // 31-60 days overdue
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => InvoiceStatus::OVERDUE,
            'due_date' => $today->copy()->subDays(45),
            'total' => 3000,
            'balance_due' => 3000,
            'created_by' => auth()->id(),
        ]);

        $report = $this->agingReport->receivables($today);

        $this->assertEquals(1000, $report['summary']['current']);
        $this->assertEquals(2000, $report['summary']['1-30']);
        $this->assertEquals(3000, $report['summary']['31-60']);
        $this->assertEquals(6000, $report['total']);
    }

    /**
     * Test payables aging buckets.
     */
    public function test_payables_aging_buckets(): void
    {
        $today = Carbon::parse('2026-01-12');

        // Current
        Bill::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'status' => BillStatus::POSTED,
            'due_date' => $today->copy()->addDays(5),
            'total' => 500,
            'balance_due' => 500,
            'created_by' => auth()->id(),
        ]);

        // 90+ days overdue
        Bill::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'status' => BillStatus::POSTED,
            'due_date' => $today->copy()->subDays(100),
            'total' => 1500,
            'balance_due' => 1500,
            'created_by' => auth()->id(),
        ]);

        $report = $this->agingReport->payables($today);

        $this->assertEquals(500, $report['summary']['current']);
        $this->assertEquals(1500, $report['summary']['90+']);
        $this->assertEquals(2000, $report['total']);
    }

    /**
     * Test aging report details include invoice info.
     */
    public function test_aging_report_includes_details(): void
    {
        $today = Carbon::now();

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-TEST-001',
            'status' => InvoiceStatus::ISSUED,
            'due_date' => $today->copy()->addDays(5),
            'total' => 750,
            'balance_due' => 750,
            'created_by' => auth()->id(),
        ]);

        $report = $this->agingReport->receivables($today);

        $this->assertCount(1, $report['details']);
        $this->assertEquals('INV-TEST-001', $report['details'][0]['invoice_number']);
        $this->assertEquals(750, $report['details'][0]['balance_due']);
    }
}
