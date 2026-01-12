<?php

declare(strict_types=1);

namespace Tests\Unit\Zatca;

use App\Enums\InvoiceCategory;
use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\Zatca\ZatcaHasher;
use App\Services\Zatca\ZatcaQrGenerator;
use App\Services\Zatca\ZatcaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for ZATCA E-Invoicing
 */
class ZatcaServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Customer $customer;
    private Invoice $invoice;
    private ZatcaService $zatcaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'tax_number' => '300000000000003',
        ]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'vat_number' => '300000000000004',
        ]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $branch);

        $this->invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-2026-001',
            'status' => InvoiceStatus::ISSUED,
            'subtotal' => 1000,
            'tax_amount' => 150,
            'total' => 1150,
            'created_by' => $user->id,
        ]);

        $this->zatcaService = app(ZatcaService::class);
    }

    /**
     * Test preparing invoice for ZATCA.
     */
    public function test_prepare_invoice_generates_uuid_hash_qr(): void
    {
        $invoice = $this->zatcaService->prepareInvoice(
            $this->invoice,
            InvoiceCategory::SIMPLIFIED
        );

        $this->assertNotNull($invoice->zatca_uuid);
        $this->assertNotNull($invoice->zatca_hash);
        $this->assertNotNull($invoice->zatca_qr_code);
        $this->assertEquals(64, strlen($invoice->zatca_hash)); // SHA256 = 64 hex chars
    }

    /**
     * Test QR code TLV encoding.
     */
    public function test_qr_code_tlv_encoding(): void
    {
        $qrGenerator = app(ZatcaQrGenerator::class);
        
        $this->invoice->zatca_uuid = 'test-uuid';
        $this->invoice->zatca_hash = hash('sha256', 'test');
        
        $qrCode = $qrGenerator->generate($this->invoice);
        
        // Should be Base64 encoded
        $this->assertNotEmpty($qrCode);
        $this->assertNotFalse(base64_decode($qrCode, true));
        
        // Decode and verify structure
        $decoded = $qrGenerator->decode($qrCode);
        $this->assertArrayHasKey(1, $decoded); // Seller
        $this->assertArrayHasKey(2, $decoded); // VAT
        $this->assertArrayHasKey(3, $decoded); // Timestamp
        $this->assertArrayHasKey(4, $decoded); // Total
        $this->assertArrayHasKey(5, $decoded); // VAT Amount
    }

    /**
     * Test invoice hash verification.
     */
    public function test_invoice_hash_verification(): void
    {
        $hasher = app(ZatcaHasher::class);
        
        $this->invoice->zatca_uuid = 'test-uuid-12345';
        $hash1 = $hasher->hash($this->invoice);
        $hash2 = $hasher->hash($this->invoice);
        
        // Same invoice should produce same hash
        $this->assertEquals($hash1, $hash2);
        
        // Verify should return true
        $this->assertTrue($hasher->verify($this->invoice, $hash1));
    }

    /**
     * Test validation for standard invoice requires customer VAT.
     */
    public function test_standard_invoice_requires_customer_vat(): void
    {
        // Remove customer VAT
        $this->customer->update(['vat_number' => null]);
        $this->invoice->refresh();
        $this->invoice->invoice_category = InvoiceCategory::STANDARD;

        $errors = $this->zatcaService->validate($this->invoice);

        $this->assertContains('Customer VAT number is required for standard invoices', $errors);
    }

    /**
     * Test simplified invoice doesn't require customer VAT.
     */
    public function test_simplified_invoice_doesnt_require_customer_vat(): void
    {
        $this->customer->update(['vat_number' => null]);
        $this->invoice->refresh();
        $this->invoice->invoice_category = InvoiceCategory::SIMPLIFIED;

        $errors = $this->zatcaService->validate($this->invoice);

        $this->assertNotContains('Customer VAT number is required for standard invoices', $errors);
    }

    /**
     * Test isReady checks all requirements.
     */
    public function test_is_ready_checks_all_requirements(): void
    {
        // Before preparation, should not be ready
        $this->assertFalse($this->zatcaService->isReady($this->invoice));

        // After preparation
        $this->zatcaService->prepareInvoice($this->invoice, InvoiceCategory::SIMPLIFIED);
        $this->assertTrue($this->zatcaService->isReady($this->invoice));
    }
}
