<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthenticationAndUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    // ==========================================
    // 1. AUTHENTICATION TESTS
    // ==========================================

    public function test_login_successful_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@fintoko.test',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'user' => [
                        'email' => 'admin@fintoko.test',
                        'role' => 'admin',
                        'is_active' => true,
                    ],
                    'token_type' => 'Bearer',
                ],
            ])
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@fintoko.test',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ]);
    }

    public function test_login_fails_with_unregistered_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@fintoko.test',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_login_fails_with_inactive_account(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonaktif@fintoko.test',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
            ]);
    }

    public function test_logout_revokes_token_successfully(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('logout_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil. Token autentikasi telah dicabut.',
            ]);

        $this->assertNull(PersonalAccessToken::findToken($token));
    }

    public function test_auth_me_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    public function test_auth_me_succeeds_with_valid_token(): void
    {
        $user = User::where('email', 'admin@fintoko.test')->first();
        $token = $user->createToken('me_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => 'admin@fintoko.test',
                    'role' => 'admin',
                    'is_active' => true,
                ],
            ]);
    }

    public function test_token_refresh_rotates_active_token(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $oldToken = $user->createToken('refresh_old')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$oldToken)
            ->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Token berhasil diperbarui',
            ])
            ->assertJsonStructure(['data' => ['token']]);

        // Old token must be revoked
        $this->assertNull(PersonalAccessToken::findToken($oldToken));
    }

    // ==========================================
    // 2. USER PROFILE TESTS
    // ==========================================

    public function test_user_can_view_own_profile(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('profile_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Staff Toko',
                ],
            ]);
    }

    public function test_user_can_update_own_name_and_email(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('update_profile_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/user/profile', [
                'name' => 'Staff Toko Diperbarui',
                'email' => 'staffbaru@fintoko.test',
                'role' => 'admin', // Attempting privilege escalation must be ignored
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Staff Toko Diperbarui',
                    'email' => 'staffbaru@fintoko.test',
                    'role' => 'staff', // Must remain staff!
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'staff',
        ]);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('change_pass_test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/user/change-password', [
                'current_password' => 'password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password berhasil diubah. Sesi pada perangkat lain telah dicabut.',
            ]);
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('change_pass_test_wrong')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/user/change-password', [
                'current_password' => 'wrong-current-password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    // ==========================================
    // 3. AUTHORIZATION & ADMIN USER MANAGEMENT TESTS
    // ==========================================

    public function test_staff_cannot_access_admin_user_list(): void
    {
        $staff = User::where('email', 'staff@fintoko.test')->first();
        $token = $staff->createToken('staff_forbidden')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_list_with_pagination(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $token = $admin->createToken('admin_list')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data',
                'pagination' => ['total', 'current_page', 'last_page'],
            ]);
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $token = $admin->createToken('admin_create')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/users', [
                'name' => 'Kasir Baru',
                'email' => 'kasirbaru@fintoko.test',
                'password' => 'password123',
                'role' => 'staff',
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Kasir Baru',
                    'email' => 'kasirbaru@fintoko.test',
                    'role' => 'staff',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'kasirbaru@fintoko.test']);
    }

    public function test_admin_can_update_staff_role_to_admin(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_role')->plainTextToken;

        $staff = User::where('email', 'staff@fintoko.test')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/admin/users/{$staff->id}/role", [
                'role' => 'admin',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $staff->id,
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_cannot_demote_last_active_admin(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_self_demote')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/admin/users/{$admin->id}/role", [
                'role' => 'staff',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Tidak dapat menurunkan role administrator aktif terakhir dalam sistem.',
            ]);
    }

    public function test_cannot_deactivate_last_active_admin(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_deactivate_last')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/admin/users/{$admin->id}/status", [
                'is_active' => false,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Tidak dapat menonaktifkan administrator aktif terakhir dalam sistem.',
            ]);
    }

    public function test_cannot_delete_last_active_admin(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_delete_last')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->deleteJson("/api/v1/admin/users/{$admin->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Tidak dapat menghapus administrator aktif terakhir dalam sistem.',
            ]);
    }

    public function test_deactivated_account_token_is_immediately_blocked(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_op')->plainTextToken;

        $staff = User::where('email', 'staff@fintoko.test')->first();
        $staffToken = $staff->createToken('staff_session')->plainTextToken;

        // Admin deactivates staff account
        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->patchJson("/api/v1/admin/users/{$staff->id}/status", [
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        // Staff attempts to access profile with old token -> must be rejected
        $this->app['auth']->forgetGuards();
        $staffResponse = $this->withHeader('Authorization', 'Bearer '.$staffToken)
            ->getJson('/api/v1/user/profile');

        // Token was revoked, so it returns 401 Unauthenticated
        $staffResponse->assertStatus(401);
    }

    public function test_admin_can_soft_delete_staff_account(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $adminToken = $admin->createToken('admin_del')->plainTextToken;

        $staff = User::where('email', 'staff@fintoko.test')->first();

        $response = $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->deleteJson("/api/v1/admin/users/{$staff->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Akun pengguna berhasil dihapus (soft delete).',
            ]);

        $this->assertSoftDeleted('users', ['id' => $staff->id]);
    }
}
