<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Enums\MovementType;
use App\Enums\ValuationMethod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\ValuationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for ValuationService
 * 
 * يتحقق من أن:
 * - FIFO يحسب التكلفة بالترتيب الصحيح
 * - Weighted Average يحسب المتوسط بشكل صحيح
 */
class ValuationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ValuationService $valuationService;
    private Company $company;
    private Branch $branch;
    private Warehouse $warehouse;
    private Product $product;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->unit = Unit::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->fifo()->create([
            'company_id' => $this->company->id,
            'base_unit_id' => $this->unit->id,
        ]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->valuationService = app(ValuationService::class);
    }

    /**
     * Test FIFO cost calculation (oldest first).
     */
    public function test_fifo_cost_calculation(): void
    {
        // Create batches with different costs
        $movement1 = $this->createMovement(50, 10.00);
        $this->createBatch($movement1, 50, 10.00, now()->subDays(2));

        $movement2 = $this->createMovement(30, 15.00);
        $this->createBatch($movement2, 30, 15.00, now()->subDay());

        $movement3 = $this->createMovement(20, 20.00);
        $this->createBatch($movement3, 20, 20.00, now());

        // Calculate FIFO cost for 60 units
        // Should use: 50 @ 10 + 10 @ 15 = 500 + 150 = 650 / 60 = 10.83
        $cost = $this->valuationService->calculateFifoCost(
            $this->product,
            $this->warehouse,
            60
        );

        $this->assertEqualsWithDelta(10.83, $cost, 0.01);
    }

    /**
     * Test LIFO cost calculation (newest first).
     */
    public function test_lifo_cost_calculation(): void
    {
        // Create batches with different costs
        $movement1 = $this->createMovement(50, 10.00);
        $this->createBatch($movement1, 50, 10.00, now()->subDays(2));

        $movement2 = $this->createMovement(30, 15.00);
        $this->createBatch($movement2, 30, 15.00, now()->subDay());

        $movement3 = $this->createMovement(20, 20.00);
        $this->createBatch($movement3, 20, 20.00, now());

        // Calculate LIFO cost for 60 units
        // Should use: 20 @ 20 + 30 @ 15 + 10 @ 10 = 400 + 450 + 100 = 950 / 60 = 15.83
        $cost = $this->valuationService->calculateLifoCost(
            $this->product,
            $this->warehouse,
            60
        );

        $this->assertEqualsWithDelta(15.83, $cost, 0.01);
    }

    /**
     * Test weighted average cost from stock item.
     */
    public function test_weighted_average_cost(): void
    {
        StockItem::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'unit_id' => $this->unit->id,
            'quantity' => 100,
            'avg_cost' => 12.50,
        ]);

        $cost = $this->valuationService->calculateWeightedAverageCost(
            $this->product,
            $this->warehouse
        );

        $this->assertEquals(12.50, $cost);
    }

    /**
     * Test consume from batches FIFO order.
     */
    public function test_consume_from_batches_fifo(): void
    {
        $movement1 = $this->createMovement(50, 10.00);
        $batch1 = $this->createBatch($movement1, 50, 10.00, now()->subDays(2));

        $movement2 = $this->createMovement(30, 15.00);
        $batch2 = $this->createBatch($movement2, 30, 15.00, now()->subDay());

        // Consume 60 units
        $this->valuationService->consumeFromBatches($this->product, $this->warehouse, 60);

        $batch1->refresh();
        $batch2->refresh();

        // First batch should be depleted
        $this->assertEquals(0, (float) $batch1->remaining_qty);
        // Second batch should have 20 remaining (30 - 10)
        $this->assertEquals(20, (float) $batch2->remaining_qty);
    }

    /**
     * Test get inventory value.
     */
    public function test_get_inventory_value(): void
    {
        StockItem::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'unit_id' => $this->unit->id,
            'quantity' => 100,
            'avg_cost' => 15.00,
        ]);

        $value = $this->valuationService->getInventoryValue($this->product, $this->warehouse);

        $this->assertEquals(1500.00, $value);
    }

    /**
     * Helper to create stock movement.
     */
    private function createMovement(float $qty, float $cost): StockMovement
    {
        return StockMovement::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'reference' => 'TEST-' . uniqid(),
            'type' => MovementType::PURCHASE,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'unit_id' => $this->unit->id,
            'quantity' => $qty,
            'unit_cost' => $cost,
            'total_cost' => $qty * $cost,
            'movement_date' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Helper to create stock batch.
     */
    private function createBatch(StockMovement $movement, float $qty, float $cost, $date): StockBatch
    {
        return StockBatch::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'received_date' => $date,
            'initial_qty' => $qty,
            'remaining_qty' => $qty,
            'unit_cost' => $cost,
            'movement_id' => $movement->id,
        ]);
    }
}
