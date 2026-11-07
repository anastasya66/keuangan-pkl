<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected string $adminToken;
    protected string $staffToken;
    protected Supplier $supplier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->adminToken = $this->admin->createToken('admin-test')->plainTextToken;

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->staffToken = $this->staff->createToken('staff-test')->plainTextToken;

        // Siapkan akun akuntansi
        ChartOfAccount::create(['account_code' => '1110', 'account_name' => 'Kas Toko', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1120', 'account_name' => 'Bank Operasional', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1130', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '2110', 'account_name' => 'Utang Usaha / Supplier', 'account_type' => 'liabilities', 'normal_balance' => 'credit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '4100', 'account_name' => 'Pendapatan Penjualan Toko', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '5110', 'account_name' => 'Beban Pokok Penjualan (HPP)', 'account_type' => 'expenses', 'normal_balance' => 'debit', 'is_active' => true]);

        $this->supplier = Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'CV Sumber Makmur',
            'phone' => '0812345678',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Botol', 'symbol' => 'btl', 'is_active' => true]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'MNM-001',
            'barcode' => '8991234567890',
            'name' => 'Teh Botol 450ml',
            'purchase_price' => 5000.00,
            'selling_price' => 7000.00,
            'average_cost' => 5000.00,
            'stock' => 10.00,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_purchase_draft_without_modifying_stock(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now()->toDateString(),
                'status' => 'draft',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 20,
                        'purchase_price' => 5500,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.grand_total', '110000.00');

        // Stok tidak boleh bertambah saat masih draft
        $this->product->refresh();
        $this->assertEquals(10.00, (float) $this->product->stock);
        $this->assertEquals(5000.00, (float) $this->product->average_cost);

        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $this->supplier->id,
            'status' => 'draft',
            'grand_total' => 110000.00,
        ]);
    }

    public function test_receiving_purchase_increases_stock_recalculates_average_cost_and_creates_journal(): void
    {
        // Stok awal: 10 @ Rp 5.000 = Rp 50.000
        // Pembelian baru: 10 @ Rp 7.000 = Rp 70.000
        // Total valuasi baru: 50.000 + 70.000 = Rp 120.000 / 20 unit = HPP Rp 6.000
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now()->toDateString(),
                'status' => 'draft',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'purchase_price' => 7000,
                    ],
                ],
            ]);

        $purchaseId = $createResponse->json('data.id');

        // Terima barang pembelian
        $receiveResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/purchases/{$purchaseId}/receive");

        $receiveResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'received');

        // Verifikasi stok dan Moving Weighted Average cost
        $this->product->refresh();
        $this->assertEquals(20.00, (float) $this->product->stock);
        $this->assertEquals(6000.00, (float) $this->product->average_cost);
        $this->assertEquals(7000.00, (float) $this->product->purchase_price);

        // Verifikasi mutasi kartu stok
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 10.00,
            'stock_before' => 10.00,
            'stock_after' => 20.00,
        ]);

        // Verifikasi jurnal akuntansi (Persediaan vs Kas)
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'purchase',
            'source_id' => $purchaseId,
            'status' => 'posted',
            'total_debit' => 70000.00,
            'total_credit' => 70000.00,
        ]);
    }

    public function test_tempo_purchase_creates_accounts_payable_and_tracks_debt_payments(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now()->toDateString(),
                'status' => 'received', // Langsung terima
                'payment_method' => 'tempo',
                'paid_amount' => 20000, // Uang muka 20.000 dari total 50.000
                'due_date' => now()->addDays(30)->toDateString(),
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 10,
                        'purchase_price' => 5000,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', 'tempo')
            ->assertJsonPath('data.payment_status', 'partial')
            ->assertJsonPath('data.grand_total', '50000.00')
            ->assertJsonPath('data.paid_amount', '20000.00');

        $purchaseId = $response->json('data.id');

        // Bayar sisa utang 30.000
        $payResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/purchases/{$purchaseId}/pay-debt", [
                'amount' => 30000,
                'payment_method' => 'cash',
                'notes' => 'Pelunasan sisa tagihan tempo',
            ]);

        $payResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.purchase.payment_status', 'paid');

        // Verifikasi jurnal pelunasan utang
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'supplier_debt_payment',
            'total_debit' => 30000.00,
            'total_credit' => 30000.00,
        ]);
    }

    public function test_cancelling_received_purchase_restores_stock_and_reverses_journal(): void
    {
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'status' => 'received',
                'payment_method' => 'cash',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'purchase_price' => 5000,
                    ],
                ],
            ]);

        $purchaseId = $createResponse->json('data.id');
        $this->product->refresh();
        $this->assertEquals(15.00, (float) $this->product->stock);

        // Batalkan pembelian
        $cancelResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/purchases/{$purchaseId}/cancel", [
                'reason' => 'Barang cacat retur total',
            ]);

        $cancelResponse->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        // Stok kembali ke 10
        $this->product->refresh();
        $this->assertEquals(10.00, (float) $this->product->stock);

        // Jurnal awal di-reverse
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'purchase',
            'source_id' => $purchaseId,
            'status' => 'reversed',
        ]);
    }

    public function test_staff_cannot_create_purchase(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/purchases', [
                'supplier_id' => $this->supplier->id,
                'items' => [
                    ['product_id' => $this->product->id, 'quantity' => 5, 'purchase_price' => 5000],
                ],
            ]);

        $response->assertForbidden();
    }
}
