<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @return bool|void
     */
    public function show(User $user)
    {
        if ($user->can('walletTransaction.show')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function update(User $user)
    {
        if ($user->can('walletTransaction.update')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function increaseByAdmin(User $user)
    {
        if ($user->can('walletTransaction.increaseByAdmin')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function decreaseByAdmin(User $user)
    {
        if ($user->can('walletTransaction.decreaseByAdmin')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function confirmTransfer(User $user)
    {
        if ($user->can('walletTransaction.confirmTransfer')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function rejectTransfer(User $user)
    {
        if ($user->can('walletTransaction.rejectTransfer')) {
            return true;
        }
    }

    /**
     * @param User $user
     * @return bool|void
     */
    public function cardToCard(User $user)
    {
        if ($user->can('walletTransaction.cardToCard')) {
            return true;
        }
    }
}
