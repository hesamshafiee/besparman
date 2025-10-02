<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ValidateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
//        if (env('APP_ENV') === 'production' && Auth::check()) {
//            $isWebservice = Auth::user()->isWebservice();
//            $profile = Auth::user()->profile;
//            $ips = empty($profile->ips) ? [] : explode('-', $profile->ips);
//            $token = Auth::user()->currentAccessToken();
//
//            if (!$isWebservice || ($isWebservice && (!is_null($token->expires_at) || !in_array($request->ip(), $ips)))) {
//                $secretKey = env('APP_SECRET_KEY');
//                $receivedSignature = $request->header('X-Signature');
//
//                if (!$receivedSignature) {
//                    return response()->json(['error' => 'Missing signature'], Response::HTTP_UNAUTHORIZED);
//                }
//
//                $parts = explode(':', $receivedSignature);
//
//                if (count($parts) !== 2) {
//                    return response()->json(['error' => 'Invalid signature format'], Response::HTTP_UNAUTHORIZED);
//                }
//
//                [$receivedTimestamp, $receivedHash] = $parts;
//
//                if (!ctype_digit($receivedTimestamp)) {
//                    return response()->json(['error' => 'Invalid timestamp format'], Response::HTTP_UNAUTHORIZED);
//                }
//
//                if (!preg_match('/^[a-f0-9]{64}$/i', $receivedHash)) {
//                    return response()->json(['error' => 'Invalid hash format'], Response::HTTP_UNAUTHORIZED);
//                }
//
//                $currentTimestamp = time();
//                $differentialTime = abs($currentTimestamp - $receivedTimestamp);
////                if ($differentialTime < 0 || $differentialTime > 10) {
////                    Log::critical('currentTimestamp: ' . $currentTimestamp . "\n" .
////                        'receivedTimestamp: ' . $receivedTimestamp . "\n" .
////                        'differentialTime: ' . $differentialTime . "\n" .
////                        'ip: ' . $request->ip() . "\n" .
////                        'path: ' . $request->path() . "\n" .
////                        'agent: ' . $request->header('User-Agent') . "\n" .
////                        'X-Different: ' . $request->header('X-Different') . "\n"
////                    );
////                    return response()->json(['error' => 'Signature expired'], Response::HTTP_UNAUTHORIZED);
////                }
//
//                $calculatedSignature = hash_hmac(
//                    'sha256',
//                    json_encode(['timestamp' => $receivedTimestamp]),
//                    $secretKey
//                );
//
//                if (!hash_equals($calculatedSignature, $receivedHash)) {
//                    return response()->json(['error' => 'Invalid signature'], Response::HTTP_UNAUTHORIZED);
//                }
//            }
//        }

        return $next($request);
    }
}
