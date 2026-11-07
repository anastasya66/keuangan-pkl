<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\DocumentSequence;
use App\Models\PaymentMethod;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\BackupService;
use App\Services\DocumentSequenceService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SettingsSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Owner Admin',
            'email' => 'admin@fintoko.id',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);

        $this->staff = User::factory()->create([
            'name' => 'Kasir Staff',
            'email' => 'staff@fintoko.id',
            'role' => 'staff',
            'is_active' => true,
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_public_settings_accessible_without_auth(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'store_name',
                    'currency',
                    'date_format',
                ],
            ]);
    }

    public function test_admin_can_retrieve_all_settings_groups(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/settings');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'groups' => [
                        'store',
                        'transaction',
                        'pos',
                        'inventory',
                        'financial',
                        'accounting',
                        'notification',
                        'security',
                        'preferences',
                    ],
                    'flattened',
                ],
            ]);
    }

    public function test_staff_cannot_access_or_update_admin_settings(): void
    {
        $response = $this->actingAs($this->staff)->getJson('/api/v1/admin/settings');
        $response->assertForbidden();

        $updateResponse = $this->actingAs($this->staff)->putJson('/api/v1/admin/settings', [
            'settings' => ['store_name' => 'Hacked Store'],
        ]);
        $updateResponse->assertForbidden();
    }

    public function test_admin_can_update_settings_batch(): void
    {
        $payload = [
            'settings' => [
                'store_name' => 'FinToko Cabang Utama',
                'pos_receipt_printer_width' => '80mm',
                'inventory_allow_negative_stock' => false,
                'inventory_default_min_stock' => 10,
            ],
        ];

        $response = $this->actingAs($this->admin)->putJson('/api/v1/admin/settings', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals('FinToko Cabang Utama', app(SettingsService::class)->get('store_name'));
        $this->assertEquals('80mm', app(SettingsService::class)->get('pos_receipt_printer_width'));
        $this->assertEquals(10, app(SettingsService::class)->get('inventory_default_min_stock'));

        // Pastikan terekam di activity logs
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'settings_update',
            'module' => 'settings',
        ]);
    }

    public function test_document_sequence_service_generates_unique_formatted_numbers(): void
    {
        $service = app(DocumentSequenceService::class);

        $num1 = $service->getNextNumber('pos_sale');
        $num2 = $service->getNextNumber('pos_sale');

        $this->assertNotEmpty($num1);
        $this->assertNotEmpty($num2);
        $this->assertNotEquals($num1, $num2);
        $this->assertStringStartsWith('POS-', $num1);
    }

    public function test_admin_can_update_document_sequence(): void
    {
        $seq = DocumentSequence::where('document_type', 'purchase')->first();

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/document-sequences/{$seq->id}", [
            'prefix' => 'KULAKAN',
            'format' => '{PREFIX}-{YYYY}{MM}-{SEQ}',
            'padding' => 5,
            'reset_frequency' => 'monthly',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.prefix', 'KULAKAN')
            ->assertJsonPath('data.padding', 5);

        $nextNum = app(DocumentSequenceService::class)->getNextNumber('purchase');
        $this->assertStringStartsWith('KULAKAN-', $nextNum);
    }

    public function test_accounting_period_lock_blocks_journal_posting(): void
    {
        $periodService = app(AccountingPeriodService::class);
        $period = AccountingPeriod::where('fiscal_year', 2026)->first();

        // Kunci periode
        $this->actingAs($this->admin)->patchJson("/api/v1/admin/accounting-periods/{$period->id}/lock")
            ->assertOk()
            ->assertJsonPath('data.is_locked', true);

        $this->assertTrue($periodService->isDateLocked($period->start_date));

        // Verifikasi exception dilempar saat validasi
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $periodService->validateDateNotLocked($period->start_date, 'Jurnal');
    }

    public function test_payment_methods_crud_and_status_toggle(): void
    {
        // 1. List
        $this->actingAs($this->admin)->getJson('/api/v1/admin/payment-methods')
            ->assertOk();

        // 2. Create
        $createRes = $this->actingAs($this->admin)->postJson('/api/v1/admin/payment-methods', [
            'code' => 'va_bca',
            'name' => 'BCA Virtual Account',
            'type' => 'digital',
            'is_active' => true,
            'requires_reference' => true,
            'instructions' => 'Bayar melalui menu Virtual Account BCA',
            'sort_order' => 5,
        ]);

        $createRes->assertCreated()
            ->assertJsonPath('data.code', 'va_bca');

        $pmId = $createRes->json('data.id');

        // 3. Toggle Status
        $this->actingAs($this->admin)->patchJson("/api/v1/admin/payment-methods/{$pmId}/status")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_roles_and_granular_permissions_management(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'roles',
                    'permissions_by_group',
                ],
            ]);

        $staffRole = Role::where('name', 'staff')->first();

        // Update permissions staff
        $updateRes = $this->actingAs($this->admin)->putJson("/api/v1/admin/roles/{$staffRole->id}/permissions", [
            'permissions' => ['pos.view', 'pos.checkout'],
        ]);

        $updateRes->assertOk();
        $this->staff->refresh();

        $this->assertTrue($this->staff->hasPermission('pos.checkout'));
        $this->assertFalse($this->staff->hasPermission('accounting.journal_post'));
    }

    public function test_backup_creation_and_restore_with_password(): void
    {
        // 1. Create Backup
        $backupRes = $this->actingAs($this->admin)->postJson('/api/v1/admin/backups', [
            'notes' => 'Testing manual backup',
        ]);

        $backupRes->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'filename', 'checksum', 'file_size'],
            ]);

        $backupId = $backupRes->json('data.id');

        // 2. Download Backup
        $downloadRes = $this->actingAs($this->admin)->get("/api/v1/admin/backups/{$backupId}/download");
        $downloadRes->assertOk();

        // 3. Restore with wrong password should fail
        $wrongPwdRes = $this->actingAs($this->admin)->postJson("/api/v1/admin/backups/{$backupId}/restore", [
            'password' => 'wrongpassword',
        ]);
        $wrongPwdRes->assertUnprocessable();

        // 4. Restore with correct password should succeed
        $restoreRes = $this->actingAs($this->admin)->postJson("/api/v1/admin/backups/{$backupId}/restore", [
            'password' => 'password123',
        ]);
        $restoreRes->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_activity_logs_search_and_pagination(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/activity-logs?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'pagination' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_user_preferences_and_login_history(): void
    {
        // 1. Preferences Get & Put
        $this->actingAs($this->staff)->getJson('/api/v1/user/preferences')
            ->assertOk()
            ->assertJsonPath('data.theme', 'light');

        $this->actingAs($this->staff)->putJson('/api/v1/user/preferences', [
            'theme' => 'dark',
            'table_per_page' => 25,
            'notifications_enabled' => false,
        ])->assertOk()
            ->assertJsonPath('data.theme', 'dark')
            ->assertJsonPath('data.table_per_page', 25);

        // 2. Login Histories
        $this->actingAs($this->staff)->getJson('/api/v1/user/login-histories')
            ->assertOk();
    }
}
