<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('payment.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function confirm(User $user)
    {
        if ($user->can('payment.confirm')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function reject(User $user)
    {
        if ($user->can('payment.reject')) {
            return true;
        }
    }

}
