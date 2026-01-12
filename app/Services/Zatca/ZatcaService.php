<?php

declare(strict_types=1);

namespace App\Services\Zatca;

use App\Enums\InvoiceCategory;
use App\Models\Invoice;
use Illuminate\Support\Str;

/**
 * ZATCA E-Invoicing Service
 * 
 * Main service for ZATCA compliance including:
 * - UUID generation
 * - Hash calculation
 * - QR code generation
 * - Invoice preparation
 */
class ZatcaService
{
    public function __construct(
        private ZatcaQrGenerator $qrGenerator,
        private ZatcaHasher $hasher
    ) {}

    /**
     * Prepare invoice for ZATCA compliance
     */
    public function prepareInvoice(Invoice $invoice, InvoiceCategory $category = null): Invoice
    {
        // Set category if not already set
        if ($category) {
            $invoice->invoice_category = $category;
        }

        // Generate UUID if not exists
        if (!$invoice->zatca_uuid) {
            $invoice->zatca_uuid = (string) Str::uuid();
        }

        // Calculate hash
        $invoice->zatca_hash = $this->hasher->hash($invoice);

        // Generate QR code
        $invoice->zatca_qr_code = $this->qrGenerator->generate($invoice);

        $invoice->save();

        return $invoice;
    }

    /**
     * Validate invoice for ZATCA compliance
     */
    public function validate(Invoice $invoice): array
    {
        $errors = [];

        // Check company VAT number
        if (!$invoice->company->tax_number) {
            $errors[] = 'Company VAT number is required';
        }

        // Check required fields
        if (!$invoice->invoice_number) {
            $errors[] = 'Invoice number is required';
        }

        if (!$invoice->invoice_date) {
            $errors[] = 'Invoice date is required';
        }

        // For standard invoices, customer VAT is required
        if ($invoice->invoice_category === InvoiceCategory::STANDARD) {
            if (!$invoice->customer?->vat_number) {
                $errors[] = 'Customer VAT number is required for standard invoices';
            }
        }

        // Check amounts
        if ((float) $invoice->total <= 0) {
            $errors[] = 'Invoice total must be greater than zero';
        }

        return $errors;
    }

    /**
     * Check if invoice is ready for clearance/reporting
     */
    public function isReady(Invoice $invoice): bool
    {
        return empty($this->validate($invoice))
            && $invoice->zatca_uuid
            && $invoice->zatca_hash
            && $invoice->zatca_qr_code;
    }

    /**
     * Mark invoice as cleared (for standard invoices)
     */
    public function markCleared(Invoice $invoice, array $response = []): void
    {
        $invoice->update([
            'zatca_cleared_at' => now(),
            'zatca_response' => $response,
        ]);
    }

    /**
     * Mark invoice as reported (for simplified invoices)
     */
    public function markReported(Invoice $invoice, array $response = []): void
    {
        $invoice->update([
            'zatca_reported_at' => now(),
            'zatca_response' => $response,
        ]);
    }

    /**
     * Get QR code as PNG image (for printing)
     */
    public function getQrCodeImage(Invoice $invoice, int $size = 200): string
    {
        // This would use a QR code library like simplesoftwareio/simple-qrcode
        // For now, return the raw data
        return $invoice->zatca_qr_code ?? '';
    }
}
