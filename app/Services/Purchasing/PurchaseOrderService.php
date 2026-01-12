<?php

declare(strict_types=1);

namespace App\Services\Purchasing;

use App\Enums\MovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\Inventory\StockService;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * PurchaseOrderService
 */
class PurchaseOrderService
{
    public function __construct(
        private TenantContext $tenantContext,
        private StockService $stockService
    ) {}

    /**
     * Create a new purchase order
     */
    public function create(Supplier $supplier, array $data = []): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $data) {
            $order = PurchaseOrder::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'order_number' => $this->generateOrderNumber(),
                'supplier_id' => $supplier->id,
                'order_date' => $data['order_date'] ?? now(),
                'expected_date' => $data['expected_date'] ?? null,
                'status' => PurchaseOrderStatus::DRAFT,
                'currency' => $data['currency'] ?? 'SAR',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            return $order;
        });
    }

    /**
     * Add line to order
     */
    public function addLine(
        PurchaseOrder $order,
        Product $product,
        Unit $unit,
        float $quantity,
        float $unitCost
    ): PurchaseOrderLine {
        if (!$order->canEdit()) {
            throw new \Exception('Cannot edit approved order');
        }

        $lineNumber = $order->lines()->max('line_number') + 1;

        $subtotal = $quantity * $unitCost;
        $taxAmount = $subtotal * ((float) $product->tax_rate / 100);

        $line = PurchaseOrderLine::create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'line_number' => $lineNumber,
            'description' => $product->display_name,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate' => $product->tax_rate,
            'tax_amount' => $taxAmount,
            'line_total' => $subtotal + $taxAmount,
        ]);

        $order->recalculateTotals();

        return $line;
    }

    /**
     * Approve order
     */
    public function approve(PurchaseOrder $order): void
    {
        if (!$order->canApprove()) {
            throw new \Exception('Order cannot be approved');
        }

        $order->update([
            'status' => PurchaseOrderStatus::CONFIRMED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Receive goods from purchase order
     */
    public function receive(PurchaseOrder $order, array $quantities): void
    {
        if (!$order->canReceive()) {
            throw new \Exception('Order cannot receive goods');
        }

        DB::transaction(function () use ($order, $quantities) {
            foreach ($quantities as $lineId => $qty) {
                $line = $order->lines()->find($lineId);
                if (!$line || $qty <= 0) continue;

                $receivable = $line->remaining_qty;
                $qtyToReceive = min($qty, $receivable);

                // Update received quantity
                $line->update([
                    'received_qty' => (float) $line->received_qty + $qtyToReceive,
                ]);

                // Add to stock
                $this->stockService->addStock(
                    product: $line->product,
                    warehouse: $order->warehouse,
                    unit: $line->unit,
                    quantity: $qtyToReceive,
                    unitCost: (float) $line->unit_cost,
                    type: MovementType::PURCHASE,
                    reference: $order->order_number,
                    sourceDocument: $order
                );
            }

            // Update order status
            if ($order->isFullyReceived()) {
                $order->update(['status' => PurchaseOrderStatus::RECEIVED]);
            } else {
                $order->update(['status' => PurchaseOrderStatus::PARTIAL]);
            }
        });
    }

    /**
     * Cancel order
     */
    public function cancel(PurchaseOrder $order): void
    {
        if ($order->status !== PurchaseOrderStatus::DRAFT) {
            throw new \Exception('Only draft orders can be cancelled');
        }

        $order->update([
            'status' => PurchaseOrderStatus::CANCELLED,
        ]);
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        
        $lastNumber = PurchaseOrder::where('company_id', $this->tenantContext->companyId())
            ->where('order_number', 'like', "{$prefix}-{$date}-%")
            ->orderBy('order_number', 'desc')
            ->value('order_number');

        if ($lastNumber) {
            $num = (int) substr($lastNumber, -5);
            $newNum = $num + 1;
        } else {
            $newNum = 1;
        }

        return sprintf('%s-%s-%05d', $prefix, $date, $newNum);
    }
}
