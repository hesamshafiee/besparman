<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\MenuRequest;
use App\Http\Resources\V1\MenuResource;
use App\Models\Menu;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Menu
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Menu::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new MenuResource(Menu::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(MenuResource::collection(Menu::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Menu
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new MenuResource(Menu::where('status', 1)->where('id', $id)->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(MenuResource::collection(Menu::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param MenuRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Menu
     */
    public function store(MenuRequest $request): JsonResponse
    {
        $this->authorize('create', Menu::class);

        $menu = new Menu();
        $menu->fill($request->safe()->all());

        if ($menu->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /***
     * @param MenuRequest $request
     * @param Menu $menu
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Menu
     */
    public function update(MenuRequest $request, Menu $menu): JsonResponse
    {
        $this->authorize('update', Menu::class);

        $menu->fill($request->safe()->all());

        $activeMenu = Menu::where('status', 1)->first();

        if ((int) $request->status && $activeMenu && $activeMenu->id !== $menu->id) {
            return response()->serverError('You just can have one active setting');
        }

        $menu->status = $request->status ?? $menu->status;

        if ($menu->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $menu->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Menu $menu
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Menu
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $this->authorize('delete', Menu::class);

        if ($menu->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $menu->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
