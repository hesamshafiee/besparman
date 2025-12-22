<?php

namespace App\Services\V1\Search;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    private string $resource;
    private bool $isClient;

    /**
     * Filters the given table data based on the provided items.
     *
     * @param string $table
     * @param array $items
     * @return mixed
     */
    public function filter(string $table, array $items): mixed
    {
        $class = 'App\Models\\' . ucfirst($table);
        $searchStructureClass = $this->validateAndPrepareSearchStructure($table, $class);

        $query = $class::query();

        $this->applyGlobalFilters($query, $searchStructureClass, $table);
        $returnFlag = $this->applySpecificFilters($query, $searchStructureClass, $items);

        if (!$returnFlag) {
            abort(404, 'No valid filters applied.');
        }

        return $this->finalizeQuery($query, $items, $searchStructureClass);
    }

    /**
     * Validates and prepares the search structure.
     *
     * @param string $table
     * @param string $class
     * @return array
     */
    private function validateAndPrepareSearchStructure(string $table, string $class): array
    {
        $this->isClient = !optional(Auth::user())->isAdmin();
        $type = $this->isClient ? 'filter-public' : 'filter';
        $resourceType = $this->isClient ? 'Client' : '';
        $this->resource = "App\Http\Resources\V1\\" . $resourceType . ucfirst($table) . 'Resource::collection';

        if (!class_exists($class)) {
            abort(404, "Class $class does not exist.");
        }

        if (Auth::check() && Auth::user()->isAdmin() && !Auth::user()->can($table . '.show')) {
            abort(403, 'Access denied');
        }

        $searchStructureClass = 'App\Services\V1\Search\\' . ucfirst($table);
        $searchStructureInstance = new $searchStructureClass();
        return $searchStructureInstance->get($type);
    }

    /**
     * Applies global filters to the query.
     *
     * @param Builder $query
     * @param array $searchStructureClass
     * @param string $table
     */
    private function applyGlobalFilters($query, array $searchStructureClass, string $table): void
    {
        if (!empty($searchStructureClass['check']['user'])) {
            $query->where('user_id', Auth::id());
        }

        if (!empty($searchStructureClass['check']['status'])) {
            $query->where('status', 1);
        }

    }

    /**
     * Applies specific filters to the query.
     *
     * @param Builder $query
     * @param array $searchStructureClass
     * @param array $items
     * @return bool
     */
    private function applySpecificFilters($query, array $searchStructureClass, array $items): bool
    {
        $returnFlag = false;

        foreach ($searchStructureClass as $index => $value) {
            if (!isset($items[$index])) {
                continue;
            }

            $returnFlag = true;

            if (!empty($value['in'])) {
                $inputValues = array_map('trim', explode('|', $items[$index]));

                foreach ($inputValues as $inputValue) {
                    if (!in_array($inputValue, $value['in'])) {
                        abort(422, "Invalid value for $index.");
                    }
                }
            }

            $this->applyAttributeFilters($query, $index, $value, $items, $searchStructureClass);
        }

        return $returnFlag;
    }

    /**
     * Applies attribute-specific filters.
     *
     * @param Builder $query
     * @param string $index
     * @param array $value
     * @param array $items
     * @param $searchStructureClass
     */
    private function applyAttributeFilters($query, string $index, array $value, array $items, $searchStructureClass): void
    {
        if (!empty($value['attribute_type'])) {
            if ($value['attribute_type'] === 'attribute') {
                $like = $value['like'] ?? true;
                $this->applyAttributeQuery($query, $index, $items, $like);
            } elseif ($value['attribute_type'] === 'hidden') {
                $query->whereNotIn('order_id', function ($query) use ($index, $items, $value) {
                    $query->select('order_id')->where($value['index'], $items[$index]);
                });
            } elseif (isset($items['groupBy'])) {
                $selects = $groupBys = explode(',', $items['groupBy']);

                foreach ($groupBys as $groupBy) {
                    if (!in_array($groupBy, $searchStructureClass['groupBy'])) {
                        abort(422, "Invalid groupBy value: $groupBy");
                    }
                }

                if ($value['attribute_type'] === 'sum' && isset($items['sum'])) {
                    $explode = explode(',', $items['sum']);

                    $allowed = array_merge($searchStructureClass['sum']['value'], ['countAll']);
                    if (!empty(array_diff($explode, $allowed))) {
                        abort(422, "Invalid sum value: " . $items['sum']);
                    }

                    foreach ($explode as $sum) {
                        if ($sum === 'countAll') {
                            $selects[] = DB::raw('COUNT(*) as sum000countAll');
                        } else {
                            $selects[] = DB::raw('SUM(`' . $sum . '`) as sum000' . $sum);
                            $selects[] = DB::raw('count(' . $sum . ') as count000' . $sum);
                        }
                    }
                }

                $query->select($selects)->groupBy($groupBys);
            }
        } elseif (!empty($value['relation'])) {
            $this->applyRelationQuery($query, $index, $value, $items);
        } elseif (!empty($value['with'])) {
            $query->with($items[$index]);
        }
    }


    /**
     * @param $query
     * @param string $index
     * @param array $items
     * @param bool $like
     * @return void
     */
    private function applyAttributeQuery($query, string $index, array $items, bool $like): void
    {
        if ($index === 'created_at') {
            $dates = explode(',', $items[$index] ?? '');
            $from = now()->subDays(30)->startOfDay();
            $to = now();

            if (!empty($dates[0]) && $this->isValidDate($dates[0])) {
                $from = Carbon::createFromFormat('Y-m-d', $dates[0])->startOfDay();
            }

            if (!empty($dates[1]) && $this->isValidDate($dates[1])) {
                $to = Carbon::createFromFormat('Y-m-d', $dates[1])->endOfDay();
            }

            $query->whereBetween('created_at', [$from, $to]);
        } else {
            $values = array_map(function ($value) {
                $trimmed = trim($value);
                $lower = strtolower($trimmed);

                if ($lower === 'null') {
                    return '__IS_NULL__';
                } elseif ($lower === 'not_null') {
                    return '__IS_NOT_NULL__';
                }

                return $trimmed;
            }, explode('|', $items[$index]));

            if (count($values) > 1) {
                if ($like) {
                    $query->where(function ($q) use ($index, $values) {
                        foreach ($values as $value) {
                            if ($value === '__IS_NULL__') {
                                $q->orWhereNull($index);
                            } elseif ($value === '__IS_NOT_NULL__') {
                                $q->orWhereNotNull($index);
                            } else {
                                $q->orWhere($index, 'LIKE', '%' . $value . '%');
                            }
                        }
                    });
                } else {
                    $query->where(function ($q) use ($index, $values) {
                        foreach ($values as $value) {
                            if ($value === '__IS_NULL__') {
                                $q->orWhereNull($index);
                            } elseif ($value === '__IS_NOT_NULL__') {
                                $q->orWhereNotNull($index);
                            } else {
                                $q->orWhere($index, $value);
                            }
                        }
                    });
                }
            } else {
                $value = $values[0];

                if ($like) {
                    if ($value === '__IS_NULL__') {
                        $query->whereNull($index);
                    } elseif ($value === '__IS_NOT_NULL__') {
                        $query->whereNotNull($index);
                    } else {
                        $query->where($index, 'LIKE', '%' . $value . '%');
                    }
                } else {
                    if ($value === '__IS_NULL__') {
                        $query->whereNull($index);
                    } elseif ($value === '__IS_NOT_NULL__') {
                        $query->whereNotNull($index);
                    } else {
                        $query->where($index, $value);
                    }
                }
            }
        }
    }

    /**
     * @param string $date
     * @param string $format
     * @return bool
     */
    private function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $dt = \DateTime::createFromFormat($format, $date);
        return $dt && $dt->format($format) === $date;
    }

    /**
     * Applies relation-based queries.
     *
     * @param Builder $query
     * @param string $index
     * @param array $value
     * @param array $items
     */
    private function applyRelationQuery($query, string $index, array $value, array $items): void
    {
        $query->whereHas($value['relation'], function ($relationQuery) use ($index, $value, $items) {
            if (!empty($value['relation2'])) {
                $relationQuery->whereHas($value['relation2'], function ($relationQuery2) use ($index, $value, $items) {
                    $relationQuery2->where($value['index'], 'LIKE', "%" . $items[$index] . "%");
                });
            } else {
                $relationQuery->where($value['index'], 'LIKE', "%" . $items[$index] . "%");
            }
        });
    }

    /**
     * Finalizes and executes the query.
     *
     * @param Builder $query
     * @param array $items
     * @param array $searchStructureClass
     * @return mixed
     */
    private function finalizeQuery($query, array $items, array $searchStructureClass): mixed
    {
        $isPagination = filter_var($items['pagination'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $perPage = isset($items['per_page']) && $items['per_page'] >= 1 ? (int)$items['per_page'] : 10;

        $orderType = in_array($items['order_type'] ?? '', ['asc', 'desc']) ? $items['order_type'] : 'desc';
        $order = $items['order'] ?? 'id';
        $resource = $this->resource;

        if (isset($items['sum']) && isset($searchStructureClass['sum']['value'])) {
            if (isset($items['groupBy'])) {
                return $isPagination
                    ? $query->orderBy($order, $orderType)->paginate($perPage)
                    : $query->orderBy($order, $orderType)->get();
            }

            $explode = explode(',', $items['sum']);

            if (!empty(array_diff($explode, $searchStructureClass['sum']['value']))) {
                abort(422, "Invalid sum value(s): " . $items['sum']);
            }

            $sums = [];
            foreach ($explode as $sumField) {
                $sums[$sumField] = $query->exists() ? $query->sum($sumField) : null;
            }

            return JsonResource::make($sums);
        }

        if (isset($items['count'])) {
            return (string) $query->count();
        }

        if (isset($items['groupBy'])) {
            if (method_exists($query, 'getResults')) {
                return $query->getResults();
            }

            $results = $query->orderBy($order, $orderType)->get();

            if ($isPagination) {
                $page = LengthAwarePaginator::resolveCurrentPage();
                $paginated = $results->forPage($page, $perPage)->values();

                $paginator = new LengthAwarePaginator(
                    $paginated,
                    $results->count(),
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()]
                );

                return [
                    'data' => $resource($paginated)->toArray(request()),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                        'from' => $paginator->firstItem(),
                        'to' => $paginator->lastItem(),
                        'has_more_pages' => $paginator->hasMorePages(),
                    ],
                    'links' => [
                        'first' => $paginator->url(1),
                        'last' => $paginator->url($paginator->lastPage()),
                        'prev' => $paginator->previousPageUrl(),
                        'next' => $paginator->nextPageUrl(),
                    ]
                ];
            }

            return $resource($results);
        }

        return $isPagination
            ? $resource($query->orderBy($order, $orderType)->paginate($perPage))
            : $resource($query->orderBy($order, $orderType)->get());
    }
}
