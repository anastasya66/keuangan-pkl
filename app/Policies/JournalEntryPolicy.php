<?php

namespace App\Policies;

use App\Models\JournalEntry;
use App\Models\User;

class JournalEntryPolicy
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
    public function view(User $user, JournalEntry $journalEntry): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Admin dan Staff aktif boleh membuat jurnal (draft)
        return $user->isActive();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, JournalEntry $journalEntry): bool
    {
        if (!$user->isActive() || $journalEntry->status !== 'draft') {
            return false;
        }

        // Admin bisa edit draft manapun; Staff hanya bisa edit draft buatannya sendiri
        return $user->isAdmin() || $journalEntry->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, JournalEntry $journalEntry): bool
    {
        if (!$user->isActive() || $journalEntry->status !== 'draft') {
            return false;
        }

        return $user->isAdmin() || $journalEntry->created_by === $user->id;
    }

    /**
     * Determine whether the user can post the journal.
     */
    public function post(User $user, JournalEntry $journalEntry): bool
    {
        return $user->isActive() && $user->isAdmin() && $journalEntry->status === 'draft';
    }

    /**
     * Determine whether the user can reverse the journal.
     */
    public function reverse(User $user, JournalEntry $journalEntry): bool
    {
        return $user->isActive() && $user->isAdmin() && $journalEntry->status === 'posted';
    }
}

