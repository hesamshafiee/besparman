<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Order;
use App\Models\ReportDailyBalance;
use App\Models\User;
use App\Models\WalletTransactionExtra;
use App\Notifications\V1\SmsSystem;
use App\Services\V1\Image\Image;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Token
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', User::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new UserResource(User::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(UserResource::collection(User::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param UserRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(UserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = new User();
        $user->fill($request->safe()->all());

        if ($user->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function indexSoftDeleted(): JsonResponse
    {
        $this->authorize('show', User::class);

        $softDeletedUsers = User::onlyTrashed()->orderByDesc('created_at')->paginate(100);

        return response()->jsonMacro(UserResource::collection($softDeletedUsers));

    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreUser(int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->authorize('update', User::class);

        if ($user->restore()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $user->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param UserRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', User::class);

        $user->fill($request->safe()->all());
        if ($request->has('profile_confirm')) {
            $user->profile_confirm = empty($request->get('profile_confirm')) ? null : now();
        }
        if (!empty($request->profile_confirm) && empty($user->getOriginal('profile_confirm'))) {
//            $user->notify(new SmsSystem(__('sms.profileConfirmed', ['phoneNumber' => $user->mobile]), 'force'));
        }

        if ($user->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $user->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    public function twoStepStatus(): JsonResponse
    {
        Auth::user()->two_step = !Auth::user()->two_step;
        if (Auth::user()->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => Auth::id()]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', User::class);

//        if ($user->orders()->doesntExist() && $user->transactions()->doesntExist() && $user->wallets->value === '0.0000' && $user->isOrdinary()) {
//            return DB::transaction(function () use ($user) {
//                $user->wallets()->forceDelete();
//                $user->verifications()->forceDelete();
//                DB::table('report_daily_balances')->where('user_id', $user->id)->delete();
//                if ($user->forceDelete()) {
//                    return response()->ok(__('general.deletedSuccessfully', ['id' => $user->id]));
//                }
//
//                return response()->serverError(__('general.somethingWrong'));
//            });
//        }
        if ($user->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $user->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function addImages(UserRequest $request): JsonResponse
    {
        if ($request->exists('images')) {
            $response = Image::modelImages(Auth::user(), $request->file('images'), Image::DRIVER_LOCAL);

            if ($response) {
                return response()->ok(__('general.savedSuccessfully'));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @return JsonResponse
     */
    public function getFinancialInfo(): JsonResponse
    {
        $results = WalletTransactionExtra::where('user_id', Auth::id())->where('third_party_status', 1)->where('created_at', '>=', Carbon::today());

        $takenValues = $results->sum('taken_value');
        $values = $results->sum('value');

        return response()->json([
            'takenValues' => $takenValues,
            'values' => $values,
            'profit' => abs($takenValues - $values)
        ], status::HTTP_OK);
    }

    public function getOrder($orderId): JsonResponse
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            $order = Order::with('products')->find($orderId);
        } else {
            $order = Order::with('products')
                ->where('id', $orderId)
                ->where('user_id', $user->id)
                ->first();
        }


        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'orderables' => $order->products,
        ]);
    }



    public function clientCheckMobileChargedBefore($mobile): JsonResponse
    {
         if (!preg_match('/^\d{12}$/', $mobile)) {
            return response()->json(['error' => 'Mobile must be exactly 12 digits'], 422);
        }


        $exists = DB::table('charged_mobiles')
            ->where('user_id', Auth::id())
            ->where('mobile', $mobile)
            ->exists();

        return response()->json([
            'charged_before' => $exists
        ]);

    }
}


