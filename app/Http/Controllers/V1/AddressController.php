<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\AddressRequest;
use App\Http\Resources\V1\AddressResource;
use App\Models\Address;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AddressController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Address
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Address::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new AddressResource(Address::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(AddressResource::collection(Address::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Address
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new AddressResource(Address::where('user_id', auth()->id())->where('id', $id)->firstOrFail()));
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

        return response()->jsonMacro(AddressResource::collection(Address::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param AddressRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Address
     */
    public function store(AddressRequest $request): JsonResponse
    {
        $this->authorize('create', Address::class);

        $address = new Address();
        $address->fill($request->safe()->all());

        if ($address->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     *
     * @param AddressRequest $request
     * @return JsonResponse
     * @group Address
     */
    public function clientStore(AddressRequest $request): JsonResponse
    {

        $address = new Address();
        $address->fill($request->safe()->all());
        $address->user_id = Auth::id();

        if ($address->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param AddressRequest $request
     * @param Address $address
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Address
     */
    public function update(AddressRequest $request, Address $address): JsonResponse
    {
        $this->authorize('update', $address);

        $address->fill($request->safe()->all());

        if ($address->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $address->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     *
     * @param AddressRequest $request
     * @param Address $address
     * @return JsonResponse
     * @group Address
     */
    public function clientUpdate(AddressRequest $request, Address $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return response()->serverError('Access denied');
        }
        $address->fill($request->safe()->except('user_id'));
        $address->user_id = Auth::id(); 

        if ($address->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $address->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Address $address
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Address
     */
    public function destroy(Address $address): JsonResponse
    {
        $this->authorize('delete', $address);

        if ($address->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $address->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }


        /**
     * @param Address $address
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Address
     */
    public function clienteStroy(Address $address): JsonResponse
    {
        if ($address->user_id !== Auth::id()) {
            return response()->serverError('Access denied');
        }
        if ($address->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $address->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
