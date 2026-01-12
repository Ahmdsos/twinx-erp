<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Enums\AlertType;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\AlertService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private User $user;
    private AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create([
            'current_company_id' => $this->company->id,
            'current_branch_id' => $this->branch->id,
        ]);
        $this->actingAs($this->user);

        $tenantContext = app(TenantContext::class);
        $tenantContext->set($this->company, $this->branch);

        $this->alertService = app(AlertService::class);
    }

    /**
     * Test create alert rule.
     */
    public function test_create_alert_rule(): void
    {
        $rule = $this->alertService->createRule([
            'name' => 'Low Stock Alert',
            'type' => AlertType::LOW_STOCK,
            'threshold' => 10,
            'email_enabled' => true,
        ]);

        $this->assertDatabaseHas('alert_rules', [
            'id' => $rule->id,
            'name' => 'Low Stock Alert',
            'type' => AlertType::LOW_STOCK->value,
        ]);
    }

    /**
     * Test trigger alert.
     */
    public function test_trigger_alert(): void
    {
        // Create rule
        $rule = AlertRule::create([
            'company_id' => $this->company->id,
            'name' => 'Low Stock',
            'type' => AlertType::LOW_STOCK,
            'is_active' => true,
            'recipients' => [$this->user->id],
        ]);

        // Trigger alert
        $logs = $this->alertService->trigger(
            AlertType::LOW_STOCK,
            'مخزون منخفض',
            'المنتج XYZ وصل للحد الأدنى',
            ['product_id' => 'test-123'],
            'product',
            'test-123'
        );

        $this->assertCount(1, $logs);
        $this->assertDatabaseHas('alert_logs', [
            'type' => AlertType::LOW_STOCK->value,
            'title' => 'مخزون منخفض',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test get unread alerts.
     */
    public function test_get_unread_alerts(): void
    {
        // Create some alerts
        AlertLog::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => AlertType::NEW_ORDER,
            'title' => 'طلب جديد',
            'message' => 'تم استلام طلب جديد',
            'is_read' => false,
        ]);

        AlertLog::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => AlertType::PAYMENT_RECEIVED,
            'title' => 'دفعة مستلمة',
            'message' => 'تم استلام دفعة',
            'is_read' => true,
        ]);

        $unread = $this->alertService->getUnreadAlerts();

        $this->assertCount(1, $unread);
        $this->assertEquals('طلب جديد', $unread->first()->title);
    }

    /**
     * Test mark all as read.
     */
    public function test_mark_all_as_read(): void
    {
        // Create unread alerts
        AlertLog::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => AlertType::NEW_ORDER,
            'title' => 'Alert 1',
            'message' => 'Message 1',
            'is_read' => false,
        ]);

        AlertLog::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => AlertType::NEW_ORDER,
            'title' => 'Alert 2',
            'message' => 'Message 2',
            'is_read' => false,
        ]);

        $count = $this->alertService->markAllAsRead();

        $this->assertEquals(2, $count);
        $this->assertEquals(0, AlertLog::where('user_id', $this->user->id)->where('is_read', false)->count());
    }

    /**
     * Test alert log mark as read.
     */
    public function test_alert_log_mark_as_read(): void
    {
        $log = AlertLog::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'type' => AlertType::NEW_ORDER,
            'title' => 'Test',
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $this->assertFalse($log->is_read);

        $log->markAsRead();

        $this->assertTrue($log->fresh()->is_read);
        $this->assertNotNull($log->fresh()->read_at);
    }
}
