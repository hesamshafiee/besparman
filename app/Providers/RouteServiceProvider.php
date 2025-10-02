<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(env('GENERAL_RATE_LIMIT', 5))->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinutes(env('AUTH_RATE_LIMIT_TIME', 2), env('AUTH_RATE_LIMIT', 5))->by(optional($request)->mobile ?: $request->ip());
        });

        RateLimiter::for('top-up-limit', function (Request $request) {
            $payload = $request->all();
            $token = $request->bearerToken();
            $user = null;

            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }

            ksort($payload);
            array_walk_recursive($payload, function (&$value) {
                if (is_array($value)) ksort($value);
            });

            $normalizedPayload = json_encode($payload);

            if ($user) {
                if (method_exists($user, 'isPanel') && $user->isPanel()) {
                    $key = 'topup:' . md5($normalizedPayload . $user->id);
                    return [
                        Limit::perSecond(1)->by($key),
                    ];
                } else {
                    return Limit::none();
                }
            }

            $key = 'topup:guest:' . md5($normalizedPayload . $request->ip());
            return [
                Limit::perSecond(1)->by($key),
            ];
        });
    }
}
