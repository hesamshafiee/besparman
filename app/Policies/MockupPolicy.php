<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MockupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any category.
     *
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('mockup.show')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create category.
     *
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('mockup.create')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the category.
     *
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('mockup.update')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('mockup.delete')) {
            return true;
        }
    }

    /**
     * Determine whether the user can work with private images the category.
     *
     * @param User $user
     * @return bool|void
     */
    public function image(User $user)
    {
        if ($user->can('mockup.image')) {
            return true;
        }
    }
}
