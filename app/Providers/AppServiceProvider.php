<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Response as status;
use Symfony\Component\HttpFoundation\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Response::macro('ok', function ($message) {
            return response()->json([
                'status' => true,
                'message' => $message,
            ], status::HTTP_OK);
        });

        Response::macro('forbidden', function ($message = null) {
            return response()->json([
                'message' => $message ?? 'You don not have permission',
            ], status::HTTP_FORBIDDEN);
        });

        Response::macro('unauthorized', function ($message) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], status::HTTP_UNAUTHORIZED);
        });

        Response::macro('unprocessable', function ($message) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], status::HTTP_UNPROCESSABLE_ENTITY);
        });

        Response::macro('serverError', function ($message) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], status::HTTP_INTERNAL_SERVER_ERROR);
        });

        Response::macro('jsonMacro', function ($data, $additional = []) {
            if ($data) {
                $data = $data->additional([
                    'balance' => optional(optional(Auth::user())->wallet)->value ?? 0,
                    'additional' => $additional
                ]);

                return $data->response();
            }

            return response()->json([
                'balance' => optional(optional(Auth::user())->wallet)->value ?? 0,
                'additional' => $additional,
                'data' => null
            ]);
        });

        if($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
