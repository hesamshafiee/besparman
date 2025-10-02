<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ReportDailyBalanceResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class ReportController extends Controller
{
    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Report
     */
    public function dynamicAggregate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'collection'   => 'required|string',
            'wheres.*'     => 'string',
            'sums'         => 'required_with:groupBy|array',
            'sums.*'       => 'nullable|string',
            'groupBy'      => 'array',
            'groupBy.*'    => 'nullable|string',
            'sort'         => 'array',
            'sort.*'       => 'integer|in:1,-1',
            'limit'        => 'integer|min:1',
            'page'         => 'integer|min:1',
        ]);

        $wheres  = $data['wheres'] ?? [];
        $sums    = $data['sums'] ?? [];
        $groupBy = $data['groupBy'] ?? [];
        $sort    = $data['sort'] ?? [];
        $limit   = $data['limit'] ?? 10;
        $page    = $data['page'] ?? 1;
        $skip    = ($page - 1) * $limit;


        if (Auth::check() && Auth::user()->isAdmin() && !Auth::user()->can('mongo.' . $data['collection'])) {
            abort(401, 'Unauthorized access.');
        }

        if (!Auth::user()->isAdmin()) {
            $wheres['user_id'] = Auth::id();
        }

        $matchStage = $this->buildMatchStage($wheres);
        $aggregationPipeline = array_merge(
            $matchStage,
            $this->buildGroupAndProjectStages($sums, $groupBy),
            $this->buildSortStage($sort),
            [['$skip' => $skip], ['$limit' => $limit]]
        );

        $countPipeline = array_merge($matchStage, $this->buildGroupAndProjectStages($sums, $groupBy), [['$count' => 'total']]);

        try {
            $collection = DB::connection('mongodb')->selectCollection($data['collection']);
            $total      = $collection->aggregate($countPipeline)->toArray()[0]['total'] ?? 0;

            $records = $collection->aggregate($aggregationPipeline, ['typeMap' => ['root' => 'array']])->toArray();

            return response()->json(ReportDailyBalanceResource::collection([
                'data'       => $records,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $limit,
                    'current_page' => $page,
                    'last_page'    => ceil($total / $limit),
                ]
            ]));

        } catch (\Exception $e) {
            return response()->json(['error' => 'Query failed', 'details' => $e->getMessage()], 500);
        }
    }

    private function buildMatchStage(array $wheres): array
    {
        $conditions = [];

        foreach ($wheres as $field => $value) {
            if (str_ends_with($field, '_second')) continue;

            $rangeField = $field . '_second';
            if (isset($wheres[$rangeField])) {
                $conditions[$field] = [
                    '$gte' => new UTCDateTime(Carbon::parse($value)->startOfDay()->getTimestamp() * 1000),
                    '$lte' => new UTCDateTime(Carbon::parse($wheres[$rangeField])->endOfDay()->getTimestamp() * 1000),
                ];
                continue;
            }

            if (in_array($field, ['user_id', 'userId'])) {
                $conditions['$or'] = [
                    [$field => $value],
                    [$field => is_numeric($value) ? (int) $value : $value]
                ];
                continue;
            }

            if (is_array($value)) {
                $parsed = [];
                foreach ($value as $op => $val) {
                    $parsed[$op] = is_string($val) && strtotime($val)
                        ? new UTCDateTime(Carbon::parse($val)->getTimestamp() * 1000)
                        : $val;
                }
                $conditions[$field] = $parsed;
                continue;
            }

            if (is_string($value) && strtotime($value)) {
                $conditions[$field] = [
                    '$gte' => new UTCDateTime(Carbon::parse($value)->startOfDay()->getTimestamp() * 1000),
                    '$lte' => new UTCDateTime(Carbon::parse($value)->endOfDay()->getTimestamp() * 1000),
                ];
                continue;
            }

            $conditions[$field] = $value;
        }

        return empty($conditions) ? [] : [['$match' => $conditions]];
    }

    private function buildGroupAndProjectStages(array $sums, array $groupBy): array
    {
        if (empty($sums)) return [];

        $addFields = [];
        foreach ($sums as $field) {
            $addFields[$field] = ['$toDouble' => '$' . $field];
        }

        $groupId = $groupBy ? array_combine($groupBy, array_map(fn($f) => '$' . $f, $groupBy)) : null;
        $groupFields = ['count' => ['$sum' => 1]];
        foreach ($sums as $field) {
            $groupFields[$field] = ['$sum' => '$' . $field];
        }

        $project = ['_id' => 0, 'count' => 1];
        if ($groupId) {
            foreach ($groupId as $key => $_) {
                $project[$key] = '$_id.' . $key;
            }
        }
        foreach ($sums as $field) {
            $project[$field] = 1;
        }

        return [
            ['$addFields' => $addFields],
            ['$group' => ['_id' => $groupId] + $groupFields],
            ['$project' => $project]
        ];
    }

    private function buildSortStage(array $sort): array
    {
        return !empty($sort) ? [['$sort' => $sort]] : [];
    }
}
