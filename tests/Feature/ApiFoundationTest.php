<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /**
     * Test Health Endpoint: GET /api/v1/health
     */
    public function test_health_check_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'FinToko API is running',
                'data' => [
                    'application' => 'FinToko',
                    'version' => '1.0.0',
                ],
            ]);
    }

    /**
     * Test Login Validation: POST /api/v1/auth/login
     */
    public function test_login_validation_fails_with_empty_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validasi gagal',
            ])
            ->assertJsonStructure(['errors' => ['email', 'password']]);
    }

    /**
     * Test Login with Invalid Credentials.
     */
    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@fintoko.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ]);
    }

    /**
     * Test Login Successful and Token Issued.
     */
    public function test_login_successful_returns_token_and_user_info(): void
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
                    ],
                    'token_type' => 'Bearer',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                ],
            ]);
    }

    /**
     * Test GET /api/v1/auth/me with Bearer Token.
     */
    public function test_auth_me_returns_current_authenticated_user(): void
    {
        $user = User::where('email', 'admin@fintoko.test')->first();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => 'admin@fintoko.test',
                    'role' => 'admin',
                ],
            ]);
    }

    /**
     * Test Role Middleware: Admin Access.
     */
    public function test_admin_can_access_admin_route(): void
    {
        $admin = User::where('email', 'admin@fintoko.test')->first();
        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/check');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'authorized' => true,
                    'scope' => 'admin_only',
                ],
            ]);
    }

    /**
     * Test Role Middleware: Staff Access Forbidden on Admin Route.
     */
    public function test_staff_cannot_access_admin_route(): void
    {
        $staff = User::where('email', 'staff@fintoko.test')->first();
        $token = $staff->createToken('staff_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/admin/check');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki hak akses untuk resource ini.',
            ]);
    }

    /**
     * Test Logout Revokes Token: POST /api/v1/auth/logout.
     */
    public function test_logout_revokes_active_token(): void
    {
        $user = User::where('email', 'staff@fintoko.test')->first();
        $token = $user->createToken('token_to_revoke')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout berhasil. Token autentikasi telah dicabut.',
            ]);

        // Verify token is deleted from database
        $this->assertNull(PersonalAccessToken::findToken($token));

        // Forget cached auth guards in test runner and verify subsequent request returns 401
        $this->app['auth']->forgetGuards();
        $subsequentResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me');

        $subsequentResponse->assertStatus(401);
    }
}
