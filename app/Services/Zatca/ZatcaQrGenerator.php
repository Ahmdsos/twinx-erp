<?php

declare(strict_types=1);

namespace App\Services\Zatca;

use App\Models\Invoice;

/**
 * ZATCA QR Code Generator
 * 
 * Generates TLV (Tag-Length-Value) encoded QR codes
 * as per ZATCA Phase 2 specifications.
 */
class ZatcaQrGenerator
{
    /**
     * Generate QR code data for invoice
     * 
     * @return string Base64 encoded TLV data
     */
    public function generate(Invoice $invoice): string
    {
        $company = $invoice->company;
        
        $tlvData = $this->buildTlvData([
            1 => $company->name,                                    // Seller name
            2 => $company->tax_number ?? '',                        // VAT number
            3 => $invoice->invoice_date->toIso8601String(),         // Timestamp
            4 => number_format((float) $invoice->total, 2, '.', ''),        // Total with VAT
            5 => number_format((float) $invoice->tax_amount, 2, '.', ''),   // VAT amount
        ]);

        // Add optional Phase 2 fields if available
        if ($invoice->zatca_hash) {
            $tlvData .= $this->encodeTlv(6, hex2bin($invoice->zatca_hash));  // Hash
        }

        return base64_encode($tlvData);
    }

    /**
     * Build TLV data from array
     */
    private function buildTlvData(array $data): string
    {
        $tlv = '';
        foreach ($data as $tag => $value) {
            $tlv .= $this->encodeTlv($tag, $value);
        }
        return $tlv;
    }

    /**
     * Encode single TLV field
     * 
     * TLV Format: [Tag (1 byte)][Length (1 byte)][Value (N bytes)]
     */
    private function encodeTlv(int $tag, string $value): string
    {
        return chr($tag) . chr(strlen($value)) . $value;
    }

    /**
     * Decode TLV data for verification
     */
    public function decode(string $base64Data): array
    {
        $data = base64_decode($base64Data);
        $result = [];
        $position = 0;

        while ($position < strlen($data)) {
            $tag = ord($data[$position]);
            $length = ord($data[$position + 1]);
            $value = substr($data, $position + 2, $length);
            
            $result[$tag] = $value;
            $position += 2 + $length;
        }

        return $result;
    }

    /**
     * Get human-readable field names
     */
    public function getFieldNames(): array
    {
        return [
            1 => 'Seller Name',
            2 => 'VAT Number',
            3 => 'Timestamp',
            4 => 'Total with VAT',
            5 => 'VAT Amount',
            6 => 'Invoice Hash',
            7 => 'ECDSA Signature',
            8 => 'Public Key',
            9 => 'Cryptographic Stamp',
        ];
    }
}
