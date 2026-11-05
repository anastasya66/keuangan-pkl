<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\TransactionCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected ChartOfAccount $cashAccount;
    protected ChartOfAccount $bankAccount;
    protected ChartOfAccount $revenueAccount;
    protected ChartOfAccount $expenseAccount;
    protected ChartOfAccount $equityAccount;
    protected Product $productBeras;
    protected Product $productMinyak;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Buat Akun Standar
        $this->cashAccount = ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Kas Toko',
            'account_type' => 'assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->bankAccount = ChartOfAccount::create([
            'account_code' => '1120',
            'account_name' => 'Bank Operasional',
            'account_type' => 'assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->revenueAccount = ChartOfAccount::create([
            'account_code' => '4100',
            'account_name' => 'Pendapatan Penjualan Toko',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        $this->expenseAccount = ChartOfAccount::create([
            'account_code' => '5100',
            'account_name' => 'Beban Operasional Toko',
            'account_type' => 'expenses',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->equityAccount = ChartOfAccount::create([
            'account_code' => '3100',
            'account_name' => 'Modal Awal Pemilik',
            'account_type' => 'equity',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        // Buat Kategori & Satuan & Produk
        $cat = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unitKg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $this->productBeras = Product::create([
            'category_id' => $cat->id,
            'unit_id' => $unitKg->id,
            'sku' => 'BRS-01',
            'name' => 'Beras Pandan Wangi 5kg',
            'stock' => 50,
            'purchase_price' => 12000,
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        $this->productMinyak = Product::create([
            'category_id' => $cat->id,
            'unit_id' => $unitKg->id,
            'sku' => 'MYK-01',
            'name' => 'Minyak Goreng 2L',
            'stock' => 5, // Stok menipis (<= 10)
            'purchase_price' => 28000,
            'selling_price' => 34000,
            'is_active' => true,
        ]);

        // Buat Jurnal Modal Awal: Kas (Debit) 10.000.000 vs Modal (Kredit) 10.000.000
        $postingService = app(JournalPostingService::class);
        $jModal = $postingService->createDraft([
            'journal_date' => now()->startOfMonth()->toDateString(),
            'description' => 'Setoran Modal Awal',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 10000000, 'credit' => 0],
                ['account_id' => $this->equityAccount->id, 'debit' => 0, 'credit' => 10000000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($jModal, $this->admin->id);

        // Buat Jurnal Penjualan: Kas (Debit) 2.000.000 vs Pendapatan (Kredit) 2.000.000
        $jSale = $postingService->createDraft([
            'journal_date' => now()->toDateString(),
            'description' => 'Penjualan Ritel Hari Ini',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 2000000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 2000000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($jSale, $this->admin->id);

        // Buat Jurnal Beban: Beban (Debit) 500.000 vs Kas (Kredit) 500.000
        $jExp = $postingService->createDraft([
            'journal_date' => now()->toDateString(),
            'description' => 'Beban Operasional Listrik',
            'items' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 500000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($jExp, $this->admin->id);

        // Buat Transaksi Keuangan Operasional
        $trxCatInc = TransactionCategory::create(['name' => 'Penjualan Toko', 'type' => 'income']);
        $trxCatExp = TransactionCategory::create(['name' => 'Biaya Listrik', 'type' => 'expense']);

        FinancialTransaction::create([
            'transaction_code' => 'TRX-INC-01',
            'transaction_type' => 'income',
            'category_id' => $trxCatInc->id,
            'amount' => 2000000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Penjualan Toko Hari Ini',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        FinancialTransaction::create([
            'transaction_code' => 'TRX-EXP-01',
            'transaction_type' => 'expense',
            'category_id' => $trxCatExp->id,
            'amount' => 500000,
            'transaction_date' => now()->toDateString(),
            'description' => 'Pembayaran Listrik',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        // Buat Mutasi Persediaan
        InventoryMovement::create([
            'movement_code' => 'MOV-TEST-001',
            'product_id' => $this->productBeras->id,
            'movement_type' => 'stock_in',
            'quantity' => 20,
            'stock_before' => 30,
            'stock_after' => 50,
            'movement_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * 1. Dashboard menampilkan data keuangan yang benar.
     */
    public function test_dashboard_displays_correct_financial_metrics(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/financial-summary?period=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_income', 2000000)
            ->assertJsonPath('data.total_expense', 500000)
            ->assertJsonPath('data.net_profit', 1500000) // 2.000.000 - 500.000 = 1.500.000
            ->assertJsonPath('data.cash_balance', 11500000) // 10.000.000 + 2.000.000 - 500.000 = 11.500.000
            ->assertJsonPath('data.is_equity_balanced', true);
    }

    /**
     * 2. Filter periode dashboard bekerja.
     */
    public function test_dashboard_period_filters_work(): void
    {
        $periods = ['today', 'this_week', 'this_month', 'this_year'];

        foreach ($periods as $p) {
            $response = $this->actingAs($this->admin, 'sanctum')
                ->getJson("/api/v1/dashboard/financial-summary?period={$p}");

            $response->assertStatus(200)
                ->assertJsonPath('data.period.type', $p);
        }

        // Custom date range
        $custom = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/financial-summary?period=custom&start_date=2026-01-01&end_date=2026-12-31');

        $custom->assertStatus(200)
            ->assertJsonPath('data.period.type', 'custom')
            ->assertJsonPath('data.period.start_date', '2026-01-01');
    }

    /**
     * 3. Grafik pemasukan dan pengeluaran sesuai database.
     */
    public function test_dashboard_financial_chart_returns_points(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/financial-chart?period=this_month');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'period' => ['start_date', 'end_date'],
                    'points' => [
                        '*' => ['label', 'date', 'income', 'expense', 'profit'],
                    ],
                ],
            ]);
    }

    /**
     * 4. Distribusi pengeluaran per kategori.
     */
    public function test_dashboard_expense_distribution(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/expense-distribution?period=this_month');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_expense', 500000)
            ->assertJsonPath('data.categories.0.category_name', 'Biaya Listrik')
            ->assertJsonPath('data.categories.0.amount', 500000)
            ->assertJsonPath('data.categories.0.percentage', 100);
    }

    /**
     * 5. Aktivitas terbaru menggabungkan transaksi, persediaan, dan jurnal.
     */
    public function test_dashboard_recent_activities(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/recent-activities?limit=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'type', 'type_label', 'title', 'date', 'status'],
                ],
            ]);

        $types = collect($response->json('data'))->pluck('type')->unique();
        $this->assertTrue($types->contains('income') || $types->contains('expense'));
        $this->assertTrue($types->contains('inventory'));
        $this->assertTrue($types->contains('journal'));
    }

    /**
     * 6. Ringkasan stok dan produk menipis di dashboard.
     */
    public function test_dashboard_inventory_summary_and_low_stock(): void
    {
        $summary = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/inventory-summary');

        $summary->assertStatus(200)
            ->assertJsonPath('data.total_products', 2)
            ->assertJsonPath('data.low_stock_count', 1); // Minyak stok 5

        $lowStock = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/dashboard/low-stock');

        $lowStock->assertStatus(200)
            ->assertJsonPath('data.0.sku', 'MYK-01')
            ->assertJsonPath('data.0.stock_status', 'low_stock');
    }

    /**
     * 7. Laporan Laba Rugi menghitung pendapatan dan beban dengan benar.
     */
    public function test_income_statement_report(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/income-statement');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.revenues.total', 2000000)
            ->assertJsonPath('data.expenses.total', 500000)
            ->assertJsonPath('data.net_profit', 1500000)
            ->assertJsonPath('data.is_profit', true);
    }

    /**
     * 8. Laporan Neraca menghitung aset, kewajiban, dan ekuitas serta seimbang.
     */
    public function test_balance_sheet_report_equilibrium(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/balance-sheet');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total_assets', 11500000)
            ->assertJsonPath('data.total_liabilities', 0)
            ->assertJsonPath('data.equity.base_equity_total', 10000000)
            ->assertJsonPath('data.equity.current_earnings', 1500000)
            ->assertJsonPath('data.total_equity', 11500000)
            ->assertJsonPath('data.is_balanced', true);
    }

    /**
     * 9. Laporan Stok menampilkan stok aktual dan valuasi persediaan.
     */
    public function test_inventory_report_stock_and_valuation(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/inventory');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_products', 2)
            ->assertJsonPath('data.summary.total_stock_units', 55) // 50 + 5
            ->assertJsonPath('data.summary.total_inventory_value', 740000); // (50*12000) + (5*28000) = 600.000 + 140.000 = 740.000
    }

    /**
     * 10. Filter status stok pada laporan persediaan.
     */
    public function test_inventory_report_filter_status(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/inventory?stock_status=low_stock');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_products', 1)
            ->assertJsonPath('data.products.0.sku', 'MYK-01');
    }

    /**
     * 11. Laporan riwayat mutasi persediaan.
     */
    public function test_stock_movements_report(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/reports/stock-movements');

        $response->assertStatus(200)
            ->assertJsonPath('data.summary.total_movements', 1)
            ->assertJsonPath('data.movements.0.movement_code', 'MOV-TEST-001')
            ->assertJsonPath('data.movements.0.quantity', 20);
    }

    /**
     * 12. Export PDF Laba Rugi berhasil.
     */
    public function test_income_statement_export_pdf(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/income-statement/export/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * 13. Export PDF Neraca berhasil.
     */
    public function test_balance_sheet_export_pdf(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/balance-sheet/export/pdf');

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /**
     * 14. Export PDF Stok & Mutasi berhasil.
     */
    public function test_inventory_and_movements_export_pdf(): void
    {
        $respInv = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/inventory/export/pdf');
        $respInv->assertStatus(200);
        $this->assertEquals('application/pdf', $respInv->headers->get('Content-Type'));

        $respMov = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/stock-movements/export/pdf');
        $respMov->assertStatus(200);
        $this->assertEquals('application/pdf', $respMov->headers->get('Content-Type'));
    }

    /**
     * 15. Export Excel / CSV berhasil untuk seluruh laporan.
     */
    public function test_excel_export_for_all_reports(): void
    {
        $routes = [
            '/api/v1/reports/income-statement/export/excel',
            '/api/v1/reports/balance-sheet/export/excel',
            '/api/v1/reports/inventory/export/excel',
            '/api/v1/reports/stock-movements/export/excel',
        ];

        foreach ($routes as $route) {
            $resp = $this->actingAs($this->admin, 'sanctum')->get($route);
            $resp->assertStatus(200);
            $this->assertTrue(
                str_contains($resp->headers->get('Content-Type'), 'application/vnd.ms-excel') ||
                str_contains($resp->headers->get('Content-Type'), 'text/csv')
            );
            $this->assertStringContainsString('attachment;', $resp->headers->get('Content-Disposition'));
        }
    }

    /**
     * 16. Unauthenticated request ditolak.
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/reports/income-statement')->assertStatus(401);
        $this->getJson('/api/v1/reports/balance-sheet')->assertStatus(401);
    }
}

