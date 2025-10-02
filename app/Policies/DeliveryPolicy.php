<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return true|void
     */
    public function show(User $user)
    {
        if ($user->can('delivery.show')) {
            return true;
        }
    }
}
