<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('menu.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('menu.create')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('menu.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('menu.delete')) {
            return true;
        }
    }
}
