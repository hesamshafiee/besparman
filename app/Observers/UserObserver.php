<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * @param User $user
     * @return void
     */
    public function creating(User $user): void
    {
        $user->presenter_code = Str::random(7);
    }

    /**
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        $user->wallet()->create();
    }
}
