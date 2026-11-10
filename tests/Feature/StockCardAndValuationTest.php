<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCardAndValuationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $adminToken;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->adminToken = $this->admin->createToken('admin-test')->plainTextToken;

        $category = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kg', 'symbol' => 'kg', 'is_active' => true]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SBK-001',
            'barcode' => '8999999999999',
            'name' => 'Beras Pandan Wangi 5kg',
            'purchase_price' => 60000.00,
            'average_cost' => 60000.00,
            'selling_price' => 75000.00,
            'stock' => 100.00,
            'min_stock' => 10.00,
            'is_active' => true,
        ]);

        // Rekam beberapa mutasi stok buatan untuk kartu stok
        InventoryMovement::create([
            'movement_code' => 'MOV-20261101-0001',
            'product_id' => $this->product->id,
            'movement_type' => 'stock_in',
            'quantity' => 100.00,
            'stock_before' => 0.00,
            'stock_after' => 100.00,
            'reference_number' => 'STOK-AWAL',
            'movement_date' => '2026-11-01',
            'created_by' => $this->admin->id,
            'description' => 'Stok awal persediaan',
        ]);

        InventoryMovement::create([
            'movement_code' => 'MOV-20261102-0001',
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 50.00,
            'cost_price' => 62000.00,
            'stock_before' => 100.00,
            'stock_after' => 150.00,
            'reference_number' => 'PO-20261102-0001',
            'movement_date' => '2026-11-02',
            'created_by' => $this->admin->id,
            'description' => 'Pembelian dari supplier',
        ]);

        InventoryMovement::create([
            'movement_code' => 'MOV-20261103-0001',
            'product_id' => $this->product->id,
            'movement_type' => 'sale',
            'quantity' => 20.00,
            'cost_price' => 60666.67,
            'stock_before' => 150.00,
            'stock_after' => 130.00,
            'reference_number' => 'POS-20261103-0001',
            'movement_date' => '2026-11-03',
            'created_by' => $this->admin->id,
            'description' => 'Penjualan kasir POS',
        ]);
    }

    public function test_stock_card_calculates_opening_balance_in_out_and_running_balance(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson("/api/v1/inventory/stock-card/{$this->product->id}?start_date=2026-11-02&end_date=2026-11-05");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.movements');

        $this->assertEquals(100.00, (float) $response->json('data.summary.opening_balance'));
        $this->assertEquals(50.00, (float) $response->json('data.summary.total_in'));
        $this->assertEquals(20.00, (float) $response->json('data.summary.total_out'));
        $this->assertEquals(130.00, (float) $response->json('data.summary.closing_balance'));
    }

    public function test_inventory_valuation_calculates_moving_weighted_average_valuation(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/inventory/valuation');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_products', 1);

        $this->assertEquals(100.00, (float) $response->json('data.summary.total_stock_units'));
        $this->assertEquals(6000000.00, (float) $response->json('data.summary.total_valuation'));
        $this->assertEquals(7500000.00, (float) $response->json('data.summary.potential_revenue'));
        $this->assertEquals(1500000.00, (float) $response->json('data.summary.potential_profit'));
    }

    public function test_inventory_valuation_export_excel_returns_valid_table_stream(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v1/inventory/valuation/export/excel');

        $response->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_inventory_valuation_export_pdf_returns_pdf_file(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->get('/api/v1/inventory/valuation/export/pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }
}
