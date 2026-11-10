<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSaleModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected string $adminToken;
    protected string $staffToken;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->adminToken = $this->admin->createToken('admin-test')->plainTextToken;

        $this->staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        $this->staffToken = $this->staff->createToken('staff-test')->plainTextToken;

        // Akun akuntansi
        ChartOfAccount::create(['account_code' => '1110', 'account_name' => 'Kas Toko', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1120', 'account_name' => 'Bank Operasional', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '1130', 'account_name' => 'Persediaan Barang Dagang', 'account_type' => 'assets', 'normal_balance' => 'debit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '4100', 'account_name' => 'Pendapatan Penjualan Toko', 'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_active' => true]);
        ChartOfAccount::create(['account_code' => '5110', 'account_name' => 'Beban Pokok Penjualan (HPP)', 'account_type' => 'expenses', 'normal_balance' => 'debit', 'is_active' => true]);

        $category = Category::create(['name' => 'Makanan Ringan', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pcs', 'symbol' => 'pcs', 'is_active' => true]);

        $this->productA = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SNK-001',
            'barcode' => '8991001001001',
            'name' => 'Keripik Singkong Balado',
            'purchase_price' => 8000.00,
            'average_cost' => 8000.00,
            'selling_price' => 12000.00,
            'stock' => 50.00,
            'is_active' => true,
        ]);

        $this->productB = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SNK-002',
            'barcode' => '8991001002002',
            'name' => 'Biskuit Cokelat',
            'purchase_price' => 5000.00,
            'average_cost' => 5000.00,
            'selling_price' => 8000.00,
            'stock' => 5.00,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_search_products_and_scan_barcode(): void
    {
        // 1. Search by keyword
        $searchResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->getJson('/api/v1/pos/products?search=Singkong');

        $searchResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Keripik Singkong Balado');

        // 2. Barcode lookup
        $barcodeResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->getJson('/api/v1/pos/products/barcode/8991001001001');

        $barcodeResponse->assertOk()
            ->assertJsonPath('data.sku', 'SNK-001');
    }

    public function test_cashier_can_checkout_sale_updates_stock_captures_cogs_and_posts_journal(): void
    {
        // Beli: 2 x Keripik Singkong @ 12.000 = 24.000
        // Bayar: 50.000 (Tunai), Kembalian: 26.000
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'customer_name' => 'Ibu Siti',
                'payment_method' => 'cash',
                'paid_amount' => 50000,
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertEquals(24000.00, (float) $response->json('data.grand_total'));
        $this->assertEquals(50000.00, (float) $response->json('data.paid_amount'));
        $this->assertEquals(26000.00, (float) $response->json('data.change_amount'));
        $this->assertEquals(16000.00, (float) $response->json('data.cogs_total'));

        $saleId = $response->json('data.id');

        // 1. Verifikasi stok produk berkurang dari 50 menjadi 48
        $this->productA->refresh();
        $this->assertEquals(48.00, (float) $this->productA->stock);

        // 2. Verifikasi mutasi barang keluar di kartu stok
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->productA->id,
            'movement_type' => 'sale',
            'quantity' => 2.00,
            'stock_before' => 50.00,
            'stock_after' => 48.00,
        ]);

        // 3. Verifikasi jurnal akuntansi (Kas 24.000 vs Penjualan 24.000, HPP 16.000 vs Persediaan 16.000)
        // Total debit/kredit seimbang = 40.000
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'sale',
            'source_id' => $saleId,
            'status' => 'posted',
            'total_debit' => 40000.00,
            'total_credit' => 40000.00,
        ]);
    }

    public function test_checkout_fails_if_stock_insufficient(): void
    {
        // Stok produkB hanya 5, coba beli 10
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'payment_method' => 'cash',
                'paid_amount' => 100000,
                'items' => [
                    [
                        'product_id' => $this->productB->id,
                        'quantity' => 10,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stock']);

        // Stok tidak berubah
        $this->productB->refresh();
        $this->assertEquals(5.00, (float) $this->productB->stock);
    }

    public function test_checkout_fails_if_cash_paid_is_less_than_grand_total(): void
    {
        // Total belanja: 12.000, uang dibayar: 10.000
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'payment_method' => 'cash',
                'paid_amount' => 10000,
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['paid_amount']);
    }

    public function test_can_retrieve_receipt_data_for_printing(): void
    {
        $saleResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'payment_method' => 'cash',
                'paid_amount' => 20000,
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 1],
                ],
            ]);

        $saleId = $saleResponse->json('data.id');

        $receiptResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->getJson("/api/v1/pos/sales/{$saleId}/receipt");

        $receiptResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.name', 'Keripik Singkong Balado');

        $this->assertEquals(12000.00, (float) $receiptResponse->json('data.grand_total'));
        $this->assertEquals(20000.00, (float) $receiptResponse->json('data.paid_amount'));
        $this->assertEquals(8000.00, (float) $receiptResponse->json('data.change_amount'));
    }

    public function test_cancelling_sale_returns_stock_and_reverses_journal(): void
    {
        $saleResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'payment_method' => 'cash',
                'paid_amount' => 50000,
                'items' => [
                    ['product_id' => $this->productA->id, 'quantity' => 2],
                ],
            ]);

        $saleId = $saleResponse->json('data.id');
        $this->productA->refresh();
        $this->assertEquals(48.00, (float) $this->productA->stock);

        // Batalkan penjualan (Admin only)
        auth('sanctum')->setUser($this->admin);
        $cancelResponse = $this->withToken($this->adminToken)
            ->postJson("/api/v1/pos/sales/{$saleId}/cancel", [
                'reason' => 'Pelanggan membatalkan pesanan di kasir',
            ]);

        $cancelResponse->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        // Stok kembali ke 50
        $this->productA->refresh();
        $this->assertEquals(50.00, (float) $this->productA->stock);

        // Jurnal di-reverse
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'sale',
            'source_id' => $saleId,
            'status' => 'reversed',
        ]);
    }

    public function test_cashier_can_create_qris_payment_and_poll_status(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/payments/qris', [
                'amount' => 50000,
                'customer_name' => 'Budi Santoso',
                'items_count' => 2,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gross_amount', 50000)
            ->assertJsonPath('data.status', 'pending');

        $orderId = $response->json('data.order_id');
        $this->assertNotEmpty($orderId);
        $this->assertNotEmpty($response->json('data.qr_image_url'));

        // Cek status polling
        $statusResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->getJson("/api/v1/pos/payments/{$orderId}/status");

        $statusResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_id', $orderId)
            ->assertJsonPath('data.is_pending', true)
            ->assertJsonPath('data.is_paid', false);
    }

    public function test_cashier_can_simulate_qris_payment_and_checkout_sale(): void
    {
        // 1. Buat QRIS
        $qrisResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/payments/qris', [
                'amount' => 12000,
                'customer_name' => 'Pak Joko',
            ]);

        $orderId = $qrisResponse->json('data.order_id');

        // 2. Simulasi pembayaran sukses
        $simResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson("/api/v1/pos/payments/{$orderId}/simulate-pay");

        $simResponse->assertOk()
            ->assertJsonPath('data.is_paid', true)
            ->assertJsonPath('data.status', 'settlement');

        // 3. Checkout dengan order_id yang sudah lunas
        $checkoutResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'customer_name' => 'Pak Joko',
                'payment_method' => 'qris',
                'paid_amount' => 12000,
                'order_id' => $orderId,
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $checkoutResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_method', 'qris');

        // Pastikan transaksi payment terhubung ke sale_id
        $saleId = $checkoutResponse->json('data.id');
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $orderId,
            'sale_id' => $saleId,
            'status' => 'settlement',
        ]);
    }

    public function test_checkout_fails_if_qris_not_yet_paid(): void
    {
        // Buat QRIS tapi TIDAK dibayar (masih pending)
        $qrisResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/payments/qris', [
                'amount' => 12000,
            ]);

        $orderId = $qrisResponse->json('data.order_id');

        $checkoutResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/checkout', [
                'customer_name' => 'Pak Joko',
                'payment_method' => 'qris',
                'paid_amount' => 12000,
                'order_id' => $orderId,
                'items' => [
                    [
                        'product_id' => $this->productA->id,
                        'quantity' => 1,
                    ],
                ],
            ]);

        $checkoutResponse->assertStatus(422)
            ->assertJsonValidationErrors(['payment']);
    }

    public function test_cashier_can_retrieve_bank_accounts(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->getJson('/api/v1/pos/bank-accounts');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['bank', 'account_number', 'account_name']]]);
    }

    public function test_can_process_midtrans_webhook_for_qris(): void
    {
        // 1. Buat transaksi QRIS di database
        $qrisResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/pos/payments/qris', [
                'amount' => 24000,
            ]);

        $orderId = $qrisResponse->json('data.order_id');

        // 2. Kirim Webhook Midtrans dari gateway
        $webhookResponse = $this->postJson('/api/v1/webhooks/payment/midtrans', [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '24000.00',
            'payment_type' => 'qris',
            'transaction_status' => 'settlement',
            'settlement_time' => now()->toDateTimeString(),
        ]);

        $webhookResponse->assertOk()
            ->assertJsonPath('success', true);

        // Verifikasi payment transaction terupdate
        $this->assertDatabaseHas('payment_transactions', [
            'order_id' => $orderId,
            'status' => 'settlement',
        ]);

        // Verifikasi webhook ter-audit
        $this->assertDatabaseHas('payment_webhook_events', [
            'order_id' => $orderId,
            'processed' => true,
        ]);
    }
}
