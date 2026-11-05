<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\JournalEntryDetail;
use Carbon\Carbon;

class FinancialReportService
{
    /**
     * Hitung Laporan Laba Rugi (Income Statement).
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getIncomeStatement(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->toDateString() : Carbon::now()->startOfMonth()->toDateString();
        $end = $endDate ? Carbon::parse($endDate)->toDateString() : Carbon::now()->toDateString();

        // 1. Akun Pendapatan (Revenue)
        $revenueAccounts = ChartOfAccount::where('account_type', 'revenue')
            ->orderBy('account_code')
            ->get();

        $revenueItems = [];
        $totalRevenue = 0.0;

        foreach ($revenueAccounts as $acc) {
            $sum = (float) JournalEntryDetail::where('account_id', $acc->id)
                ->whereHas('journal', function ($q) use ($start, $end) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '>=', $start)
                      ->whereDate('journal_date', '<=', $end);
                })
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');

            $amount = round($sum, 2);
            $totalRevenue += $amount;

            $revenueItems[] = [
                'account_id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'amount' => $amount,
            ];
        }

        // 2. Akun Beban (Expenses)
        $expenseAccounts = ChartOfAccount::where('account_type', 'expenses')
            ->orderBy('account_code')
            ->get();

        $expenseItems = [];
        $totalExpenses = 0.0;

        foreach ($expenseAccounts as $acc) {
            $sum = (float) JournalEntryDetail::where('account_id', $acc->id)
                ->whereHas('journal', function ($q) use ($start, $end) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '>=', $start)
                      ->whereDate('journal_date', '<=', $end);
                })
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $amount = round($sum, 2);
            $totalExpenses += $amount;

            $expenseItems[] = [
                'account_id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'amount' => $amount,
            ];
        }

        $totalRevenue = round($totalRevenue, 2);
        $totalExpenses = round($totalExpenses, 2);
        $netProfit = round($totalRevenue - $totalExpenses, 2);

        return [
            'period' => [
                'start_date' => $start,
                'end_date' => $end,
                'formatted' => Carbon::parse($start)->translatedFormat('d M Y') . ' — ' . Carbon::parse($end)->translatedFormat('d M Y'),
            ],
            'revenues' => [
                'items' => $revenueItems,
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'items' => $expenseItems,
                'total' => $totalExpenses,
            ],
            'net_profit' => $netProfit,
            'is_profit' => $netProfit >= 0,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Hitung Laporan Neraca (Balance Sheet).
     *
     * @param string|null $asOfDate
     * @return array
     */
    public function getBalanceSheet(?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->toDateString() : Carbon::now()->toDateString();

        // 1. Aset (Assets)
        $assetAccounts = ChartOfAccount::where('account_type', 'assets')
            ->orderBy('account_code')
            ->get();

        $assetItems = [];
        $totalAssets = 0.0;

        foreach ($assetAccounts as $acc) {
            $sum = (float) JournalEntryDetail::where('account_id', $acc->id)
                ->whereHas('journal', function ($q) use ($asOf) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '<=', $asOf);
                })
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $amount = round($sum, 2);
            $totalAssets += $amount;

            $assetItems[] = [
                'account_id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'amount' => $amount,
            ];
        }

        // 2. Kewajiban (Liabilities)
        $liabilityAccounts = ChartOfAccount::where('account_type', 'liabilities')
            ->orderBy('account_code')
            ->get();

        $liabilityItems = [];
        $totalLiabilities = 0.0;

        foreach ($liabilityAccounts as $acc) {
            $sum = (float) JournalEntryDetail::where('account_id', $acc->id)
                ->whereHas('journal', function ($q) use ($asOf) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '<=', $asOf);
                })
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');

            $amount = round($sum, 2);
            $totalLiabilities += $amount;

            $liabilityItems[] = [
                'account_id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'amount' => $amount,
            ];
        }

        // 3. Ekuitas / Modal (Equity)
        $equityAccounts = ChartOfAccount::where('account_type', 'equity')
            ->orderBy('account_code')
            ->get();

        $equityItems = [];
        $baseEquityTotal = 0.0;

        foreach ($equityAccounts as $acc) {
            $sum = (float) JournalEntryDetail::where('account_id', $acc->id)
                ->whereHas('journal', function ($q) use ($asOf) {
                    $q->whereIn('status', ['posted', 'reversed'])
                      ->whereDate('journal_date', '<=', $asOf);
                })
                ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
                ->value('balance');

            $amount = round($sum, 2);
            $baseEquityTotal += $amount;

            $equityItems[] = [
                'account_id' => $acc->id,
                'account_code' => $acc->account_code,
                'account_name' => $acc->account_name,
                'amount' => $amount,
            ];
        }

        // 4. Laba / Rugi Berjalan Kumulatif s/d $asOf (masuk ke komponen Ekuitas)
        $currentEarnings = (float) JournalEntryDetail::whereHas('journal', function ($q) use ($asOf) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $asOf);
            })
            ->selectRaw('COALESCE(SUM(CASE WHEN chart_of_accounts.account_type = "revenue" THEN credit - debit WHEN chart_of_accounts.account_type = "expenses" THEN -(debit - credit) ELSE 0 END), 0) as earnings')
            ->join('chart_of_accounts', 'journal_entry_details.account_id', '=', 'chart_of_accounts.id')
            ->value('earnings');

        $currentEarnings = round($currentEarnings, 2);
        $totalEquity = round($baseEquityTotal + $currentEarnings, 2);

        $totalAssets = round($totalAssets, 2);
        $totalLiabilities = round($totalLiabilities, 2);
        $totalLiabilitiesAndEquity = round($totalLiabilities + $totalEquity, 2);
        $difference = round(abs($totalAssets - $totalLiabilitiesAndEquity), 2);
        $isBalanced = ($difference === 0.00);

        return [
            'as_of_date' => $asOf,
            'formatted_as_of' => Carbon::parse($asOf)->translatedFormat('d F Y'),
            'assets' => [
                'items' => $assetItems,
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'items' => $liabilityItems,
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'items' => $equityItems,
                'base_equity_total' => round($baseEquityTotal, 2),
                'current_earnings' => $currentEarnings,
                'total' => $totalEquity,
            ],
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'difference' => $difference,
            'is_balanced' => $isBalanced,
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}

