<?php

namespace App\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ReportController extends BaseController
{
    private ReportService $reportService;

    public function __construct()
    {
        $this->reportService = new ReportService();
    }

    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $startDate = $queryParams['start'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $queryParams['end'] ?? Carbon::now()->format('Y-m-d');

        $dailySales = $this->reportService->getDailySales($startDate, $endDate);
        $topProducts = $this->reportService->getTopProducts(10);
        $categorySales = $this->reportService->getCategorySales();
        $summary = $this->reportService->getSalesSummary();

        return $this->render($request, $response, 'admin/reports/index.twig', [
            'daily_sales' => $dailySales,
            'top_products' => $topProducts,
            'category_sales' => $categorySales,
            'summary' => $summary,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);
    }
}