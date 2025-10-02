<?php

namespace App\Http\Controllers\V1;

use App\Events\CreateToken;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\TokenResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    /**
     * @return JsonResponse
     * @group Token
     */
    public function index(): JsonResponse
    {
        return response()->jsonMacro(TokenResource::collection(PersonalAccessToken::where('tokenable_id', Auth::id())->where('name', '!=', 'refreshToken')->orderByDesc('created_at')->paginate(100)), ['currentAccessToken' => Auth::user()->currentAccessToken()->token, 'id' => Auth::user()->currentAccessToken()->id]);
    }

    /**
     * @return JsonResponse
     * @group Token
     */
    public function create(): JsonResponse
    {
        if (Auth::user()->isWebservice()) {
            $token = Auth::user()->createToken(User::TYPE_WEBSERVICE);
            if ($token) {
                return response()->json([
                    'token' => $token->plainTextToken,
                ]);
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PersonalAccessToken $personalAccessToken
     * @return JsonResponse
     * @group Token
     */
    public function destroy(PersonalAccessToken $personalAccessToken): JsonResponse
    {
        if ($personalAccessToken->tokenable_id !== Auth::id()) {
            if ($personalAccessToken->delete()) {
                return response()->ok(__('general.deletedSuccessfully', ['id' => $personalAccessToken->id]));
            }
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @return JsonResponse
     * @group Token
     */
    public function refreshToken(): JsonResponse
    {
        $user = Auth::user();
        if ($user->currentAccessToken()->name === 'refreshToken') {
            $user->tokens()->whereNotNull('expires_at')->where('expires_at', '<', now())->delete();

            $newAccessTokenObj = $user->createToken(User::TYPE_PANEL, ['*'], now()->addHour());
            $newAccessToken = $newAccessTokenObj->plainTextToken;
            $tokenModel = $newAccessTokenObj->accessToken;

            $newRefreshToken = $user->createToken('refreshToken', ['refresh'], now()->addHours(3))->plainTextToken;

            CreateToken::dispatch(
                $user,
                $tokenModel,
                request()->ip(),
                request()->header('User-Agent')
            );

            return response()->json([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken
            ]);
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @return JsonResponse
     * @group Token
     */
    public function destroyAll(): JsonResponse
    {
        $response = PersonalAccessToken::where('tokenable_id', Auth::id())->whereNot('id', Auth::user()->currentAccessToken()->id)->delete();

        if (is_int($response)) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => '']));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->ok('Logged out successfully');

    }
}
