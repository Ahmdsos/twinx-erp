<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Get sales KPIs
     */
    public function salesKpis(): JsonResponse
    {
        $period = request('period', 'month');
        $kpis = $this->dashboardService->getSalesKPIs($period);

        return $this->success($kpis, 'Sales KPIs retrieved');
    }

    /**
     * Get inventory KPIs
     */
    public function inventoryKpis(): JsonResponse
    {
        $kpis = $this->dashboardService->getInventoryKPIs();

        return $this->success($kpis, 'Inventory KPIs retrieved');
    }

    /**
     * Get finance KPIs
     */
    public function financeKpis(): JsonResponse
    {
        $period = request('period', 'month');
        $kpis = $this->dashboardService->getFinanceKPIs($period);

        return $this->success($kpis, 'Finance KPIs retrieved');
    }

    /**
     * Get top products
     */
    public function topProducts(): JsonResponse
    {
        $limit = (int) request('limit', 5);
        $period = request('period', 'month');
        $products = $this->dashboardService->getTopProducts($limit, $period);

        return $this->success($products, 'Top products retrieved');
    }

    /**
     * Get top customers
     */
    public function topCustomers(): JsonResponse
    {
        $limit = (int) request('limit', 5);
        $period = request('period', 'month');
        $customers = $this->dashboardService->getTopCustomers($limit, $period);

        return $this->success($customers, 'Top customers retrieved');
    }

    /**
     * Get sales chart data
     */
    public function salesChart(): JsonResponse
    {
        $period = request('period', 'month');
        $chart = $this->dashboardService->getSalesChart($period);

        return $this->success($chart, 'Sales chart data retrieved');
    }
}
