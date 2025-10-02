<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Models\IdempotencyKey;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class EnsureIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return response()->json(['message' => 'Missing Idempotency-Key header'], 400);
        }

        $user = $this->getUserFromToken($request);
        $ownerId = $user?->id ?? $request->ip();

        $requestHash = sha1(
            $request->method() .
            '|' . $request->path() .
            '|' . json_encode($request->all())
        );

        $now = Carbon::now();
        $ttl = 24;

        $record = IdempotencyKey::where('idempotency_key', $key)
            ->where('user_id', $ownerId)
            ->where('request_hash', $requestHash)
            ->first();

        if ($record) {
            if ($record->response_body !== null) {
                return $this->makeResponseFromStored($record);
            }

            if ($record->locked_at && $record->locked_at->diffInSeconds($now) < 30) {
                return response()->json(['message' => 'Request is already being processed'], 409);
            }
        } else {
            try {
                IdempotencyKey::create([
                    'idempotency_key' => $key,
                    'user_id' => $ownerId,
                    'request_hash' => $requestHash,
                    'locked_at' => $now,
                    'expires_at' => $now->copy()->addHours($ttl),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                return response()->json(['message' => 'Request is already being processed'], 409);
            }
        }

        $response = $next($request);

        IdempotencyKey::where('idempotency_key', $key)
            ->where('user_id', $ownerId)
            ->where('request_hash', $requestHash)
            ->update([
                'response_status' => $response->getStatusCode(),
                'response_headers' => $response->headers->all(),
                'response_body' => $response->getContent(),
            ]);

        return $response;
    }

    /**
     * @param Request $request
     * @return mixed|null
     */
    protected function getUserFromToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) return null;

        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken?->tokenable;
    }

    /**
     * @param IdempotencyKey $record
     * @return ResponseFactory|Application|Response|object
     */
    protected function makeResponseFromStored(IdempotencyKey $record)
    {
        $res = response($record->response_body, $record->response_status);

        foreach ($record->response_headers ?? [] as $name => $values) {
            $res->headers->set($name, implode(',', $values));
        }

        return $res;
    }
}
