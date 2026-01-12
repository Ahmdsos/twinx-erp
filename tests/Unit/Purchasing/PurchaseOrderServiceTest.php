<?php

declare(strict_types=1);

namespace Tests\Unit\Purchasing;

use App\Enums\PurchaseOrderStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for PurchaseOrderService
 */
class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchaseOrderService $orderService;
    private Company $company;
    private Branch $branch;
    private Supplier $supplier;
    private Product $product;
    private Unit $unit;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id]);
        $this->unit = Unit::factory()->create(['company_id' => $this->company->id]);
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'base_unit_id' => $this->unit->id,
            'cost_price' => 50,
            'tax_rate' => 15,
        ]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->orderService = app(PurchaseOrderService::class);
    }

    /**
     * Test create purchase order.
     */
    public function test_create_purchase_order(): void
    {
        $order = $this->orderService->create($this->supplier);

        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $this->supplier->id,
            'status' => PurchaseOrderStatus::DRAFT->value,
        ]);

        $this->assertEquals(PurchaseOrderStatus::DRAFT, $order->status);
        $this->assertStringStartsWith('PO-', $order->order_number);
    }

    /**
     * Test add line to order.
     */
    public function test_add_line_to_order(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        
        $line = $this->orderService->addLine(
            order: $order,
            product: $this->product,
            unit: $this->unit,
            quantity: 10,
            unitCost: 50.00
        );

        $order->refresh();

        $this->assertEquals(10, (float) $line->quantity);
        $this->assertEquals(50, (float) $line->unit_cost);
        $this->assertGreaterThan(0, (float) $order->total);
    }

    /**
     * Test approve order.
     */
    public function test_approve_order(): void
    {
        $order = $this->orderService->create($this->supplier);
        $this->orderService->addLine($order, $this->product, $this->unit, 5, 100);
        
        $this->orderService->approve($order);
        $order->refresh();

        $this->assertEquals(PurchaseOrderStatus::CONFIRMED, $order->status);
        $this->assertNotNull($order->approved_at);
    }

    /**
     * Test cannot approve empty order.
     */
    public function test_cannot_approve_empty_order(): void
    {
        $order = $this->orderService->create($this->supplier);

        $this->expectException(\Exception::class);
        $this->orderService->approve($order);
    }

    /**
     * Test cancel order.
     */
    public function test_cancel_order(): void
    {
        $order = $this->orderService->create($this->supplier);
        
        $this->orderService->cancel($order);
        $order->refresh();

        $this->assertEquals(PurchaseOrderStatus::CANCELLED, $order->status);
    }

    /**
     * Test cannot edit approved order.
     */
    public function test_cannot_edit_approved_order(): void
    {
        $order = $this->orderService->create($this->supplier);
        $this->orderService->addLine($order, $this->product, $this->unit, 5, 100);
        $this->orderService->approve($order);

        $this->expectException(\Exception::class);
        $this->orderService->addLine($order, $this->product, $this->unit, 2, 100);
    }

    /**
     * Test order number generation.
     */
    public function test_order_number_generation(): void
    {
        $num1 = $this->orderService->generateOrderNumber();
        $this->orderService->create($this->supplier);
        $num2 = $this->orderService->generateOrderNumber();

        $this->assertStringStartsWith('PO-', $num1);
        $this->assertStringStartsWith('PO-', $num2);
    }
}
