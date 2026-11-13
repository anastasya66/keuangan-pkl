<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Consignor;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Product $product;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin FinToko',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'name' => 'Staff Kasir Toko',
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Akun akuntansi standar untuk auto-journaling POS
        ChartOfAccount::create(['account_code' => '1110', 'account_name' => 'Kas Toko', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1130', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '4100', 'account_name' => 'Pendapatan Penjualan Toko', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '5110', 'account_name' => 'Beban Pokok Penjualan (HPP)', 'account_type' => 'expenses', 'normal_balance' => 'debit', 'is_active' => true]);

        $category = Category::create([
            'name' => 'Makanan Ringan',
            'slug' => 'makanan-ringan',
            'is_active' => true,
        ]);

        $unit = Unit::create([
            'name' => 'Pcs',
            'symbol' => 'pcs',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SNK-001',
            'name' => 'Keripik Singkong Balado',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'stock' => 50,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-001',
            'name' => 'PT Snack Nusantara',
            'contact_person' => 'Budi Santoso',
            'phone' => '08123456789',
            'is_active' => true,
        ]);
    }

    /**
     * Test Staff dapat mengakses modul operasional harian (POS, Produk, Inventori, Supplier, Pembelian, Konsinyasi).
     */
    public function test_staff_can_access_operational_endpoints(): void
    {
        // 1. Kasir POS Products
        $response = $this->actingAs($this->staff)->getJson('/api/v1/pos/products');
        $response->assertOk();

        // 2. Transaksi Checkout POS oleh Staff
        $checkoutResponse = $this->actingAs($this->staff)->postJson('/api/v1/pos/checkout', [
            'payment_method' => 'cash',
            'paid_amount' => 20000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                ],
            ],
        ]);
        $checkoutResponse->assertStatus(201);
        $this->product->refresh();
        $this->assertEquals(48, $this->product->stock); // 50 - 2 = 48

        // 3. Stok Barang (Inventory Report)
        $invResponse = $this->actingAs($this->staff)->getJson('/api/v1/reports/inventory');
        $invResponse->assertOk();

        // 4. Kartu Stok
        $cardResponse = $this->actingAs($this->staff)->getJson("/api/v1/inventory/stock-card/{$this->product->id}");
        $cardResponse->assertOk();

        // 5. Data Supplier (Read-only list)
        $supResponse = $this->actingAs($this->staff)->getJson('/api/v1/suppliers');
        $supResponse->assertOk();

        // 6. Data Pembelian (Read-only list & detail)
        $purchaseResponse = $this->actingAs($this->staff)->getJson('/api/v1/purchases');
        $purchaseResponse->assertOk();

        // 7. Modul Konsinyasi
        $consignmentResponse = $this->actingAs($this->staff)->getJson('/api/v1/consignments/summary');
        $consignmentResponse->assertOk();

        // 8. Dashboard Summary Staff
        $dashResponse = $this->actingAs($this->staff)->getJson('/api/v1/dashboard/financial-summary?period=today');
        $dashResponse->assertOk();
    }

    /**
     * Test Staff DILARANG mengakses endpoint sensitif / Admin-only.
     */
    public function test_staff_is_forbidden_from_admin_management_endpoints(): void
    {
        // User Management Admin
        $userResponse = $this->actingAs($this->staff)->getJson('/api/v1/admin/users');
        $userResponse->assertStatus(403);

        // System Settings Admin
        $settingsResponse = $this->actingAs($this->staff)->getJson('/api/v1/admin/settings');
        $settingsResponse->assertStatus(403);

        // Database Backup Admin
        $backupResponse = $this->actingAs($this->staff)->getJson('/api/v1/admin/backups');
        $backupResponse->assertStatus(403);
    }

    /**
     * Test aksi sensitif seperti pembatalan penjualan kasir dan konfirmasi penerimaan PO dibatasi hanya untuk Admin.
     */
    public function test_sensitive_actions_restricted_to_admin(): void
    {
        $sale = Sale::create([
            'transaction_number' => 'POS-20261113-0099',
            'cashier_id' => $this->staff->id,
            'sale_date' => now()->toDateString(),
            'subtotal' => 10000,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'cogs_total' => 5000,
            'status' => 'completed',
        ]);

        // Staff mencoba membatalkan penjualan kasir -> harus 403
        $cancelResponse = $this->actingAs($this->staff)->postJson("/api/v1/pos/sales/{$sale->id}/cancel", [
            'reason' => 'Salah input oleh kasir',
        ]);
        $cancelResponse->assertStatus(403);

        // Admin membatalkan penjualan kasir -> sukses
        $adminCancel = $this->actingAs($this->admin)->postJson("/api/v1/pos/sales/{$sale->id}/cancel", [
            'reason' => 'Dibatalkan oleh manajer',
        ]);
        $adminCancel->assertOk();
    }
}
