<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use App\Enums\MovementType;
use App\Enums\ValuationMethod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockService;
use App\Services\Inventory\ValuationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for StockService
 * 
 * يتحقق من أن:
 * - إضافة المخزون تعمل بشكل صحيح
 * - سحب المخزون يعمل بشكل صحيح
 * - تحويل المخزون بين المخازن يعمل
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
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
        $this->product = Product::factory()->create([
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

        $this->stockService = app(StockService::class);
    }

    /**
     * Test adding stock creates movement and updates quantity.
     */
    public function test_add_stock_creates_movement_and_updates_quantity(): void
    {
        $movement = $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 100,
            unitCost: 10.00,
            type: MovementType::PURCHASE
        );

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 100,
        ]);

        $stockItem = StockItem::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(100, (float) $stockItem->quantity);
        $this->assertEquals(10.00, (float) $stockItem->avg_cost);
    }

    /**
     * Test remove stock decreases quantity.
     */
    public function test_remove_stock_decreases_quantity(): void
    {
        // First add stock
        $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 100,
            unitCost: 10.00,
            type: MovementType::PURCHASE
        );

        // Then remove stock
        $movement = $this->stockService->removeStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 30,
            type: MovementType::SALE
        );

        $stockItem = StockItem::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(70, (float) $stockItem->quantity);
        $this->assertEquals(MovementType::SALE, $movement->type);
    }

    /**
     * Test transfer stock between warehouses.
     */
    public function test_transfer_stock_between_warehouses(): void
    {
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);

        // Add initial stock
        $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 100,
            unitCost: 15.00,
            type: MovementType::PURCHASE
        );

        // Transfer 40 units
        $result = $this->stockService->transfer(
            product: $this->product,
            fromWarehouse: $this->warehouse,
            toWarehouse: $warehouse2,
            unit: $this->unit,
            quantity: 40
        );

        // Check source warehouse
        $sourceItem = StockItem::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(60, (float) $sourceItem->quantity);

        // Check destination warehouse
        $destItem = StockItem::where('product_id', $this->product->id)
            ->where('warehouse_id', $warehouse2->id)
            ->first();
        $this->assertEquals(40, (float) $destItem->quantity);

        // Check movements are linked
        $this->assertEquals($result['inbound']->id, $result['outbound']->related_movement_id);
    }

    /**
     * Test weighted average cost calculation.
     */
    public function test_weighted_average_cost_calculation(): void
    {
        // Add 100 units at 10.00
        $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 100,
            unitCost: 10.00,
            type: MovementType::PURCHASE
        );

        // Add 50 units at 20.00
        $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 50,
            unitCost: 20.00,
            type: MovementType::PURCHASE
        );

        // Expected: (100 * 10 + 50 * 20) / 150 = 2000 / 150 = 13.33
        $stockItem = StockItem::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(150, (float) $stockItem->quantity);
        $this->assertEqualsWithDelta(13.33, (float) $stockItem->avg_cost, 0.01);
    }

    /**
     * Test get stock level for product.
     */
    public function test_get_stock_level(): void
    {
        $this->stockService->addStock(
            product: $this->product,
            warehouse: $this->warehouse,
            unit: $this->unit,
            quantity: 50,
            unitCost: 10.00,
            type: MovementType::OPENING
        );

        $level = $this->stockService->getStockLevel($this->product, $this->warehouse);
        $this->assertEquals(50, $level);

        $totalLevel = $this->stockService->getStockLevel($this->product);
        $this->assertEquals(50, $totalLevel);
    }

    /**
     * Test reference number generation.
     */
    public function test_reference_number_generation(): void
    {
        $ref1 = $this->stockService->generateReference(MovementType::PURCHASE);
        $ref2 = $this->stockService->generateReference(MovementType::SALE);

        $this->assertStringStartsWith('PO-', $ref1);
        $this->assertStringStartsWith('SO-', $ref2);
        $this->assertStringContainsString(date('Ymd'), $ref1);
    }
}
