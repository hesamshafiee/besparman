<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryResource;
use App\Models\Delivery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Delivery
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Delivery::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new DeliveryResource(Delivery::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(DeliveryResource::collection(Delivery::orderBy($order, $typeOrder)->paginate($perPage)));
    }
}
