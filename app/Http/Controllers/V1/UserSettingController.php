<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserSettingResource;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;


class UserSettingController extends Controller
{
   /**
    *
    * @param Request $request
    * @return void
    * @group UserSetting
    */
    public function clientIndex(Request $request)
    {
        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $userSetting = UserSetting::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage);

        return Response::jsonMacro(UserSettingResource::collection($userSetting));
    }

    
    /**
     *
     * @param Request $request
     * @return void
     * @group UserSetting
     */
    public function clientStore(Request $request) 
    { 
        if (UserSetting::where('user_id', Auth::id())->exists()) { 
            return response()->serverError(__('general.somethingWrong')); 
        } 

        $validated = $request->validate([ 
            'settings' => 'required|array', 
            'settings.minimum_wallet' => 'required|integer|min:0', 
            'settings.otp_telegram' => 'integer|min:0|max:1', 
        ]); 

        $userSetting = new UserSetting(); 
        $userSetting->user_id = Auth::id(); 
        $userSetting->settings = $validated['settings']; 

        if ($userSetting->save()) { 
            return response()->ok(__('general.savedSuccessfully')); 
        } 

        return response()->serverError(__('general.somethingWrong')); 
    }

   
    /**
     *
     * @param Request $request
     * @param string $id
     * @return void
     * @group UserSetting
     */
    public function clientUpdate(Request $request, string $id)
    {
        $userSetting = UserSetting::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        
         $validated = $request->validate([
            'settings' => 'required|array',
            'settings.minimum_wallet' => 'sometimes|integer|min:0', 
            'settings.otp_telegram' => 'integer|min:0|max:1',
        ]);

        $userSetting->settings = $validated['settings'] ?? [];
        
        if ($userSetting->save()) {
            return response()->ok(__('general.updatedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

   /**
    *
    * @param string $id
    * @return void
    * @group UserSetting
    */
    public function clientDestroy(string $id)
    {
        $userSetting = UserSetting::where('user_id', Auth::id())->where('id', $id)->firstOrFail();

        if ($userSetting->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $userSetting->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
