<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Bill;
use App\Models\Invoice;
use App\Services\TenantContext;
use Carbon\Carbon;

/**
 * Aging Report
 * تقرير تقادم الذمم
 */
class AgingReport
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Get accounts receivable aging (customer invoices)
     * تقادم المدينين
     */
    public function receivables(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $companyId = $this->tenantContext->companyId();

        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['issued', 'overdue', 'partially_paid'])
            ->where('balance_due', '>', 0)
            ->with('customer:id,name,code')
            ->get();

        $buckets = $this->initializeBuckets();
        $details = [];

        foreach ($invoices as $invoice) {
            $bucket = $this->getAgingBucket($invoice->due_date, $asOf);
            $buckets[$bucket] += (float) $invoice->balance_due;

            $details[] = [
                'invoice_number' => $invoice->invoice_number,
                'customer' => $invoice->customer->name ?? 'N/A',
                'invoice_date' => $invoice->invoice_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'days_overdue' => max(0, $asOf->diffInDays($invoice->due_date, false) * -1),
                'balance_due' => (float) $invoice->balance_due,
                'bucket' => $bucket,
            ];
        }

        return [
            'type' => 'receivables',
            'as_of' => $asOf->format('Y-m-d'),
            'summary' => $buckets,
            'total' => array_sum($buckets),
            'details' => $details,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get accounts payable aging (supplier bills)
     * تقادم الدائنين
     */
    public function payables(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? now();
        $companyId = $this->tenantContext->companyId();

        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->where('balance_due', '>', 0)
            ->with('supplier:id,name,code')
            ->get();

        $buckets = $this->initializeBuckets();
        $details = [];

        foreach ($bills as $bill) {
            $bucket = $this->getAgingBucket($bill->due_date, $asOf);
            $buckets[$bucket] += (float) $bill->balance_due;

            $details[] = [
                'bill_number' => $bill->bill_number,
                'supplier' => $bill->supplier->name ?? 'N/A',
                'bill_date' => $bill->bill_date->format('Y-m-d'),
                'due_date' => $bill->due_date->format('Y-m-d'),
                'days_overdue' => max(0, $asOf->diffInDays($bill->due_date, false) * -1),
                'balance_due' => (float) $bill->balance_due,
                'bucket' => $bucket,
            ];
        }

        return [
            'type' => 'payables',
            'as_of' => $asOf->format('Y-m-d'),
            'summary' => $buckets,
            'total' => array_sum($buckets),
            'details' => $details,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Initialize aging buckets
     */
    private function initializeBuckets(): array
    {
        return [
            'current' => 0,
            '1-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];
    }

    /**
     * Determine aging bucket based on due date
     */
    private function getAgingBucket(Carbon $dueDate, Carbon $asOf): string
    {
        $days = $asOf->diffInDays($dueDate, false);

        return match (true) {
            $days >= 0 => 'current',      // Not overdue
            $days >= -30 => '1-30',       // 1-30 days overdue
            $days >= -60 => '31-60',      // 31-60 days overdue
            $days >= -90 => '61-90',      // 61-90 days overdue
            default => '90+',             // Over 90 days
        };
    }
}
