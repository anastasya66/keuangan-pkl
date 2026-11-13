<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Consignor;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsignmentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Consignor $consignor;
    protected Category $category;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Akun akuntansi untuk jurnal penjualan kasir POS
        ChartOfAccount::create(['account_code' => '1110', 'account_name' => 'Kas Toko', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1120', 'account_name' => 'Bank Operasional', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1130', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '4100', 'account_name' => 'Pendapatan Penjualan Toko', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '5110', 'account_name' => 'Beban Pokok Penjualan (HPP)', 'account_type' => 'expenses', 'normal_balance' => 'debit', 'is_active' => true]);

        $this->category = Category::create([
            'name' => 'Snack & Makanan',
            'description' => 'Kategori makanan ringan',
            'is_active' => true,
        ]);

        $this->unit = Unit::create([
            'name' => 'Bungkus',
            'symbol' => 'Bks',
            'is_active' => true,
        ]);

        $this->consignor = Consignor::create([
            'code' => 'KNS-MITRA-001',
            'name' => 'Dapur Bu Ani',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 10',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'Ani Suryani',
            'default_commission_rate' => 15.00,
            'is_active' => true,
        ]);
    }

    /**
     * Test admin dapat membuat barang konsinyasi baru yang otomatis tersimpan di katalog produk utama
     * dengan flag is_consignment dan nomor SKU otomatis (KNS-XXXX).
     */
    public function test_can_create_consignment_product_and_sync_to_main_catalog(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/consignments/products', [
            'consignor_id' => $this->consignor->id,
            'name' => 'Keripik Tempe Bu Ani',
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'consignment_cost_price' => 8000,
            'selling_price' => 10000,
            'stock' => 20,
            'minimum_stock' => 5,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $productId = $response->json('data.id');

        // Pastikan tersimpan di tabel products
        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Keripik Tempe Bu Ani',
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'consignment_cost_price' => 8000.00,
            'selling_price' => 10000.00,
            'purchase_price' => 0.00, // HPP toko bernilai 0 untuk konsinyasi
            'stock' => 20.00,
        ]);

        $product = Product::find($productId);
        $this->assertStringStartsWith('KNS-', $product->sku);
    }

    /**
     * Test barang konsinyasi langsung tampil di daftar produk Kasir POS dan dapat dicari.
     */
    public function test_consignment_product_appears_in_pos_and_can_be_searched(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'KNS-0099',
            'name' => 'Bolu Kukus Spesial',
            'selling_price' => 15000,
            'purchase_price' => 0.00,
            'stock' => 10,
            'is_active' => true,
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'consignment_cost_price' => 12000,
            'commission_rate' => 20,
            'commission_amount' => 3000,
        ]);

        $response = $this->actingAs($this->cashier)->getJson('/api/v1/pos/products?query=Bolu');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $this->assertEquals($product->id, $items[0]['id']);
        $this->assertTrue($items[0]['is_consignment']);
    }

    /**
     * Test transaksi kasir POS dengan barang konsinyasi memotong stok secara otomatis dan mencatat tagihan konsinyor.
     */
    public function test_pos_checkout_consignment_product_decreases_stock_and_records_consignor_debt(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'KNS-0100',
            'name' => 'Pastel Ayam Renyah',
            'selling_price' => 5000,
            'purchase_price' => 0.00,
            'stock' => 15,
            'is_active' => true,
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'consignment_cost_price' => 4000,
            'commission_rate' => 20,
            'commission_amount' => 1000,
        ]);

        $checkoutResponse = $this->actingAs($this->cashier)->postJson('/api/v1/pos/checkout', [
            'payment_method' => 'cash',
            'paid_amount' => 15000,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 5000,
                ],
            ],
        ]);

        $checkoutResponse->assertStatus(201);
        $checkoutResponse->assertJsonPath('success', true);

        // Cek stok terpotong: 15 - 3 = 12
        $product->refresh();
        $this->assertEquals(12, $product->stock);

        // Cek pergerakan stok (inventory_movements)
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'sale',
            'quantity' => 3.00,
            'stock_before' => 15.00,
            'stock_after' => 12.00,
        ]);

        // Cek sale_items mencatat hak mitra (3 * 4000 = 12000) dan komisi toko (3 * 1000 = 3000)
        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'quantity' => 3.00,
            'consignment_cost' => 12000.00,
            'commission_amount' => 3000.00,
            'settlement_status' => 'unsettled',
        ]);
    }

    /**
     * Test data mitra konsinyasi menampilkan informasi ringkas (jumlah jenis barang dan status).
     */
    public function test_consignor_table_displays_concise_info(): void
    {
        Product::create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'KNS-0101',
            'name' => 'Donat Kentang',
            'selling_price' => 6000,
            'purchase_price' => 0.00,
            'stock' => 25,
            'is_active' => true,
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'consignment_cost_price' => 5000,
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/consignments/consignors');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $consignorData = $data[0];
        $this->assertEquals($this->consignor->name, $consignorData['name']);
        $this->assertEquals(1, $consignorData['products_count']);
        $this->assertEquals(25, $consignorData['total_stock_units']);
        $this->assertTrue($consignorData['is_active']);
    }

    /**
     * Test penyesuaian stok konsinyasi: Tambah Titipan dan Retur ke Mitra sinkron ke Kartu Stok.
     */
    public function test_consignment_stock_adjustment_in_and_return(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'unit_id' => $this->unit->id,
            'sku' => 'KNS-0102',
            'name' => 'Keripik Tempe Renyah',
            'selling_price' => 10000,
            'purchase_price' => 0.00,
            'stock' => 10,
            'is_active' => true,
            'is_consignment' => true,
            'consignor_id' => $this->consignor->id,
            'consignment_cost_price' => 8000,
        ]);

        // 1. Tambah Titipan (+5)
        $addResponse = $this->actingAs($this->admin)->postJson("/api/v1/consignments/products/{$product->id}/adjust-stock", [
            'type' => 'in',
            'quantity' => 5,
            'notes' => 'Tambahan titipan dari mitra',
        ]);

        $addResponse->assertOk();
        $addResponse->assertJsonPath('success', true);
        $product->refresh();
        $this->assertEquals(15, $product->stock);

        // Cek pencatatan Kartu Stok (inventory_movements) untuk Tambah Titipan
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'stock_in',
            'quantity' => 5.00,
            'stock_before' => 10.00,
            'stock_after' => 15.00,
            'reference_type' => 'consignment_stock_in',
        ]);

        // 2. Retur ke Mitra (-3)
        $returnResponse = $this->actingAs($this->admin)->postJson("/api/v1/consignments/products/{$product->id}/adjust-stock", [
            'type' => 'return',
            'quantity' => 3,
            'notes' => 'Retur barang tidak laku ke mitra',
        ]);

        $returnResponse->assertOk();
        $returnResponse->assertJsonPath('success', true);
        $product->refresh();
        $this->assertEquals(12, $product->stock);

        // Cek pencatatan Kartu Stok (inventory_movements) untuk Retur
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'movement_type' => 'stock_out',
            'quantity' => 3.00,
            'stock_before' => 15.00,
            'stock_after' => 12.00,
            'reference_type' => 'consignment_return',
        ]);

        // 3. Validasi retur melebihi sisa stok harus ditolak
        $invalidReturn = $this->actingAs($this->admin)->postJson("/api/v1/consignments/products/{$product->id}/adjust-stock", [
            'type' => 'return',
            'quantity' => 50,
            'notes' => 'Retur melebihi stok fisik',
        ]);

        $invalidReturn->assertStatus(422);
    }
}
