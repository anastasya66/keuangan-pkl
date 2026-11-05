<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Data Lengkap Dashboard FinToko.
     */
    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', 'this_month');
        $start = $request->query('start_date');
        $end = $request->query('end_date');

        $summary = $this->dashboardService->getFinancialSummary($period, $start, $end);
        $chart = $this->dashboardService->getFinancialChart($period, $start, $end);
        $expenseDistribution = $this->dashboardService->getExpenseDistribution($period, $start, $end);
        $recentActivities = $this->dashboardService->getRecentActivities(8);
        $inventorySummary = $this->dashboardService->getInventorySummary();
        $lowStockProducts = $this->dashboardService->getLowStockProducts(6);

        return response()->json([
            'status' => 'success',
            'message' => 'Data dashboard berhasil diambil.',
            'data' => [
                'financial_summary' => $summary,
                'financial_chart' => $chart,
                'expense_distribution' => $expenseDistribution,
                'recent_activities' => $recentActivities,
                'inventory_summary' => $inventorySummary,
                'low_stock_products' => $lowStockProducts,
            ],
        ]);
    }

    /**
     * Ringkasan Keuangan.
     */
    public function financialSummary(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getFinancialSummary(
            $request->query('period', 'this_month'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Ringkasan keuangan berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Data Grafik Pemasukan dan Pengeluaran.
     */
    public function financialChart(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getFinancialChart(
            $request->query('period', 'this_month'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data grafik keuangan berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Distribusi Pengeluaran per Kategori.
     */
    public function expenseDistribution(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getExpenseDistribution(
            $request->query('period', 'this_month'),
            $request->query('start_date'),
            $request->query('end_date')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Data distribusi pengeluaran berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Aktivitas Transaksi Terbaru.
     */
    public function recentActivities(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $data = $this->dashboardService->getRecentActivities($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar aktivitas terbaru berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Ringkasan Stok & Valuasi Persediaan.
     */
    public function inventorySummary(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getInventorySummary();

        return response()->json([
            'status' => 'success',
            'message' => 'Ringkasan persediaan berhasil diambil.',
            'data' => $data,
        ]);
    }

    /**
     * Produk dengan Stok Menipis.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 10);
        $data = $this->dashboardService->getLowStockProducts($limit);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar produk stok menipis berhasil diambil.',
            'data' => $data,
        ]);
    }
}
