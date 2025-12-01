<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OptionPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('option.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('option.create')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('option.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('option.delete')) {
            return true;
        }
    }
}
