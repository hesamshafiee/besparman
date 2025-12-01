<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OptionValuePolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('option-value.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('option-value.create')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('option-value.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('option-value.delete')) {
            return true;
        }
    }
}
