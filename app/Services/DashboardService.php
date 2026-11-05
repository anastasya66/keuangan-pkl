<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Product;
use App\Models\TransactionCategory;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Resolusi rentang tanggal berdasarkan parameter periode.
     *
     * @param string $period 'today'|'this_week'|'this_month'|'this_year'|'custom'
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array{0: string, 1: string, 'label': string}
     */
    public function parseDateRange(string $period = 'this_month', ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay()->toDateString();
                $end = $now->copy()->endOfDay()->toDateString();
                $label = 'Hari Ini (' . $now->translatedFormat('d M Y') . ')';
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek()->toDateString();
                $end = $now->copy()->endOfWeek()->toDateString();
                $label = 'Minggu Ini';
                break;

            case 'this_year':
                $start = $now->copy()->startOfYear()->toDateString();
                $end = $now->copy()->endOfYear()->toDateString();
                $label = 'Tahun ' . $now->year;
                break;

            case 'custom':
                $start = $startDate ? Carbon::parse($startDate)->toDateString() : $now->copy()->startOfMonth()->toDateString();
                $end = $endDate ? Carbon::parse($endDate)->toDateString() : $now->copy()->toDateString();
                $label = 'Kustom (' . Carbon::parse($start)->translatedFormat('d M Y') . ' - ' . Carbon::parse($end)->translatedFormat('d M Y') . ')';
                break;

            case 'this_month':
            default:
                $start = $now->copy()->startOfMonth()->toDateString();
                $end = $now->copy()->endOfMonth()->toDateString();
                $label = 'Bulan ' . $now->translatedFormat('F Y');
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    /**
     * Ringkasan Finansial Toko.
     *
     * @param string $period
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getFinancialSummary(string $period = 'this_month', ?string $startDate = null, ?string $endDate = null): array
    {
        ['start' => $start, 'end' => $end, 'label' => $label] = $this->parseDateRange($period, $startDate, $endDate);

        // 1. Total Pemasukan Operasional dari FinancialTransaction (completed)
        $totalIncome = (float) FinancialTransaction::income()
            ->completed()
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end)
            ->sum('amount');

        // 2. Total Pengeluaran Operasional dari FinancialTransaction (completed)
        $totalExpense = (float) FinancialTransaction::expense()
            ->completed()
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end)
            ->sum('amount');

        // 3. Laba Bersih Akuntansi: Pendapatan - Beban dari Jurnal Posted
        $revenue = (float) JournalEntryDetail::whereHas('account', fn($q) => $q->where('account_type', 'revenue'))
            ->whereHas('journal', function ($q) use ($start, $end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '>=', $start)
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as net_revenue')
            ->value('net_revenue');

        $expenses = (float) JournalEntryDetail::whereHas('account', fn($q) => $q->where('account_type', 'expenses'))
            ->whereHas('journal', function ($q) use ($start, $end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '>=', $start)
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as net_expense')
            ->value('net_expense');

        $netProfit = round($revenue - $expenses, 2);

        // 4. Saldo Kas & Bank Aktual (Kumulatif s/d tanggal $end)
        $cashBalance = (float) JournalEntryDetail::whereHas('account', function ($q) {
                $q->where('account_type', 'assets')
                  ->where(function ($sub) {
                      $sub->where('account_code', 'like', '111%')
                          ->orWhere('account_code', 'like', '112%')
                          ->orWhere('account_name', 'like', '%Kas%')
                          ->orWhere('account_name', 'like', '%Bank%');
                  });
            })
            ->whereHas('journal', function ($q) use ($end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as cash_balance')
            ->value('cash_balance');

        // 5. Total Aset Kumulatif
        $totalAssets = (float) JournalEntryDetail::whereHas('account', fn($q) => $q->where('account_type', 'assets'))
            ->whereHas('journal', function ($q) use ($end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as total_assets')
            ->value('total_assets');

        // 6. Total Kewajiban Kumulatif
        $totalLiabilities = (float) JournalEntryDetail::whereHas('account', fn($q) => $q->where('account_type', 'liabilities'))
            ->whereHas('journal', function ($q) use ($end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as total_liabilities')
            ->value('total_liabilities');

        // 7. Laba Kumulatif Sejak Awal s/d tanggal $end
        $cumulativeProfit = (float) JournalEntryDetail::whereHas('journal', function ($q) use ($end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN chart_of_accounts.account_type = 'revenue' THEN credit - debit WHEN chart_of_accounts.account_type = 'expenses' THEN -(debit - credit) ELSE 0 END), 0) as cum_profit")
            ->join('chart_of_accounts', 'journal_entry_details.account_id', '=', 'chart_of_accounts.id')
            ->value('cum_profit');

        // Modal Awal Kumulatif
        $baseEquity = (float) JournalEntryDetail::whereHas('account', fn($q) => $q->where('account_type', 'equity'))
            ->whereHas('journal', function ($q) use ($end) {
                $q->whereIn('status', ['posted', 'reversed'])
                  ->whereDate('journal_date', '<=', $end);
            })
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as base_equity')
            ->value('base_equity');

        $totalEquity = round($baseEquity + $cumulativeProfit, 2);

        return [
            'period' => [
                'type' => $period,
                'start_date' => $start,
                'end_date' => $end,
                'label' => $label,
            ],
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'net_profit' => $netProfit,
            'cash_balance' => round($cashBalance, 2),
            'total_assets' => round($totalAssets, 2),
            'total_liabilities' => round($totalLiabilities, 2),
            'total_equity' => $totalEquity,
            'is_equity_balanced' => (round(abs($totalAssets - ($totalLiabilities + $totalEquity)), 2) === 0.00),
        ];
    }

    /**
     * Data Time-Series untuk Grafik Pemasukan, Pengeluaran & Laba.
     *
     * @param string $period
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getFinancialChart(string $period = 'this_month', ?string $startDate = null, ?string $endDate = null): array
    {
        ['start' => $start, 'end' => $end] = $this->parseDateRange($period, $startDate, $endDate);

        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);
        $diffDays = $startCarbon->diffInDays($endCarbon);

        // Ambil transaksi keuangan harian
        $transactions = FinancialTransaction::completed()
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end)
            ->get()
            ->groupBy(fn($item) => $item->transaction_date->format('Y-m-d'));

        $chartPoints = [];

        // Jika rentang <= 31 hari, tampilkan per hari
        if ($diffDays <= 31) {
            $current = $startCarbon->copy();
            while ($current->lte($endCarbon)) {
                $dateKey = $current->format('Y-m-d');
                $dayTransactions = $transactions->get($dateKey, collect());

                $income = (float) $dayTransactions->where('transaction_type', 'income')->sum('amount');
                $expense = (float) $dayTransactions->where('transaction_type', 'expense')->sum('amount');

                $chartPoints[] = [
                    'label' => $current->format('d M'),
                    'date' => $dateKey,
                    'income' => round($income, 2),
                    'expense' => round($expense, 2),
                    'profit' => round($income - $expense, 2),
                ];

                $current->addDay();
            }
        } else {
            // Jika lebih dari 31 hari, kelompokkan per bulan (database-agnostic via Collection)
            $monthlyData = FinancialTransaction::completed()
                ->whereDate('transaction_date', '>=', $start)
                ->whereDate('transaction_date', '<=', $end)
                ->get()
                ->groupBy(fn($item) => $item->transaction_date->format('Y-m'));

            $current = $startCarbon->copy()->startOfMonth();
            while ($current->lte($endCarbon)) {
                $ym = $current->format('Y-m');
                $monthGroup = $monthlyData->get($ym, collect());
                $inc = (float) $monthGroup->where('transaction_type', 'income')->sum('amount');
                $exp = (float) $monthGroup->where('transaction_type', 'expense')->sum('amount');

                $chartPoints[] = [
                    'label' => $current->translatedFormat('M Y'),
                    'date' => $ym,
                    'income' => round($inc, 2),
                    'expense' => round($exp, 2),
                    'profit' => round($inc - $exp, 2),
                ];

                $current->addMonth();
            }
        }

        return [
            'period' => ['start_date' => $start, 'end_date' => $end],
            'points' => $chartPoints,
        ];
    }

    /**
     * Distribusi Pengeluaran Berdasarkan Kategori.
     *
     * @param string $period
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    public function getExpenseDistribution(string $period = 'this_month', ?string $startDate = null, ?string $endDate = null): array
    {
        ['start' => $start, 'end' => $end] = $this->parseDateRange($period, $startDate, $endDate);

        $expenses = FinancialTransaction::expense()
            ->completed()
            ->whereDate('transaction_date', '>=', $start)
            ->whereDate('transaction_date', '<=', $end)
            ->with('category')
            ->get();

        $totalExpense = $expenses->sum('amount');

        $grouped = $expenses->groupBy('category_id')->map(function ($items) use ($totalExpense) {
            $cat = $items->first()->category;
            $amount = (float) $items->sum('amount');
            $percentage = $totalExpense > 0 ? round(($amount / $totalExpense) * 100, 1) : 0;

            return [
                'category_id' => $cat?->id,
                'category_name' => $cat?->name ?? 'Lain-lain',
                'amount' => round($amount, 2),
                'percentage' => $percentage,
                'transaction_count' => $items->count(),
            ];
        })->values()->sortByDesc('amount')->values();

        return [
            'total_expense' => round($totalExpense, 2),
            'categories' => $grouped,
        ];
    }

    /**
     * Aktivitas Terkini (Gabungan Transaksi, Persediaan, dan Jurnal).
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentActivities(int $limit = 10): Collection
    {
        $activities = collect();

        // 1. Transaksi Keuangan Terbaru
        $transactions = FinancialTransaction::with(['category', 'creator'])
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => "trx-{$t->id}",
                    'type' => $t->transaction_type === 'income' ? 'income' : 'expense',
                    'type_label' => $t->transaction_type === 'income' ? 'Pemasukan' : 'Pengeluaran',
                    'title' => $t->transaction_code . ' — ' . ($t->category?->name ?? 'Transaksi'),
                    'description' => $t->description ?: ($t->transaction_type === 'income' ? 'Pemasukan Kas' : 'Pengeluaran Kas'),
                    'amount' => (float) $t->amount,
                    'status' => $t->status,
                    'date' => $t->transaction_date->format('Y-m-d'),
                    'creator' => $t->creator?->name,
                    'created_at' => $t->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        // 2. Mutasi Persediaan Terbaru
        $movements = InventoryMovement::with(['product', 'creator'])
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function ($m) {
                $labels = [
                    'stock_in' => 'Stok Masuk',
                    'stock_out' => 'Stok Keluar',
                    'adjustment_in' => 'Opname Bertambah',
                    'adjustment_out' => 'Opname Berkurang',
                ];

                return [
                    'id' => "inv-{$m->id}",
                    'type' => 'inventory',
                    'type_label' => $labels[$m->movement_type] ?? 'Persediaan',
                    'title' => $m->movement_code . ' — ' . ($m->product?->name ?? 'Produk'),
                    'description' => ($m->quantity_numeric ?? $m->quantity) . ' unit (' . ($m->description ?: $m->adjustment_reason ?: 'Mutasi Stok') . ')',
                    'amount' => null,
                    'status' => 'completed',
                    'date' => $m->movement_date->format('Y-m-d'),
                    'creator' => $m->creator?->name,
                    'created_at' => $m->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        // 3. Jurnal Akuntansi Terbaru
        $journals = JournalEntry::with('creator')
            ->latest('id')
            ->take($limit)
            ->get()
            ->map(function ($j) {
                return [
                    'id' => "jrn-{$j->id}",
                    'type' => 'journal',
                    'type_label' => 'Jurnal ' . ucfirst($j->status),
                    'title' => $j->journal_number,
                    'description' => $j->description,
                    'amount' => (float) $j->total_debit,
                    'status' => $j->status,
                    'date' => $j->journal_date->format('Y-m-d'),
                    'creator' => $j->creator?->name,
                    'created_at' => $j->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return $activities->concat($transactions)
            ->concat($movements)
            ->concat($journals)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Ringkasan Persediaan (Inventory Summary).
     *
     * @return array
     */
    public function getInventorySummary(): array
    {
        $products = Product::where('is_active', true)->get();

        $totalProducts = $products->count();
        $totalCategories = Category::where('is_active', true)->count();
        $totalUnits = (float) $products->sum('stock');
        $totalValuation = (float) $products->reduce(fn($carry, $p) => $carry + ((float) $p->stock * (float) $p->purchase_price), 0);

        $outOfStock = $products->where('stock', '<=', 0)->count();
        $lowStock = $products->filter(fn($p) => (float) $p->stock > 0 && (float) $p->stock <= 10)->count();
        $available = $products->filter(fn($p) => (float) $p->stock > 10)->count();

        return [
            'total_products' => $totalProducts,
            'total_categories' => $totalCategories,
            'total_units' => round($totalUnits, 2),
            'total_valuation' => round($totalValuation, 2),
            'available_count' => $available,
            'low_stock_count' => $lowStock,
            'out_of_stock_count' => $outOfStock,
        ];
    }

    /**
     * Daftar Produk dengan Stok Rendah / Habis.
     *
     * @param int $limit
     * @return Collection
     */
    public function getLowStockProducts(int $limit = 6): Collection
    {
        return Product::where('is_active', true)
            ->where('stock', '<=', 10)
            ->with(['category', 'unit'])
            ->orderBy('stock', 'asc')
            ->take($limit)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'category' => $p->category?->name,
                    'unit' => $p->unit?->symbol,
                    'stock' => (float) $p->stock,
                    'purchase_price' => (float) $p->purchase_price,
                    'selling_price' => (float) $p->selling_price,
                    'status' => (float) $p->stock <= 0 ? 'out_of_stock' : 'low_stock',
                    'stock_status' => (float) $p->stock <= 0 ? 'out_of_stock' : 'low_stock',
                    'status_label' => (float) $p->stock <= 0 ? 'Stok Habis' : 'Stok Menipis',
                ];
            });
    }
}
