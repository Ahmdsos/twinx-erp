<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\JournalType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Services\Accounting\JournalService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * InvoiceService
 */
class InvoiceService
{
    public function __construct(
        private TenantContext $tenantContext,
        private JournalService $journalService
    ) {}

    /**
     * Create invoice from sales order
     */
    public function createFromOrder(SalesOrder $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $invoice = Invoice::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'type' => InvoiceType::SALES,
                'customer_id' => $order->customer_id,
                'sales_order_id' => $order->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays($order->customer->payment_terms),
                'status' => InvoiceStatus::DRAFT,
                'subtotal' => $order->subtotal ?? 0,
                'discount_amount' => $order->discount_amount ?? 0,
                'tax_amount' => $order->tax_amount ?? 0,
                'total' => $order->total ?? 0,
                'balance_due' => $order->total ?? 0,
                'currency' => $order->currency,
                'exchange_rate' => $order->exchange_rate,
                'created_by' => auth()->id(),
            ]);

            // Copy lines from order
            $lineNumber = 1;
            foreach ($order->lines as $orderLine) {
                $remainingQty = $orderLine->remaining_qty;
                if ($remainingQty <= 0) continue;

                InvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $orderLine->product_id,
                    'unit_id' => $orderLine->unit_id,
                    'sales_order_line_id' => $orderLine->id,
                    'line_number' => $lineNumber++,
                    'description' => $orderLine->description,
                    'quantity' => $remainingQty,
                    'unit_price' => $orderLine->unit_price,
                    'discount_percent' => $orderLine->discount_percent,
                    'discount_amount' => $orderLine->discount_amount * ($remainingQty / (float) $orderLine->quantity),
                    'tax_rate' => $orderLine->tax_rate,
                    'tax_amount' => $orderLine->tax_amount * ($remainingQty / (float) $orderLine->quantity),
                    'line_total' => $orderLine->line_total * ($remainingQty / (float) $orderLine->quantity),
                ]);

                // Update invoiced quantity on order line
                $orderLine->update([
                    'invoiced_qty' => (float) $orderLine->invoiced_qty + $remainingQty,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });
    }

    /**
     * Create direct invoice (without order)
     */
    public function create(Customer $customer, array $data = []): Invoice
    {
        return Invoice::create([
            'company_id' => $this->tenantContext->companyId(),
            'branch_id' => $this->tenantContext->branchId(),
            'invoice_number' => $this->generateInvoiceNumber(),
            'type' => $data['type'] ?? InvoiceType::SALES,
            'customer_id' => $customer->id,
            'invoice_date' => $data['invoice_date'] ?? now(),
            'due_date' => $data['due_date'] ?? now()->addDays($customer->payment_terms),
            'status' => InvoiceStatus::DRAFT,
            'currency' => $data['currency'] ?? 'SAR',
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Issue invoice (post to accounting)
     */
    public function issue(Invoice $invoice): void
    {
        if (!$invoice->canEdit()) {
            throw new \Exception('Invoice already issued');
        }

        if ($invoice->lines()->count() === 0) {
            throw new \Exception('Invoice has no lines');
        }

        DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::ISSUED,
                'issued_by' => auth()->id(),
                'issued_at' => now(),
            ]);

            // Mark as overdue if past due date
            if ($invoice->due_date->isPast()) {
                $invoice->update(['status' => InvoiceStatus::OVERDUE]);
            }
        });
    }

    /**
     * Receive payment for invoice
     */
    public function receivePayment(
        Invoice $invoice,
        float $amount,
        PaymentMethod $method,
        ?string $reference = null
    ): Payment {
        if (!$invoice->canReceivePayment()) {
            throw new \Exception('Cannot receive payment for this invoice');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $reference) {
            $payment = Payment::create([
                'company_id' => $invoice->company_id,
                'branch_id' => $invoice->branch_id,
                'payment_number' => $this->generatePaymentNumber(),
                'type' => 'receipt',
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'payment_date' => now(),
                'payment_method' => $method,
                'amount' => $amount,
                'currency' => $invoice->currency,
                'exchange_rate' => $invoice->exchange_rate,
                'reference' => $reference,
                'created_by' => auth()->id(),
            ]);

            $invoice->applyPayment($amount);

            return $payment;
        });
    }

    /**
     * Generate unique invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        
        $lastNumber = Invoice::where('company_id', $this->tenantContext->companyId())
            ->where('invoice_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('invoice_number', 'desc')
            ->value('invoice_number');

        if ($lastNumber) {
            $num = (int) substr($lastNumber, -5);
            $newNum = $num + 1;
        } else {
            $newNum = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $newNum);
    }

    /**
     * Generate unique payment number
     */
    public function generatePaymentNumber(): string
    {
        $prefix = 'REC';
        $date = now()->format('Ymd');
        
        $lastNumber = Payment::where('company_id', $this->tenantContext->companyId())
            ->where('payment_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('payment_number', 'desc')
            ->value('payment_number');

        if ($lastNumber) {
            $num = (int) substr($lastNumber, -5);
            $newNum = $num + 1;
        } else {
            $newNum = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $newNum);
    }
}
