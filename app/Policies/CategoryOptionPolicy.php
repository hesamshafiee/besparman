<?php

namespace App\Policies;

use App\Models\CategoryOption;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryOptionPolicy
{
    use HandlesAuthorization;



    /**
     * Determine whether the user can view the model.
     */
    public function show(User $user)
    {
        if ($user->can('category-option.show')) {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        if ($user->can('category-option.create')) {
            return true;
        }
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user)
    {
        if ($user->can('category-option.update')) {
            return true;
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user)
    {
       if ($user->can('category-option.delete')) {
            return true;
        }
    }
}
