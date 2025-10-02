<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Financial\DiscountRequest;
use App\Http\Resources\V1\DiscountResource;
use App\Models\Discount;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Discount
     */
    public function index(Request $request) : JsonResponse
    {
        $this->authorize('show', Discount::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new DiscountResource(Discount::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(DiscountResource::collection(Discount::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param DiscountRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Discount
     */
    public function store(DiscountRequest $request) : JsonResponse
    {
        $this->authorize('create', Discount::class);

        $status = false;
        $counter = 0;

        while ($counter++ < $request->validated('count', 1)) {
            $discount = new Discount();
            $discount->fill($request->safe()->all());

            if (!$request->has('code') || $request->validated('count', 1) !== 1) {
                $discount->code = Str::random(10);

            }

            if ($discount->save()) {
                if ($request->has('users')) {
                    $discount->users()->sync($request->users);
                }
                if ($request->has('products')) {
                    $discount->products()->sync($request->products);
                }

                $status = true;
            }
        }

        if ($status) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @param DiscountRequest $request
     * @param Discount $discount
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Discount
     */
    public function update(DiscountRequest $request, Discount $discount) : JsonResponse
    {
        $this->authorize('update', Discount::class);

        $discount->fill($request->safe()->all());

        if ($discount->save()) {
            if ($request->has('users')) {
                $discount->users()->sync($request->users);
            }
            if ($request->has('products')) {
                $discount->products()->sync($request->products);
            }
            return response()->ok(__('general.updatedSuccessfully', ['id' => $discount->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Discount $discount
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Discount
     */
    public function destroy(Discount $discount) : JsonResponse
    {
        $this->authorize('delete', Discount::class);

        if ($discount->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $discount->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
