<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Product $productA;
    protected Product $productB;

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

        $category = Category::create([
            'name' => 'Minuman',
            'slug' => 'minuman',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'name' => 'Botol',
            'symbol' => 'btl',
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'TEST-001',
            'name' => 'Kopi Susu 250ml',
            'purchase_price' => 8000,
            'selling_price' => 15000,
            'stock' => 100,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'TEST-002',
            'name' => 'Teh Tarik 250ml',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    /**
     * Test laporan penjualan mode harian (daily) dengan kalkulasi omzet, HPP, laba, dan margin yang akurat.
     */
    public function test_sales_report_daily_mode(): void
    {
        $today = now()->format('Y-m-d');

        // Transaksi 1: 2x Kopi Susu (Omzet 30.000, HPP 16.000, Laba 14.000)
        Sale::create([
            'transaction_number' => 'POS-TEST-0001',
            'cashier_id' => $this->staff->id,
            'sale_date' => $today,
            'subtotal' => 30000,
            'discount_amount' => 0,
            'grand_total' => 30000,
            'paid_amount' => 30000,
            'cogs_total' => 16000,
            'status' => 'completed',
        ]);

        // Transaksi 2: 1x Teh Tarik (Omzet 10.000, HPP 5.000, Laba 5.000)
        Sale::create([
            'transaction_number' => 'POS-TEST-0002',
            'cashier_id' => $this->staff->id,
            'sale_date' => $today,
            'subtotal' => 10000,
            'discount_amount' => 0,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'cogs_total' => 5000,
            'status' => 'completed',
        ]);

        // Transaksi 3: Dibatalkan (harus diabaikan)
        Sale::create([
            'transaction_number' => 'POS-TEST-0003',
            'cashier_id' => $this->staff->id,
            'sale_date' => $today,
            'subtotal' => 15000,
            'discount_amount' => 0,
            'grand_total' => 15000,
            'paid_amount' => 15000,
            'cogs_total' => 8000,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/reports/sales?mode=daily&start_date={$today}&end_date={$today}");

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $data = $response->json('data');
        $this->assertEquals('daily', $data['mode']);
        $this->assertEquals(2, $data['summary']['total_transactions']);
        $this->assertEquals(40000, $data['summary']['total_revenue']);
        $this->assertEquals(21000, $data['summary']['total_cogs']);
        $this->assertEquals(19000, $data['summary']['gross_profit']);
        // Margin = (19000 / 40000) * 100 = 47.5%
        $this->assertEquals(47.5, $data['summary']['profit_margin']);

        $this->assertNotEmpty($data['items']);
        $item = $data['items'][0];
        $this->assertEquals($today, $item['date']);
        $this->assertEquals(2, $item['transaction_count']);
        $this->assertEquals(40000, $item['total_revenue']);
        $this->assertEquals(21000, $item['total_cogs']);
        $this->assertEquals(19000, $item['gross_profit']);
        $this->assertEquals(47.5, $item['profit_margin']);
    }

    /**
     * Test laporan penjualan mode bulanan (monthly).
     */
    public function test_sales_report_monthly_mode(): void
    {
        $year = now()->year;
        $date = "{$year}-05-15";

        Sale::create([
            'transaction_number' => 'POS-TEST-0004',
            'cashier_id' => $this->staff->id,
            'sale_date' => $date,
            'subtotal' => 50000,
            'discount_amount' => 0,
            'grand_total' => 50000,
            'paid_amount' => 50000,
            'cogs_total' => 20000,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/v1/reports/sales?mode=monthly&year={$year}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('monthly', $data['mode']);
        $this->assertEquals(1, $data['summary']['total_transactions']);
        $this->assertEquals(50000, $data['summary']['total_revenue']);
        $this->assertEquals(20000, $data['summary']['total_cogs']);
        $this->assertEquals(30000, $data['summary']['gross_profit']);
        // Margin = (30000 / 50000) * 100 = 60%
        $this->assertEquals(60.0, $data['summary']['profit_margin']);

        // Bulan ke-5 (Mei) harus memiliki transaksi
        $mayItem = collect($data['items'])->firstWhere('month', 5);
        $this->assertNotNull($mayItem);
        $this->assertEquals(1, $mayItem['transaction_count']);
        $this->assertEquals(50000, $mayItem['total_revenue']);
        $this->assertEquals(30000, $mayItem['gross_profit']);
        $this->assertEquals(60.0, $mayItem['profit_margin']);

        // Test filter spesifik bulan Mei (month=5)
        $specificMonthResponse = $this->actingAs($this->admin)->getJson("/api/v1/reports/sales?mode=monthly&year={$year}&month=5");
        $specificMonthResponse->assertOk();
        $specData = $specificMonthResponse->json('data');
        $this->assertCount(1, $specData['items']);
        $this->assertEquals(5, $specData['items'][0]['month']);
        $this->assertEquals(50000, $specData['summary']['total_revenue']);

        // Test filter bulan lain yang kosong (month=6)
        $emptyMonthResponse = $this->actingAs($this->admin)->getJson("/api/v1/reports/sales?mode=monthly&year={$year}&month=6");
        $emptyMonthResponse->assertOk();
        $emptyData = $emptyMonthResponse->json('data');
        $this->assertCount(1, $emptyData['items']);
        $this->assertEquals(0, $emptyData['summary']['total_transactions']);
        $this->assertEquals(0, $emptyData['summary']['total_revenue']);
    }

    /**
     * Test export Excel dan PDF laporan penjualan.
     */
    public function test_sales_report_exports(): void
    {
        $today = now()->format('Y-m-d');

        Sale::create([
            'transaction_number' => 'POS-TEST-0005',
            'cashier_id' => $this->staff->id,
            'sale_date' => $today,
            'subtotal' => 20000,
            'discount_amount' => 0,
            'grand_total' => 20000,
            'paid_amount' => 20000,
            'cogs_total' => 10000,
            'status' => 'completed',
        ]);

        // Export Excel
        $excelResponse = $this->actingAs($this->admin)->get("/api/v1/reports/sales/export/excel?mode=daily&start_date={$today}&end_date={$today}");
        $excelResponse->assertOk();
        $this->assertTrue(str_contains($excelResponse->headers->get('content-type') ?? '', 'spreadsheetml') || str_contains($excelResponse->headers->get('content-type') ?? '', 'octet-stream'));

        // Export PDF
        $pdfResponse = $this->actingAs($this->admin)->get("/api/v1/reports/sales/export/pdf?mode=daily&start_date={$today}&end_date={$today}");
        $pdfResponse->assertOk();
        $this->assertTrue(str_contains($pdfResponse->headers->get('content-type') ?? '', 'pdf'));
    }
}
