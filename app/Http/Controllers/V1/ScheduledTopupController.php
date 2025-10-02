<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\scheduledTopupResource;
use App\Models\ScheduledTopup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScheduledTopupController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ScheduledTopup
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            $this->authorize('show', ScheduledTopup::class);
        }

        $query = ScheduledTopup::query();

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new scheduledTopupResource(ScheduledTopup::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(scheduledTopupResource::collection($query->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ScheduledTopup
     */

    public function cancel($id): JsonResponse
    {
        $user = auth()->user();

        $topup = ScheduledTopup::where('id', $id)
            ->when(!$user->isAdmin(), fn($query) => $query->where('user_id', $user->id))
            ->where('status', 'pending')
            ->firstOrFail();

        if ($user->isAdmin()) {
            $this->authorize('cancel', $topup);
        }

        $topup->update(['status' => 'canceled']);

        return response()->json([
            'status' => true,
            'message' => 'Scheduled top-up canceled successfully'
        ]);
    }
}
