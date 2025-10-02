<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProfileRequest;
use App\Http\Resources\V1\ProfileResource;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profile
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Profile::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new ProfileResource(Profile::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(ProfileResource::collection(Profile::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param ProfileRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profile
     */
    public function store(ProfileRequest $request): JsonResponse
    {
        $profile = new Profile();
        $profile->fill($request->safe()->all());

        if (!Auth::user()->profile_id) {
            return DB::transaction(function () use ($profile, $request) {
                if ($profile->save()) {
                    $user = Auth::user();
                    $user->profile_id = $profile->id;
                    $user->name = $request->name;
                    if ($user->save()) {
                        return response()->ok(__('general.savedSuccessfully'));
                    }
                }

                return response()->serverError(__('general.somethingWrong'));
            });
        }

        return response()->serverError('User has already profile you can just update current one');
    }

    /**
     * @param ProfileRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profile
     */
    public function storeByAdmin(ProfileRequest $request, User $user): JsonResponse
    {
        $this->authorize('create', Profile::class);

        $profile = new Profile();
        $profile->fill($request->safe()->all());

        if (!$user->profile_id) {
            return DB::transaction(function () use ($profile, $user, $request) {
                if ($profile->save()) {
                    $user->profile_id = $profile->id;
                    $user->name = $request->name;
                    if ($user->save()) {
                        return response()->ok(__('general.savedSuccessfully'));
                    }
                }

                return response()->serverError(__('general.somethingWrong'));
            });
        }

        return response()->serverError('User has already profile you can just update current one');
    }

    /***
     * @param ProfileRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profile
     */
    public function update(ProfileRequest $request): JsonResponse
    {
        $profile = Auth::user()->profile;

        if ($profile) {
            $oldNationalCode = $profile->national_code;
            $profile->fill($request->safe()->all());
            $profile->national_code = $oldNationalCode;

            return DB::transaction(function () use ($profile, $request) {
                if ($profile->save()) {
                    $user = Auth::user();
                    $user->name = $request->name;
                    if ($user->save()) {
                        return response()->ok(__('general.updatedSuccessfully', ['id' => $profile->id]));
                    }
                }

                return response()->serverError(__('general.somethingWrong'));
            });
        }

        abort(404);
    }

    /***
     * @param ProfileRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profile
     */
    public function updateByAdmin(ProfileRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', Profile::class);

        $profile = $user->profile;

        if ($profile) {
            $profile->fill($request->safe()->all());

            if ($request->ips) {
                $profile->ips = implode('-', $request->ips);
            } else {
                $profile->ips = null;
            }

            return DB::transaction(function () use ($profile, $user, $request) {
                if ($profile->save()) {
                    $user->name = $request->name;
                    if ($user->save()) {
                        return response()->ok(__('general.updatedSuccessfully', ['id' => $profile->id]));
                    }
                }

                return response()->serverError(__('general.somethingWrong'));
            });
        }

        abort(404);
    }

    /**
     * @return JsonResponse
     * @group Profile
     */
    public function getProfileOfLoggedInUser(): JsonResponse
    {
        $profile = Auth::user()->profile;
        if ($profile) {
            return response()->jsonMacro(new ProfileResource($profile));
        }

        abort(404);
    }
}
