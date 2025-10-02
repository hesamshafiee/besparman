<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SettingRequest;
use App\Http\Resources\V1\SettingResource;
use App\Models\Setting;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Setting
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Setting::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new SettingResource(Setting::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(SettingResource::collection(Setting::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param SettingRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Setting
     */
    public function store(SettingRequest $request): JsonResponse
    {
        $this->authorize('create', Setting::class);

        $settingsArray['sms'] = $request->sms;
        $settingsArray['email'] = $request->email;
        $settingsArray['auth'] = $request->auth;
        $settingsArray['otp'] = $request->otp;
        $settingsArray['jwt_expiration_time'] = $request->jwt_expiration_time;
        $settingsArray['bank_default'] = $request->bank_default;
        $settingsArray['bank_saman_status'] = $request->bank_saman_status;
        $settingsArray['bank_mellat_status'] = $request->bank_mellat_status;

        $setting = new Setting();
        $setting->settings = $settingsArray;

        if ($setting->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param SettingRequest $request
     * @param Setting $setting
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Setting
     */
    public function update(SettingRequest $request, Setting $setting): JsonResponse
    {
        $this->authorize('update', Setting::class);

        $setting->settings['sms'] = $request->sms ?? $setting->settings['sms'];
        $setting->settings['email'] = $request->email ?? $setting->settings['email'];
        $setting->settings['auth'] = $request->auth ?? $setting->settings['auth'];
        $setting->settings['otp'] = $request->otp ?? $setting->settings['otp'];
        $setting->settings['jwt_expiration_time'] = $request->jwt_expiration_time ?? $setting->settings['jwt_expiration_time'];
        $setting->settings['bank_default'] = $request->bank_default;
        $setting->settings['bank_saman_status'] = $request->bank_saman_status;
        $setting->settings['bank_mellat_status'] = $request->bank_mellat_status;

        $activeSettings = Setting::where('status', 1)->first();

        if ((int) $request->status && $activeSettings && $activeSettings->id !== $setting->id) {
            return response()->serverError('You just can have one active setting');
        }

        $setting->status = $request->status ?? $setting->status;

        if ($setting->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $setting->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Setting $setting
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Setting
     */
    public function destroy(Setting $setting): JsonResponse
    {
        $this->authorize('delete', Setting::class);

        if ($setting->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $setting->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
