<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    /**
     * Determine whether the user can view any financial transactions.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can view the specific financial transaction.
     */
    public function view(User $user, FinancialTransaction $transaction): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Staff can view transactions
        return true;
    }

    /**
     * Determine whether the user can create financial transactions.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    /**
     * Determine whether the user can update the financial transaction.
     */
    public function update(User $user, FinancialTransaction $transaction): bool
    {
        // Cancelled transactions cannot be updated
        if ($transaction->isCancelled()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Staff can only update their own recorded transaction
        return (int) $user->id === (int) $transaction->created_by;
    }

    /**
     * Determine whether the user can cancel the financial transaction.
     */
    public function cancel(User $user, FinancialTransaction $transaction): bool
    {
        // Cannot cancel if already cancelled
        if ($transaction->isCancelled()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Staff can only cancel their own transaction
        return (int) $user->id === (int) $transaction->created_by;
    }

    /**
     * Determine whether the user can delete the financial transaction.
     */
    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return $user->isAdmin();
    }
}
