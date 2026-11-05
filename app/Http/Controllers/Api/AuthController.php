<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * Handle user authentication and token creation.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check whether account is active
        if (! $user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Issue fresh Sanctum Personal Access Token
        $token = $user->createToken('fintoko_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => (bool) $user->is_active,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Revoke currently active authentication token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil. Token autentikasi telah dicabut.',
        ], Response::HTTP_OK);
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Informasi pengguna berhasil diambil',
            'data' => new UserResource($request->user()),
        ], Response::HTTP_OK);
    }

    /**
     * Rotate current token by revoking the old token and issuing a new token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the old active token
        $user->currentAccessToken()?->delete();

        // Issue fresh token
        $newToken = $user->createToken('fintoko_auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Token berhasil diperbarui',
            'data' => [
                'token' => $newToken,
                'token_type' => 'Bearer',
            ],
        ], Response::HTTP_OK);
    }
}
