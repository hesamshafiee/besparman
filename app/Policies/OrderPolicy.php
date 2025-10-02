<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('order.show')) {
            return true;
        }
    }

   

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('order.update')) {
            return true;
        }
    }

   
}
