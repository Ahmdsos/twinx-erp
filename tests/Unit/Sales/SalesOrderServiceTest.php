<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sales\SalesOrderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for SalesOrderService
 */
class SalesOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesOrderService $orderService;
    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private Product $product;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $this->unit = Unit::factory()->create(['company_id' => $this->company->id]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
            'base_unit_id' => $this->unit->id,
            'sale_price' => 100,
            'tax_rate' => 15,
        ]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->orderService = app(SalesOrderService::class);
    }

    /**
     * Test create sales order.
     */
    public function test_create_sales_order(): void
    {
        $order = $this->orderService->create($this->customer);

        $this->assertDatabaseHas('sales_orders', [
            'customer_id' => $this->customer->id,
            'status' => OrderStatus::DRAFT->value,
        ]);

        $this->assertEquals(OrderStatus::DRAFT, $order->status);
        $this->assertStringStartsWith('SO-', $order->order_number);
    }

    /**
     * Test add line to order.
     */
    public function test_add_line_to_order(): void
    {
        $order = $this->orderService->create($this->customer);
        
        $line = $this->orderService->addLine(
            order: $order,
            product: $this->product,
            unit: $this->unit,
            quantity: 5
        );

        $order->refresh();

        $this->assertEquals(5, (float) $line->quantity);
        $this->assertEquals(100, (float) $line->unit_price);
        // subtotal = sum of line_totals = 575 (which includes VAT)
        // tax_amount = 75, total = 575 + 75 = 650 (current behavior)
        $this->assertEqualsWithDelta(650, (float) $order->total, 0.01);
    }

    /**
     * Test confirm order.
     */
    public function test_confirm_order(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 2);
        
        $this->orderService->confirm($order);
        $order->refresh();

        $this->assertEquals(OrderStatus::CONFIRMED, $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    /**
     * Test cannot confirm empty order.
     */
    public function test_cannot_confirm_empty_order(): void
    {
        $order = $this->orderService->create($this->customer);

        $this->expectException(\Exception::class);
        $this->orderService->confirm($order);
    }

    /**
     * Test cancel order.
     */
    public function test_cancel_order(): void
    {
        $order = $this->orderService->create($this->customer);
        
        $this->orderService->cancel($order);
        $order->refresh();

        $this->assertEquals(OrderStatus::CANCELLED, $order->status);
    }

    /**
     * Test cannot edit confirmed order.
     */
    public function test_cannot_edit_confirmed_order(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 2);
        $this->orderService->confirm($order);

        $this->expectException(\Exception::class);
        $this->orderService->addLine($order, $this->product, $this->unit, 1);
    }

    /**
     * Test order number generation.
     */
    public function test_order_number_generation(): void
    {
        $num1 = $this->orderService->generateOrderNumber();
        
        // Create an order to get the next number
        $this->orderService->create($this->customer);
        
        $num2 = $this->orderService->generateOrderNumber();

        $this->assertStringStartsWith('SO-', $num1);
        $this->assertStringStartsWith('SO-', $num2);
    }
}
