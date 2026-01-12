<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboard;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ExportService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private DashboardService $dashboardService;
    private ExportService $exportService;

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

        $this->dashboardService = app(DashboardService::class);
        $this->exportService = app(ExportService::class);
    }

    /**
     * Test get sales KPIs.
     */
    public function test_get_sales_kpis(): void
    {
        // Create customer and invoices
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 1000,
            'status' => 'issued',
        ]);

        $kpis = $this->dashboardService->getSalesKPIs('month');

        $this->assertArrayHasKey('total_sales', $kpis);
        $this->assertArrayHasKey('invoice_count', $kpis);
        $this->assertArrayHasKey('pending_invoices', $kpis);
        $this->assertEquals(3, $kpis['invoice_count']);
        $this->assertEquals(3000, $kpis['total_sales']);
    }

    /**
     * Test get inventory KPIs.
     */
    public function test_get_inventory_kpis(): void
    {
        Product::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $kpis = $this->dashboardService->getInventoryKPIs();

        $this->assertArrayHasKey('total_products', $kpis);
        $this->assertEquals(5, $kpis['total_products']);
    }

    /**
     * Test get finance KPIs.
     */
    public function test_get_finance_kpis(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 5000,
            'status' => 'paid',
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'invoice_date' => now(),
            'total' => 3000,
            'status' => 'issued',
        ]);

        $kpis = $this->dashboardService->getFinanceKPIs('month');

        $this->assertEquals(5000, $kpis['revenue']);
        $this->assertEquals(3000, $kpis['receivables']);
    }

    /**
     * Test CSV export.
     */
    public function test_csv_export(): void
    {
        $headers = ['الاسم', 'القيمة'];
        $data = [
            ['منتج 1', 100],
            ['منتج 2', 200],
        ];

        $response = $this->exportService->exportCsv($headers, $data, 'test_export');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    /**
     * Test JSON export.
     */
    public function test_json_export(): void
    {
        $data = ['name' => 'Test', 'value' => 100];

        $response = $this->exportService->exportJson($data, 'test_export');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}
