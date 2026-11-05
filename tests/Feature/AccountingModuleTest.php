<?php

namespace Tests\Feature;

use App\Models\AccountingMapping;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\FinancialTransaction;
use App\Models\InventoryMovement;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\TransactionCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected string $adminToken;
    protected string $staffToken;
    protected ChartOfAccount $cashAccount;
    protected ChartOfAccount $revenueAccount;
    protected ChartOfAccount $expenseAccount;
    protected ChartOfAccount $inventoryAccount;
    protected ChartOfAccount $inventoryLossAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->adminToken = $this->admin->createToken('admin-test')->plainTextToken;

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
        $this->staffToken = $this->staff->createToken('staff-test')->plainTextToken;

        // Siapkan akun standar
        $this->cashAccount = ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Kas Toko',
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

        $this->inventoryAccount = ChartOfAccount::create([
            'account_code' => '1130',
            'account_name' => 'Persediaan Barang Dagang',
            'account_type' => 'assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->inventoryLossAccount = ChartOfAccount::create([
            'account_code' => '5200',
            'account_name' => 'Beban Kerugian Persediaan',
            'account_type' => 'expenses',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        // Mapping konfigurasi
        AccountingMapping::create([
            'mapping_code' => 'cash_default',
            'name' => 'Kas Utama',
            'account_id' => $this->cashAccount->id,
        ]);
        AccountingMapping::create([
            'mapping_code' => 'revenue_default',
            'name' => 'Pendapatan Utama',
            'account_id' => $this->revenueAccount->id,
        ]);
        AccountingMapping::create([
            'mapping_code' => 'expense_default',
            'name' => 'Beban Utama',
            'account_id' => $this->expenseAccount->id,
        ]);
        AccountingMapping::create([
            'mapping_code' => 'inventory_asset',
            'name' => 'Persediaan',
            'account_id' => $this->inventoryAccount->id,
        ]);
        AccountingMapping::create([
            'mapping_code' => 'inventory_loss',
            'name' => 'Kerugian Persediaan',
            'account_id' => $this->inventoryLossAccount->id,
        ]);
    }

    /**
     * 1. Membuat akun baru oleh admin.
     */
    public function test_admin_can_create_chart_of_account(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/chart-of-accounts', [
                'account_code' => '1120',
                'account_name' => 'Bank BCA Operasional',
                'account_type' => 'assets',
                'description' => 'Rekening operasional toko',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.account_code', '1120')
            ->assertJsonPath('data.normal_balance', 'debit');

        $this->assertDatabaseHas('chart_of_accounts', [
            'account_code' => '1120',
            'account_name' => 'Bank BCA Operasional',
            'normal_balance' => 'debit',
        ]);
    }

    /**
     * 2. Validasi kode akun unik.
     */
    public function test_account_code_must_be_unique(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/chart-of-accounts', [
                'account_code' => '1110', // Sudah ada
                'account_name' => 'Kas Duplikat',
                'account_type' => 'assets',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account_code']);
    }

    /**
     * 3. Menonaktifkan akun.
     */
    public function test_admin_can_toggle_account_active_status(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->patchJson("/api/v1/chart-of-accounts/{$this->cashAccount->id}/status", [
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($this->cashAccount->fresh()->is_active);
    }

    /**
     * 4. Mencegah hapus akun yang sudah digunakan dalam jurnal.
     */
    public function test_cannot_delete_account_used_in_journal(): void
    {
        // Buat jurnal yang menggunakan kasAccount
        $journal = JournalEntry::create([
            'journal_number' => 'JRN-TEST-001',
            'journal_date' => now()->toDateString(),
            'description' => 'Test Jurnal',
            'status' => 'posted',
            'total_debit' => 100000,
            'total_credit' => 100000,
            'created_by' => $this->admin->id,
        ]);

        $journal->details()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => 100000,
            'credit' => 0,
        ]);
        $journal->details()->create([
            'account_id' => $this->revenueAccount->id,
            'debit' => 0,
            'credit' => 100000,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->deleteJson("/api/v1/chart-of-accounts/{$this->cashAccount->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['account']);

        $this->assertDatabaseHas('chart_of_accounts', ['id' => $this->cashAccount->id]);
    }

    /**
     * 5. Membuat jurnal seimbang (draft).
     */
    public function test_can_create_balanced_journal_draft(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/journals', [
                'journal_date' => '2026-11-05',
                'description' => 'Penerimaan modal tunai tambahan',
                'items' => [
                    [
                        'account_id' => $this->cashAccount->id,
                        'debit' => 2000000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $this->revenueAccount->id,
                        'debit' => 0,
                        'credit' => 2000000,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total_debit', 2000000)
            ->assertJsonPath('data.total_credit', 2000000);

        $this->assertDatabaseHas('journal_entries', [
            'description' => 'Penerimaan modal tunai tambahan',
            'status' => 'draft',
        ]);
    }

    /**
     * 6. Menolak jurnal tidak seimbang (debit != credit).
     */
    public function test_rejects_unbalanced_journal(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/journals', [
                'journal_date' => '2026-11-05',
                'description' => 'Jurnal tidak seimbang',
                'items' => [
                    [
                        'account_id' => $this->cashAccount->id,
                        'debit' => 500000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $this->revenueAccount->id,
                        'debit' => 0,
                        'credit' => 450000, // Selisih 50.000
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /**
     * 7. Menolak jurnal dengan akun nonaktif.
     */
    public function test_rejects_journal_with_inactive_account(): void
    {
        $this->revenueAccount->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/journals', [
                'journal_date' => '2026-11-05',
                'description' => 'Jurnal dengan akun nonaktif',
                'items' => [
                    [
                        'account_id' => $this->cashAccount->id,
                        'debit' => 100000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $this->revenueAccount->id,
                        'debit' => 0,
                        'credit' => 100000,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /**
     * 8. Melakukan posting jurnal draft.
     */
    public function test_admin_can_post_journal(): void
    {
        $postingService = app(JournalPostingService::class);
        $journal = $postingService->createDraft([
            'journal_date' => '2026-11-05',
            'description' => 'Jurnal untuk diposting',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 150000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 150000],
            ],
        ], $this->admin->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/journals/{$journal->id}/post");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.posted_by.id', $this->admin->id);

        $this->assertEquals('posted', $journal->fresh()->status);
        $this->assertNotNull($journal->fresh()->posted_at);
    }

    /**
     * 9. Mencegah pengeditan jurnal yang sudah diposting.
     */
    public function test_cannot_update_posted_journal(): void
    {
        $postingService = app(JournalPostingService::class);
        $journal = $postingService->createDraft([
            'journal_date' => '2026-11-05',
            'description' => 'Draft awal',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
        ], $this->admin->id);

        $postingService->postJournal($journal, $this->admin->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->putJson("/api/v1/journals/{$journal->id}", [
                'description' => 'Mencoba ubah posted journal',
                'items' => [
                    ['account_id' => $this->cashAccount->id, 'debit' => 200000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 200000],
                ],
            ]);

        $response->assertStatus(403); // Otorisasi policy menolak pengubahan non-draft
    }

    /**
     * 10. Melakukan reversal jurnal yang sudah diposting.
     */
    public function test_admin_can_reverse_posted_journal(): void
    {
        $postingService = app(JournalPostingService::class);
        $journal = $postingService->createDraft([
            'journal_date' => '2026-11-05',
            'description' => 'Transaksi salah input untuk dibalik',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 300000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 300000],
            ],
        ], $this->admin->id);

        $postingService->postJournal($journal, $this->admin->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/journals/{$journal->id}/reverse", [
                'reason' => 'Koreksi salah nominal',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.original_journal.status', 'reversed')
            ->assertJsonPath('data.reversal_journal.status', 'posted');

        $this->assertEquals('reversed', $journal->fresh()->status);
        $this->assertNotNull($journal->fresh()->reversal_journal_id);

        // Periksa jurnal pembalik
        $reversal = JournalEntry::find($journal->fresh()->reversal_journal_id);
        $this->assertEquals('posted', $reversal->status);
        $this->assertEquals(300000, $reversal->total_debit);
        $this->assertEquals(300000, $reversal->total_credit);

        // Periksa bahwa detail baris telah dibalik
        $cashDetail = $reversal->details()->where('account_id', $this->cashAccount->id)->first();
        $this->assertEquals(0, (float) $cashDetail->debit);
        $this->assertEquals(300000, (float) $cashDetail->credit);
    }

    /**
     * 11. Menghitung saldo buku besar (opening balance & running balance).
     */
    public function test_calculates_general_ledger_running_balance(): void
    {
        $postingService = app(JournalPostingService::class);

        // Transaksi 1: Kas masuk 500.000 pada 2026-11-01
        $j1 = $postingService->createDraft([
            'journal_date' => '2026-11-01',
            'description' => 'Pemasukan 1',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 500000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 500000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($j1, $this->admin->id);

        // Transaksi 2: Kas keluar 100.000 pada 2026-11-03
        $j2 = $postingService->createDraft([
            'journal_date' => '2026-11-03',
            'description' => 'Pengeluaran operasional',
            'items' => [
                ['account_id' => $this->expenseAccount->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $this->cashAccount->id, 'debit' => 0, 'credit' => 100000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($j2, $this->admin->id);

        // Query buku besar akun Kas (1110) dari 2026-11-02 s/d 2026-11-05
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson("/api/v1/general-ledger/{$this->cashAccount->id}?start_date=2026-11-02&end_date=2026-11-05");

        $response->assertStatus(200)
            ->assertJsonPath('data.account.account_code', '1110')
            ->assertJsonPath('data.opening_balance', 500000) // Saldo awal sebelum 2026-11-02
            ->assertJsonPath('data.total_credit', 100000)
            ->assertJsonPath('data.closing_balance', 400000); // 500.000 - 100.000 = 400.000
    }

    /**
     * 12. Menghasilkan neraca saldo dan verifikasi is_balanced.
     */
    public function test_generates_trial_balance_and_checks_equilibrium(): void
    {
        $postingService = app(JournalPostingService::class);

        $j = $postingService->createDraft([
            'journal_date' => '2026-11-05',
            'description' => 'Transaksi penjualan',
            'items' => [
                ['account_id' => $this->cashAccount->id, 'debit' => 750000, 'credit' => 0],
                ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 750000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($j, $this->admin->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/trial-balance?as_of_date=2026-11-05');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_balanced', true)
            ->assertJsonPath('data.total_debit', 750000)
            ->assertJsonPath('data.total_credit', 750000);
    }

    /**
     * 13. Mencegah jurnal ganda dari transaksi sumber yang sama.
     */
    public function test_prevents_duplicate_journal_from_same_source(): void
    {
        $category = TransactionCategory::create([
            'name' => 'Penjualan Ritel',
            'type' => 'income',
            'is_active' => true,
        ]);

        $trx = FinancialTransaction::create([
            'transaction_code' => 'TRX-TEST-001',
            'transaction_type' => 'income',
            'category_id' => $category->id,
            'amount' => 250000,
            'transaction_date' => '2026-11-05',
            'description' => 'Pemasukan Kasir',
            'status' => 'completed',
            'created_by' => $this->admin->id,
        ]);

        $accountingService = app(AccountingService::class);

        // Buat jurnal pertama
        $journal1 = $accountingService->createJournalForFinancialTransaction($trx, $this->admin->id);
        $this->assertNotNull($journal1);

        // Coba sinkronisasi lagi via endpoint API
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/accounting/sync-transaction/{$trx->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['transaction_id']);
    }

    /**
     * 14. Hak akses admin vs staff pada jurnal dan akun.
     */
    public function test_authorization_admin_vs_staff(): void
    {
        // Staff tidak bisa membuat akun COA (harus 403)
        $respCreateAcc = $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/chart-of-accounts', [
                'account_code' => '1199',
                'account_name' => 'Akun Staff',
                'account_type' => 'assets',
            ]);
        $respCreateAcc->assertStatus(403);

        // Staff bisa melihat akun COA
        $respViewAcc = $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/v1/chart-of-accounts');
        $respViewAcc->assertStatus(200);

        // Staff bisa membuat jurnal draft
        $respCreateJrn = $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/journals', [
                'journal_date' => '2026-11-05',
                'description' => 'Draft oleh staff',
                'items' => [
                    ['account_id' => $this->cashAccount->id, 'debit' => 50000, 'credit' => 0],
                    ['account_id' => $this->revenueAccount->id, 'debit' => 0, 'credit' => 50000],
                ],
            ]);
        $respCreateJrn->assertStatus(201);
        $journalId = $respCreateJrn->json('data.id');

        // Staff tidak bisa memposting jurnal (harus 403)
        $respPost = $this->actingAs($this->staff, 'sanctum')
            ->postJson("/api/v1/journals/{$journalId}/post");
        $respPost->assertStatus(403);

        // Admin bisa memposting jurnal
        $respAdminPost = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/journals/{$journalId}/post");
        $respAdminPost->assertStatus(200);
    }

    /**
     * 15. Integrasi Inventory: jurnal otomatis saat penyesuaian kehilangan stok (adjustment_out).
     */
    public function test_inventory_adjustment_creates_accounting_journal(): void
    {
        $cat = Category::create(['name' => 'Sembako']);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);

        $product = Product::create([
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'sku' => 'BERAS-01',
            'name' => 'Beras Pandan Wangi',
            'purchase_price' => 15000,
            'selling_price' => 18000,
            'stock' => 100,
            'is_active' => true,
        ]);

        $movement = InventoryMovement::create([
            'movement_code' => 'MOV-TEST-ADJ-01',
            'product_id' => $product->id,
            'movement_type' => 'adjustment_out',
            'quantity' => 2, // Kehilangan 2 kg @ 15.000 = 30.000
            'stock_before' => 100,
            'stock_after' => 98,
            'adjustment_reason' => 'Kemasan sobek bocor',
            'movement_date' => '2026-11-05',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson("/api/v1/accounting/sync-inventory/{$movement->id}");

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'posted')
            ->assertJsonPath('data.total_debit', 30000)
            ->assertJsonPath('data.total_credit', 30000);

        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'inventory_movement',
            'source_id' => $movement->id,
            'total_debit' => 30000,
        ]);
    }

    /**
     * 16. Direct alias /api/ without /v1/ works seamlessly.
     */
    public function test_direct_api_prefix_without_v1_works_for_accounting(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/chart-of-accounts');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $trialResp = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/trial-balance');

        $trialResp->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
}
