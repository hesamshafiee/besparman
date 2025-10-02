<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\PrizeRequest;
use App\Http\Resources\V1\PrizePurchaseResource;
use App\Http\Resources\V1\PrizeResource;
use App\Models\Prize;
use App\Models\PrizeItem;
use App\Models\PrizePurchase;
use App\Models\Product;
use App\Models\User;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Image\Image;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PrizeController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Prize::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PrizeResource(Prize::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PrizeResource::collection(Prize::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Prize
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PrizeResource(Prize::where('status', 1)->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PrizeResource::collection(Prize::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function purchaseIndex(Request $request): JsonResponse
    {
        $this->authorize('show', Prize::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PrizePurchaseResource(PrizePurchase::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PrizePurchaseResource::collection(PrizePurchase::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Prize
     */
    public function purchaseClientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PrizePurchaseResource(PrizePurchase::where('status', 1)->where('user_id', Auth::id())->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PrizePurchaseResource::collection(PrizePurchase::where('status', 1)->where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * @param PrizeRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function store(PrizeRequest $request): JsonResponse
    {
        $this->authorize('create', Prize::class);

        $prize = new Prize();

        $prize->fill($request->safe()->all());
        if ($request->has('product_id') && $request->filled('product_id')) {
            
            $product = Product::find($request->input('product_id'));
            if ($product) {
                $prize->name = $product->name;
                $prize->operator_id = $product->operator_id;
                $prize->profile_id = $product->profile_id;
                $prize->type = $product->type;
            }
        }

        if ($prize->save()) {
            if ($request->has('tags')) {
                $prize->syncTagsWithType($request->get('tags'), 'prize');
            }
            if ($request->exists('images')) {
                Image::modelImages($prize, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PrizeRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function ItemsStore(Request $request): JsonResponse
    {
        $this->authorize('create', Prize::class);

        $validated = $request->validate([
            'prize_id' => ['required', 'numeric', 'exists:prizes,id'],
            'code.*' => ['required', 'string', ' max:100'],
        ]);

        foreach ($validated['code'] as $code) {
            $batchArray[] = ['code' => $code, 'prize_id' => $validated['prize_id'], 'created_at' => now(), 'updated_at' => now()];
        }

        $response = DB::table('prize_items')->insert($batchArray);

        if ($response) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param PrizeRequest $request
     * @param Prize $prize
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function update(PrizeRequest $request, Prize $prize): JsonResponse
    {
        $this->authorize('update', Prize::class);

        $prize->fill($request->safe()->all());
        
        if ($request->has('product_id') && $request->filled('product_id')) {
            
            $product = Product::find($request->input('product_id'));
            if ($product) {
                $prize->name = $product->name;
                $prize->operator_id = $product->operator_id;
                $prize->profile_id = $product->profile_id;
                $prize->type = $product->type;
            }
        }
        if ($prize->save()) {
            if ($request->has('tags')) {
                $prize->syncTagsWithType($request->get('tags'), 'prize');
            }
            if ($request->exists('images')) {
                Image::modelImages($prize, $request->file('images'), Image::DRIVER_PUBLIC);
            }
            return response()->ok(__('general.updatedSuccessfully', ['id' => $prize->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Prize $prize
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function destroy(Prize $prize): JsonResponse
    {
        $this->authorize('delete', Prize::class);

        if ($prize->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $prize->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Prize $prize
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function status(Prize $prize): JsonResponse
    {
        $this->authorize('update', Prize::class);

        $prize->status = !$prize->status;

        if ($prize->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $prize->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PrizePurchase $prizePurchase
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Prize
     */
    public function purchaseStatus(PrizePurchase $prizePurchase): JsonResponse
    {
        $this->authorize('update', Prize::class);

        $prizePurchase->status = true;
        $user = User::find($prizePurchase->user_id);
        $prize = Prize::find($prizePurchase->prize_id);
        $user->points -= $prize->point;

        return DB::transaction(function () use ($user, $prizePurchase) {
            if ($user->save()) {
                if ($prizePurchase->save()) {
                    return response()->ok(__('general.updatedSuccessfully', ['id' => $prizePurchase->id]));
                }
            }
            return response()->serverError(__('general.somethingWrong'));
        });
    }

    /**
     * @param Request $request
     * @param Prize $prize
     * @return JsonResponse
     * @group Prize
     */
    public function purchase(Request $request, Prize $prize): JsonResponse
    {
        return DB::transaction(function () use ($request, $prize) {
            if ($prize->type !== Prize::TYPE_PHYSICAL && $prize->type !== Prize::TYPE_DISCOUNT && $prize->type !== Prize::TYPE_INCREASE_PRIZE) {
                $validated = $request->validate([
                    'mobile' => ['required', 'numeric', 'min_digits:12', 'max_digits:12']
                ]);
            }

            $response = null;
            $user = Auth::user();

            if ($user->points < $prize->point) {
                return response()->serverError('Not enough points');
            }

            if (!$prize->status) {
                return response()->serverError('Inactive status');
            }

            $prizePurchase = new PrizePurchase();
            $prizePurchase->user_id = Auth::id();
            $prizePurchase->prize_id = $prize->id;
            $prizePurchase->price = $prize->price;
            $prizePurchase->points = $prize->point;
            $profile = $user ? $user->profile : false;
            $storeName = $profile ? $profile->store_name : 'esaj';
            $nationalCode = $profile ? $profile->national_code : '';
            $orderId = time() . Str::random(7);

            if ($prize->type === Prize::TYPE_PHYSICAL) {
                $prizePurchase->status = Prize::STATUS_INACTIVE;
                $status = true;
            } elseif ($prize->type === Prize::TYPE_DISCOUNT) {
                $prizePurchase->status = Prize::STATUS_INACTIVE;
                $status = false;
                $prizeItem = PrizeItem::where('prize_id', $prize->id)->where('used', 0)->lockForUpdate()->first();
                if ($prizeItem) {
                    $prizePurchase->status = Prize::STATUS_ACTIVE;
                    $prizePurchase->code = $prizeItem->code;
                    $prizeItem->used = PrizeItem::USED_TRUE;
                    if ($prizeItem->save()) {
                        $message = $prizeItem->code;
                        $status = true;
                    }
                } else {
                    $message = 'Prize not available';
                }
            } elseif ($prize->type === Prize::TYPE_INCREASE_PRIZE) {
                    $response = Wallet::IncreaseByPrize($prize->price, Auth::id(), '');
                    $status = $response['status'] ?? false; 
                    $message = 'Increased successfully';
                    if ($status === true) {
                        $prizePurchase->status = Prize::STATUS_ACTIVE;
                        $user->points -= $prize->point;
                    }

            } else {
                $operator = 'App\Services\V1\Esaj\\' . optional($prize->operator)->name;

                $esajService = new EsajService();
                $esajService->setGateway(new $operator());
                $mobileNumber = 0 . substr($validated['mobile'], 2);


                if ($prize->type === Prize::TYPE_CELL_DIRECT_CHARGE) {
                    $response = $esajService->topUp(
                        $mobileNumber,
                        (int) $prize->price,
                        $orderId,
                        $prize->type,
                        $nationalCode,
                        $storeName,
                        $prize->profile_id,
                        $prize->ext_id,
                        true
                    );
                } elseif (!empty($prize->profile_id) && $prize->type === Prize::TYPE_CELL_INTERNET_PACKAGE) {
                    $response = $esajService->topUpPackage(
                        $mobileNumber,
                        0,
                        $orderId,
                        '',
                        $nationalCode,
                        $storeName,
                        $prize->profile_id,
                        '',
                        '',
                        '',
                        true
                    );
                }

                $status = $response['status'] ?? false;
                $prizePurchase->third_party_info = json_encode($response);
                if ($status === true) {
                    $prizePurchase->status = Prize::STATUS_ACTIVE;
                    $user->points -= $prize->point;
                }
            }

            if ($user->save()) {
                if ($prizePurchase->save()) {
                    if ($status === true) {
                        return response()->ok($message ?? 'Purchased successfully');
                    }
                }
            }

            return response()->serverError($message ?? __('general.somethingWrong'));
        });
    }
}
