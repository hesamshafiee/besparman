<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateGroupChargeRequest;
use App\Http\Requests\V1\GroupChargeRequest;
use App\Http\Requests\V1\StoreGroupChargeRequest;
use App\Http\Resources\V1\GroupChargeDetailResource;
use App\Http\Resources\V1\GroupChargeResource;
use App\Models\GroupCharge;
use App\Models\GroupChargeDetail;
use App\Models\Product;
use App\Models\User;
use App\Services\V1\Esaj\GroupChargeOperation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GroupChargeController extends Controller
{
    /**
     * @param int|null $id
     * @return JsonResponse
     * @group GroupCharge
     */
    public function index(Request $request): JsonResponse
    {


        $this->authorize('show', GroupCharge::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new GroupChargeResource(
                GroupCharge::where(
                    [
                        'id' => $id
                    ]
                )->firstOrFail()
            ));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(GroupChargeResource::collection(GroupCharge::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * Update the force attribute of a GroupCharge.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     * @group GroupCharge
     */
    public function updateForce(int $id): JsonResponse
    {
        $TimeAllowedForCancellation = time() - GroupCharge::TIMESECONDALLOWEDFORCANCELLATION;
        $TimeAllowedForCancellation = date("Y-m-d H:i:s", $TimeAllowedForCancellation);

        $groupCharge = GroupCharge::where('id', $id)
            ->where('charge_status', GroupCharge::CHARGE_STATUS_PENDING)
            ->where('created_at', '>', $TimeAllowedForCancellation)
            ->first();
        if (!$groupCharge) {
                return response()->serverError('Not Found');
            }
        if ($groupCharge->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->serverError('Access denied');
            }


        $groupCharge->force = GroupCharge::CHARGE_FORCE_ACTIVE;

        if ($groupCharge->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new GroupChargeResource(
                GroupCharge::where(
                    [
                        'user_id' => Auth::id(),
                        'id' => $id
                    ]
                )->firstOrFail()
            ));
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

        return response()->jsonMacro(GroupChargeResource::collection(GroupCharge::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     * @group GroupCharge
     */
    public function storeTopup(Request $request, Product $product): JsonResponse
    {

        $groupCharge = new GroupCharge();
        $min = 'min:' . $product->price;
        $max = (is_null($product->second_price) || $product->price > $product->second_price) ? '' : 'max:' . $product->second_price;

        $validated = $request->validate([
            'phone_numbers' => ['required', 'json'],
            'price' => ['required', 'numeric', $min, $max],
            'refCode' => ['string'],
            'operator_type' => ['string'],
        ]);
        $groupCharge->fill($request->all());
        $groupCharge->user_id = Auth::id();
        $groupCharge->group_type = GroupCharge::TYPE_TOPUP;
        $groupCharge->topup_information = json_encode([
            'price' => $request->price,
            'refCode' => $request->refCode,
            'operator_type' => $request->operator_type,
            'product_id' => $product->id,
            'ip' => \Illuminate\Support\Facades\Request::ip(),
        ]);

        $product = Product::where(
            [
                'id' => $product->id
            ]
        )
            ->first();

        $groupCharge->operator_id = isset($product->operator_id) ? $product->operator_id : null;


        if ($groupCharge->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }
        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Request $request
     * @param Product $product
     * @return JsonResponse
     * @group GroupCharge
     */
    public function storeTopupPackage(Request $request, Product $product): JsonResponse
    {
        $groupCharge = new GroupCharge();
        $min = 'min:' . $product->price;
        $max = (is_null($product->second_price) || $product->price > $product->second_price) ? '' : 'max:' . $product->second_price;

        $validated = $request->validate([
            'phone_numbers.*' => ['required', 'numeric', 'digits_between:12,12'],
            'refCode' => ['string'],
            'offerCode' => ['string'],
            'offerType' => ['string'],
            'operator_type' => ['string'],
            'price' => ['required', 'numeric']
        ]);

        $groupCharge->fill($request->all());
        $groupCharge->user_id = Auth::id();
        $groupCharge->group_type = GroupCharge::TYPE_TOPUP_PACKAGE;
        $groupCharge->topup_information = json_encode([
            'refCode' => $request->refCode,
            'offerCode' => $request->offerCode,
            'offerType' => $request->offerType,
            'price' => $request->price,
            'operator_type' => $request->operator_type,
            'product_id' => $product->id,
            'ip' => \Illuminate\Support\Facades\Request::ip(),
        ]);
        $product = Product::where(
            [
                'id' => $product->id
            ]
        )
            ->first();

        $groupCharge->operator_id = isset($product->operator_id) ? $product->operator_id : null;


        if ($groupCharge->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }
        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @param int $id
     * @return JsonResponse
     * @group GroupCharge
     *
     */
    public function Cancel(int $id): JsonResponse
    {
        $TimeAllowedForCancellation = time() - GroupCharge::TIMESECONDALLOWEDFORCANCELLATION;
        $TimeAllowedForCancellation = date("Y-m-d H:i:s", $TimeAllowedForCancellation);

        $model = GroupCharge::where('id', $id)
            ->where('charge_status', GroupCharge::CHARGE_STATUS_PENDING)
            ->where('created_at', '>', $TimeAllowedForCancellation)
            ->first();

        if ($model) {
            if ($model->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
                return response()->serverError('Access denied');
            }
            $model->charge_status = GroupCharge::CHARGE_STATUS_CANCELED;
            $model->save();
            return response()->ok(__('general.savedSuccessfully'));
        } else {
            return response()->serverError(__('general.somethingWrong'));
        }
    }

}
