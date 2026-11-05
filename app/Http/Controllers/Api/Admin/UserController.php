<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a paginated listing of users with search and filter.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        // Filter by status (active / inactive)
        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = (int) $request->input('per_page', 10);
        $users = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengguna berhasil diambil',
            'data' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // Automatically hashed by User model cast
            'role' => $validated['role'],
            'is_active' => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna baru berhasil ditambahkan',
            'data' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified user details.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengguna berhasil diambil',
            'data' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user details (name, email, and optional password).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
            // Invalidate existing tokens when password is changed by admin
            $user->tokens()->delete();
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Informasi pengguna berhasil diperbarui',
            'data' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user's role.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('changeRole', $user);

        $validated = $request->validated();

        $user->role = $validated['role'];
        $user->save();

        // Invalidate all tokens so that new role permissions take immediate effect
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "Role pengguna berhasil diubah menjadi {$user->role}",
            'data' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Update the specified user's active status.
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $this->authorize('changeStatus', $user);

        $validated = $request->validated();

        $user->is_active = $validated['is_active'];
        $user->save();

        // If deactivated, revoke all active sessions/tokens immediately
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'success' => true,
            'message' => "Akun pengguna berhasil {$statusText}",
            'data' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    /**
     * Remove the specified user from storage using Soft Delete.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        // Revoke all tokens before deletion
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun pengguna berhasil dihapus (soft delete).',
        ], Response::HTTP_OK);
    }
}
