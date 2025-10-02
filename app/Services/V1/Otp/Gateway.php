<?php

namespace App\Services\V1\Otp;

use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * Interface Gateway
 */
interface Gateway
{
    public function init(User $user) : JsonResponse;
    public function send(User $user) : JsonResponse;
    public function verify(string $code, User $user) : bool;
}
