<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models (user list).
     */
    public function viewAny(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Hanya administrator yang diizinkan melihat daftar pengguna.');
    }

    /**
     * Determine whether the user can view the model.
     * Admin can view all users; staff can only view themselves.
     */
    public function view(User $user, User $model): Response
    {
        if ($user->isAdmin() || $user->id === $model->id) {
            return Response::allow();
        }

        return Response::deny('Anda hanya diizinkan melihat informasi akun Anda sendiri.');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Hanya administrator yang dapat mendaftarkan akun pengguna baru.');
    }

    /**
     * Determine whether the user can update the model.
     * Admin can update users; staff can only update themselves.
     */
    public function update(User $user, User $model): Response
    {
        if ($user->isAdmin() || $user->id === $model->id) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki izin untuk memperbarui data pengguna lain.');
    }

    /**
     * Determine whether the user can delete the model.
     * Only admin, with protection against deleting the last active admin.
     */
    public function delete(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya administrator yang dapat menghapus akun pengguna.');
        }

        // Check if deleting this user would leave zero active admins
        if ($model->isAdmin()) {
            $activeAdminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1 && $model->is_active) {
                return Response::deny('Tidak dapat menghapus administrator aktif terakhir dalam sistem.');
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can change user role.
     */
    public function changeRole(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya administrator yang dapat mengubah role pengguna.');
        }

        // Prevent demoting the last active admin
        if ($model->isAdmin()) {
            $activeAdminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1 && $model->is_active) {
                return Response::deny('Tidak dapat menurunkan role administrator aktif terakhir dalam sistem.');
            }
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can change user status (activate / deactivate).
     */
    public function changeStatus(User $user, User $model): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Hanya administrator yang dapat mengaktifkan atau menonaktifkan pengguna.');
        }

        // Prevent deactivating the last active admin
        if ($model->isAdmin() && $model->is_active) {
            $activeAdminCount = User::where('role', 'admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                return Response::deny('Tidak dapat menonaktifkan administrator aktif terakhir dalam sistem.');
            }
        }

        return Response::allow();
    }
}
