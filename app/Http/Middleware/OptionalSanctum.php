<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OptionalSanctum
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                $token = $user->currentAccessToken();

                if ($token && $token->name !== 'refreshToken' && (is_null($token->expires_at) || !$token->expires_at->isPast())) {
                    Auth::setUser(
                        $user
                    );
                }


            } else {
                abort(401);
            }
        }

        if (!$request->bearerToken()) {
//            Log::emergency($request . '//' . $request->path() . ' // ' . $request->ip());
        }
        return $next($request);
    }
}
