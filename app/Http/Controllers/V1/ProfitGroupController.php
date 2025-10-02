<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Esaj\ProfitGroupRequest;
use App\Http\Resources\V1\ProfitGroupResource;
use App\Http\Resources\V1\ProfitMixResource;
use App\Models\Profit;
use App\Models\ProfitGroup;
use App\Models\ProfitSplit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProfitGroupController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', ProfitGroup::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            $profitGroup = ProfitGroup::findOrFail($id);
            $profitSplits = ProfitSplit::whereIn('id', $profitGroup->profit_split_ids)->get();
            $profitIds = collect($profitSplits)->pluck('profit_id')->toArray();
            $profits = Profit::whereIn('id', $profitIds)->get();
            return response()->jsonMacro(ProfitMixResource::collection(['profitGroup' => $profitGroup, 'profitSplits' => $profitSplits, 'profit' => $profits]));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(ProfitGroupResource::collection(ProfitGroup::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param ProfitGroupRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function store(ProfitGroupRequest $request): JsonResponse
    {
        $this->authorize('create', ProfitGroup::class);

        if (!$this->check($request->profit_split_ids)) {
            return response()->serverError('Same profit source is forbidden');
        }

        $warehouse = new ProfitGroup();
        $warehouse->fill($request->safe()->all());

        if ($warehouse->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param ProfitGroupRequest $request
     * @param ProfitGroup $profitGroup
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function update(ProfitGroupRequest $request, ProfitGroup $profitGroup): JsonResponse
    {
        $this->authorize('update', ProfitGroup::class);

        if (!$this->check($request->profit_split_ids)) {
            return response()->serverError('Same profit source is forbidden');
        }

        $profitGroup->fill($request->safe()->all());

        if ($profitGroup->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $profitGroup->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param ProfitGroup $profitGroup
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function destroy(ProfitGroup $profitGroup): JsonResponse
    {
        $this->authorize('delete', ProfitGroup::class);

        if ($profitGroup->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $profitGroup->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param ProfitGroupRequest $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function assignProfitGroupToUser(ProfitGroupRequest $request, User $user) : JsonResponse
    {
        $this->authorize('assignProfitGroup', ProfitGroup::class);

        $user->profitGroups()->sync($request->profit_group_id);

        return response()->ok('Profit group has been assigned to user');
    }

    public function getUserProfitGroup(): JsonResponse
    {
        $profitGroup = Auth::user()->profitGroups->first();
        if ($profitGroup) {
            $profitSplits = ProfitSplit::select('title', 'profit_id', 'seller_profit')->whereIn('id', $profitGroup->profit_split_ids)->get();
            if ($profitSplits) {
                $profits = Profit::select('id', 'type', 'title', 'operator_id')->where('status', 1)->whereIn('id', $profitSplits->pluck('profit_id'))->with('operator')->get();
            }
        }

        return response()->jsonMacro(ProfitMixResource::collection(['profitGroup' => $profitGroup, 'profitSplits' => $profitSplits ?? [], 'profit' => $profits ?? []]));
    }

    /**
     * @param array $profitSplitIds
     * @return bool
     * @group ProfitGroup
     */
    private function check(array $profitSplitIds): bool
    {
        $profitIds = ProfitSplit::whereIn('id', $profitSplitIds)->get()->pluck('profit_id')->toArray();

        $profits = Profit::select('operator_id', 'type')->whereIn('id', $profitIds)->get();

        $array = [];
        foreach ($profits as $profit) {
            $x = isset($array[$profit->type . $profit->operator_id]) ? ($array[$profit->type . $profit->operator_id] + 1) : 1;
            $array[$profit->type . $profit->operator_id] = $x;

            if ($x > 1) {
                return false;
            }
        }

        return true;
    }
}
