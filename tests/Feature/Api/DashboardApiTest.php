<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
            'is_super_admin' => true,
        ]);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);
    }

    /**
     * Test sales KPIs endpoint.
     */
    public function test_sales_kpis_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/dashboard/sales-kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_sales',
                    'invoice_count',
                    'pending_invoices',
                ],
            ]);
    }

    /**
     * Test inventory KPIs endpoint.
     */
    public function test_inventory_kpis_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/dashboard/inventory-kpis');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_products',
                    'low_stock_count',
                ],
            ]);
    }

    /**
     * Test POS products endpoint.
     */
    public function test_pos_products_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        Product::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'is_sellable' => true,
        ]);

        $response = $this->getJson('/api/v1/pos/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test POS customer search.
     */
    public function test_pos_customer_search(): void
    {
        Sanctum::actingAs($this->user);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/pos/customers/search?search=Test');

        $response->assertStatus(200);
    }
}
