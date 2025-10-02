<?php

namespace App\Policies;

use App\Models\GroupCharge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupChargePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function show(User $user)
    {
        if ($user->can('group-charge.show')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->can('group-charge.create')) {
            return true;
        }
    }



    /**
     * Determine whether the user can delete the model.
     */
    public function cancel(User $user)

    {
        if ($user->can('group-charge.cancel')) {
            return true;
        }
    }

}
