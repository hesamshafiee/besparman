<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Esaj\ProfitRequest;
use App\Http\Resources\V1\ProfitResource;
use App\Models\Profit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profit
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Profit::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new ProfitResource(Profit::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(ProfitResource::collection(Profit::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param ProfitRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profit
     */
    public function store(ProfitRequest $request): JsonResponse
    {
        $this->authorize('create', Profit::class);

        $data = $request->safe()->all();


        $exists = Profit::where('operator_id', $data['operator_id'])
            ->where('type', $data['type'])
            ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($data['title']))])
            ->where('profit', $data['profit'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'A similar profit record already exists.'
            ], 422);
        }

        $profit = new Profit();
        $profit->fill($request->safe()->all());

        if ($profit->save()) {
            return response()->jsonMacro(new ProfitResource($profit));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param ProfitRequest $request
     * @param Profit $profit
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profit
     */
    public function update(ProfitRequest $request, Profit $profit): JsonResponse
    {
        $this->authorize('update', Profit::class);

        $data = $request->safe()->all();

        $exists = Profit::where('operator_id', $data['operator_id'])
            ->where('type', $data['type'])
            ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($data['title']))])
            ->where('profit', $data['profit'])
            ->where('id', '!=', $profit->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'A similar profit record already exists.'
            ], 422);
        }

        $profit->fill($request->safe()->all());

        if ($profit->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $profit->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Profit $profit
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Profit
     */
    public function destroy(Profit $profit): JsonResponse
    {
        $this->authorize('delete', Profit::class);

        if ($profit->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $profit->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
