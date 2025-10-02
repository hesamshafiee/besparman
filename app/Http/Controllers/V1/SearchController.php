<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\FilterRequest;
use App\Http\Requests\V1\SearchRequest;
use App\Services\V1\Search\Search;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * @param SearchRequest $request
     * @return JsonResponse
     * @group Search
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $result = Search::find($request->table, $request->search);
        $class = 'App\Http\Resources\V1\\' . ucfirst($request->table) . 'Resource';
        return response()->jsonMacro($class::collection($result));
    }

    /**
     * @param FilterRequest $request
     * @return JsonResponse
     * @group Search
     */
    public function filter(FilterRequest $request): JsonResponse
    {
        $result = Search::filter($request->table, $request->items);
        if (is_string($result) || isset($request->items['groupBy'])) {
            return response()->json($result);
        } else {
            return response()->jsonMacro($result);
        }
    }
}
