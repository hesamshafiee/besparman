<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;


    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('comment.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('comment.create')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('comment.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('comment.delete')) {
            return true;
        }
    }
}
