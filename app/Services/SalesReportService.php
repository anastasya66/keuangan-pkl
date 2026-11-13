<?php

namespace App\Services;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SalesReportService
{
    /**
     * Dapatkan Laporan Penjualan (Harian / Bulanan) beserta analisis laba & margin.
     *
     * @param array $params
     * @return array
     */
    public function getSalesReport(array $params = []): array
    {
        $mode = $params['mode'] ?? 'daily'; // 'daily' or 'monthly'

        if ($mode === 'monthly') {
            return $this->getMonthlySalesReport($params);
        }

        return $this->getDailySalesReport($params);
    }

    /**
     * Laporan Penjualan Rekap Harian.
     */
    protected function getDailySalesReport(array $params): array
    {
        $now = Carbon::now();
        $startDate = !empty($params['start_date'])
            ? Carbon::parse($params['start_date'])->startOfDay()
            : $now->copy()->startOfMonth()->startOfDay();

        $endDate = !empty($params['end_date'])
            ? Carbon::parse($params['end_date'])->endOfDay()
            : $now->copy()->endOfDay();

        // Pastikan urutan tanggal valid
        if ($startDate->gt($endDate)) {
            $temp = $startDate;
            $startDate = $endDate->copy()->startOfDay();
            $endDate = $temp->copy()->endOfDay();
        }

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->whereDate('sale_date', '>=', $startDate->toDateString())
            ->whereDate('sale_date', '<=', $endDate->toDateString())
            ->orderBy('sale_date', 'asc')
            ->get();

        // Grouping per tanggal (Y-m-d)
        $grouped = $sales->groupBy(function (Sale $sale) {
            return Carbon::parse($sale->sale_date)->format('Y-m-d');
        });

        $items = [];
        $totalTransactions = 0;
        $totalRevenue = 0.00;
        $totalCogs = 0.00;

        foreach ($grouped as $dateStr => $dailySales) {
            $txCount = $dailySales->count();
            $rev = round((float) $dailySales->sum('grand_total'), 2);
            $cogs = round((float) $dailySales->sum('cogs_total'), 2);
            $profit = round($rev - $cogs, 2);
            $margin = $rev > 0 ? round(($profit / $rev) * 100, 2) : 0.00;

            $carbonDate = Carbon::parse($dateStr)->locale('id');

            $items[] = [
                'period_key' => $dateStr,
                'date' => $dateStr,
                'label' => $carbonDate->translatedFormat('d M Y'),
                'sublabel' => $carbonDate->translatedFormat('l'),
                'transaction_count' => $txCount,
                'total_revenue' => $rev,
                'total_cogs' => $cogs,
                'gross_profit' => $profit,
                'profit_margin' => $margin,
            ];

            $totalTransactions += $txCount;
            $totalRevenue += $rev;
            $totalCogs += $cogs;
        }

        $totalProfit = round($totalRevenue - $totalCogs, 2);
        $averageMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0.00;

        return [
            'mode' => 'daily',
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'formatted' => $startDate->locale('id')->translatedFormat('d M Y') . ' s/d ' . $endDate->locale('id')->translatedFormat('d M Y'),
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_revenue' => round($totalRevenue, 2),
                'total_cogs' => round($totalCogs, 2),
                'gross_profit' => $totalProfit,
                'profit_margin' => $averageMargin,
            ],
            'items' => array_values($items),
            'generated_at' => Carbon::now()->locale('id')->translatedFormat('d/m/Y H:i'),
        ];
    }

    /**
     * Laporan Penjualan Rekap Bulanan.
     */
    protected function getMonthlySalesReport(array $params): array
    {
        $year = (int) ($params['year'] ?? date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }

        $selectedMonth = (!empty($params['month']) && is_numeric($params['month']) && (int) $params['month'] >= 1 && (int) $params['month'] <= 12)
            ? (int) $params['month']
            : null;

        if ($selectedMonth) {
            $startDate = Carbon::createFromDate($year, $selectedMonth, 1)->startOfMonth()->startOfDay();
            $endDate = Carbon::createFromDate($year, $selectedMonth, 1)->endOfMonth()->endOfDay();
            $monthRange = [$selectedMonth];
            $monthName = Carbon::createFromDate($year, $selectedMonth, 1)->locale('id')->translatedFormat('F');
            $periodFormatted = "Bulan {$monthName} {$year}";
        } else {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();
            $monthRange = range(1, 12);
            $periodFormatted = "Tahun {$year} (Semua Bulan)";
        }

        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->whereDate('sale_date', '>=', $startDate->toDateString())
            ->whereDate('sale_date', '<=', $endDate->toDateString())
            ->orderBy('sale_date', 'asc')
            ->get();

        // Grouping per bulan (1..12)
        $grouped = $sales->groupBy(function (Sale $sale) {
            return (int) Carbon::parse($sale->sale_date)->format('n');
        });

        $items = [];
        $totalTransactions = 0;
        $totalRevenue = 0.00;
        $totalCogs = 0.00;

        foreach ($monthRange as $month) {
            $monthlySales = $grouped->get($month, collect());
            $txCount = $monthlySales->count();
            $rev = round((float) $monthlySales->sum('grand_total'), 2);
            $cogs = round((float) $monthlySales->sum('cogs_total'), 2);
            $profit = round($rev - $cogs, 2);
            $margin = $rev > 0 ? round(($profit / $rev) * 100, 2) : 0.00;

            $carbonMonth = Carbon::createFromDate($year, $month, 1)->locale('id');
            $monthKey = sprintf('%04d-%02d', $year, $month);

            $items[] = [
                'period_key' => $monthKey,
                'month' => $month,
                'label' => $carbonMonth->translatedFormat('F Y'),
                'sublabel' => 'Bulan ke-' . $month,
                'transaction_count' => $txCount,
                'total_revenue' => $rev,
                'total_cogs' => $cogs,
                'gross_profit' => $profit,
                'profit_margin' => $margin,
            ];

            $totalTransactions += $txCount;
            $totalRevenue += $rev;
            $totalCogs += $cogs;
        }

        $totalProfit = round($totalRevenue - $totalCogs, 2);
        $averageMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0.00;

        return [
            'mode' => 'monthly',
            'period' => [
                'year' => $year,
                'month' => $selectedMonth,
                'formatted' => $periodFormatted,
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'total_revenue' => round($totalRevenue, 2),
                'total_cogs' => round($totalCogs, 2),
                'gross_profit' => $totalProfit,
                'profit_margin' => $averageMargin,
            ],
            'items' => $items,
            'generated_at' => Carbon::now()->locale('id')->translatedFormat('d/m/Y H:i'),
        ];
    }
}

