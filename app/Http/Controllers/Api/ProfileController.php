<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\UserPreference;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    /**
     * Display the authenticated user's profile.
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Profil pengguna berhasil diambil',
            'data' => new UserResource($request->user()->load('preference')),
        ], Response::HTTP_OK);
    }

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->name = $validated['name'];

        if ($user->email !== $validated['email']) {
            $user->email = $validated['email'];
            $user->email_verified_at = null;
        }

        if (array_key_exists('username', $validated)) {
            $user->username = $validated['username'];
        }

        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        if (array_key_exists('avatar', $validated)) {
            $user->avatar = $validated['avatar'];
        }

        $user->save();

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'auth',
            description: "Pengguna '{$user->name}' memperbarui data profilnya",
            user: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Update the authenticated user's password.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Verify current password
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password saat ini yang Anda masukkan salah.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Update password using automatic hash casting
        $user->password = $validated['password'];
        $user->save();

        // Revoke other tokens except the current one for security
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'auth',
            description: "Pengguna '{$user->name}' berhasil mengubah kata sandi",
            user: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Sesi pada perangkat lain telah dicabut.',
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan preferensi pengguna saat ini.
     */
    public function preferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $pref = $user->preference ?: UserPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'theme' => 'light',
                'locale' => 'id',
                'table_per_page' => 15,
                'notifications_enabled' => true,
                'sound_enabled' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferensi pengguna berhasil diambil',
            'data' => $pref,
        ], Response::HTTP_OK);
    }

    /**
     * Perbarui preferensi pengguna saat ini.
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'theme' => ['string', 'in:light,dark,system'],
            'locale' => ['string', 'in:id,en'],
            'table_per_page' => ['integer', 'min:5', 'max:100'],
            'notifications_enabled' => ['boolean'],
            'sound_enabled' => ['boolean'],
            'preferences' => ['nullable', 'array'],
        ]);

        $pref = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Preferensi pengguna berhasil diperbarui',
            'data' => $pref,
        ], Response::HTTP_OK);
    }

    /**
     * Dapatkan riwayat sesi dan login pengguna.
     */
    public function loginHistories(Request $request): JsonResponse
    {
        $histories = $request->user()
            ->loginHistories()
            ->orderByDesc('login_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat login pengguna berhasil diambil',
            'data' => $histories,
        ], Response::HTTP_OK);
    }
}

