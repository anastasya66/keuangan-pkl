<?php

namespace App\Policies;

use App\Models\ChartOfAccount;
use App\Models\User;

class ChartOfAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine whether the user can toggle the active status.
     */
    public function toggleStatus(User $user, ChartOfAccount $chartOfAccount): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
