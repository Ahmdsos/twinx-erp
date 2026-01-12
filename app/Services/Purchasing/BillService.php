<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Enums\BillStatus;
use App\Enums\PaymentMethod;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * BillService
 */
class BillService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create bill from purchase order
     */
    public function createFromOrder(PurchaseOrder $order): Bill
    {
        return DB::transaction(function () use ($order) {
            $bill = Bill::create([
                'company_id' => $order->company_id,
                'branch_id' => $order->branch_id,
                'bill_number' => $this->generateBillNumber(),
                'supplier_id' => $order->supplier_id,
                'purchase_order_id' => $order->id,
                'bill_date' => now(),
                'due_date' => now()->addDays($order->supplier->payment_terms),
                'status' => BillStatus::DRAFT,
                'subtotal' => $order->subtotal ?? 0,
                'discount_amount' => $order->discount_amount ?? 0,
                'tax_amount' => $order->tax_amount ?? 0,
                'total' => $order->total ?? 0,
                'balance_due' => $order->total ?? 0,
                'currency' => $order->currency,
                'exchange_rate' => $order->exchange_rate ?? 1,
                'created_by' => auth()->id(),
            ]);

            // Copy lines from order
            $lineNumber = 1;
            foreach ($order->lines as $orderLine) {
                $remainingQty = (float) $orderLine->received_qty - (float) $orderLine->billed_qty;
                if ($remainingQty <= 0) continue;

                BillLine::create([
                    'bill_id' => $bill->id,
                    'product_id' => $orderLine->product_id,
                    'unit_id' => $orderLine->unit_id,
                    'purchase_order_line_id' => $orderLine->id,
                    'line_number' => $lineNumber++,
                    'description' => $orderLine->description ?? $orderLine->product->display_name,
                    'quantity' => $remainingQty,
                    'unit_cost' => $orderLine->unit_cost,
                    'discount_percent' => $orderLine->discount_percent ?? 0,
                    'discount_amount' => ($orderLine->discount_amount ?? 0) * ($remainingQty / max(1, (float) $orderLine->quantity)),
                    'tax_rate' => $orderLine->tax_rate,
                    'tax_amount' => ($orderLine->tax_amount ?? 0) * ($remainingQty / max(1, (float) $orderLine->quantity)),
                    'line_total' => ($orderLine->line_total ?? 0) * ($remainingQty / max(1, (float) $orderLine->quantity)),
                ]);

                // Update billed quantity on order line
                $orderLine->update([
                    'billed_qty' => (float) $orderLine->billed_qty + $remainingQty,
                ]);
            }

            $bill->recalculateTotals();

            return $bill;
        });
    }

    /**
     * Create direct bill (without order)
     */
    public function create(Supplier $supplier, array $data = []): Bill
    {
        return Bill::create([
            'company_id' => $this->tenantContext->companyId(),
            'branch_id' => $this->tenantContext->branchId(),
            'bill_number' => $this->generateBillNumber(),
            'supplier_id' => $supplier->id,
            'supplier_ref' => $data['supplier_ref'] ?? null,
            'bill_date' => $data['bill_date'] ?? now(),
            'due_date' => $data['due_date'] ?? now()->addDays($supplier->payment_terms),
            'status' => BillStatus::DRAFT,
            'currency' => $data['currency'] ?? 'SAR',
            'exchange_rate' => $data['exchange_rate'] ?? 1,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Post bill (finalize)
     */
    public function post(Bill $bill): void
    {
        if (!$bill->canEdit()) {
            throw new \Exception('Bill already posted');
        }

        if ($bill->lines()->count() === 0) {
            throw new \Exception('Bill has no lines');
        }

        DB::transaction(function () use ($bill) {
            $bill->update([
                'status' => BillStatus::POSTED,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);
        });
    }

    /**
     * Pay bill
     */
    public function pay(
        Bill $bill,
        float $amount,
        PaymentMethod $method,
        ?string $reference = null
    ): SupplierPayment {
        if (!$bill->canPay()) {
            throw new \Exception('Cannot pay this bill');
        }

        return DB::transaction(function () use ($bill, $amount, $method, $reference) {
            $payment = SupplierPayment::create([
                'company_id' => $bill->company_id,
                'branch_id' => $bill->branch_id,
                'payment_number' => $this->generatePaymentNumber(),
                'supplier_id' => $bill->supplier_id,
                'bill_id' => $bill->id,
                'payment_date' => now(),
                'payment_method' => $method,
                'amount' => $amount,
                'currency' => $bill->currency,
                'exchange_rate' => $bill->exchange_rate ?? 1,
                'reference' => $reference,
                'created_by' => auth()->id(),
            ]);

            $bill->applyPayment($amount);

            return $payment;
        });
    }

    /**
     * Generate unique bill number
     */
    public function generateBillNumber(): string
    {
        $prefix = 'BILL';
        $date = now()->format('Ymd');
        
        $lastNumber = Bill::where('company_id', $this->tenantContext->companyId())
            ->where('bill_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('bill_number', 'desc')
            ->value('bill_number');

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
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        
        $lastNumber = SupplierPayment::where('company_id', $this->tenantContext->companyId())
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
