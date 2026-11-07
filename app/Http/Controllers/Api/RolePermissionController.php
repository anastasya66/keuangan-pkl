<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RolePermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolePermissionController extends Controller
{
    public function __construct(
        protected RolePermissionService $rolePermissionService
    ) {}

    /**
     * Dapatkan daftar roles beserta permissions aktif dan seluruh daftar permission yang tersedia.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data role dan hak akses berhasil diambil',
            'data' => [
                'roles' => $this->rolePermissionService->getRoles(),
                'permissions_by_group' => $this->rolePermissionService->getAllPermissionsGrouped(),
            ],
        ], Response::HTTP_OK);
    }

    /**
     * Perbarui hak akses (permissions) untuk role tertentu.
     */
    public function update(Request $request, int $roleId): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = $this->rolePermissionService->updateRolePermissions(
            $roleId,
            $validated['permissions'],
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => "Hak akses role '{$role->display_name}' berhasil diperbarui.",
            'data' => $role,
        ], Response::HTTP_OK);
    }
}
