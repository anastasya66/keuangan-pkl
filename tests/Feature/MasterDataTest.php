<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

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
    }

    /*
    |--------------------------------------------------------------------------
    | Category Management Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_category(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Elektronik Toko',
            'description' => 'Peralatan elektronik toko',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Elektronik Toko');

        $this->assertDatabaseHas('categories', ['name' => 'Elektronik Toko']);
    }

    public function test_category_name_must_be_unique(): void
    {
        Category::create([
            'name' => 'Sembako',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/categories', [
            'name' => 'Sembako',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create([
            'name' => 'Minuman Ringan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/categories/{$category->id}", [
            'name' => 'Minuman Dingin',
            'description' => 'Aneka minuman dingin segar',
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Minuman Dingin');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Minuman Dingin']);
    }

    public function test_admin_can_change_category_status(): void
    {
        $category = Category::create([
            'name' => 'Kategori Nonaktifkan',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->patchJson("/api/v1/categories/{$category->id}/status", [
            'is_active' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => 0]);
    }

    public function test_cannot_delete_category_if_has_products(): void
    {
        $category = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-TEST-001',
            'name' => 'Beras Pandan',
            'purchase_price' => 10000,
            'selling_price' => 12000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }

    public function test_can_delete_category_without_products(): void
    {
        $category = Category::create(['name' => 'Kategori Kosong', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_staff_cannot_create_or_modify_category(): void
    {
        $category = Category::create(['name' => 'Bumbu Dapur', 'is_active' => true]);

        $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/categories', ['name' => 'Coba'])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->putJson("/api/v1/categories/{$category->id}", ['name' => 'Ganti'])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->deleteJson("/api/v1/categories/{$category->id}")
            ->assertForbidden();
    }

    public function test_staff_only_receives_active_categories(): void
    {
        Category::create(['name' => 'Kategori Aktif', 'is_active' => true]);
        Category::create(['name' => 'Kategori Tidak Aktif', 'is_active' => false]);

        $response = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/categories');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Kategori Aktif', $data[0]['name']);
    }

    /*
    |--------------------------------------------------------------------------
    | Unit Management Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_unit(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/units', [
            'name' => 'Meter',
            'symbol' => 'm',
            'description' => 'Satuan panjang meter',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.symbol', 'm');

        $this->assertDatabaseHas('units', ['name' => 'Meter', 'symbol' => 'm']);
    }

    public function test_unit_name_and_symbol_must_be_unique(): void
    {
        Unit::create(['name' => 'Pieces', 'symbol' => 'pcs', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/units', [
            'name' => 'Pieces',
            'symbol' => 'pcs',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'symbol']);
    }

    public function test_cannot_delete_unit_if_has_products(): void
    {
        $category = Category::create(['name' => 'Bumbu', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Botol', 'symbol' => 'btl', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-TEST-002',
            'name' => 'Kecap Manis',
            'purchase_price' => 15000,
            'selling_price' => 18000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/units/{$unit->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('units', ['id' => $unit->id, 'deleted_at' => null]);
    }

    public function test_staff_cannot_create_or_modify_unit(): void
    {
        $unit = Unit::create(['name' => 'Liter', 'symbol' => 'L', 'is_active' => true]);

        $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/units', ['name' => 'Coba', 'symbol' => 'cb'])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->putJson("/api/v1/units/{$unit->id}", ['name' => 'Ganti', 'symbol' => 'gt'])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->deleteJson("/api/v1/units/{$unit->id}")
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Product Management Tests
    |--------------------------------------------------------------------------
    */

    public function test_admin_can_create_product(): void
    {
        $category = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-001',
            'barcode' => '8990001',
            'name' => 'Beras Rojolele 5kg',
            'purchase_price' => 60000,
            'selling_price' => 70000,
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sku', 'SKU-001')
            ->assertJsonPath('data.category.name', 'Sembako')
            ->assertJsonPath('data.unit.symbol', 'kg');

        $this->assertDatabaseHas('products', ['sku' => 'SKU-001']);
    }

    public function test_cannot_create_product_with_inactive_category_or_unit(): void
    {
        $inactiveCat = Category::create(['name' => 'Kategori Nonaktif', 'is_active' => false]);
        $inactiveUnit = Unit::create(['name' => 'Satuan Nonaktif', 'symbol' => 'snk', 'is_active' => false]);
        $activeCat = Category::create(['name' => 'Kategori Aktif', 'is_active' => true]);
        $activeUnit = Unit::create(['name' => 'Satuan Aktif', 'symbol' => 'sat', 'is_active' => true]);

        // Attempt with inactive category
        $response1 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', [
            'category_id' => $inactiveCat->id,
            'unit_id' => $activeUnit->id,
            'sku' => 'SKU-TEST-1',
            'name' => 'Produk A',
            'purchase_price' => 1000,
            'selling_price' => 2000,
        ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);

        // Attempt with inactive unit
        $response2 = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', [
            'category_id' => $activeCat->id,
            'unit_id' => $inactiveUnit->id,
            'sku' => 'SKU-TEST-2',
            'name' => 'Produk B',
            'purchase_price' => 1000,
            'selling_price' => 2000,
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['unit_id']);
    }

    public function test_cannot_create_product_with_negative_price(): void
    {
        $category = Category::create(['name' => 'Sembako', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg', 'is_active' => true]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-NEG',
            'name' => 'Harga Minus',
            'purchase_price' => -100,
            'selling_price' => -500,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['purchase_price', 'selling_price']);
    }

    public function test_product_sku_and_barcode_must_be_unique(): void
    {
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Botol', 'symbol' => 'btl', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-UNIQUE',
            'barcode' => 'BARCODE-UNIQUE',
            'name' => 'Teh Kotak',
            'purchase_price' => 3000,
            'selling_price' => 4000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/products', [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-UNIQUE',
            'barcode' => 'BARCODE-UNIQUE',
            'name' => 'Produk Duplikat',
            'purchase_price' => 3000,
            'selling_price' => 4000,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku', 'barcode']);
    }

    public function test_admin_can_update_product(): void
    {
        $category = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Botol', 'symbol' => 'btl', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-EDIT',
            'name' => 'Kopi Kaleng',
            'purchase_price' => 5000,
            'selling_price' => 7000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/products/{$product->id}", [
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-EDIT',
            'name' => 'Kopi Kaleng Rasa Moka',
            'purchase_price' => 5500,
            'selling_price' => 7500,
            'is_active' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Kopi Kaleng Rasa Moka');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Kopi Kaleng Rasa Moka']);
    }

    public function test_admin_can_soft_delete_product(): void
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pack', 'symbol' => 'pack', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-DEL',
            'name' => 'Keripik Tempe',
            'purchase_price' => 4000,
            'selling_price' => 6000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_staff_cannot_create_or_modify_products(): void
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pack', 'symbol' => 'pack', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'SKU-STAFF',
            'name' => 'Kerupuk',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'is_active' => true,
        ]);

        $this->actingAs($this->staff, 'sanctum')
            ->postJson('/api/v1/products', [
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'sku' => 'SKU-NEW',
                'name' => 'Baru',
                'purchase_price' => 1000,
                'selling_price' => 2000,
            ])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", [
                'category_id' => $category->id,
                'unit_id' => $unit->id,
                'sku' => 'SKU-STAFF',
                'name' => 'Baru',
                'purchase_price' => 1000,
                'selling_price' => 2000,
            ])
            ->assertForbidden();

        $this->actingAs($this->staff, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertForbidden();
    }

    public function test_staff_only_receives_active_products(): void
    {
        $category = Category::create(['name' => 'Snack', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Pack', 'symbol' => 'pack', 'is_active' => true]);

        Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-ACTIVE',
            'name' => 'Produk Aktif',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'sku' => 'PRD-INACTIVE',
            'name' => 'Produk Nonaktif',
            'purchase_price' => 1000,
            'selling_price' => 2000,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->staff, 'sanctum')->getJson('/api/v1/products');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('PRD-ACTIVE', $data[0]['sku']);
    }
}

