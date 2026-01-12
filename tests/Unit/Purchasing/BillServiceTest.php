<?php

declare(strict_types=1);

namespace Tests\Unit\Purchasing;

use App\Enums\BillStatus;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseOrderStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Purchasing\BillService;
use App\Services\Purchasing\PurchaseOrderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for BillService
 */
class BillServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillService $billService;
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
        $this->supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'payment_terms' => 30,
        ]);
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

        $this->billService = app(BillService::class);
        $this->orderService = app(PurchaseOrderService::class);
    }

    /**
     * Test create bill from order.
     */
    public function test_create_bill_from_order(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->orderService->addLine($order, $this->product, $this->unit, 10, 50);
        $this->orderService->approve($order);

        // Simulate receiving goods
        $order->lines->first()->update(['received_qty' => 10]);

        $bill = $this->billService->createFromOrder($order);

        $this->assertDatabaseHas('bills', [
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $order->id,
        ]);

        $this->assertEquals(1, $bill->lines()->count());
        $this->assertEquals(BillStatus::DRAFT, $bill->status);
    }

    /**
     * Test post bill.
     */
    public function test_post_bill(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->orderService->addLine($order, $this->product, $this->unit, 5, 100);
        $this->orderService->approve($order);
        $order->lines->first()->update(['received_qty' => 5]);
        $bill = $this->billService->createFromOrder($order);

        $this->billService->post($bill);
        $bill->refresh();

        $this->assertEquals(BillStatus::POSTED, $bill->status);
        $this->assertNotNull($bill->posted_at);
    }

    /**
     * Test pay bill.
     */
    public function test_pay_bill(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->orderService->addLine($order, $this->product, $this->unit, 4, 100);
        $this->orderService->approve($order);
        $order->lines->first()->update(['received_qty' => 4]);
        $bill = $this->billService->createFromOrder($order);
        $this->billService->post($bill);

        $payment = $this->billService->pay(
            bill: $bill,
            amount: (float) $bill->total,
            method: PaymentMethod::BANK_TRANSFER,
            reference: 'TRN-12345'
        );

        $bill->refresh();

        $this->assertDatabaseHas('supplier_payments', [
            'bill_id' => $bill->id,
        ]);

        $this->assertEquals(BillStatus::PAID, $bill->status);
        $this->assertEquals(0, (float) $bill->balance_due);
    }

    /**
     * Test partial payment.
     */
    public function test_partial_payment(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->orderService->addLine($order, $this->product, $this->unit, 10, 100);
        $this->orderService->approve($order);
        $order->lines->first()->update(['received_qty' => 10]);
        $bill = $this->billService->createFromOrder($order);
        $this->billService->post($bill);

        $halfAmount = (float) $bill->total / 2;

        $this->billService->pay(
            bill: $bill,
            amount: $halfAmount,
            method: PaymentMethod::CHECK,
            reference: 'CHK-001'
        );

        $bill->refresh();

        $this->assertEquals(BillStatus::PARTIALLY_PAID, $bill->status);
        $this->assertEqualsWithDelta($halfAmount, (float) $bill->balance_due, 0.01);
    }

    /**
     * Test bill number generation.
     */
    public function test_bill_number_generation(): void
    {
        $num1 = $this->billService->generateBillNumber();

        $this->assertStringStartsWith('BILL-', $num1);
    }

    /**
     * Test cannot pay draft bill.
     */
    public function test_cannot_pay_draft_bill(): void
    {
        $order = $this->orderService->create($this->supplier, [
            'warehouse_id' => $this->warehouse->id,
        ]);
        $this->orderService->addLine($order, $this->product, $this->unit, 2, 100);
        $this->orderService->approve($order);
        $order->lines->first()->update(['received_qty' => 2]);
        $bill = $this->billService->createFromOrder($order);

        $this->expectException(\Exception::class);
        $this->billService->pay($bill, 100, PaymentMethod::CASH);
    }
}
