<?php

declare(strict_types=1);

namespace App\Services\Logistics;

use App\Enums\DeliveryStatus;
use App\Enums\DriverStatus;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Services\TenantContext;

/**
 * Delivery Service
 * خدمة التوصيل
 */
class DeliveryService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create delivery from sales order
     */
    public function createFromOrder(SalesOrder $order): DeliveryOrder
    {
        return DeliveryOrder::create([
            'company_id' => $this->tenantContext->companyId(),
            'branch_id' => $this->tenantContext->branchId(),
            'delivery_number' => $this->generateDeliveryNumber(),
            'sales_order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer->name ?? 'Walk-in',
            'delivery_address' => $order->shipping_address ?? '',
            'contact_phone' => $order->customer->phone ?? '',
            'status' => DeliveryStatus::PENDING,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Assign driver to delivery
     */
    public function assignDriver(DeliveryOrder $delivery, Driver $driver): DeliveryOrder
    {
        if (!$delivery->canAssign()) {
            throw new \Exception('Cannot assign driver to this delivery');
        }

        if (!$driver->isAvailable()) {
            throw new \Exception('Driver is not available');
        }

        $delivery->update([
            'driver_id' => $driver->id,
            'vehicle_id' => $driver->vehicle?->id,
            'status' => DeliveryStatus::ASSIGNED,
            'assigned_at' => now(),
        ]);

        $driver->update(['status' => DriverStatus::ON_DELIVERY]);

        return $delivery->fresh();
    }

    /**
     * Mark as picked up
     */
    public function markPickedUp(DeliveryOrder $delivery): DeliveryOrder
    {
        $delivery->update([
            'status' => DeliveryStatus::PICKED_UP,
            'picked_up_at' => now(),
        ]);

        return $delivery->fresh();
    }

    /**
     * Mark as in transit
     */
    public function markInTransit(DeliveryOrder $delivery): DeliveryOrder
    {
        $delivery->update([
            'status' => DeliveryStatus::IN_TRANSIT,
        ]);

        return $delivery->fresh();
    }

    /**
     * Complete delivery
     */
    public function completeDelivery(
        DeliveryOrder $delivery,
        string $receiverName,
        ?string $notes = null
    ): DeliveryOrder {
        $delivery->update([
            'status' => DeliveryStatus::DELIVERED,
            'delivered_at' => now(),
            'receiver_name' => $receiverName,
            'delivery_notes' => $notes,
        ]);

        // Free up driver
        if ($delivery->driver) {
            $this->updateDriverStatus($delivery->driver);
        }

        return $delivery->fresh();
    }

    /**
     * Mark as failed
     */
    public function markFailed(DeliveryOrder $delivery, string $reason): DeliveryOrder
    {
        $delivery->update([
            'status' => DeliveryStatus::FAILED,
            'failure_reason' => $reason,
        ]);

        // Free up driver
        if ($delivery->driver) {
            $this->updateDriverStatus($delivery->driver);
        }

        return $delivery->fresh();
    }

    /**
     * Update driver status based on active deliveries
     */
    private function updateDriverStatus(Driver $driver): void
    {
        $hasActiveDeliveries = $driver->activeDeliveries()->exists();
        
        $driver->update([
            'status' => $hasActiveDeliveries 
                ? DriverStatus::ON_DELIVERY 
                : DriverStatus::AVAILABLE
        ]);
    }

    /**
     * Generate delivery number
     */
    private function generateDeliveryNumber(): string
    {
        $count = DeliveryOrder::where('company_id', $this->tenantContext->companyId())
            ->whereDate('created_at', today())
            ->count();

        $date = now()->format('Ymd');
        return "DLV-{$date}-" . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get available drivers
     */
    public function getAvailableDrivers(): \Illuminate\Database\Eloquent\Collection
    {
        return Driver::where('company_id', $this->tenantContext->companyId())
            ->where('status', DriverStatus::AVAILABLE)
            ->where('is_active', true)
            ->get();
    }
}
