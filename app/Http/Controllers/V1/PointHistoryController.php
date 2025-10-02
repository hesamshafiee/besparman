<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PointHistoryResource;
use App\Models\PointHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class PointHistoryController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @group PointHistory
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', PointHistory::class);


        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $pointHistories = PointHistory::orderBy($order, $typeOrder)->paginate($perPage);

        return Response::jsonMacro(PointHistoryResource::collection($pointHistories));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group PointHistory
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $pointHistories = PointHistory::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage);

        return Response::jsonMacro(PointHistoryResource::collection($pointHistories));
    }
}
