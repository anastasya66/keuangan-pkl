<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Product $product;
    private Product $inactiveProduct;

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

        $category = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-TEST-INV-1',
            'name' => 'Beras Rojo',
            'purchase_price' => 10000,
            'selling_price' => 12000,
            'stock' => 20.00,
            'is_active' => true,
        ]);

        $this->inactiveProduct = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-TEST-INACTIVE',
            'name' => 'Barang Arsip',
            'purchase_price' => 5000,
            'selling_price' => 6000,
            'stock' => 10.00,
            'is_active' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock In Tests
    |--------------------------------------------------------------------------
    */

    public function test_stock_in_increases_product_stock_and_creates_movement(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')->postJson('/api/v1/inventory/stock-in', [
            'product_id' => $this->product->id,
            'quantity' => 15,
            'movement_date' => '2026-11-05',
            'description' => 'Drop stok baru dari distributor',
            'reference_number' => 'PO-001',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.movement_type', 'stock_in')
            ->assertJsonPath('data.quantity', '15.00')
            ->assertJsonPath('data.stock_before', '20.00')
            ->assertJsonPath('data.stock_after', '35.00')
            ->assertJsonPath('data.created_by.id', $this->staff->id);

        $this->assertEquals(35.00, (float) $this->product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'movement_type' => 'stock_in',
            'quantity' => 15.00,
        ]);
    }

    public function test_stock_in_rejects_zero_and_negative_quantity(): void
    {
        // Zero
        $res1 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/stock-in', [
            'product_id' => $this->product->id,
            'quantity' => 0,
            'movement_date' => '2026-11-05',
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['quantity']);

        // Negative
        $res2 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/stock-in', [
            'product_id' => $this->product->id,
            'quantity' => -5,
            'movement_date' => '2026-11-05',
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    public function test_stock_in_rejects_inactive_or_nonexistent_product(): void
    {
        // Inactive product
        $res1 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/stock-in', [
            'product_id' => $this->inactiveProduct->id,
            'quantity' => 10,
            'movement_date' => '2026-11-05',
        ]);
        $res1->assertStatus(422)->assertJsonValidationErrors(['product_id']);

        // Nonexistent product
        $res2 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/stock-in', [
            'product_id' => 99999,
            'quantity' => 10,
            'movement_date' => '2026-11-05',
        ]);
        $res2->assertStatus(422)->assertJsonValidationErrors(['product_id']);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Out Tests
    |--------------------------------------------------------------------------
    */

    public function test_stock_out_decreases_product_stock_and_creates_movement(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')->postJson('/api/v1/inventory/stock-out', [
            'product_id' => $this->product->id,
            'quantity' => 8,
            'movement_date' => '2026-11-05',
            'description' => 'Penjualan partai besar',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.movement_type', 'stock_out')
            ->assertJsonPath('data.quantity', '8.00')
            ->assertJsonPath('data.stock_before', '20.00')
            ->assertJsonPath('data.stock_after', '12.00');

        $this->assertEquals(12.00, (float) $this->product->fresh()->stock);
    }

    public function test_stock_out_rejects_insufficient_stock(): void
    {
        // Current stock is 20, attempting to withdraw 25
        $response = $this->actingAs($this->staff, 'sanctum')->postJson('/api/v1/inventory/stock-out', [
            'product_id' => $this->product->id,
            'quantity' => 25,
            'movement_date' => '2026-11-05',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // Product stock remains 20
        $this->assertEquals(20.00, (float) $this->product->fresh()->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Adjustment Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_adjust_stock_increase(): void
    {
        // Current stock: 20 -> Actual physical stock: 25 (+5)
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/adjustments', [
            'product_id' => $this->product->id,
            'actual_stock' => 25,
            'adjustment_reason' => 'Stok fisik lebih banyak 5 karung saat stock opname',
            'movement_date' => '2026-11-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.movement_type', 'adjustment_in')
            ->assertJsonPath('data.quantity', '5.00')
            ->assertJsonPath('data.stock_before', '20.00')
            ->assertJsonPath('data.stock_after', '25.00');

        $this->assertEquals(25.00, (float) $this->product->fresh()->stock);
    }

    public function test_admin_can_adjust_stock_decrease_and_zero_stock(): void
    {
        // Current stock: 20 -> Actual physical stock: 0 (-20)
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/adjustments', [
            'product_id' => $this->product->id,
            'actual_stock' => 0,
            'adjustment_reason' => 'Barang habis total dan rusak',
            'movement_date' => '2026-11-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.movement_type', 'adjustment_out')
            ->assertJsonPath('data.quantity', '20.00')
            ->assertJsonPath('data.stock_before', '20.00')
            ->assertJsonPath('data.stock_after', '0.00');

        $this->assertEquals(0.00, (float) $this->product->fresh()->stock);
    }

    public function test_adjustment_with_same_stock_is_rejected(): void
    {
        // Current stock: 20 -> Actual physical stock: 20 (no change)
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/inventory/adjustments', [
            'product_id' => $this->product->id,
            'actual_stock' => 20,
            'adjustment_reason' => 'Tidak ada selisih',
            'movement_date' => '2026-11-05',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['actual_stock']);
    }

    public function test_staff_cannot_perform_stock_adjustment(): void
    {
        $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/inventory/adjustments', [
                'product_id' => $this->product->id,
                'actual_stock' => 22,
                'adjustment_reason' => 'Coba staff ubah stok',
                'movement_date' => '2026-11-05',
            ])
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Overview & Movement History Tests
    |--------------------------------------------------------------------------
    */

    public function test_inventory_overview_list_and_detail(): void
    {
        // Overview list
        $response = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/inventory');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data',
                'summary' => ['total_items', 'total_stock_units', 'out_of_stock_count'],
                'pagination',
            ]);

        // Product stock detail
        $detailRes = $this->actingAs($this->staff, 'sanctum')->getJson("/api/v1/inventory/{$this->product->id}");
        $detailRes->assertOk()
            ->assertJsonPath('data.sku', $this->product->sku)
            ->assertJsonPath('data.stock', '20.00');
    }

    public function test_movements_history_list_and_filters(): void
    {
        // Create 2 movements
        InventoryMovement::create([
            'movement_code' => 'MOV-20261105-0001',
            'product_id' => $this->product->id,
            'movement_type' => 'stock_in',
            'quantity' => 10,
            'stock_before' => 10,
            'stock_after' => 20,
            'movement_date' => '2026-11-05',
            'created_by' => $this->admin->id,
        ]);

        InventoryMovement::create([
            'movement_code' => 'MOV-20261105-0002',
            'product_id' => $this->product->id,
            'movement_type' => 'stock_out',
            'quantity' => 5,
            'stock_before' => 20,
            'stock_after' => 15,
            'movement_date' => '2026-11-05',
            'created_by' => $this->staff->id,
        ]);

        // All movements
        $res = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/inventory/movements');
        $res->assertOk();
        $this->assertCount(2, $res->json('data'));

        // Stock in list endpoint
        $stockInRes = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/inventory/stock-in');
        $stockInRes->assertOk();
        $this->assertCount(1, $stockInRes->json('data'));
        $this->assertEquals('stock_in', $stockInRes->json('data.0.movement_type'));

        // Stock out list endpoint
        $stockOutRes = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/inventory/stock-out');
        $stockOutRes->assertOk();
        $this->assertCount(1, $stockOutRes->json('data'));
        $this->assertEquals('stock_out', $stockOutRes->json('data.0.movement_type'));
    }

    public function test_direct_api_prefix_without_v1_also_works_for_inventory(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/inventory');
        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
