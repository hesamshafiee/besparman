<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('tag.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('tag.create')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('tag.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('tag.delete')) {
            return true;
        }
    }
}
