<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RolePermissionService
{
    /**
     * Dapatkan seluruh roles beserta relasi permissions.
     */
    public function getRoles(): Collection
    {
        return Role::with('permissions')->orderBy('id')->get();
    }

    /**
     * Dapatkan seluruh permissions yang dikelompokkan berdasarkan grup modul.
     */
    public function getAllPermissionsGrouped(): array
    {
        return Permission::all()->groupBy('group')->toArray();
    }

    /**
     * Update permissions yang dimiliki oleh role tertentu.
     */
    public function updateRolePermissions(int $roleId, array $permissionNames, ?User $user = null): Role
    {
        $role = Role::findOrFail($roleId);

        $permissionIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        $role->permissions()->sync($permissionIds);

        app(ActivityLogService::class)->log(
            action: 'update',
            module: 'settings',
            description: "Memperbarui hak akses (permissions) untuk role '{$role->display_name}'",
            subjectType: Role::class,
            subjectId: (string) $role->id,
            properties: ['permissions' => $permissionNames],
            user: $user
        );

        return $role->load('permissions');
    }
}
