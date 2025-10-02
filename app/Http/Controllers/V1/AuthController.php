<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\AuthRequest;
use App\Http\Requests\V1\Auth\ResetRequest;
use App\Http\Requests\V1\Auth\SetRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Setting;
use App\Services\V1\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * @param AuthRequest $request
     * @return JsonResponse
     * @group Auth
     */
    public function auth(AuthRequest $request) : JsonResponse
    {
        $authService = new AuthService();
        $authService->mobile = $request->mobile;
        $authService->code = $request->code;
        $authService->password = $request->password;
        $authService->twoStepType = $request->twoStepType ?? null;
        $authService->otpForce = $request->otpForce ?? false;
        return $authService->serviceController();
    }

    /**
     * @param SetRequest $request
     * @return JsonResponse
     * @group Auth
     */
    public function setPassword(SetRequest $request) : JsonResponse
    {
        $authService = new AuthService();
        $authService->password = $request->password;
        return $authService->setPassword();
    }

    /**
     * @param ResetRequest $request
     * @return JsonResponse
     * @group Auth
     */
    public function resetPassword(ResetRequest $request) : JsonResponse
    {
        $authService = new AuthService();
        $authService->mobile = $request->mobile;
        $authService->code = $request->code;
        $authService->password = $request->password;
        $authService->otpForce = $request->otpForce ?? false;
        return $authService->resetPassword();
    }

    /**
     * @param ResetRequest $request
     * @return JsonResponse
     * @group Auth
     */
    public function resetGoogle2fa(ResetRequest $request) : JsonResponse
    {
        $authService = new AuthService();
        $authService->mobile = $request->mobile;
        $authService->code = $request->code;
        $authService->password = $request->password;
        $authService->otpForce = $request->otpForce ?? false;
        return $authService->resetGoogle2fa();
    }

    /**
     * @return JsonResponse
     * @group Auth
     */
    public function authType() : JsonResponse
    {
        return response()->json([
            'status' => true,
            'auth' => config('general.auth'),
            'otp' => config('general.otp'),
        ], 200);
    }
    /**
     * @group Auth
     *
     * @return JsonResponse
     */
    public function checkToken(): JsonResponse
    {
        return response()->json([
            'user' => new UserResource(Auth::user()),
            'setting' => optional(Setting::where('status', 1)->first())->toArray(),
        ], 200);
    }
}
