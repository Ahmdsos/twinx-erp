<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Unit;
use App\Models\User;
use App\Services\Sales\InvoiceService;
use App\Services\Sales\SalesOrderService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for InvoiceService
 */
class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;
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
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'payment_terms' => 30,
        ]);
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

        $this->invoiceService = app(InvoiceService::class);
        $this->orderService = app(SalesOrderService::class);
    }

    /**
     * Test create invoice from order.
     */
    public function test_create_invoice_from_order(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 5);
        $this->orderService->confirm($order);

        $invoice = $this->invoiceService->createFromOrder($order);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'sales_order_id' => $order->id,
        ]);

        $this->assertEquals(1, $invoice->lines()->count());
        $this->assertEquals(InvoiceStatus::DRAFT, $invoice->status);
    }

    /**
     * Test issue invoice.
     */
    public function test_issue_invoice(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 3);
        $this->orderService->confirm($order);
        $invoice = $this->invoiceService->createFromOrder($order);

        $this->invoiceService->issue($invoice);
        $invoice->refresh();

        $this->assertEquals(InvoiceStatus::ISSUED, $invoice->status);
        $this->assertNotNull($invoice->issued_at);
    }

    /**
     * Test receive payment.
     */
    public function test_receive_payment(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 2);
        $this->orderService->confirm($order);
        $invoice = $this->invoiceService->createFromOrder($order);
        $this->invoiceService->issue($invoice);

        $payment = $this->invoiceService->receivePayment(
            invoice: $invoice,
            amount: (float) $invoice->total,
            method: PaymentMethod::CASH
        );

        $invoice->refresh();

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total,
        ]);

        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals(0, (float) $invoice->balance_due);
    }

    /**
     * Test partial payment.
     */
    public function test_partial_payment(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 10);
        $this->orderService->confirm($order);
        $invoice = $this->invoiceService->createFromOrder($order);
        $this->invoiceService->issue($invoice);

        $halfAmount = (float) $invoice->total / 2;

        $this->invoiceService->receivePayment(
            invoice: $invoice,
            amount: $halfAmount,
            method: PaymentMethod::BANK_TRANSFER,
            reference: 'TRN-12345'
        );

        $invoice->refresh();

        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $invoice->status);
        $this->assertEquals($halfAmount, (float) $invoice->balance_due);
    }

    /**
     * Test invoice number generation.
     */
    public function test_invoice_number_generation(): void
    {
        $num1 = $this->invoiceService->generateInvoiceNumber();
        
        // Create an invoice to get the next number
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 1);
        $this->orderService->confirm($order);
        $this->invoiceService->createFromOrder($order);
        
        $num2 = $this->invoiceService->generateInvoiceNumber();

        $this->assertStringStartsWith('INV-', $num1);
        $this->assertStringStartsWith('INV-', $num2);
    }

    /**
     * Test cannot receive payment on draft invoice.
     */
    public function test_cannot_receive_payment_on_draft_invoice(): void
    {
        $order = $this->orderService->create($this->customer);
        $this->orderService->addLine($order, $this->product, $this->unit, 1);
        $this->orderService->confirm($order);
        $invoice = $this->invoiceService->createFromOrder($order);

        $this->expectException(\Exception::class);
        $this->invoiceService->receivePayment($invoice, 100, PaymentMethod::CASH);
    }
}
