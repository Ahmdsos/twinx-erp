<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Services\TenantContext;

/**
 * Quotation Service
 * خدمة عروض الأسعار
 */
class QuotationService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create quotation
     */
    public function create(array $data): Quotation
    {
        return Quotation::create([
            'company_id' => $this->tenantContext->companyId(),
            'branch_id' => $this->tenantContext->branchId(),
            'customer_id' => $data['customer_id'],
            'quotation_number' => $this->generateQuotationNumber(),
            'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? now()->addDays(30)->toDateString(),
            'status' => QuotationStatus::DRAFT,
            'subject' => $data['subject'] ?? null,
            'notes' => $data['notes'] ?? null,
            'currency_code' => $data['currency_code'] ?? 'SAR',
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Add line to quotation
     */
    public function addLine(Quotation $quotation, array $data): QuotationLine
    {
        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];
        $discountPercent = (float) ($data['discount_percent'] ?? 0);
        $taxRate = (float) ($data['tax_rate'] ?? 15);

        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discountPercent / 100);
        $taxableAmount = $subtotal - $discountAmount;
        $taxAmount = $taxableAmount * ($taxRate / 100);
        $lineTotal = $taxableAmount + $taxAmount;

        $line = QuotationLine::create([
            'quotation_id' => $quotation->id,
            'product_id' => $data['product_id'] ?? null,
            'description' => $data['description'],
            'quantity' => $quantity,
            'unit' => $data['unit'] ?? null,
            'unit_price' => $unitPrice,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ]);

        $this->recalculateTotals($quotation);

        return $line;
    }

    /**
     * Send quotation to customer
     */
    public function send(Quotation $quotation): Quotation
    {
        $quotation->update(['status' => QuotationStatus::SENT]);
        return $quotation->fresh();
    }

    /**
     * Accept quotation
     */
    public function accept(Quotation $quotation): Quotation
    {
        $quotation->update(['status' => QuotationStatus::ACCEPTED]);
        return $quotation->fresh();
    }

    /**
     * Reject quotation
     */
    public function reject(Quotation $quotation): Quotation
    {
        $quotation->update(['status' => QuotationStatus::REJECTED]);
        return $quotation->fresh();
    }

    /**
     * Recalculate totals
     */
    private function recalculateTotals(Quotation $quotation): void
    {
        $lines = $quotation->lines;
        
        $subtotal = $lines->sum(fn ($l) => ($l->quantity * $l->unit_price) - $l->discount_amount);
        $taxAmount = $lines->sum('tax_amount');
        $total = $lines->sum('line_total');

        $quotation->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    /**
     * Generate quotation number
     */
    private function generateQuotationNumber(): string
    {
        $count = Quotation::where('company_id', $this->tenantContext->companyId())
            ->whereYear('created_at', now()->year)
            ->count();

        $year = now()->format('Y');
        return "QT-{$year}-" . str_pad((string) ($count + 1), 5, '0', STR_PAD_LEFT);
    }
}
