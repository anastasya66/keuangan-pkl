<?php

namespace App\Policies;

use App\Models\TransactionCategory;
use App\Models\User;

class TransactionCategoryPolicy
{
    /**
     * Determine whether the user can view any transaction categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the specific transaction category.
     */
    public function view(User $user, TransactionCategory $category): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $category->is_active;
    }

    /**
     * Determine whether the user can create transaction categories.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the transaction category.
     */
    public function update(User $user, TransactionCategory $category): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the transaction category.
     */
    public function delete(User $user, TransactionCategory $category): bool
    {
        return $user->isAdmin();
    }
}
