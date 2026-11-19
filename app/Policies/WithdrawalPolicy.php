<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    /**
     * Determine whether the user can view the withdrawal.
     */
    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->id === $withdrawal->user_id;
    }

    /**
     * Determine whether the user can sync the withdrawal status.
     */
    public function sync(User $user, Withdrawal $withdrawal): bool
    {
        return $user->id === $withdrawal->user_id;
    }
}
