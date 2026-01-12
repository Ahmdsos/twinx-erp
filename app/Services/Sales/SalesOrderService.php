<?php

declare(strict_types=1);

namespace App\Services\Sales;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\PriceList;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * SalesOrderService
 */
class SalesOrderService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create a new sales order
     */
    public function create(Customer $customer, array $data = []): SalesOrder
    {
        return DB::transaction(function () use ($customer, $data) {
            $order = SalesOrder::create([
                'company_id' => $this->tenantContext->companyId(),
                'branch_id' => $this->tenantContext->branchId(),
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'price_list_id' => $customer->price_list_id ?? $data['price_list_id'] ?? null,
                'order_date' => $data['order_date'] ?? now(),
                'delivery_date' => $data['delivery_date'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => OrderStatus::DRAFT,
                'currency' => $data['currency'] ?? 'SAR',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? $customer->address,
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
        SalesOrder $order,
        Product $product,
        Unit $unit,
        float $quantity,
        ?float $unitPrice = null
    ): SalesOrderLine {
        if (!$order->canEdit()) {
            throw new \Exception('Cannot edit confirmed order');
        }

        // Get price from price list or product
        if ($unitPrice === null) {
            $priceList = $order->priceList ?? PriceList::where('company_id', $order->company_id)
                ->where('is_default', true)
                ->first();

            $unitPrice = $priceList 
                ? $priceList->getPriceForProduct($product, $unit, $quantity)
                : (float) $product->sale_price;
        }

        $lineNumber = $order->lines()->max('line_number') + 1;

        $subtotal = $quantity * $unitPrice;
        $taxAmount = $subtotal * ((float) $product->tax_rate / 100);

        $line = SalesOrderLine::create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'warehouse_id' => $order->warehouse_id,
            'line_number' => $lineNumber,
            'description' => $product->display_name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $product->tax_rate,
            'tax_amount' => $taxAmount,
            'line_total' => $subtotal + $taxAmount,
        ]);

        $order->recalculateTotals();

        return $line;
    }

    /**
     * Confirm order
     */
    public function confirm(SalesOrder $order): void
    {
        if (!$order->canConfirm()) {
            throw new \Exception('Order cannot be confirmed');
        }

        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel order
     */
    public function cancel(SalesOrder $order): void
    {
        if (!$order->canCancel()) {
            throw new \Exception('Order cannot be cancelled');
        }

        $order->update([
            'status' => OrderStatus::CANCELLED,
        ]);
    }

    /**
     * Generate unique order number
     */
    public function generateOrderNumber(): string
    {
        $prefix = 'SO';
        $date = now()->format('Ymd');
        
        $lastNumber = SalesOrder::where('company_id', $this->tenantContext->companyId())
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
