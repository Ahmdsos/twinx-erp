<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AlertType;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Alert Service
 * خدمة التنبيهات
 */
class AlertService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Create alert rule
     */
    public function createRule(array $data): AlertRule
    {
        return AlertRule::create([
            'company_id' => $this->tenantContext->companyId(),
            'name' => $data['name'],
            'type' => $data['type'],
            'conditions' => $data['conditions'] ?? null,
            'email_enabled' => $data['email_enabled'] ?? true,
            'database_enabled' => $data['database_enabled'] ?? true,
            'recipients' => $data['recipients'] ?? null,
            'threshold' => $data['threshold'] ?? null,
            'days_before' => $data['days_before'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Trigger alert
     */
    public function trigger(AlertType $type, string $title, string $message, array $data = [], ?string $referenceType = null, ?string $referenceId = null): Collection
    {
        $logs = collect();

        // Find active rules for this type
        $rules = AlertRule::where('company_id', $this->tenantContext->companyId())
            ->where('type', $type)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            // Get recipients
            $recipients = $this->getRecipients($rule);

            foreach ($recipients as $userId) {
                $log = AlertLog::create([
                    'company_id' => $this->tenantContext->companyId(),
                    'alert_rule_id' => $rule->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ]);

                $logs->push($log);

                // Send email if enabled
                if ($rule->email_enabled) {
                    $this->sendEmailNotification($log);
                }
            }
        }

        return $logs;
    }

    /**
     * Get user's unread alerts
     */
    public function getUnreadAlerts(?string $userId = null): Collection
    {
        $userId = $userId ?? auth()->id();

        return AlertLog::where('company_id', $this->tenantContext->companyId())
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Mark all as read for user
     */
    public function markAllAsRead(?string $userId = null): int
    {
        $userId = $userId ?? auth()->id();

        return AlertLog::where('company_id', $this->tenantContext->companyId())
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Get recipients from rule
     */
    private function getRecipients(AlertRule $rule): array
    {
        if (!empty($rule->recipients)) {
            return $rule->recipients;
        }

        // Default: company admins
        return User::where('current_company_id', $rule->company_id)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Send email notification
     */
    private function sendEmailNotification(AlertLog $log): void
    {
        // TODO: Implement email sending
        // Mail::to($log->user)->send(new AlertNotification($log));
    }
}
