<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ScheduledTopup;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledTopupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view scheduled top-ups.
     */
    public function view(User $user)
    {
        return $user->can('scheduled_topup.view');
    }

    /**
     * Determine whether the user can create a scheduled top-up.
     */
    public function create(User $user)
    {
        return $user->can('scheduled_topup.create');
    }

    /**
     * Determine whether the user can update a scheduled top-up.
     */
    public function update(User $user)
    {
        return $user->can('scheduled_topup.update');
    }

    /**
     * Determine whether the user can cancel (delete) a scheduled top-up.
     */
    public function cancel(User $user)
    {
        return $user->can('scheduled_topup.cancel');
    }
}
