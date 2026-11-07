<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected string $adminToken;
    protected string $staffToken;

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
    }

    public function test_admin_can_create_supplier_with_auto_generated_code(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->postJson('/api/v1/suppliers', [
                'name' => 'PT Sumber Pangan Sejahtera',
                'phone' => '08123456789',
                'email' => 'contact@sumberpangan.com',
                'address' => 'Jl. Industri No. 45, Surabaya',
                'contact_person' => 'Budi Santoso',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'PT Sumber Pangan Sejahtera')
            ->assertJsonPath('data.code', 'SUP-0001');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'PT Sumber Pangan Sejahtera',
            'code' => 'SUP-0001',
        ]);
    }

    public function test_admin_can_list_and_filter_suppliers(): void
    {
        Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Distributor Jaya',
            'phone' => '0811111111',
            'is_active' => true,
        ]);

        Supplier::create([
            'code' => 'SUP-0002',
            'name' => 'Grosir Berkah',
            'phone' => '0822222222',
            'is_active' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/suppliers?search=Jaya');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Distributor Jaya');

        // Test active suppliers endpoint
        $activeResponse = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->getJson('/api/v1/suppliers/active');

        $activeResponse->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Nama Lama',
            'phone' => '081234',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->adminToken}")
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Nama Baru Supplier',
                'phone' => '089999',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nama Baru Supplier');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Nama Baru Supplier',
        ]);
    }

    public function test_staff_cannot_create_or_modify_supplier(): void
    {
        $supplier = Supplier::create([
            'code' => 'SUP-0001',
            'name' => 'Distributor A',
        ]);

        $createResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->postJson('/api/v1/suppliers', [
                'name' => 'Supplier Ilegal',
            ]);

        $createResponse->assertForbidden();

        $updateResponse = $this->withHeader('Authorization', "Bearer {$this->staffToken}")
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Update Ilegal',
            ]);

        $updateResponse->assertForbidden();
    }
}
