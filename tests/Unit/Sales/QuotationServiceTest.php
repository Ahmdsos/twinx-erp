<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use App\Enums\QuotationStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use App\Services\Sales\QuotationService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Customer $customer;
    private QuotationService $quotationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->quotationService = app(QuotationService::class);
    }

    /**
     * Test create quotation.
     */
    public function test_create_quotation(): void
    {
        $quotation = $this->quotationService->create([
            'customer_id' => $this->customer->id,
            'subject' => 'عرض سعر للخدمات',
        ]);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'customer_id' => $this->customer->id,
            'status' => QuotationStatus::DRAFT->value,
        ]);

        $this->assertStringStartsWith('QT-', $quotation->quotation_number);
    }

    /**
     * Test add line to quotation.
     */
    public function test_add_line_to_quotation(): void
    {
        $quotation = $this->quotationService->create([
            'customer_id' => $this->customer->id,
        ]);

        $line = $this->quotationService->addLine($quotation, [
            'description' => 'خدمة استشارية',
            'quantity' => 10,
            'unit_price' => 500,
            'tax_rate' => 15,
        ]);

        // Line: 10 * 500 = 5000, Tax: 5000 * 15% = 750, Total: 5750
        $this->assertEquals(5000, (float) ($line->quantity * $line->unit_price));
        $this->assertEquals(750, (float) $line->tax_amount);
        $this->assertEquals(5750, (float) $line->line_total);

        // Quotation totals updated
        $quotation->refresh();
        $this->assertEquals(5000, (float) $quotation->subtotal);
        $this->assertEquals(5750, (float) $quotation->total);
    }

    /**
     * Test quotation status workflow.
     */
    public function test_quotation_status_workflow(): void
    {
        $quotation = $this->quotationService->create([
            'customer_id' => $this->customer->id,
        ]);

        $this->assertEquals(QuotationStatus::DRAFT, $quotation->status);

        // Send
        $quotation = $this->quotationService->send($quotation);
        $this->assertEquals(QuotationStatus::SENT, $quotation->status);

        // Accept
        $quotation = $this->quotationService->accept($quotation);
        $this->assertEquals(QuotationStatus::ACCEPTED, $quotation->status);
        $this->assertTrue($quotation->canConvert());
    }

    /**
     * Test quotation rejection.
     */
    public function test_reject_quotation(): void
    {
        $quotation = $this->quotationService->create([
            'customer_id' => $this->customer->id,
        ]);

        $quotation = $this->quotationService->send($quotation);
        $quotation = $this->quotationService->reject($quotation);

        $this->assertEquals(QuotationStatus::REJECTED, $quotation->status);
        $this->assertFalse($quotation->canConvert());
    }

    /**
     * Test quotation number generation.
     */
    public function test_quotation_number_generation(): void
    {
        $q1 = $this->quotationService->create(['customer_id' => $this->customer->id]);
        $q2 = $this->quotationService->create(['customer_id' => $this->customer->id]);

        $this->assertNotEquals($q1->quotation_number, $q2->quotation_number);
        $this->assertStringContainsString(now()->format('Y'), $q1->quotation_number);
    }
}
