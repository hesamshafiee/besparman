<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PanelMessageResource;
use App\Http\Requests\V1\PanelMessageRequest;
use App\Models\PanelMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PanelMessageController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PanelMessage
     */

    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', PanelMessage::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PanelMessageResource(PanelMessage::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PanelMessageResource::collection(PanelMessage::orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @group PanelMessage
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new PanelMessageResource(PanelMessage::where('status', 1)->where('id', $id)->firstOrFail()));
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

        return response()->jsonMacro(PanelMessageResource::collection(PanelMessage::where('status', 1)->orderBy($order, $typeOrder)->paginate($perPage)));
    }


    /**
     * @param PanelMessageRequest $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PanelMessage
     */
    public function store(PanelMessageRequest $request): JsonResponse
    {
        $this->authorize('show', PanelMessage::class);

        $panelMessage = new PanelMessage() ;
        $panelMessage->fill($request->safe()->all());

        if ($panelMessage->save()) {
            return response()->ok(__('general.savedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PanelMessageRequest $request
     * @param PanelMessage $panelMessage
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PanelMessage
     */
    public function update(PanelMessageRequest $request, PanelMessage $panelMessage): JsonResponse
    {

        $this->authorize('update', PanelMessage::class);

        $panelMessage->fill($request->safe()->all());

        if ($panelMessage->save()) {
            return response()->ok(__('general.updatedSuccessfully', ['id' => $panelMessage->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PanelMessage $panelMessage
     * @return JsonResponse
     * @throws AuthorizationException
     * @group PanelMessage
     */
    public function destroy(PanelMessage $panelMessage): JsonResponse
    {
        $this->authorize('delete', PanelMessage::class);

        if ($panelMessage->delete()) {
            return response()->ok(__('general.deletedSuccessfully', ['id' => $panelMessage->id]));
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
