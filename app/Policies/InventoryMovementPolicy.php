<?php

namespace App\Policies;

use App\Models\InventoryMovement;
use App\Models\User;

class InventoryMovementPolicy
{
    /**
     * Determine whether the user can view inventory stocks or movements.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the specific movement detail.
     */
    public function view(User $user, ?InventoryMovement $movement = null): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can record stock in.
     */
    public function stockIn(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can record stock out.
     */
    public function stockOut(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can perform physical stock adjustment.
     * Hanya Administrator yang berhak melakukan penyesuaian stok opname fisik.
     */
    public function adjustStock(User $user): bool
    {
        return $user->isAdmin();
    }
}

