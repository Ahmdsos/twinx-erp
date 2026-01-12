<?php

declare(strict_types=1);

namespace App\Services\Zatca;

use App\Models\Invoice;

/**
 * ZATCA Invoice Hasher
 * 
 * Generates SHA256 hash for invoice data integrity.
 */
class ZatcaHasher
{
    /**
     * Generate hash for invoice
     */
    public function hash(Invoice $invoice): string
    {
        $data = $this->getHashableData($invoice);
        return hash('sha256', $data);
    }

    /**
     * Get data to be hashed
     */
    private function getHashableData(Invoice $invoice): string
    {
        // Concatenate critical invoice fields
        return implode('|', [
            $invoice->zatca_uuid,
            $invoice->invoice_number,
            $invoice->invoice_date->format('Y-m-d'),
            $invoice->company->tax_number ?? '',
            $invoice->customer->vat_number ?? '',
            number_format((float) $invoice->subtotal, 2, '.', ''),
            number_format((float) $invoice->tax_amount, 2, '.', ''),
            number_format((float) $invoice->total, 2, '.', ''),
        ]);
    }

    /**
     * Verify hash matches invoice
     */
    public function verify(Invoice $invoice, string $hash): bool
    {
        return hash_equals($this->hash($invoice), $hash);
    }
}
