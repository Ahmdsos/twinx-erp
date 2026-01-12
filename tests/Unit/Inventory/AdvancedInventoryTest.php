<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\ReorderRule;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\AdvancedInventoryService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Warehouse $warehouse;
    private Product $product;
    private AdvancedInventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->product = Product::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->inventoryService = app(AdvancedInventoryService::class);
    }

    /**
     * Test create batch.
     */
    public function test_create_batch(): void
    {
        $batch = $this->inventoryService->createBatch([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'BATCH-2026-001',
            'quantity' => 100,
            'expiry_date' => now()->addMonths(6)->toDateString(),
        ]);

        $this->assertDatabaseHas('batches', [
            'id' => $batch->id,
            'batch_number' => 'BATCH-2026-001',
            'initial_quantity' => 100,
        ]);
    }

    /**
     * Test batch expiry detection.
     */
    public function test_batch_expiry_detection(): void
    {
        // Not expired
        $batch1 = Batch::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'NOT-EXPIRED',
            'expiry_date' => now()->addDays(60),
            'initial_quantity' => 50,
            'current_quantity' => 50,
        ]);

        // Expiring soon
        $batch2 = Batch::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'EXPIRING-SOON',
            'expiry_date' => now()->addDays(15),
            'initial_quantity' => 50,
            'current_quantity' => 50,
        ]);

        $this->assertFalse($batch1->isNearExpiry(30));
        $this->assertTrue($batch2->isNearExpiry(30));
    }

    /**
     * Test get expiring batches.
     */
    public function test_get_expiring_batches(): void
    {
        // Create batch expiring in 20 days
        Batch::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'EXPIRING',
            'expiry_date' => now()->addDays(20),
            'initial_quantity' => 50,
            'current_quantity' => 50,
        ]);

        // Create batch not expiring soon
        Batch::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'batch_number' => 'NOT-EXPIRING',
            'expiry_date' => now()->addDays(90),
            'initial_quantity' => 50,
            'current_quantity' => 50,
        ]);

        $expiring = $this->inventoryService->getExpiringBatches(30);

        $this->assertCount(1, $expiring);
        $this->assertEquals('EXPIRING', $expiring->first()->batch_number);
    }

    /**
     * Test reorder rule creation.
     */
    public function test_create_reorder_rule(): void
    {
        $rule = $this->inventoryService->createReorderRule([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
            'max_quantity' => 100,
        ]);

        $this->assertDatabaseHas('reorder_rules', [
            'id' => $rule->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
        ]);
    }

    /**
     * Test reorder rule logic.
     */
    public function test_reorder_rule_needs_reorder(): void
    {
        $rule = ReorderRule::create([
            'company_id' => $this->company->id,
            'product_id' => $this->product->id,
            'min_quantity' => 10,
            'reorder_quantity' => 50,
            'max_quantity' => 100,
        ]);

        $this->assertTrue($rule->needsReorder(5));
        $this->assertTrue($rule->needsReorder(10));
        $this->assertFalse($rule->needsReorder(15));
    }
}
