<?php

namespace Tests\Feature;

use App\Models\FinancialTransaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff1;
    private User $staff2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staff1 = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->staff2 = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Categories Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_add_transaction_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transaction-categories', [
            'name' => 'Pendapatan Bonus',
            'type' => 'income',
            'description' => 'Bonus omset bulanan toko',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Pendapatan Bonus')
            ->assertJsonPath('data.type', 'income');

        $this->assertDatabaseHas('transaction_categories', [
            'name' => 'Pendapatan Bonus',
            'type' => 'income',
        ]);
    }

    public function test_admin_can_edit_transaction_category(): void
    {
        $category = TransactionCategory::create([
            'name' => 'Biaya Gudang',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/transaction-categories/{$category->id}", [
            'name' => 'Biaya Sewa Gudang',
            'type' => 'expense',
            'description' => 'Sewa gudang penyimpanan stok beras',
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Biaya Sewa Gudang');

        $this->assertDatabaseHas('transaction_categories', [
            'id' => $category->id,
            'name' => 'Biaya Sewa Gudang',
        ]);
    }

    public function test_staff_cannot_create_or_modify_transaction_category(): void
    {
        $category = TransactionCategory::create([
            'name' => 'Biaya Kebersihan',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $this->actingAs($this->staff1, 'sanctum')
            ->postJson('/api/v1/transaction-categories', [
                'name' => 'Uji Coba',
                'type' => 'income',
            ])
            ->assertForbidden();

        $this->actingAs($this->staff1, 'sanctum')
            ->putJson("/api/v1/transaction-categories/{$category->id}", [
                'name' => 'Ubah Nama',
            ])
            ->assertForbidden();

        $this->actingAs($this->staff1, 'sanctum')
            ->deleteJson("/api/v1/transaction-categories/{$category->id}")
            ->assertForbidden();
    }

    public function test_duplicate_category_name_in_same_type_is_rejected(): void
    {
        TransactionCategory::create([
            'name' => 'Penjualan Toko',
            'type' => 'income',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transaction-categories', [
            'name' => 'Penjualan Toko',
            'type' => 'income',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_same_category_name_in_different_type_is_allowed(): void
    {
        TransactionCategory::create([
            'name' => 'Lain-Lain',
            'type' => 'income',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transaction-categories', [
            'name' => 'Lain-Lain',
            'type' => 'expense',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'expense');
    }

    public function test_cannot_delete_category_if_has_transactions(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Bahan Baku',
            'type' => 'expense',
            'is_active' => true,
        ]);

        FinancialTransaction::create([
            'transaction_code' => 'TRX-20261105-0001',
            'transaction_type' => 'expense',
            'category_id' => $cat->id,
            'amount' => 50000,
            'transaction_date' => '2026-11-05',
            'created_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/transaction-categories/{$cat->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('transaction_categories', ['id' => $cat->id, 'deleted_at' => null]);
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Transactions Tests
    |--------------------------------------------------------------------------
    */

    public function test_income_transaction_successfully_created(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Penjualan Barang',
            'type' => 'income',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->staff1, 'sanctum')->postJson('/api/v1/transactions', [
            'transaction_type' => 'income',
            'category_id' => $cat->id,
            'amount' => 150000,
            'transaction_date' => '2026-11-05',
            'description' => 'Penjualan sembako shift siang',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_type', 'income')
            ->assertJsonPath('data.amount', '150000.00')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.created_by.id', $this->staff1->id);

        $this->assertDatabaseHas('financial_transactions', [
            'amount' => 150000.00,
            'created_by' => $this->staff1->id,
        ]);
    }

    public function test_dedicated_income_endpoint_creates_income_transaction(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Jasa Titip',
            'type' => 'income',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/incomes', [
            'category_id' => $cat->id,
            'amount' => 25000,
            'transaction_date' => '2026-11-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.transaction_type', 'income')
            ->assertJsonPath('data.amount', '25000.00');
    }

    public function test_dedicated_expense_endpoint_creates_expense_transaction(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Biaya Listrik Toko',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/expenses', [
            'category_id' => $cat->id,
            'amount' => 120000,
            'transaction_date' => '2026-11-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.transaction_type', 'expense')
            ->assertJsonPath('data.amount', '120000.00');
    }

    public function test_reject_negative_and_zero_nominal(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Penjualan',
            'type' => 'income',
            'is_active' => true,
        ]);

        // Negative
        $response1 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transactions', [
            'transaction_type' => 'income',
            'category_id' => $cat->id,
            'amount' => -1000,
            'transaction_date' => '2026-11-05',
        ]);
        $response1->assertStatus(422)->assertJsonValidationErrors(['amount']);

        // Zero
        $response2 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transactions', [
            'transaction_type' => 'income',
            'category_id' => $cat->id,
            'amount' => 0,
            'transaction_date' => '2026-11-05',
        ]);
        $response2->assertStatus(422)->assertJsonValidationErrors(['amount']);
    }

    public function test_cannot_use_inactive_category_for_transaction(): void
    {
        $inactiveCat = TransactionCategory::create([
            'name' => 'Kategori Lama',
            'type' => 'income',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transactions', [
            'transaction_type' => 'income',
            'category_id' => $inactiveCat->id,
            'amount' => 50000,
            'transaction_date' => '2026-11-05',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_cannot_use_mismatched_category_type(): void
    {
        $expenseCat = TransactionCategory::create([
            'name' => 'Beli Bensin',
            'type' => 'expense',
            'is_active' => true,
        ]);

        // Trying to use expense category for income transaction
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/transactions', [
            'transaction_type' => 'income',
            'category_id' => $expenseCat->id,
            'amount' => 50000,
            'transaction_date' => '2026-11-05',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/v1/transactions')->assertUnauthorized();
        $this->postJson('/api/v1/transactions', [])->assertUnauthorized();
    }

    public function test_staff_cannot_update_other_users_transaction(): void
    {
        $cat = TransactionCategory::create([
            'name' => 'Operasional',
            'type' => 'expense',
            'is_active' => true,
        ]);

        $tx = FinancialTransaction::create([
            'transaction_code' => 'TRX-STAFF1-01',
            'transaction_type' => 'expense',
            'category_id' => $cat->id,
            'amount' => 30000,
            'transaction_date' => '2026-11-05',
            'created_by' => $this->staff1->id,
            'status' => 'completed',
        ]);

        // Staff2 tries to update Staff1's transaction
        $this->actingAs($this->staff2, 'sanctum')
            ->putJson("/api/v1/transactions/{$tx->id}", [
                'category_id' => $cat->id,
                'amount' => 35000,
                'transaction_date' => '2026-11-05',
            ])
            ->assertForbidden();

        // Staff1 can update their own transaction
        $this->actingAs($this->staff1, 'sanctum')
            ->putJson("/api/v1/transactions/{$tx->id}", [
                'category_id' => $cat->id,
                'amount' => 35000,
                'transaction_date' => '2026-11-05',
            ])
            ->assertOk();
    }

    public function test_transaction_can_be_cancelled_and_excluded_from_active_balance(): void
    {
        $catIncome = TransactionCategory::create(['name' => 'Jual', 'type' => 'income', 'is_active' => true]);
        $catExpense = TransactionCategory::create(['name' => 'Beli', 'type' => 'expense', 'is_active' => true]);

        // Active Income: 100.000
        FinancialTransaction::create([
            'transaction_code' => 'TRX-INC-100',
            'transaction_type' => 'income',
            'category_id' => $catIncome->id,
            'amount' => 100000,
            'transaction_date' => '2026-11-05',
            'created_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        // Active Expense: 40.000
        $expenseTx = FinancialTransaction::create([
            'transaction_code' => 'TRX-EXP-040',
            'transaction_type' => 'expense',
            'category_id' => $catExpense->id,
            'amount' => 40000,
            'transaction_date' => '2026-11-05',
            'created_by' => $this->admin->id,
            'status' => 'completed',
        ]);

        // Initial summary: 100.000 - 40.000 = 60.000 net balance
        $initRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/transactions');
        $initRes->assertOk()
            ->assertJsonPath('summary.total_income', '100000.00')
            ->assertJsonPath('summary.total_expense', '40000.00')
            ->assertJsonPath('summary.net_balance', '60000.00');

        // Cancel the 40.000 expense
        $cancelRes = $this->actingAs($this->admin, 'sanctum')->patchJson("/api/v1/transactions/{$expenseTx->id}/cancel", [
            'cancellation_reason' => 'Pengeluaran dibatalkan supplier',
        ]);
        $cancelRes->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        // New summary after cancel: expense becomes 0, net balance = 100.000
        $newRes = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/transactions');
        $newRes->assertOk()
            ->assertJsonPath('summary.total_income', '100000.00')
            ->assertJsonPath('summary.total_expense', '0.00')
            ->assertJsonPath('summary.net_balance', '100000.00');
    }

    public function test_cannot_cancel_already_cancelled_transaction(): void
    {
        $cat = TransactionCategory::create(['name' => 'Operasional', 'type' => 'expense', 'is_active' => true]);

        $tx = FinancialTransaction::create([
            'transaction_code' => 'TRX-ALREADY-CANCELLED',
            'transaction_type' => 'expense',
            'category_id' => $cat->id,
            'amount' => 10000,
            'transaction_date' => '2026-11-05',
            'created_by' => $this->admin->id,
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->patchJson("/api/v1/transactions/{$tx->id}/cancel");

        // Policy or controller rejects
        $this->assertTrue(in_array($response->status(), [403, 422]));
    }

    public function test_direct_api_prefix_without_v1_also_works(): void
    {
        // Testing /api/transactions alias
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/transactions');
        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}

