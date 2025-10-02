<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\LogResource;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Models\Activity;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class LogController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @group Log
     */
    public function activityLog(Request $request): JsonResponse
    {
        if (Auth::user()->can('log.show')) {
            $order = $request->query('order', 'id');
            $typeOrder = $request->query('type_order', 'desc');
            $perPage = (int) $request->query('per_page', 10);
            return response()->jsonMacro(LogResource::collection(Activity::orderBy($order, $typeOrder)->paginate($perPage)));
        } else {
            return response()->forbidden();
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     * @group Log
     */
    public function laravelLog(Request $request): JsonResponse
    {
        if (Auth::user()->can('log.show')) {
            $validated = $request->validate([
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = $validated['per_page'] ?? 15;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();

            $query = DB::connection('mongodb')->table('logs');

            if (!empty($validated['start_date'])) {
                $query->where('created_at', '>=', new UTCDateTime(new DateTime($validated['start_date'])));
            }

            if (!empty($validated['end_date'])) {
                $endDate = new DateTime($validated['end_date']);
                $endDate->modify('+1 day');
                $query->where('created_at', '<', new UTCDateTime($endDate));
            }

            $results = $query
                ->orderBy('created_at', 'desc')
                ->skip(($currentPage - 1) * $perPage)
                ->take($perPage)
                ->get();

            $total = $query->count();

            $logs = new LengthAwarePaginator(
                $results,
                $total,
                $perPage,
                $currentPage,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query()
                ]
            );

            return response()->json([
                'logs' => $logs,
            ], 200);
        } else {
            return response()->forbidden();
        }
    }
}
