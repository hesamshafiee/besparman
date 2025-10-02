<?php

namespace App\Policies;

use App\Models\GroupCharge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CardChargePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the model.
     */
    public function show(User $user)
    {
        if ($user->can('card-charge.show')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->can('card-charge.create')) {
            return true;
        }
    }



    /**
     * Determine whether the user can suspend the model.
     */
    public function suspension(User $user)
    {
        if ($user->can('card-charge.suspension')) {
            return true;
        }
    }


    /**
     * Determine whether the user can findBySerial the model.
     */
    public function findBySerial(User $user)
    {
        if ($user->can('card-charge.findBySerial')) {
            return true;
        }
    }

    /**
     * Determine whether the user can freeReport the model.
     */
    public function freeReport(User $user)
    {
        if ($user->can('card-charge.freeReport')) {
            return true;
        }
    }

}
