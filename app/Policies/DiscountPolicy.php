<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DiscountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any discount.
     *
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('discount.show')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create discount.
     *
     * @param User $user
     * @return bool|void
     */
    public function create(User $user)
    {
        if ($user->can('discount.create')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the discount.
     *
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('discount.update')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the discount.
     *
     * @param User $user
     * @return bool|void
     */
    public function delete(User $user)
    {
        if ($user->can('discount.delete')) {
            return true;
        }
    }

    /**
     * Determine whether the user can work with private images the discount.
     *
     * @param User $user
     * @return bool|void
     */
    public function image(User $user)
    {
        if ($user->can('discount.image')) {
            return true;
        }
    }
}
