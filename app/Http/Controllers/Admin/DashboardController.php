<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function index(): Response
    {
        $salesKpis = $this->dashboardService->getSalesKPIs();
        $financeKpis = $this->dashboardService->getFinanceKPIs();

        return Inertia::render('Dashboard/Index', [
            'kpis' => array_merge($salesKpis, $financeKpis),
            'salesChart' => $this->dashboardService->getSalesChart(),
        ]);
    }
}
