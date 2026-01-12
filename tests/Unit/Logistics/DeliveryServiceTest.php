<?php

declare(strict_types=1);

namespace Tests\Unit\Logistics;

use App\Enums\DeliveryStatus;
use App\Enums\DriverStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\DeliveryOrder;
use App\Models\Driver;
use App\Models\User;
use App\Services\Logistics\DeliveryService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private Driver $driver;
    private DeliveryOrder $delivery;
    private DeliveryService $deliveryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->driver = Driver::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => DriverStatus::AVAILABLE,
        ]);

        $this->delivery = DeliveryOrder::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => DeliveryStatus::PENDING,
        ]);

        $this->deliveryService = app(DeliveryService::class);
    }

    /**
     * Test assign driver to delivery.
     */
    public function test_assign_driver_to_delivery(): void
    {
        $delivery = $this->deliveryService->assignDriver($this->delivery, $this->driver);

        $this->assertEquals(DeliveryStatus::ASSIGNED, $delivery->status);
        $this->assertEquals($this->driver->id, $delivery->driver_id);
        $this->assertNotNull($delivery->assigned_at);
        
        // Driver status should be on_delivery
        $this->driver->refresh();
        $this->assertEquals(DriverStatus::ON_DELIVERY, $this->driver->status);
    }

    /**
     * Test delivery status workflow.
     */
    public function test_delivery_status_workflow(): void
    {
        // Assign
        $delivery = $this->deliveryService->assignDriver($this->delivery, $this->driver);
        $this->assertEquals(DeliveryStatus::ASSIGNED, $delivery->status);

        // Pick up
        $delivery = $this->deliveryService->markPickedUp($delivery);
        $this->assertEquals(DeliveryStatus::PICKED_UP, $delivery->status);
        $this->assertNotNull($delivery->picked_up_at);

        // In transit
        $delivery = $this->deliveryService->markInTransit($delivery);
        $this->assertEquals(DeliveryStatus::IN_TRANSIT, $delivery->status);

        // Deliver
        $delivery = $this->deliveryService->completeDelivery($delivery, 'أحمد محمد', 'تم التسليم بنجاح');
        $this->assertEquals(DeliveryStatus::DELIVERED, $delivery->status);
        $this->assertEquals('أحمد محمد', $delivery->receiver_name);
        $this->assertNotNull($delivery->delivered_at);
    }

    /**
     * Test driver availability after delivery complete.
     */
    public function test_driver_available_after_delivery(): void
    {
        $delivery = $this->deliveryService->assignDriver($this->delivery, $this->driver);
        $this->assertEquals(DriverStatus::ON_DELIVERY, $this->driver->fresh()->status);

        // Complete delivery
        $this->deliveryService->completeDelivery($delivery, 'Test Receiver');
        
        // Driver should be available again
        $this->assertEquals(DriverStatus::AVAILABLE, $this->driver->fresh()->status);
    }

    /**
     * Test cannot assign unavailable driver.
     */
    public function test_cannot_assign_unavailable_driver(): void
    {
        $this->driver->update(['status' => DriverStatus::OFF_DUTY]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Driver is not available');

        $this->deliveryService->assignDriver($this->delivery, $this->driver);
    }

    /**
     * Test mark delivery as failed.
     */
    public function test_mark_delivery_failed(): void
    {
        $delivery = $this->deliveryService->assignDriver($this->delivery, $this->driver);
        
        $delivery = $this->deliveryService->markFailed($delivery, 'العميل غير متواجد');
        
        $this->assertEquals(DeliveryStatus::FAILED, $delivery->status);
        $this->assertEquals('العميل غير متواجد', $delivery->failure_reason);
        
        // Driver should be available
        $this->assertEquals(DriverStatus::AVAILABLE, $this->driver->fresh()->status);
    }
}
