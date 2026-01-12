<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Service
 * خدمة لوحة التحكم
 */
class DashboardService
{
    public function __construct(
        private TenantContext $tenantContext
    ) {}

    /**
     * Get sales KPIs
     */
    public function getSalesKPIs(string $period = 'month'): array
    {
        $companyId = $this->tenantContext->companyId();
        $dateRange = $this->getDateRange($period);

        $totalSales = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', $dateRange)
            ->sum('total');

        $invoiceCount = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', $dateRange)
            ->count();

        $pendingInvoices = Invoice::where('company_id', $companyId)
            ->where('status', 'issued')
            ->count();

        $ordersToday = SalesOrder::where('company_id', $companyId)
            ->whereDate('order_date', today())
            ->count();

        return [
            'total_sales' => round((float) $totalSales, 2),
            'invoice_count' => $invoiceCount,
            'pending_invoices' => $pendingInvoices,
            'orders_today' => $ordersToday,
            'average_order_value' => $invoiceCount > 0 ? round($totalSales / $invoiceCount, 2) : 0,
        ];
    }

    /**
     * Get inventory KPIs
     */
    public function getInventoryKPIs(): array
    {
        $companyId = $this->tenantContext->companyId();

        $totalProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // Simplified - count products with reorder rules where stock might be low
        $lowStockCount = 0;

        return [
            'total_products' => $totalProducts,
            'low_stock_count' => $lowStockCount,
            'total_stock_value' => 0,
        ];
    }

    /**
     * Get finance KPIs
     */
    public function getFinanceKPIs(string $period = 'month'): array
    {
        $companyId = $this->tenantContext->companyId();
        $dateRange = $this->getDateRange($period);

        $revenue = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', $dateRange)
            ->where('status', 'paid')
            ->sum('total');

        $receivables = Invoice::where('company_id', $companyId)
            ->where('status', 'issued')
            ->sum('total');

        return [
            'revenue' => round((float) $revenue, 2),
            'receivables' => round((float) $receivables, 2),
        ];
    }

    /**
     * Get top products
     */
    public function getTopProducts(int $limit = 5, string $period = 'month'): Collection
    {
        $companyId = $this->tenantContext->companyId();
        $dateRange = $this->getDateRange($period);

        return DB::table('invoice_lines')
            ->join('invoices', 'invoice_lines.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_lines.product_id', '=', 'products.id')
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.invoice_date', $dateRange)
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(invoice_lines.quantity) as total_quantity'),
                DB::raw('SUM(invoice_lines.line_total) as total_sales')
            )
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top customers
     */
    public function getTopCustomers(int $limit = 5, string $period = 'month'): Collection
    {
        $companyId = $this->tenantContext->companyId();
        $dateRange = $this->getDateRange($period);

        return DB::table('invoices')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->where('invoices.company_id', $companyId)
            ->whereBetween('invoices.invoice_date', $dateRange)
            ->groupBy('customers.id', 'customers.name', 'customers.customer_number')
            ->select(
                'customers.id',
                'customers.name',
                'customers.customer_number',
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('SUM(invoices.total) as total_spent')
            )
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales chart data
     */
    public function getSalesChart(string $period = 'month'): array
    {
        $companyId = $this->tenantContext->companyId();
        
        $groupBy = match ($period) {
            'week' => 'DATE(invoice_date)',
            'month' => 'DATE(invoice_date)',
            'year' => "TO_CHAR(invoice_date, 'YYYY-MM')",
            default => 'DATE(invoice_date)',
        };

        $data = Invoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', $this->getDateRange($period))
            ->groupBy(DB::raw($groupBy))
            ->select(
                DB::raw("{$groupBy} as date"),
                DB::raw('SUM(total) as total')
            )
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->toArray(),
            'data' => $data->pluck('total')->map(fn ($v) => round((float) $v, 2))->toArray(),
        ];
    }

    /**
     * Get date range based on period
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [today()->startOfDay(), today()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }
}
