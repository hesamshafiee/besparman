<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Order
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Order::class);

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

         $records = Order::orderBy($order, $typeOrder)
            ->paginate($perPage);

        $orderIds = $records->pluck('id')->toArray();

        return response()->jsonMacro(
            OrderResource::collection(
                Order::whereIn('id', $orderIds)->orderBy($order, $typeOrder)->paginate($perPage)
            )
        );
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Order
     */
    public function clientIndexOrder(Request $request): JsonResponse
    {

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns, true)) {
            $order = 'id';
        }

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'], true)) {
            $typeOrder = 'desc';
        }

        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->whereHas('products', function ($query) {
                $query->where('type', 'cart');
            })
            ->orderBy($order, $typeOrder)
            ->paginate($perPage);

        return response()->jsonMacro(OrderResource::collection($orders));
    }


    /**
     *
     * @param [type] $orderId
     * @return JsonResponse
     * @group Order
     */
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
    /**
     *
     * @param Request $request
     * @param Order $order
     * @return void
     * @group Order
     */
    public function updateStatus(Request $request, Order $order)
    {
        
        $this->authorize('update', Order::class);

        $request->validate([
            'status' => 'required|string|in:canceled,posted,preparation,paid,reserved,unpaid,received',
        ]);
        if ($order->status == Order::STATUSRESERVED) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or access denied.'
            ], 404);
        }
        $order->status = $request->status;
         if ($order->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $order->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
