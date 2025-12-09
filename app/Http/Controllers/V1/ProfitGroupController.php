<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ProfitGroupRequest;
use App\Http\Resources\V1\ProfitGroupResource;
use App\Models\ProfitGroup;
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

        // اگر id فرستاده شده، فقط همون ProfitGroup رو برگردون
        if ($id) {
            $profitGroup = ProfitGroup::findOrFail($id);
            return response()->jsonMacro(new ProfitGroupResource($profitGroup));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        $query = ProfitGroup::orderBy($order, $typeOrder);

        return response()->jsonMacro(
            ProfitGroupResource::collection($query->paginate($perPage))
        );
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

        // داده‌ها از Request ولید شده
        $data = $request->safe()->only([
            'title',
            'designer_profit',
            'site_profit',
            'referrer_profit',
        ]);

        $profitGroup = new ProfitGroup();
        $profitGroup->fill($data);

        if ($profitGroup->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param ProfitGroupRequest $request
     * @param ProfitGroup $profitGroup
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function update(ProfitGroupRequest $request, ProfitGroup $profitGroup): JsonResponse
    {
        $this->authorize('update', ProfitGroup::class);

        $data = $request->safe()->only([
            'title',
            'designer_profit',
            'site_profit',
            'referrer_profit',
        ]);

        $profitGroup->fill($data);

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
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException
     * @group ProfitGroup
     */
    public function assignProfitGroupToUser(Request $request, User $user): JsonResponse
    {
        $this->authorize('assignProfitGroup', ProfitGroup::class);

        $validated = $request->validate([
            'profit_group_id' => ['required', 'exists:profit_groups,id'],
        ]);

        $user->profit_group_id = $validated['profit_group_id'];
        $user->save();

        return response()->ok('Profit group has been assigned to user');
    }

    /**
     * گرفتن ProfitGroup کاربر لاگین شده
     *
     * @return JsonResponse
     */
    public function getUserProfitGroup(): JsonResponse
    {
        $user = Auth::user();
        $profitGroup = $user?->profitGroup;

        return response()->jsonMacro(
            $profitGroup
            ? new ProfitGroupResource($profitGroup)
            : null
        );
    }
}
